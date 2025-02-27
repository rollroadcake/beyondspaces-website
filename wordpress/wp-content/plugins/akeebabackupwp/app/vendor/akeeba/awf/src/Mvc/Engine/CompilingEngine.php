<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\Engine;

use Awf\Filesystem\File;
use Awf\Mvc\Compiler\CompilerInterface;
use Awf\Utils\Buffer;
use Awf\Utils\HashHelper;

/**
 * View engine for compiling PHP template files.
 */
abstract class CompilingEngine extends AbstractEngine implements EngineInterface
{
	/** @var  CompilerInterface  The compiler used by this engine */
	protected $compiler = null;

	/**
	 * Get the 3ναlυa+3d contents of the view template. (I use leetspeak here because of bad quality hosts with broken
	 * scanners)
	 *
	 * @param   string  $path         The path to the view template
	 * @param   array   $forceParams  Any additional information to pass to the view template engine
	 *
	 * @return  array  Content evaluation information
	 */
	public function get($path, array $forceParams = [])
	{
		// If it's cached return the path to the cached file's path
		if ($this->isCached($path))
		{
			return [
				'type'     => 'path',
				'content'  => $this->getCachePath($path),
				'original' => $path,
			];
		}

		/**
		 * Compile and cache the file. We also add the file path in a comment at the top of the file so phpStorm can
		 * debug it.
		 *
		 * @see https://blog.jetbrains.com/phpstorm/2019/02/phpstorm-2019-1-eap-191-5849-26/
		 * @see https://laravel-news.com/laravel-5-8-blade-template-file-path
		 */
		$content        = "<?php /* $path */ ?>\n";
		$content        .= $this->compile($path, $forceParams);
		$cacheFolder    = $this->view->getContainer()->temporaryPath;
		$cachedFilePath = $this->putToCache($path, $content);
		$isPHPFile      = substr($path, -4) == '.php';

		// If we could cache it, return the cached file's path
		if ($cachedFilePath !== false)
		{
			// Bust the opcode cache for .php files
			if ($isPHPFile)
			{
				$this->bustOpCache($path);
			}

			return [
				'type'     => 'path',
				'content'  => $cachedFilePath,
				'original' => $path,
			];
		}

		// We could not write to the cache. Hm, can I use a stream wrapper?
		$canUseStreams = Buffer::canRegisterWrapper();

		if ($canUseStreams)
		{
			$id         = $this->getIdentifier($path);
			$streamPath = 'awf://' . $this->view->getContainer()->application_name . '/compiled_templates/' . $id . '.php';

			file_put_contents($streamPath, $content);

			// Bust the opcode cache for .php files
			if ($isPHPFile)
			{
				$this->bustOpCache($path);
			}

			return [
				'type'     => 'path',
				'content'  => $streamPath,
				'original' => $path,
			];
		}

		// I couldn't use a stream wrapper. I have to give up.
		$errorMessage = "Could not write to your temporary directory “%s”. Please make it writeable to PHP by changing the permissions. Alternatively, ask your host to make sure that they have not disabled the stream_wrapper_register() function in PHP. Moreover, if your host is using the Suhosin patch for PHP ask them to whitelist the awf:// stream wrapper in their server's php.ini file. If you do not understand what this means please contact your host and paste this entire message to them.";
		throw new \RuntimeException(sprintf($errorMessage, $cacheFolder), 500);
	}

	/**
	 * Returns the path where I can find a precompiled version of the uncompiled view template which lives in $path
	 *
	 * @param   string  $path  The path to the uncompiled view template
	 *
	 * @return  bool|string  False if the view template is outside the component's front- or backend.
	 */
	public function getPrecompiledPath($path)
	{
		// Normalize the path to the file
		$path = realpath($path);

		if ($path === false)
		{
			// The file doesn't exist
			return false;
		}

		// Is this path under the application folder?
		$componentPath = realpath($this->view->getContainer()->basePath);
		$frontPos      = strpos($path, $componentPath);

		if ($frontPos !== 0)
		{
			// This is not a view template shipped with the application, i.e. it can't be precompiled
			return false;
		}

		// Eliminate the component path from $path to get the relative path to the file
		$relativePath = ltrim(substr($path, strlen($componentPath)), '\\/');

		// Break down the relative path to its parts
		$relativePath = str_replace('\\', '/', $relativePath);
		$pathParts    = explode('/', $relativePath);

		// Remove the prefix
		$prefix = array_shift($pathParts);

		// If it's a legacy view, View, Views, or views prefix remove the 'tmpl' part
		if ($prefix != 'ViewTemplates')
		{
			unset($pathParts[1]);
		}

		// Get the last part and process the extension
		$viewFile            = array_pop($pathParts);
		$extensionWithoutDot = $this->compiler->getFileExtension();
		$pathParts[]         = substr($viewFile, 0, -strlen($extensionWithoutDot)) . 'php';

		$precompiledRelativePath = implode(DIRECTORY_SEPARATOR, $pathParts);

		return $componentPath . DIRECTORY_SEPARATOR . 'PrecompiledTemplates' . DIRECTORY_SEPARATOR . $precompiledRelativePath;
	}

	/**
	 * A method to compile the raw view template into valid PHP
	 *
	 * @param   string  $path         The path to the view template
	 * @param   array   $forceParams  Any additional information to pass to the view template compiler
	 *
	 * @return  string  The template compiled to executable PHP
	 */
	protected function compile($path, array $forceParams = [])
	{
		return $this->compiler->compile($path, $forceParams);
	}

