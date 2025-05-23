<?php
/**
 * @package   solo
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Bootstrap file for Akeeba Solo for WordPress
use Akeeba\Engine\Platform as EnginePlatform;
use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Input\Input;
use Composer\Autoload\ClassLoader;
use Composer\CaBundle\CaBundle;
use Solo\Application\AppConfig;
use Solo\Application\UserManager;
use Solo\Pythia\Oracle\Wordpress;

defined('AKEEBASOLO') or die;

global $akeebaBackupWordPressContainer;
global $akeebaBackupWordPressAutoloader;

// Load the constants and the Composer autoloader
defined('APATH_BASE') || require_once(__DIR__ . '/defines.php');
$akeebaBackupWordPressAutoloader   = include __DIR__ . '/../app/vendor/autoload.php';

if (!$akeebaBackupWordPressAutoloader instanceof ClassLoader)
{
	echo 'ERROR: Composer Autoloader not found' . PHP_EOL;

	exit(1);
}

defined('AKEEBA_CACERT_PEM') || define('AKEEBA_CACERT_PEM', CaBundle::getBundledCaBundlePath());

// Add PSR-4 overrides for the application namespace
call_user_func(
	function (ClassLoader $akeebaBackupWordPressAutoloader) {
		$prefixes     = $akeebaBackupWordPressAutoloader->getPrefixesPsr4();
		$soloPrefixes = array_filter(array_map('realpath', $prefixes['Solo\\'] ?? []));

		if (!in_array(__DIR__ . '/Solo', $prefixes['Solo\\'] ?? []))
		{
			$akeebaBackupWordPressAutoloader->setPsr4(
				'Solo\\', array_unique(
					array_merge(
						[
							realpath(__DIR__ . '/Solo'),
							realpath(__DIR__ . '/../app/Solo'),
						],
						$soloPrefixes
					)
				)
			);
		}
	}, $akeebaBackupWordPressAutoloader
);

// If we are not called from inside WordPress itself we will need to import its configuration
defined('WPINC')
|| call_user_func(
	function () {
		$foundWpConfig = false;

		$dirParts      = explode(DIRECTORY_SEPARATOR, __DIR__);
		$dirParts      = array_splice($dirParts, 0, -4);
		$filePath      = implode(DIRECTORY_SEPARATOR, $dirParts);
		$foundWpConfig = file_exists($filePath . '/wp-config.php');

		if (!$foundWpConfig)
		{
			$dirParts      = array_splice($dirParts, 0, -1);
			$altFilePath   = implode(DIRECTORY_SEPARATOR, $dirParts);
			$foundWpConfig = file_exists($altFilePath . '/wp-config.php');
		}

		if (!$foundWpConfig)
		{
			$possibleDirs = [getcwd()];

			if (isset($_SERVER['SCRIPT_FILENAME']))
			{
				$possibleDirs[] = dirname($_SERVER['SCRIPT_FILENAME']);
			}

			foreach ($possibleDirs as $scriptFolder)
			{
				// Can't use realpath() because in our dev environment it will resolve the symlinks outside the site root
				$dirParts = explode(DIRECTORY_SEPARATOR, $scriptFolder);

				$filePath = $scriptFolder;

				if (!is_file($filePath . '/wp-config.php'))
				{
					$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -2));
				}

				if (!is_file($filePath . '/wp-config.php'))
				{
					$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -3));
				}

				if (!is_file($filePath . '/wp-config.php'))
				{
					$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -4));
				}

				if (!is_file($filePath . '/wp-config.php'))
				{
					$filePath = implode(DIRECTORY_SEPARATOR, array_slice($dirParts, 0, -5));
				}

				$foundWpConfig = file_exists($filePath . '/wp-config.php');

				if ($foundWpConfig)
				{
					$filePath = dirname(realpath($filePath . '/wp-config.php'));

					break;
				}
			}
		}

		$noWpConfig = (isset($_REQUEST) && isset($_REQUEST['no-wp-config']))
		              || (isset($argv) && in_array('--no-wp-config', $argv))
		              || @file_exists(__DIR__ . '/private/no-wp-config.txt')
		              || @file_exists(__DIR__ . '/private/wp-config.php');

		$oracle = new Wordpress($filePath);

		if ($noWpConfig)
		{
			$oracle->setLoadWPConfig(false);
		}

		if (!$oracle->isRecognised())
		{
			$filePath = realpath($filePath . '/..');
			$oracle   = new Wordpress($filePath);
		}

		if (!$oracle->isRecognised())
		{
			$curDir = __DIR__;
			echo <<< ENDTEXT
ERROR: Could not find wp-config.php

Technical information
--
integration.php directory	$curDir
filePath					$filePath
isRecognised				false
--

ENDTEXT;
			exit(1);
		}

		define('ABSPATH', $filePath);

		if (!defined('AKEEBABACKUPWP_PATH'))
		{
			$absPluginPath = realpath(__DIR__ . '/..');
			$absPluginPath = @is_dir($absPluginPath) ? $absPluginPath : ABSPATH . '/wp-content/plugins/akeebabackupwp';

			define('AKEEBABACKUPWP_PATH', ABSPATH . '/wp-content/plugins/akeebabackupwp');
		}

		$dbInfo = $oracle->getDbInformation();

		if (@file_exists(__DIR__ . '/private/wp-config.php'))
		{
			include_once __DIR__ . '/private/wp-config.php';
		}

		if (!defined('DB_NAME'))
		{
			define('DB_NAME', $dbInfo['name']);
		}
		if (!defined('DB_USER'))
		{
			define('DB_USER', $dbInfo['username']);
		}
		if (!defined('DB_PASSWORD'))
		{
			define('DB_PASSWORD', $dbInfo['password']);
		}
		if (!defined('DB_HOST'))
		{
			define('DB_HOST', $dbInfo['host']);
		}

		global $table_prefix;

		// Apply the table prefix only if it hasn't been already defined before (ie from our saved configuration file)
		$table_prefix = $table_prefix ?? ($dbInfo['prefix'] ?? '');

		// Also apply detected proxy settings
		if (!empty($dbInfo['proxy_host']) && !defined('WP_PROXY_HOST'))
		{
			define('WP_PROXY_HOST', $dbInfo['proxy_host']);
		}
		if (!empty($dbInfo['proxy_port']) && !defined('WP_PROXY_PORT'))
		{
			define('WP_PROXY_PORT', $dbInfo['proxy_port']);
		}
		if (!empty($dbInfo['proxy_user']) && !defined('WP_PROXY_USERNAME'))
		{
			define('WP_PROXY_USERNAME', $dbInfo['proxy_user']);
		}
		if (!empty($dbInfo['proxy_pass']) && !defined('WP_PROXY_PASSWORD'))
		{
			define('WP_PROXY_PASSWORD', $dbInfo['proxy_pass']);
		}
	}
);

// Should I enable debug?
if (defined('AKEEBADEBUG') && defined('AKEEBADEBUG_ERROR_DISPLAY'))
{
	error_reporting(E_ALL | E_NOTICE | E_DEPRECATED);
	ini_set('display_errors', 1);
}

// Create the Container
$akeebaBackupWordPressContainer = $akeebaBackupWordPressContainer ?? call_user_func(
	function () {
		try
		{
			if (!defined('AKEEBABACKUPWP_PATH'))
			{
				$absPluginPath = realpath(__DIR__ . '/..');
				$absPluginPath = @is_dir($absPluginPath) ? $absPluginPath
					: ABSPATH . '/wp-content/plugins/akeebabackupwp';

				define('AKEEBABACKUPWP_PATH', ABSPATH . '/wp-content/plugins/akeebabackupwp');
			}

			// Create objects
			return new \Solo\Container(
				[
					'appConfig'        => function (Container $c) {
						return new AppConfig($c);
					},
					'userManager'      => function (Container $c) {
						return new UserManager($c);
					},
					'input'            => function (Container $c) {
						// WordPress is always escaping the input. WTF!
						// See http://stackoverflow.com/questions/8949768/with-magic-quotes-disabled-why-does-php-wordpress-continue-to-auto-escape-my

						global $AKEEBABACKUPWP_REAL_REQUEST;

						if (isset($AKEEBABACKUPWP_REAL_REQUEST))
						{
							return new Input($AKEEBABACKUPWP_REAL_REQUEST, ['magicQuotesWorkaround' => true]);
						}
						elseif (defined('WPINC'))
						{
							$fakeRequest = array_map('stripslashes_deep', $_REQUEST);

							return new Input($fakeRequest, ['magicQuotesWorkaround' => true]);
						}
						else
						{
							return new Input();
						}
					},
					'application_name' => 'Solo',
					'filesystemBase'   => AKEEBABACKUPWP_PATH . '/app',
					'updateStreamURL'  => (defined('AKEEBABACKUP_PRO') && AKEEBABACKUP_PRO)
						? 'http://cdn.akeeba.com/updates/akeebabackupwp_pro.json'
						: 'http://cdn.akeeba.com/updates/akeebabackupwp_core.json',
					'changelogPath'    => AKEEBABACKUPWP_PATH . 'CHANGELOG.php',
				]
			);
		}
		catch (Exception $exc)
		{
			unset($akeebaBackupWordPressContainer);

			$filename = null;

			if (isset($application))
			{
				if ($application instanceof Application)
				{
					$template = $application->getTemplate();

					if (file_exists(APATH_THEMES . '/' . $template . '/error.php'))
					{
						$filename = APATH_THEMES . '/' . $template . '/error.php';
					}
				}
			}

			if (is_null($filename))
			{
				die($exc->getMessage());
			}

			include $filename;

			die;
		}
	}
);

// Workaround: you have entered a Download ID in the Core version. We have to update you to the Professional version.
call_user_func(
	function ($akeebaBackupWordPressContainer) {
		$downloadId = $akeebaBackupWordPressContainer->appConfig->get('options.update_dlid', '');

		if (!empty($downloadId))
		{
			$akeebaBackupWordPressContainer['updateStreamURL'] = 'http://cdn.akeeba.com/updates/backupwppro.ini';
		}
	}, $akeebaBackupWordPressContainer
);

call_user_func(function($akeebaBackupWordPressContainer) {
	// Include the Akeeba Engine and ALICE factories, if required
	if (defined('AKEEBAENGINE'))
	{
		return;
	}

	define('AKEEBAENGINE', 1);

	$factoryPath = __DIR__ . '/../app/vendor/akeeba/engine/engine/Factory.php';
	$alicePath   = __DIR__ . '/../app/Solo/alice/factory.php';

	// Load the engine
	if (!file_exists($factoryPath))
	{
		echo "ERROR!\n";
		echo "Could not load the backup engine; file does not exist. Technical information:\n";
		echo "Path to " . basename(__FILE__) . ": " . __DIR__ . "\n";
		echo "Path to factory file: $factoryPath\n";
		die("\n");
	}

	try
	{
		require_once $factoryPath;
	}
	catch (Exception $e)
	{
		echo "ERROR!\n";
		echo "Backup engine returned an error. Technical information:\n";
		echo "Error message:\n\n";
		echo $e->getMessage() . "\n\n";
		echo "Path to " . basename(__FILE__) . ":" . __DIR__ . "\n";
		echo "Path to factory file: $factoryPath\n";
		die("\n");
	}

	if (file_exists($alicePath))
	{
		require_once $alicePath;
	}

	EnginePlatform::addPlatform('Wordpress', __DIR__ . '/Platform/Wordpress');
	$platform = EnginePlatform::getInstance();

	$platform->setContainer($akeebaBackupWordPressContainer);
	$platform->load_version_defines();
	$platform->apply_quirk_definitions();
}, $akeebaBackupWordPressContainer);

return $akeebaBackupWordPressContainer;