	/**
	 * Returns a unique identifier for the template file being compiled
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  string  The unique identifier of the file
	 */
	protected function getIdentifier($path)
	{
		if (function_exists('sha1'))
		{
			return HashHelper::sha1($path);
		}

		return HashHelper::md5($path);
	}

	/**
	 * Returns the path to cached, compiled version of the template file.
	 *
	 * As of 1.2.0 the cached files are distributed into two subfolder levels for performance reasons, i.e.
	 * `/path/to/tmp/compiled_templates/a/b/abcdef0123456789abcdef0123456789.php` instead of the legacy storage scheme
	 * of `/path/to/tmp/compiled_templates/abcdef0123456789abcdef0123456789.php`.
	 *
	 * @param   string  $path   The full path to the template file which needs to be compiled.
	 * @param   bool    $legacy Should I use legacy storage (all compiled files in the same folder)?
	 *
	 * @return  string  The full path to the cached compiled file
	 */
	protected function getCachePath(string $path, bool $legacy = false): string
	{
		$id = $this->getIdentifier($path);

		// In legacy mode all files were under the same folder
		if ($legacy)
		{
			return sprintf(
				'%s/compiled_templates/%s.php',
				$this->view->getContainer()->temporaryPath,
				$id
			);
		}

		// In modern mode files are distributed into two folder levels
		return sprintf(
			'%s/compiled_templates/%s/%s/%s.php',
			$this->view->getContainer()->temporaryPath,
			substr($id, 0, 1),
			substr($id, 1, 1),
			$id
		);
	}

	/**
	 * Do we have a cached, compiled version of the template file which is up-to-date with the uncompiled file (we check
	 * the last modification timestamp)?
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  bool  True if we have a cached, compiled, up-to-date version of the file
	 */
	protected function isCached($path)
	{
		if (!$this->compiler->isCacheable())
		{
			return false;
		}

		clearstatcache();

		$cachePath    = $this->getCachePath($path);

		if (!file_exists($cachePath))
		{
			// If the legacy file does not exist we return false immediately.
			$oldCachePath = $this->getCachePath($path, true);

			if (!file_exists($oldCachePath))
			{
				return false;
			}

			// Try to move the file from the legacy into the new location.
			$lastModTime = @filemtime($oldCachePath) ?: 0;
			$fs = $this->view->getContainer()->fileSystem;

			// If the move failed try to delete the old file and return false anyway; we'll have to recompile.
			if (!$fs->move($oldCachePath, $cachePath))
			{
				$fs->delete($oldCachePath);

				return false;
			}

			/**
			 * Try to change the last modification time of the file we moved if we're using the local filesystem.
			 * Other filesystem adapters do not have the equivalent of touch() as it's not reliably implemented across
			 * FTP / SFTP servers :(
			 */
			if ($fs instanceof File)
			{
				touch($cachePath, $lastModTime);
			}
		}

		$cacheTime = filemtime($cachePath) ?: 0;
		$fileTime  = filemtime($path) ?: 0;

		return $fileTime <= $cacheTime;
	}

	/**
	 * Get the cached, compiled version of the template file
	 *
	 * @param   string  $path  The full path to the template file which needs to be compiled
	 *
	 * @return  bool|string  The ceched, compiled contents of the template file or boolean false if the file doesn't
	 *                       exist
	 */
	protected function getCached($path)
	{
		$cachePath = $this->getCachePath($path);

		return file_get_contents($cachePath);
	}

	/**
	 * Put the compiled version of a template file into the cache
	 *
	 * @param   string  $path     The full path to the template file which needs to be compiled
	 * @param   string  $content  The compiled content to cache
	 *
	 * @return  bool|string  The path of the cached file, or boolean false if writing to the cache failed
	 */
	protected function putToCache($path, $content)
	{
		$cachePath   = $this->getCachePath($path);
		$cacheFolder = dirname($cachePath);

		$this->makeCacheFolder($cacheFolder);

		if (@file_put_contents($cachePath, $content))
		{
			return $cachePath;
		}

		if ($this->view->getContainer()->fileSystem->write($cachePath, $content))
		{
			return $cachePath;
		}

		return false;
	}

	/**
	 * Makes sure the cache folder actually exists
	 *
	 * @param   string  $cacheFolder  The absolute filesystem path to the cache folder
	 *
	 * @return  void
	 */
	private function makeCacheFolder($cacheFolder)
	{
		if (@is_dir($cacheFolder))
		{
			return;
		}

		if (@mkdir($cacheFolder, 0755, true))
		{
			return;
		}

		$this->view->getContainer()->fileSystem->mkdir($cacheFolder, 0644);
	}

	/**
	 * Bust the opcode cache for a given .php file
	 *
	 * This method can address opcode caching with:
	 * - Zend OPcache
	 * - Alternative PHP Cache (now defunct)
	 * - Windows Cache Extension for PHP (versions lower than 2.0.0)
	 * - XCache (now defunct)
	 *
	 * @param   string  $path  The file to bus the cache for
	 *
	 * @return  void
	 */
	private function bustOpCache($path)
	{
		if (function_exists('opcache_invalidate'))
		{
			opcache_invalidate($path, true);
		}

		if (function_exists('apc_compile_file'))
		{
			apc_compile_file($path);
		}

		if (function_exists('wincache_refresh_if_changed'))
		{
			wincache_refresh_if_changed([$path]);
		}

		if (function_exists('xcache_asm'))
		{
			xcache_asm($path);
		}
	}
}
