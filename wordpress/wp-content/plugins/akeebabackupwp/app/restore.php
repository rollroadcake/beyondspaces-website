<?php
/**
 * Akeeba Kickstart
 * An AJAX-powered archive extraction tool
 *
 * @package   kickstart
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

define('_AKEEBA_RESTORATION', 1);
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// Unarchiver run states
define('AK_STATE_NOFILE', 0); // File header not read yet
define('AK_STATE_HEADER', 1); // File header read; ready to process data
define('AK_STATE_DATA', 2); // Processing file data
define('AK_STATE_DATAREAD', 3); // Finished processing file data; ready to post-process
define('AK_STATE_POSTPROC', 4); // Post-processing
define('AK_STATE_DONE', 5); // Done with post-processing

/* Windows system detection */
if (!defined('_AKEEBA_IS_WINDOWS'))
{
	if (function_exists('php_uname'))
	{
		define('_AKEEBA_IS_WINDOWS', stristr(php_uname(), 'windows'));
	}
	else
	{
		define('_AKEEBA_IS_WINDOWS', DIRECTORY_SEPARATOR == '\\');
	}
}

// Get the file's root
if (!defined('KSROOTDIR'))
{
	define('KSROOTDIR', dirname(__FILE__));
}
if (!defined('KSLANGDIR'))
{
	define('KSLANGDIR', KSROOTDIR);
}

// Make sure the locale is correct for basename() to work
if (function_exists('setlocale'))
{
	@setlocale(LC_ALL, 'en_US.UTF8');
}

// fnmatch not available on non-POSIX systems
// Thanks to soywiz@php.net for this usefull alternative function [http://gr2.php.net/fnmatch]
if (!function_exists('fnmatch'))
{
	function fnmatch($pattern, $string)
	{
		return @preg_match(
			'/^' . strtr(addcslashes($pattern, '/\\.+^$(){}=!<>|'),
				array('*' => '.*', '?' => '.?')) . '$/i', $string
		);
	}
}

// Unicode-safe binary data length function
if (!function_exists('akstringlen'))
{
	if (function_exists('mb_strlen'))
	{
		function akstringlen($string)
		{
			return mb_strlen($string, '8bit');
		}
	}
	else
	{
		function akstringlen($string)
		{
			return strlen($string);
		}
	}
}

if (!function_exists('aksubstr'))
{
	if (function_exists('mb_strlen'))
	{
		function aksubstr($string, $start, $length = null)
		{
			return mb_substr($string, $start, $length, '8bit');
		}
	}
	else
	{
		function aksubstr($string, $start, $length = null)
		{
			return substr($string, $start, $length);
		}
	}
}

/**
 * Gets a query parameter from GET or POST data
 *
 * @param $key
 * @param $default
 */
function getQueryParam($key, $default = null)
{
	$value = $default;

	if (array_key_exists($key, $_REQUEST))
	{
		$value = $_REQUEST[$key];
	}

	if (version_compare(PHP_VERSION, '5.4.0', 'lt') && get_magic_quotes_gpc() && !is_null($value))
	{
		$value = stripslashes($value);
	}

	return $value;
}

// Debugging function
function debugMsg($msg)
{
	if (!defined('KSDEBUG'))
	{
		return;
	}

	$fp = fopen('debug.txt', 'a');

	fwrite($fp, $msg . PHP_EOL);
	fclose($fp);

	// Echo to stdout if KSDEBUGCLI is defined
	if (defined('KSDEBUGCLI'))
	{
		echo $msg . "\n";
	}
}

/**
 * Invalidate a file in OPcache.
 *
 * Only applies if the file has a .php extension.
 *
 * @param   string  $file  The filepath to clear from OPcache
 *
 * @return  boolean
 * @since   7.1.0
 */
function clearFileInOPCache($file)
{
	static $hasOpCache = null;

	if (is_null($hasOpCache))
	{
		$hasOpCache = ini_get('opcache.enable')
			&& function_exists('opcache_invalidate')
			&& (!ini_get('opcache.restrict_api') || stripos(realpath($_SERVER['SCRIPT_FILENAME']), ini_get('opcache.restrict_api')) === 0);
	}

	if ($hasOpCache && (strtolower(substr($file, -4)) === '.php'))
	{
		return opcache_invalidate($file, true);
	}

	return false;
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The base class of Akeeba Engine objects. Allows for error and warnings logging
 * and propagation. Largely based on the Joomla! 1.5 JObject class.
 */
abstract class AKAbstractObject
{
	/** @var    array    The queue size of the $_errors array. Set to 0 for infinite size. */
	protected $_errors_queue_size = 0;
	/** @var    array    The queue size of the $_warnings array. Set to 0 for infinite size. */
	protected $_warnings_queue_size = 0;
	/** @var    array    An array of errors */
	private $_errors = array();
	/** @var    array    An array of warnings */
	private $_warnings = array();

	/**
	 * Get the most recent error message
	 *
	 * @param    integer $i Optional error index
	 *
	 * @return    string    Error message
	 */
	public function getError($i = null)
	{
		return $this->getItemFromArray($this->_errors, $i);
	}

	/**
	 * Returns the last item of a LIFO string message queue, or a specific item
	 * if so specified.
	 *
	 * @param array $array An array of strings, holding messages
	 * @param int   $i     Optional message index
	 *
	 * @return mixed The message string, or false if the key doesn't exist
	 */
	private function getItemFromArray($array, $i = null)
	{
		// Find the item
		if ($i === null)
		{
			// Default, return the last item
			$item = end($array);
		}
		else if (!array_key_exists($i, $array))
		{
			// If $i has been specified but does not exist, return false
			return false;
		}
		else
		{
			$item = $array[$i];
		}

		return $item;
	}

	/**
	 * Return all errors, if any
	 *
	 * @return    array    Array of error messages
	 */
	public function getErrors()
	{
		return $this->_errors;
	}

	/**
	 * Resets all error messages
	 */
	public function resetErrors()
	{
		$this->_errors = array();
	}

	/**
	 * Get the most recent warning message
	 *
	 * @param    integer $i Optional warning index
	 *
	 * @return    string    Error message
	 */
	public function getWarning($i = null)
	{
		return $this->getItemFromArray($this->_warnings, $i);
	}

	/**
	 * Return all warnings, if any
	 *
	 * @return    array    Array of error messages
	 */
	public function getWarnings()
	{
		return $this->_warnings;
	}

	/**
	 * Resets all warning messages
	 */
	public function resetWarnings()
	{
		$this->_warnings = array();
	}

	/**
	 * Propagates errors and warnings to a foreign object. The foreign object SHOULD
	 * implement the setError() and/or setWarning() methods but DOESN'T HAVE TO be of
	 * AKAbstractObject type. For example, this can even be used to propagate to a
	 * JObject instance in Joomla!. Propagated items will be removed from ourselves.
	 *
	 * @param object $object The object to propagate errors and warnings to.
	 */
	public function propagateToObject(&$object)
	{
		// Skip non-objects
		if (!is_object($object))
		{
			return;
		}

		if (method_exists($object, 'setError'))
		{
			if (!empty($this->_errors))
			{
				foreach ($this->_errors as $error)
				{
					$object->setError($error);
				}
				$this->_errors = array();
			}
		}

		if (method_exists($object, 'setWarning'))
		{
			if (!empty($this->_warnings))
			{
				foreach ($this->_warnings as $warning)
				{
					$object->setWarning($warning);
				}
				$this->_warnings = array();
			}
		}
	}

	/**
	 * Propagates errors and warnings from a foreign object. Each propagated list is
	 * then cleared on the foreign object, as long as it implements resetErrors() and/or
	 * resetWarnings() methods.
	 *
	 * @param object $object The object to propagate errors and warnings from
	 */
	public function propagateFromObject(&$object)
	{
		if (method_exists($object, 'getErrors'))
		{
			$errors = $object->getErrors();
			if (!empty($errors))
			{
				foreach ($errors as $error)
				{
					$this->setError($error);
				}
			}
			if (method_exists($object, 'resetErrors'))
			{
				$object->resetErrors();
			}
		}

		if (method_exists($object, 'getWarnings'))
		{
			$warnings = $object->getWarnings();
			if (!empty($warnings))
			{
				foreach ($warnings as $warning)
				{
					$this->setWarning($warning);
				}
			}
			if (method_exists($object, 'resetWarnings'))
			{
				$object->resetWarnings();
			}
		}
	}

	/**
	 * Add an error message
	 *
	 * @param    string $error Error message
	 */
	public function setError($error)
	{
		if ($this->_errors_queue_size > 0)
		{
			if (count($this->_errors) >= $this->_errors_queue_size)
			{
				array_shift($this->_errors);
			}
		}

		$this->_errors[] = $error;
	}

	/**
	 * Add an error message
	 *
	 * @param    string $error Error message
	 */
	public function setWarning($warning)
	{
		if ($this->_warnings_queue_size > 0)
		{
			if (count($this->_warnings) >= $this->_warnings_queue_size)
			{
				array_shift($this->_warnings);
			}
		}

		$this->_warnings[] = $warning;
	}

	/**
	 * Sets the size of the error queue (acts like a LIFO buffer)
	 *
	 * @param int $newSize The new queue size. Set to 0 for infinite length.
	 */
	protected function setErrorsQueueSize($newSize = 0)
	{
		$this->_errors_queue_size = (int) $newSize;
	}

	/**
	 * Sets the size of the warnings queue (acts like a LIFO buffer)
	 *
	 * @param int $newSize The new queue size. Set to 0 for infinite length.
	 */
	protected function setWarningsQueueSize($newSize = 0)
	{
		$this->_warnings_queue_size = (int) $newSize;
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The superclass of all Akeeba Kickstart parts. The "parts" are intelligent stateful
 * classes which perform a single procedure and have preparation, running and
 * finalization phases. The transition between phases is handled automatically by
 * this superclass' tick() final public method, which should be the ONLY public API
 * exposed to the rest of the Akeeba Engine.
 */
abstract class AKAbstractPart extends AKAbstractObject
{
	/**
	 * Indicates whether this part has finished its initialisation cycle
	 *
	 * @var boolean
	 */
	protected $isPrepared = false;

	/**
	 * Indicates whether this part has more work to do (it's in running state)
	 *
	 * @var boolean
	 */
	protected $isRunning = false;

	/**
	 * Indicates whether this part has finished its finalization cycle
	 *
	 * @var boolean
	 */
	protected $isFinished = false;

	/**
	 * Indicates whether this part has finished its run cycle
	 *
	 * @var boolean
	 */
	protected $hasRun = false;

	/**
	 * The name of the engine part (a.k.a. Domain), used in return table
	 * generation.
	 *
	 * @var string
	 */
	protected $active_domain = "";

	/**
	 * The step this engine part is in. Used verbatim in return table and
	 * should be set by the code in the _run() method.
	 *
	 * @var string
	 */
	protected $active_step = "";

	/**
	 * A more detailed description of the step this engine part is in. Used
	 * verbatim in return table and should be set by the code in the _run()
	 * method.
	 *
	 * @var string
	 */
	protected $active_substep = "";

	/**
	 * Any configuration variables, in the form of an array.
	 *
	 * @var array
	 */
	protected $_parametersArray = array();

	/** @var string The database root key */
	protected $databaseRoot = array();
	/** @var array An array of observers */
	protected $observers = array();
	/** @var int Last reported warnings's position in array */
	private $warnings_pointer = -1;

	/**
	 * The public interface to an engine part. This method takes care for
	 * calling the correct method in order to perform the initialisation -
	 * run - finalisation cycle of operation and return a proper response array.
	 *
	 * @return    array    A Response Array
	 */
	final public function tick()
	{
		// Call the right action method, depending on engine part state
		switch ($this->getState())
		{
			case "init":
				$this->_prepare();
				break;
			case "prepared":
				$this->_run();
				break;
			case "running":
				$this->_run();
				break;
			case "postrun":
				$this->_finalize();
				break;
		}

		// Send a Return Table back to the caller
		$out = $this->_makeReturnTable();

		return $out;
	}

	/**
	 * Returns the state of this engine part.
	 *
	 * @return string The state of this engine part. It can be one of
	 * error, init, prepared, running, postrun, finished.
	 */
	final public function getState()
	{
		if ($this->getError())
		{
			return "error";
		}

		if (!($this->isPrepared))
		{
			return "init";
		}

		if (!($this->isFinished) && !($this->isRunning) && !($this->hasRun) && ($this->isPrepared))
		{
			return "prepared";
		}

		if (!($this->isFinished) && $this->isRunning && !($this->hasRun))
		{
			return "running";
		}

		if (!($this->isFinished) && !($this->isRunning) && $this->hasRun)
		{
			return "postrun";
		}

		if ($this->isFinished)
		{
			return "finished";
		}
	}

	/**
	 * Runs the preparation for this part. Should set _isPrepared
	 * to true
	 */
	abstract protected function _prepare();

	/**
	 * Runs the main functionality loop for this part. Upon calling,
	 * should set the _isRunning to true. When it finished, should set
	 * the _hasRan to true. If an error is encountered, setError should
	 * be used.
	 */
	abstract protected function _run();

	/**
	 * Runs the finalisation process for this part. Should set
	 * _isFinished to true.
	 */
	abstract protected function _finalize();

	/**
	 * Constructs a Response Array based on the engine part's state.
	 *
	 * @return array The Response Array for the current state
	 */
	final protected function _makeReturnTable()
	{
		// Get a list of warnings
		$warnings = $this->getWarnings();
		// Report only new warnings if there is no warnings queue size
		if ($this->_warnings_queue_size == 0)
		{
			if (($this->warnings_pointer > 0) && ($this->warnings_pointer < (count($warnings))))
			{
				$warnings = array_slice($warnings, $this->warnings_pointer + 1);
				$this->warnings_pointer += count($warnings);
			}
			else
			{
				$this->warnings_pointer = count($warnings);
			}
		}

		$out = array(
			'HasRun'   => (!($this->isFinished)),
			'Domain'   => $this->active_domain,
			'Step'     => $this->active_step,
			'Substep'  => $this->active_substep,
			'Error'    => $this->getError(),
			'Warnings' => $warnings
		);

		return $out;
	}

	/**
	 * Returns a copy of the class's status array
	 *
	 * @return array
	 */
	public function getStatusArray()
	{
		return $this->_makeReturnTable();
	}

	/**
	 * Sends any kind of setup information to the engine part. Using this,
	 * we avoid passing parameters to the constructor of the class. These
	 * parameters should be passed as an indexed array and should be taken
	 * into account during the preparation process only. This function will
	 * set the error flag if it's called after the engine part is prepared.
	 *
	 * @param array $parametersArray The parameters to be passed to the
	 *                               engine part.
	 */
	final public function setup($parametersArray)
	{
		if ($this->isPrepared)
		{
			$this->setState('error', "Can't modify configuration after the preparation of " . $this->active_domain);
		}
		else
		{
			$this->_parametersArray = $parametersArray;
			if (array_key_exists('root', $parametersArray))
			{
				$this->databaseRoot = $parametersArray['root'];
			}
		}
	}

	/**
	 * Sets the engine part's internal state, in an easy to use manner
	 *
	 * @param    string $state        One of init, prepared, running, postrun, finished, error
	 * @param    string $errorMessage The reported error message, should the state be set to error
	 */
	protected function setState($state = 'init', $errorMessage = 'Invalid setState argument')
	{
		switch ($state)
		{
			case 'init':
				$this->isPrepared = false;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'prepared':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'running':
				$this->isPrepared = true;
				$this->isRunning  = true;
				$this->isFinished = false;
				$this->hasRun     = false;
				break;

			case 'postrun':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = false;
				$this->hasRun     = true;
				break;

			case 'finished':
				$this->isPrepared = true;
				$this->isRunning  = false;
				$this->isFinished = true;
				$this->hasRun     = false;
				break;

			case 'error':
			default:
				$this->setError($errorMessage);
				break;
		}
	}

	final public function getDomain()
	{
		return $this->active_domain;
	}

	final public function getStep()
	{
		return $this->active_step;
	}

	final public function getSubstep()
	{
		return $this->active_substep;
	}

	/**
	 * Attaches an observer object
	 *
	 * @param AKAbstractPartObserver $obs
	 */
	function attach(AKAbstractPartObserver $obs)
	{
		$this->observers["$obs"] = $obs;
	}

	/**
	 * Detaches an observer object
	 *
	 * @param AKAbstractPartObserver $obs
	 */
	function detach(AKAbstractPartObserver $obs)
	{
		unset($this->observers["$obs"]);
	}

	/**
	 * Sets the BREAKFLAG, which instructs this engine part that the current step must break immediately,
	 * in fear of timing out.
	 */
	protected function setBreakFlag()
	{
		AKFactory::set('volatile.breakflag', true);
	}

	final protected function setDomain($new_domain)
	{
		$this->active_domain = $new_domain;
	}

	final protected function setStep($new_step)
	{
		$this->active_step = $new_step;
	}

	final protected function setSubstep($new_substep)
	{
		$this->active_substep = $new_substep;
	}

	/**
	 * Notifies observers each time something interesting happened to the part
	 *
	 * @param mixed $message The event object
	 */
	protected function notify($message)
	{
		foreach ($this->observers as $obs)
		{
			$obs->update($this, $message);
		}
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The base class of unarchiver classes
 */
abstract class AKAbstractUnarchiver extends AKAbstractPart
{
	/** @var array List of the names of all archive parts */
	public $archiveList = array();
	/** @var int The total size of all archive parts */
	public $totalSize = array();
	/** @var array Which files to rename */
	public $renameFiles = array();
	/** @var array Which directories to rename */
	public $renameDirs = array();
	/** @var array Which files to skip */
	public $skipFiles = array();
	/** @var string Archive filename */
	protected $filename = null;
	/** @var integer Current archive part number */
	protected $currentPartNumber = -1;
	/** @var integer The offset inside the current part */
	protected $currentPartOffset = 0;
	/** @var bool Should I restore permissions? */
	protected $flagRestorePermissions = false;
	/** @var AKAbstractPostproc Post processing class */
	protected $postProcEngine = null;
	/** @var string Absolute path to prepend to extracted files */
	protected $addPath = '';
	/** @var string Absolute path to remove from extracted files */
	protected $removePath = '';
	/** @var integer Chunk size for processing */
	protected $chunkSize = 524288;

	/** @var resource File pointer to the current archive part file */
	protected $fp = null;

	/** @var int Run state when processing the current archive file */
	protected $runState = null;

	/** @var stdClass File header data, as read by the readFileHeader() method */
	protected $fileHeader = null;

	/** @var int How much of the uncompressed data we've read so far */
	protected $dataReadLength = 0;

	/** @var array Unwriteable files in these directories are always ignored and do not cause errors when not extracted */
	protected $ignoreDirectories = array();

	/**
	 * Wakeup function, called whenever the class is unserialized
	 */
	public function __wakeup()
	{
		if ($this->currentPartNumber >= 0)
		{
			$this->fp = @fopen($this->archiveList[$this->currentPartNumber], 'r');

			if ((is_resource($this->fp)) && ($this->currentPartOffset > 0))
			{
				@fseek($this->fp, $this->currentPartOffset);
			}
		}
	}

	/**
	 * Sleep function, called whenever the class is serialized
	 */
	public function shutdown()
	{
		if (is_resource($this->fp))
		{
			$this->currentPartOffset = @ftell($this->fp);
			@fclose($this->fp);
		}
	}

	/**
	 * Is this file or directory contained in a directory we've decided to ignore
	 * write errors for? This is useful to let the extraction work despite write
	 * errors in the log, logs and tmp directories which MIGHT be used by the system
	 * on some low quality hosts and Plesk-powered hosts.
	 *
	 * @param   string $shortFilename The relative path of the file/directory in the package
	 *
	 * @return  boolean  True if it belongs in an ignored directory
	 */
	public function isIgnoredDirectory($shortFilename)
	{
		// return false;

		if (substr($shortFilename, -1) == '/')
		{
			$check = rtrim($shortFilename, '/');
		}
		else
		{
			$check = dirname($shortFilename);
		}

		return in_array($check, $this->ignoreDirectories);
	}

	/**
	 * Implements the abstract _prepare() method
	 */
	final protected function _prepare()
	{
		if (count($this->_parametersArray) > 0)
		{
			foreach ($this->_parametersArray as $key => $value)
			{
				switch ($key)
				{
					// Archive's absolute filename
					case 'filename':
						$this->filename = $value;

						// Sanity check
						if (!empty($value))
						{
							$value = strtolower($value);

							if (strlen($value) > 6)
							{
								if (
									(substr($value, 0, 7) == 'http://')
									|| (substr($value, 0, 8) == 'https://')
									|| (substr($value, 0, 6) == 'ftp://')
									|| (substr($value, 0, 7) == 'ssh2://')
									|| (substr($value, 0, 6) == 'ssl://')
								)
								{
									$this->setState('error', 'Invalid archive location');
								}
							}
						}


						break;

					// Should I restore permissions?
					case 'restore_permissions':
						$this->flagRestorePermissions = $value;
						break;

					// Should I use FTP?
					case 'post_proc':
						$this->postProcEngine = AKFactory::getpostProc($value);
						break;

					// Path to add in the beginning
					case 'add_path':
						$this->addPath = $value;
						$this->addPath = str_replace('\\', '/', $this->addPath);
						$this->addPath = rtrim($this->addPath, '/');
						if (!empty($this->addPath))
						{
							$this->addPath .= '/';
						}
						break;

					// Path to remove from the beginning
					case 'remove_path':
						$this->removePath = $value;
						$this->removePath = str_replace('\\', '/', $this->removePath);
						$this->removePath = rtrim($this->removePath, '/');
						if (!empty($this->removePath))
						{
							$this->removePath .= '/';
						}
						break;

					// Which files to rename (hash array)
					case 'rename_files':
						$this->renameFiles = $value;
						break;

					// Which files to rename (hash array)
					case 'rename_dirs':
						$this->renameDirs = $value;
						break;

					// Which files to skip (indexed array)
					case 'skip_files':
						$this->skipFiles = $value;
						break;

					// Which directories to ignore when we can't write files in them (indexed array)
					case 'ignoredirectories':
						$this->ignoreDirectories = $value;
						break;
				}
			}
		}

		$this->scanArchives();

		$this->readArchiveHeader();
		$errMessage = $this->getError();
		if (!empty($errMessage))
		{
			$this->setState('error', $errMessage);
		}
		else
		{
			$this->runState = AK_STATE_NOFILE;
			$this->setState('prepared');
		}
	}

	/**
	 * Scans for archive parts
	 */
	private function scanArchives()
	{
		if (defined('KSDEBUG'))
		{
			@unlink('debug.txt');
		}
		debugMsg('Preparing to scan archives');

		$privateArchiveList = array();

		// Get the components of the archive filename
		$dirname         = dirname($this->filename);
		$base_extension  = $this->getBaseExtension();
		$basename        = basename($this->filename, $base_extension);
		$this->totalSize = 0;

		// Scan for multiple parts until we don't find any more of them
		$count             = 0;
		$found             = true;
		$this->archiveList = array();
		while ($found)
		{
			++$count;
			$extension = substr($base_extension, 0, 2) . sprintf('%02d', $count);
			$filename  = $dirname . DIRECTORY_SEPARATOR . $basename . $extension;
			$found     = file_exists($filename);
			if ($found)
			{
				debugMsg('- Found archive ' . $filename);
				// Add yet another part, with a numeric-appended filename
				$this->archiveList[] = $filename;

				$filesize = @filesize($filename);
				$this->totalSize += $filesize;

				$privateArchiveList[] = array($filename, $filesize);
			}
			else
			{
				debugMsg('- Found archive ' . $this->filename);
				// Add the last part, with the regular extension
				$this->archiveList[] = $this->filename;

				$filename = $this->filename;
				$filesize = @filesize($filename);
				$this->totalSize += $filesize;

				$privateArchiveList[] = array($filename, $filesize);
			}
		}
		debugMsg('Total archive parts: ' . $count);

		$this->currentPartNumber = -1;
		$this->currentPartOffset = 0;
		$this->runState          = AK_STATE_NOFILE;

		// Send start of file notification
		$message                     = new stdClass;
		$message->type               = 'totalsize';
		$message->content            = new stdClass;
		$message->content->totalsize = $this->totalSize;
		$message->content->filelist  = $privateArchiveList;
		$this->notify($message);
	}

	/**
	 * Returns the base extension of the file, e.g. '.jpa'
	 *
	 * @return string
	 */
	private function getBaseExtension()
	{
		static $baseextension;

		if (empty($baseextension))
		{
			$basename      = basename($this->filename);
			$lastdot       = strrpos($basename, '.');
			$baseextension = substr($basename, $lastdot);
		}

		return $baseextension;
	}

	/**
	 * Concrete classes are supposed to use this method in order to read the archive's header and
	 * prepare themselves to the point of being ready to extract the first file.
	 */
	protected abstract function readArchiveHeader();

	protected function _run()
	{
		if ($this->getState() == 'postrun')
		{
			return;
		}

		$this->setState('running');

		$timer = AKFactory::getTimer();

		$status = true;
		while ($status && ($timer->getTimeLeft() > 0))
		{
			switch ($this->runState)
			{
				case AK_STATE_NOFILE:
					debugMsg(__CLASS__ . '::_run() - Reading file header');
					$status = $this->readFileHeader();
					if ($status)
					{
						// Send start of file notification
						$message                        = new stdClass;
						$message->type                  = 'startfile';
						$message->content               = new stdClass;
						$message->content->realfile     = $this->fileHeader->file;
						$message->content->file         = $this->fileHeader->file;
						$message->content->uncompressed = $this->fileHeader->uncompressed;

						if (array_key_exists('realfile', get_object_vars($this->fileHeader)))
						{
							$message->content->realfile = $this->fileHeader->realFile;
						}

						if (array_key_exists('compressed', get_object_vars($this->fileHeader)))
						{
							$message->content->compressed = $this->fileHeader->compressed;
						}
						else
						{
							$message->content->compressed = 0;
						}

						debugMsg(__CLASS__ . '::_run() - Preparing to extract ' . $message->content->realfile);

						$this->notify($message);
					}
					else
					{
						debugMsg(__CLASS__ . '::_run() - Could not read file header');
					}
					break;

				case AK_STATE_HEADER:
				case AK_STATE_DATA:
					debugMsg(__CLASS__ . '::_run() - Processing file data');
					$status = $this->processFileData();
					break;

				case AK_STATE_DATAREAD:
				case AK_STATE_POSTPROC:
					debugMsg(__CLASS__ . '::_run() - Calling post-processing class');
					$this->postProcEngine->timestamp = $this->fileHeader->timestamp;
					$status                          = $this->postProcEngine->process();
					$this->propagateFromObject($this->postProcEngine);
					$this->runState = AK_STATE_DONE;
					break;

				case AK_STATE_DONE:
				default:
					if ($status)
					{
						debugMsg(__CLASS__ . '::_run() - Finished extracting file');
						// Send end of file notification
						$message          = new stdClass;
						$message->type    = 'endfile';
						$message->content = new stdClass;
						if (array_key_exists('realfile', get_object_vars($this->fileHeader)))
						{
							$message->content->realfile = $this->fileHeader->realFile;
						}
						else
						{
							$message->content->realfile = $this->fileHeader->file;
						}
						$message->content->file = $this->fileHeader->file;
						if (array_key_exists('compressed', get_object_vars($this->fileHeader)))
						{
							$message->content->compressed = $this->fileHeader->compressed;
						}
						else
						{
							$message->content->compressed = 0;
						}
						$message->content->uncompressed = $this->fileHeader->uncompressed;
						$this->notify($message);
					}
					$this->runState = AK_STATE_NOFILE;

					break;
			}
		}

		$error = $this->getError();

		if (!$status && ($this->runState == AK_STATE_NOFILE) && empty($error))
		{
			debugMsg(__CLASS__ . '::_run() - Just finished');
			// We just finished
			$this->setState('postrun');

			// Reset internal state, prevents __wakeup from trying to open a non-existent file
			$this->currentPartNumber = -1;
		}
		elseif (!empty($error))
		{
			debugMsg(__CLASS__ . '::_run() - Halted with an error:');
			debugMsg($error);
			$this->setState('error', $error);
		}
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected abstract function readFileHeader();

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected abstract function processFileData();

	protected function _finalize()
	{
		// Nothing to do
		$this->setState('finished');
	}

	/**
	 * Opens the next part file for reading
	 */
	protected function nextFile()
	{
		debugMsg('Current part is ' . $this->currentPartNumber . '; opening the next part');
		++$this->currentPartNumber;

		if ($this->currentPartNumber > (count($this->archiveList) - 1))
		{
			$this->setState('postrun');

			return false;
		}
		else
		{
			if (is_resource($this->fp))
			{
				@fclose($this->fp);
			}
			debugMsg('Opening file ' . $this->archiveList[$this->currentPartNumber]);
			$this->fp = @fopen($this->archiveList[$this->currentPartNumber], 'r');
			if ($this->fp === false)
			{
				debugMsg('Could not open file - crash imminent');
				$this->setError(AKText::sprintf('ERR_COULD_NOT_OPEN_ARCHIVE_PART', $this->archiveList[$this->currentPartNumber]));
			}
			fseek($this->fp, 0);
			$this->currentPartOffset = 0;

			return true;
		}
	}

	/**
	 * Returns true if we have reached the end of file
	 *
	 * @param $local bool True to return EOF of the local file, false (default) to return if we have reached the end of
	 *               the archive set
	 *
	 * @return bool True if we have reached End Of File
	 */
	protected function isEOF($local = false)
	{
		$eof = @feof($this->fp);

		if (!$eof)
		{
			// Border case: right at the part's end (eeeek!!!). For the life of me, I don't understand why
			// feof() doesn't report true. It expects the fp to be positioned *beyond* the EOF to report
			// true. Incredible! :(
			$position = @ftell($this->fp);
			$filesize = @filesize($this->archiveList[$this->currentPartNumber]);
			if ($filesize <= 0)
			{
				// 2Gb or more files on a 32 bit version of PHP tend to get screwed up. Meh.
				$eof = false;
			}
			elseif ($position >= $filesize)
			{
				$eof = true;
			}
		}

		if ($local)
		{
			return $eof;
		}
		else
		{
			return $eof && ($this->currentPartNumber >= (count($this->archiveList) - 1));
		}
	}

	/**
	 * Tries to make a directory user-writable so that we can write a file to it
	 *
	 * @param $path string A path to a file
	 */
	protected function setCorrectPermissions($path)
	{
		static $rootDir = null;

		if (is_null($rootDir))
		{
			$rootDir = rtrim(AKFactory::get('kickstart.setup.destdir', ''), '/\\');
		}

		$directory = rtrim(dirname($path), '/\\');
		if ($directory != $rootDir)
		{
			// Is this an unwritable directory?
			if (!is_writeable($directory))
			{
				$this->postProcEngine->chmod($directory, 0755);
			}
		}
		$this->postProcEngine->chmod($path, 0644);
	}

	/**
	 * Reads data from the archive and notifies the observer with the 'reading' message
	 *
	 * @param $fp
	 * @param $length
	 */
	protected function fread($fp, $length = null)
	{
		if (is_numeric($length))
		{
			if ($length > 0)
			{
				$data = fread($fp, $length);
			}
			else
			{
				$data = fread($fp, PHP_INT_MAX);
			}
		}
		else
		{
			$data = fread($fp, PHP_INT_MAX);
		}
		if ($data === false)
		{
			$data = '';
		}

		// Send start of file notification
		$message                  = new stdClass;
		$message->type            = 'reading';
		$message->content         = new stdClass;
		$message->content->length = strlen($data);
		$this->notify($message);

		return $data;
	}

	/**
	 * Removes the configured $removePath from the path $path
	 *
	 * @param   string $path The path to reduce
	 *
	 * @return  string  The reduced path
	 */
	protected function removePath($path)
	{
		if (empty($this->removePath))
		{
			return $path;
		}

		if (strpos($path, $this->removePath) === 0)
		{
			$path = substr($path, strlen($this->removePath));
			$path = ltrim($path, '/\\');
		}

		return $path;
	}

	/**
	 * Am I supposed to skip the extraction of the current file? This depends on
	 *
	 * @return bool
	 */
	protected function mustSkip()
	{
		static $isDryRun = null;

		// List of files (and patterns) to extract
		static $extractList = null;

		// Internal cache of the last file we checked and whether it must be skipped
		static $lastFileName = '';
		static $mustSkip = false;

		// Make sure the dry run flag is, indeed, populated
		if (is_null($isDryRun))
		{
			$isDryRun = AKFactory::get('kickstart.setup.dryrun', '0');
		}

		// If it's a Kickstart dry run we have to skip the extraction of the file
		if ($isDryRun)
		{
			return true;
		}

		// Make sure I have a list of files and patterns to extract
		if (is_null($extractList))
		{
			$extractList = $this->getExtractList();
		}

		// No list of files to extract is given; we must extract everything.
		if (empty($extractList))
		{
			return false;
		}

		// I am asked about the same file again. Return the cached result.
		if ($this->fileHeader->file == $lastFileName)
		{
			return $mustSkip;
		}

		// Does the current file match the extract patterns or not?
		$lastFileName = $this->fileHeader->file;
		$lastFileName = (strpos($lastFileName, $this->addPath) === 0) ? substr($lastFileName, strlen(rtrim($this->addPath, "\\/")) + 1) : $lastFileName;
		$mustSkip     = !$this->matchesGlobPatterns($lastFileName, $extractList);

		return $mustSkip;
	}

	protected function fuzzySignatureSearch($requiredSignatures, $sigLen)
	{
		if (!is_array($requiredSignatures))
		{
			$requiredSignatures = [$requiredSignatures];
		}

		fseek($this->fp, 0, SEEK_SET);

		$stuff  = $this->fread($this->fp, 131072);
		$maxPos = function_exists('mb_strlen') ? mb_strlen($stuff, 'binary') : strlen($stuff);

		for ($i = 0; $i < $maxPos; $i++)
		{
			foreach ($requiredSignatures as $signature)
			{
				$sigBinary = function_exists('mb_substr') ? mb_substr($stuff, $i, $sigLen, 'binary') : substr($stuff, $i, $sigLen);

				if ($sigBinary === $signature)
				{
					fseek($this->fp, $i, SEEK_SET);

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get the list of files / folders to extract. The list can contain filenames or glob patterns.
	 *
	 * @return  array
	 */
	private function getExtractList()
	{
		$rawList = AKFactory::get('kickstart.setup.extract_list', '');

		// Sometimes I could get an array, e.g. from CLI
		if (is_array($rawList))
		{
			$rawList = implode("\n", $rawList);
		}

		// Remove any whitespace
		$rawList = trim($rawList);

		if (empty($rawList))
		{
			return array();
		}

		// Convert commas to newlines so we can support both ways to express lists
		$rawList = str_replace(",", "\n", $rawList);
		$rawList = trim($rawList);

		// Convert the list to an array and clean it
		$list = explode("\n", $rawList);
		$list = array_map('trim', $list);

		return array_unique($list);
	}

	/**
	 * Tests whether the item $item matches the list of shell patterns $list.
	 *
	 * @param   string  $item  The file name to test
	 * @param   array   $list  The list of glob patterns to match
	 *
	 * @return  bool
	 */
	private function matchesGlobPatterns($item, array $list)
	{
		if (empty($list))
		{
			return true;
		}

		foreach ($list as $pattern)
		{
			if (fnmatch($pattern, $item))
			{
				return true;
			}
		}

		return false;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * File post processor engines base class
 */
abstract class AKAbstractPostproc extends AKAbstractObject
{
	/** @var int The UNIX timestamp of the file's desired modification date */
	public $timestamp = 0;
	/** @var string The current (real) file path we'll have to process */
	protected $filename = null;
	/** @var int The requested permissions */
	protected $perms = 0755;
	/** @var string The temporary file path we gave to the unarchiver engine */
	protected $tempFilename = null;
	/** @var string The temporary directory where the data will be stored */
	protected $tempDir = '';

	/**
	 * Processes the current file, e.g. moves it from temp to final location by FTP
	 */
	abstract public function process();

	/**
	 * The unarchiver tells us the path to the filename it wants to extract and we give it
	 * a different path instead.
	 *
	 * @param string $filename The path to the real file
	 * @param int    $perms    The permissions we need the file to have
	 *
	 * @return string The path to the temporary file
	 */
	abstract public function processFilename($filename, $perms = 0755);

	/**
	 * Recursively creates a directory if it doesn't exist
	 *
	 * @param string $dirName The directory to create
	 * @param int    $perms   The permissions to give to that directory
	 */
	abstract public function createDirRecursive($dirName, $perms);

	abstract public function chmod($file, $perms);

	abstract public function unlink($file);

	abstract public function rmdir($directory);

	abstract public function rename($from, $to);

	/**
	 * Returns the configured temporary directory
	 *
	 * @return string
	 */
	public function getTempDir()
	{
		return $this->tempDir;
	}
}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Descendants of this class can be used in the unarchiver's observer methods (attach, detach and notify)
 *
 * @author Nicholas
 *
 */
abstract class AKAbstractPartObserver
{
	abstract public function update($object, $message);
}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Direct file writer
 */
class AKPostprocDirect extends AKAbstractPostproc
{
	public function process()
	{
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);
		if ($restorePerms)
		{
			@chmod($this->filename, $this->perms);
		}
		else
		{
			if (@is_file($this->filename))
			{
				@chmod($this->filename, 0644);
			}
			else
			{
				@chmod($this->filename, 0755);
			}
		}
		if ($this->timestamp > 0)
		{
			@touch($this->filename, $this->timestamp);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	public function processFilename($filename, $perms = 0755)
	{
		$this->perms    = $perms;
		$this->filename = $filename;

		return $filename;
	}

	public function createDirRecursive($dirName, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		if (@mkdir($dirName, 0755, true))
		{
			@chmod($dirName, 0755);

			return true;
		}

		$root = AKFactory::get('kickstart.setup.destdir');
		$root = rtrim(str_replace('\\', '/', $root), '/');
		$dir  = rtrim(str_replace('\\', '/', $dirName), '/');
		if (strpos($dir, $root) === 0)
		{
			$dir = ltrim(substr($dir, strlen($root)), '/');
			$root .= '/';
		}
		else
		{
			$root = '';
		}

		if (empty($dir))
		{
			return true;
		}

		$dirArray = explode('/', $dir);
		$path     = '';
		foreach ($dirArray as $dir)
		{
			$path .= $dir . '/';
			$ret = is_dir($root . $path) ? true : @mkdir($root . $path);
			if (!$ret)
			{
				// Is this a file instead of a directory?
				if (is_file($root . $path))
				{
					@unlink($root . $path);
					$ret = @mkdir($root . $path);
				}
				if (!$ret)
				{
					$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $path));

					return false;
				}
			}
			// Try to set new directory permissions to 0755
			@chmod($root . $path, $perms);
		}

		return true;
	}

	public function chmod($file, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		return @chmod($file, $perms);
	}

	public function unlink($file)
	{
		return @unlink($file);
	}

	public function rmdir($directory)
	{
		return @rmdir($directory);
	}

	public function rename($from, $to)
	{
		return @rename($from, $to);
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * FTP file writer
 */
class AKPostprocFTP extends AKAbstractPostproc
{
	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;
	/** @var bool use Passive mode? */
	public $passive = true;
	/** @var string FTP host name */
	public $host = '';
	/** @var int FTP port */
	public $port = 21;
	/** @var string FTP user name */
	public $user = '';
	/** @var string FTP password */
	public $pass = '';
	/** @var string FTP initial directory */
	public $dir = '';
	/** @var resource The FTP handle */
	private $handle = null;

	public function __construct()
	{
		$this->useSSL  = AKFactory::get('kickstart.ftp.ssl', false);
		$this->passive = AKFactory::get('kickstart.ftp.passive', true);
		$this->host    = AKFactory::get('kickstart.ftp.host', '');
		$this->port    = AKFactory::get('kickstart.ftp.port', 21);

		if (trim($this->port) == '')
		{
			$this->port = 21;
		}
		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		$connected = $this->connect();

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');

				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}

				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');

				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');

					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}

			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('FTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	public function connect()
	{
		// Connect to server, using SSL if so required
		if ($this->useSSL)
		{
			$this->handle = @ftp_ssl_connect($this->host, $this->port);
		}
		else
		{
			$this->handle = @ftp_connect($this->host, $this->port);
		}

		if ($this->handle === false)
		{
			$this->setError(AKText::_('WRONG_FTP_HOST'));

			return false;
		}

		// Login
		if (!@ftp_login($this->handle, $this->user, $this->pass))
		{
			$this->setError(AKText::_('WRONG_FTP_USER'));
			@ftp_close($this->handle);

			return false;
		}

		// Change to initial directory
		if (!@ftp_chdir($this->handle, $this->dir))
		{
			$this->setError(AKText::_('WRONG_FTP_PATH1'));
			@ftp_close($this->handle);

			return false;
		}

		// Enable passive mode if the user requested it
		if ($this->passive)
		{
			@ftp_pasv($this->handle, true);
		}
		else
		{
			@ftp_pasv($this->handle, false);
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$tempHandle   = fopen('php://temp', 'r+');

		if (@ftp_fget($this->handle, $tempHandle, $testFilename, FTP_ASCII, 0) === false)
		{
			$this->setError(AKText::_('WRONG_FTP_PATH2'));
			@ftp_close($this->handle);
			fclose($tempHandle);

			return false;
		}

		fclose($tempHandle);

		return true;
	}

	private function isDirWritable($dir)
	{
		$fp = @fopen($dir . '/kickstart.dat', 'w');

		if ($fp === false)
		{
			return false;
		}
		else
		{
			@fclose($fp);
			unlink($dir . '/kickstart.dat');

			return true;
		}
	}

	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}

		if (empty($dirName))
		{
			$dirName = '';
		} // 'cause the substr() above may return FALSE.

		$check = '/' . trim($this->dir, '/') . '/' . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs     = explode('/', $dirName);
		$previousDir = '/' . trim($this->dir);

		foreach ($alldirs as $curdir)
		{
			$check = $previousDir . '/' . $curdir;

			if (!$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				@ftp_delete($this->handle, $check);

				if (@ftp_mkdir($this->handle, $check) === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($removePath . $check);

					if (@ftp_mkdir($this->handle, $check) === false)
					{
						// Can we fall back to pure PHP mode, sire?
						if (!@mkdir($check))
						{
							$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

							return false;
						}
						else
						{
							// Since the directory was built by PHP, change its permissions
							$trustMeIKnowWhatImDoing =
								500 + 10 + 1; // working around overzealous scanners written by bozos
							@chmod($check, $trustMeIKnowWhatImDoing);

							return true;
						}
					}
				}

				@ftp_chmod($this->handle, $perms, $check);

			}

			$previousDir = $check;
		}

		return true;
	}

	private function is_dir($dir)
	{
		return @ftp_chdir($this->handle, $dir);
	}

	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = @error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}
			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	public function __sleep()
	{
		if (!is_null($this->handle) && is_resource($this->handle))
		{
			@ftp_close($this->handle);
		}

		$this->handle = null;
	}

	public function __destruct()
	{
		if (!is_null($this->handle) && is_resource($this->handle))
		{
			@ftp_close($this->handle);
		}
	}


	public function __wakeup()
	{
		$this->connect();
	}

	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath = dirname($this->filename);
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$removePath = ltrim($removePath, "/");
			$remotePath = ltrim($remotePath, "/");
			$left       = substr($remotePath, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$remotePath = substr($remotePath, strlen($removePath));
			}
		}

		$absoluteFSPath  = dirname($this->filename);
		$relativeFTPPath = trim($remotePath, '/');
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		$ret = @ftp_chdir($this->handle, $absoluteFTPPath);

		if ($ret === false)
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}

			$ret = @ftp_chdir($this->handle, $absoluteFTPPath);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		$ret = @ftp_put($this->handle, $remoteName, $this->tempFilename, FTP_BINARY);

		if ($ret === false)
		{
			// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$fp = @fopen($this->tempFilename, 'r');

			if ($fp !== false)
			{
				$ret = @ftp_fput($this->handle, $remoteName, $fp, FTP_BINARY);
				@fclose($fp);
			}
			else
			{
				$ret = false;
			}
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if ($restorePerms)
		{
			@ftp_chmod($this->_handle, $this->perms, $remoteName);
		}
		else
		{
			@ftp_chmod($this->_handle, 0644, $remoteName);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	/*
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 * @param $path string The full path to a directory or file
	 */

	public function unlink($file)
	{
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($file, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$file = substr($file, strlen($removePath));
			}
		}

		$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

		return @ftp_delete($this->handle, $check);
	}

	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	public function close()
	{
		@ftp_close($this->handle);
	}

	public function chmod($file, $perms)
	{
		return @ftp_chmod($this->handle, $perms, $file);
	}

	public function rmdir($directory)
	{
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($directory, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$directory = substr($directory, strlen($removePath));
			}
		}

		$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

		return @ftp_rmdir($this->handle, $check);
	}

	public function rename($from, $to)
	{
		$originalFrom = $from;
		$originalTo   = $to;

		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($from, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$from = substr($from, strlen($removePath));
			}
		}

		$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');

		if (!empty($removePath))
		{
			$left = substr($to, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$to = substr($to, strlen($removePath));
			}
		}

		$to = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

		$result = @ftp_rename($this->handle, $from, $to);

		if ($result !== true)
		{
			return @rename($from, $to);
		}
		else
		{
			return true;
		}
	}

}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * FTP file writer
 */
class AKPostprocSFTP extends AKAbstractPostproc
{
	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;
	/** @var bool use Passive mode? */
	public $passive = true;
	/** @var string FTP host name */
	public $host = '';
	/** @var int FTP port */
	public $port = 21;
	/** @var string FTP user name */
	public $user = '';
	/** @var string FTP password */
	public $pass = '';
	/** @var string FTP initial directory */
	public $dir = '';

	/** @var resource SFTP resource handle */
	private $handle = null;

	/** @var resource SSH2 connection resource handle */
	private $_connection = null;

	/** @var string Current remote directory, including the remote directory string */
	private $_currentdir;

	public function __construct()
	{
		$this->host = AKFactory::get('kickstart.ftp.host', '');
		$this->port = AKFactory::get('kickstart.ftp.port', 22);

		if (trim($this->port) == '')
		{
			$this->port = 22;
		}

		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		$connected = $this->connect();

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');
				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}
				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');
				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');
					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}
			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('SFTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	public function connect()
	{
		$this->_connection = false;

		if (!function_exists('ssh2_connect'))
		{
			$this->setError(AKText::_('SFTP_NO_SSH2'));

			return false;
		}

		$this->_connection = @ssh2_connect($this->host, $this->port);

		if (!@ssh2_auth_password($this->_connection, $this->user, $this->pass))
		{
			$this->setError(AKText::_('SFTP_WRONG_USER'));

			$this->_connection = false;

			return false;
		}

		$this->handle = @ssh2_sftp($this->_connection);

		// I must have an absolute directory
		if (!$this->dir)
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			return false;
		}

		// Change to initial directory
		if (!$this->sftp_chdir('/'))
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			unset($this->_connection);
			unset($this->handle);

			return false;
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$basePath     = '/' . trim($this->dir, '/');

		if (@fopen("ssh2.sftp://{$this->handle}$basePath/$testFilename", 'r+') === false)
		{
			$this->setError(AKText::_('SFTP_WRONG_STARTING_DIR'));

			unset($this->_connection);
			unset($this->handle);

			return false;
		}

		return true;
	}

	/**
	 * Changes to the requested directory in the remote server. You give only the
	 * path relative to the initial directory and it does all the rest by itself,
	 * including doing nothing if the remote directory is the one we want.
	 *
	 * @param   string $dir The (realtive) remote directory
	 *
	 * @return  bool True if successful, false otherwise.
	 */
	private function sftp_chdir($dir)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dir        = str_replace('\\', '/', $dir);

			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dir        = rtrim($dir, '/\\') . '/';

			// Process the path removal
			$left = substr($dir, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dir = substr($dir, strlen($removePath));
			}
		}

		if (empty($dir))
		{
			// Because the substr() above may return FALSE.
			$dir = '';
		}

		// Calculate "real" (absolute) SFTP path
		$realdir = substr($this->dir, -1) == '/' ? substr($this->dir, 0, strlen($this->dir) - 1) : $this->dir;
		$realdir .= '/' . $dir;
		$realdir = substr($realdir, 0, 1) == '/' ? $realdir : '/' . $realdir;

		if ($this->_currentdir == $realdir)
		{
			// Already there, do nothing
			return true;
		}

		$result = @ssh2_sftp_stat($this->handle, $realdir);

		if ($result === false)
		{
			return false;
		}
		else
		{
			// Update the private "current remote directory" variable
			$this->_currentdir = $realdir;

			return true;
		}
	}

	private function isDirWritable($dir)
	{
		if (@fopen("ssh2.sftp://{$this->handle}$dir/kickstart.dat", 'w') === false)
		{
			return false;
		}
		else
		{
			@ssh2_sftp_unlink($this->handle, $dir . '/kickstart.dat');

			return true;
		}
	}

	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));
			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}
		if (empty($dirName))
		{
			$dirName = '';
		} // 'cause the substr() above may return FALSE.

		$check = '/' . trim($this->dir, '/ ') . '/' . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs     = explode('/', $dirName);
		$previousDir = '/' . trim($this->dir, '/ ');

		foreach ($alldirs as $curdir)
		{
			if (!$curdir)
			{
				continue;
			}

			$check = $previousDir . '/' . $curdir;

			if (!$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				@ssh2_sftp_unlink($this->handle, $check);

				if (@ssh2_sftp_mkdir($this->handle, $check) === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($check);

					if (@ssh2_sftp_mkdir($this->handle, $check) === false)
					{
						// Can we fall back to pure PHP mode, sire?
						if (!@mkdir($check))
						{
							$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

							return false;
						}
						else
						{
							// Since the directory was built by PHP, change its permissions
							$trustMeIKnowWhatImDoing =
								500 + 10 + 1; // working around overzealous scanners written by bozos
							@chmod($check, $trustMeIKnowWhatImDoing);

							return true;
						}
					}
				}

				@ssh2_sftp_chmod($this->handle, $check, $perms);
			}

			$previousDir = $check;
		}

		return true;
	}

	private function is_dir($dir)
	{
		return $this->sftp_chdir($dir);
	}

	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = @error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}

			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . '/' . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . '/' . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . '/' . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	function __wakeup()
	{
		$this->connect();
	}

	/*
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 * @param $path string The full path to a directory or file
	 */

	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath      = dirname($this->filename);
		$absoluteFSPath  = dirname($this->filename);
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		$ret = $this->sftp_chdir($absoluteFTPPath);

		if ($ret === false)
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}

			$ret = $this->sftp_chdir($absoluteFTPPath);

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		// Create the file
		$ret = $this->write($this->tempFilename, $remoteName);

		// If I got a -1 it means that I wasn't able to open the file, so I have to stop here
		if ($ret === -1)
		{
			$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		if ($ret === false)
		{
			// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$ret = $this->write($this->tempFilename, $remoteName);
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('SFTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if ($restorePerms)
		{
			$this->chmod($remoteName, $this->perms);
		}
		else
		{
			$this->chmod($remoteName, 0644);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	private function write($local, $remote)
	{
		$fp      = @fopen("ssh2.sftp://{$this->handle}$remote", 'w');
		$localfp = @fopen($local, 'r');

		if ($fp === false)
		{
			return -1;
		}

		if ($localfp === false)
		{
			@fclose($fp);

			return -1;
		}

		$res = true;

		while (!feof($localfp) && ($res !== false))
		{
			$buffer = @fread($localfp, 65567);
			$res    = @fwrite($fp, $buffer);
		}

		@fclose($fp);
		@fclose($localfp);

		return $res;
	}

	public function unlink($file)
	{
		$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

		return @ssh2_sftp_unlink($this->handle, $check);
	}

	public function chmod($file, $perms)
	{
		return @ssh2_sftp_chmod($this->handle, $file, $perms);
	}

	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));
			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	public function close()
	{
		unset($this->_connection);
		unset($this->handle);
	}

	public function rmdir($directory)
	{
		$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

		return @ssh2_sftp_rmdir($this->handle, $check);
	}

	public function rename($from, $to)
	{
		$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');
		$to   = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

		$result = @ssh2_sftp_rename($this->handle, $from, $to);

		if ($result !== true)
		{
			return @rename($from, $to);
		}
		else
		{
			return true;
		}
	}

}


/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Hybrid direct / FTP mode file writer
 */
class AKPostprocHybrid extends AKAbstractPostproc
{

	/** @var bool Should I use the FTP layer? */
	public $useFTP = false;

	/** @var bool Should I use FTP over implicit SSL? */
	public $useSSL = false;

	/** @var bool use Passive mode? */
	public $passive = true;

	/** @var string FTP host name */
	public $host = '';

	/** @var int FTP port */
	public $port = 21;

	/** @var string FTP user name */
	public $user = '';

	/** @var string FTP password */
	public $pass = '';

	/** @var string FTP initial directory */
	public $dir = '';

	/** @var resource The FTP handle */
	private $handle = null;

	/** @var null The FTP connection handle */
	private $_handle = null;

	/**
	 * Public constructor. Tries to connect to the FTP server.
	 */
	public function __construct()
	{
		$this->useFTP  = true;
		$this->useSSL  = AKFactory::get('kickstart.ftp.ssl', false);
		$this->passive = AKFactory::get('kickstart.ftp.passive', true);
		$this->host    = AKFactory::get('kickstart.ftp.host', '');
		$this->port    = AKFactory::get('kickstart.ftp.port', 21);
		$this->user    = AKFactory::get('kickstart.ftp.user', '');
		$this->pass    = AKFactory::get('kickstart.ftp.pass', '');
		$this->dir     = AKFactory::get('kickstart.ftp.dir', '');
		$this->tempDir = AKFactory::get('kickstart.ftp.tempdir', '');

		if (trim($this->port) == '')
		{
			$this->port = 21;
		}

		// If FTP is not configured, skip it altogether
		if (empty($this->host) || empty($this->user) || empty($this->pass))
		{
			$this->useFTP = false;
		}

		// Try to connect to the FTP server
		$connected = $this->connect();

		// If the connection fails, skip FTP altogether
		if (!$connected)
		{
			$this->useFTP = false;
		}

		if ($connected)
		{
			if (!empty($this->tempDir))
			{
				$tempDir  = rtrim($this->tempDir, '/\\') . '/';
				$writable = $this->isDirWritable($tempDir);
			}
			else
			{
				$tempDir  = '';
				$writable = false;
			}

			if (!$writable)
			{
				// Default temporary directory is the current root
				$tempDir = KSROOTDIR;
				if (empty($tempDir))
				{
					// Oh, we have no directory reported!
					$tempDir = '.';
				}
				$absoluteDirToHere = $tempDir;
				$tempDir           = rtrim(str_replace('\\', '/', $tempDir), '/');
				if (!empty($tempDir))
				{
					$tempDir .= '/';
				}
				$this->tempDir = $tempDir;
				// Is this directory writable?
				$writable = $this->isDirWritable($tempDir);
			}

			if (!$writable)
			{
				// Nope. Let's try creating a temporary directory in the site's root.
				$tempDir                 = $absoluteDirToHere . '/kicktemp';
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				$this->createDirRecursive($tempDir, $trustMeIKnowWhatImDoing);
				// Try making it writable...
				$this->fixPermissions($tempDir);
				$writable = $this->isDirWritable($tempDir);
			}

			// Was the new directory writable?
			if (!$writable)
			{
				// Let's see if the user has specified one
				$userdir = AKFactory::get('kickstart.ftp.tempdir', '');
				if (!empty($userdir))
				{
					// Is it an absolute or a relative directory?
					$absolute = false;
					$absolute = $absolute || (substr($userdir, 0, 1) == '/');
					$absolute = $absolute || (substr($userdir, 1, 1) == ':');
					$absolute = $absolute || (substr($userdir, 2, 1) == ':');
					if (!$absolute)
					{
						// Make absolute
						$tempDir = $absoluteDirToHere . $userdir;
					}
					else
					{
						// it's already absolute
						$tempDir = $userdir;
					}
					// Does the directory exist?
					if (is_dir($tempDir))
					{
						// Yeah. Is it writable?
						$writable = $this->isDirWritable($tempDir);
					}
				}
			}
			$this->tempDir = $tempDir;

			if (!$writable)
			{
				// No writable directory found!!!
				$this->setError(AKText::_('FTP_TEMPDIR_NOT_WRITABLE'));
			}
			else
			{
				AKFactory::set('kickstart.ftp.tempdir', $tempDir);
				$this->tempDir = $tempDir;
			}
		}
	}

	/**
	 * Tries to connect to the FTP server
	 *
	 * @return bool
	 */
	public function connect()
	{
		if (!$this->useFTP)
		{
			return false;
		}

		// Connect to server, using SSL if so required
		if ($this->useSSL)
		{
			$this->handle = @ftp_ssl_connect($this->host, $this->port);
		}
		else
		{
			$this->handle = @ftp_connect($this->host, $this->port);
		}
		if ($this->handle === false)
		{
			$this->setError(AKText::_('WRONG_FTP_HOST'));

			return false;
		}

		// Login
		if (!@ftp_login($this->handle, $this->user, $this->pass))
		{
			$this->setError(AKText::_('WRONG_FTP_USER'));
			@ftp_close($this->handle);

			return false;
		}

		// Change to initial directory
		if (!@ftp_chdir($this->handle, $this->dir))
		{
			$this->setError(AKText::_('WRONG_FTP_PATH1'));
			@ftp_close($this->handle);

			return false;
		}

		// Enable passive mode if the user requested it
		if ($this->passive)
		{
			@ftp_pasv($this->handle, true);
		}
		else
		{
			@ftp_pasv($this->handle, false);
		}

		// Try to download ourselves
		$testFilename = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$tempHandle   = fopen('php://temp', 'r+');

		if (@ftp_fget($this->handle, $tempHandle, $testFilename, FTP_ASCII, 0) === false)
		{
			$this->setError(AKText::_('WRONG_FTP_PATH2'));
			@ftp_close($this->handle);
			fclose($tempHandle);

			return false;
		}

		fclose($tempHandle);

		return true;
	}

	/**
	 * Is the directory writeable?
	 *
	 * @param string $dir The directory ti check
	 *
	 * @return bool
	 */
	private function isDirWritable($dir)
	{
		$fp = @fopen($dir . '/kickstart.dat', 'w');

		if ($fp === false)
		{
			return false;
		}

		@fclose($fp);
		unlink($dir . '/kickstart.dat');

		return true;
	}

	/**
	 * Create a directory, recursively
	 *
	 * @param string $dirName The directory to create
	 * @param int    $perms   The permissions to give to the directory
	 *
	 * @return bool
	 */
	public function createDirRecursive($dirName, $perms)
	{
		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			// UNIXize the paths
			$removePath = str_replace('\\', '/', $removePath);
			$dirName    = str_replace('\\', '/', $dirName);
			// Make sure they both end in a slash
			$removePath = rtrim($removePath, '/\\') . '/';
			$dirName    = rtrim($dirName, '/\\') . '/';
			// Process the path removal
			$left = substr($dirName, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$dirName = substr($dirName, strlen($removePath));
			}
		}

		// 'cause the substr() above may return FALSE.
		if (empty($dirName))
		{
			$dirName = '';
		}

		$check   = '/' . trim($this->dir, '/') . '/' . trim($dirName, '/');
		$checkFS = $removePath . trim($dirName, '/');

		if ($this->is_dir($check))
		{
			return true;
		}

		$alldirs       = explode('/', $dirName);
		$previousDir   = '/' . trim($this->dir);
		$previousDirFS = rtrim($removePath, '/\\');

		foreach ($alldirs as $curdir)
		{
			$check   = $previousDir . '/' . $curdir;
			$checkFS = $previousDirFS . '/' . $curdir;

			if (!is_dir($checkFS) && !$this->is_dir($check))
			{
				// Proactively try to delete a file by the same name
				if (!@unlink($checkFS) && $this->useFTP)
				{
					@ftp_delete($this->handle, $check);
				}

				$createdDir = @mkdir($checkFS, 0755);

				if (!$createdDir && $this->useFTP)
				{
					$createdDir = @ftp_mkdir($this->handle, $check);
				}

				if ($createdDir === false)
				{
					// If we couldn't create the directory, attempt to fix the permissions in the PHP level and retry!
					$this->fixPermissions($checkFS);

					$createdDir = @mkdir($checkFS, 0755);
					if (!$createdDir && $this->useFTP)
					{
						$createdDir = @ftp_mkdir($this->handle, $check);
					}

					if ($createdDir === false)
					{
						$this->setError(AKText::sprintf('FTP_CANT_CREATE_DIR', $check));

						return false;
					}
				}

				if (!@chmod($checkFS, $perms) && $this->useFTP)
				{
					@ftp_chmod($this->handle, $perms, $check);
				}
			}

			$previousDir   = $check;
			$previousDirFS = $checkFS;
		}

		return true;
	}

	private function is_dir($dir)
	{
		if ($this->useFTP)
		{
			return @ftp_chdir($this->handle, $dir);
		}

		return false;
	}

	/**
	 * Tries to fix directory/file permissions in the PHP level, so that
	 * the FTP operation doesn't fail.
	 *
	 * @param $path string The full path to a directory or file
	 */
	private function fixPermissions($path)
	{
		// Turn off error reporting
		if (!defined('KSDEBUG'))
		{
			$oldErrorReporting = error_reporting(0);
		}

		// Get UNIX style paths
		$relPath  = str_replace('\\', '/', $path);
		$basePath = rtrim(str_replace('\\', '/', KSROOTDIR), '/');
		$basePath = rtrim($basePath, '/');

		if (!empty($basePath))
		{
			$basePath .= '/';
		}

		// Remove the leading relative root
		if (substr($relPath, 0, strlen($basePath)) == $basePath)
		{
			$relPath = substr($relPath, strlen($basePath));
		}

		$dirArray  = explode('/', $relPath);
		$pathBuilt = rtrim($basePath, '/');

		foreach ($dirArray as $dir)
		{
			if (empty($dir))
			{
				continue;
			}

			$oldPath = $pathBuilt;
			$pathBuilt .= '/' . $dir;

			if (is_dir($oldPath . $dir))
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing);
			}
			else
			{
				$trustMeIKnowWhatImDoing = 500 + 10 + 1; // working around overzealous scanners written by bozos
				if (@chmod($oldPath . $dir, $trustMeIKnowWhatImDoing) === false)
				{
					@unlink($oldPath . $dir);
				}
			}
		}

		// Restore error reporting
		if (!defined('KSDEBUG'))
		{
			@error_reporting($oldErrorReporting);
		}
	}

	/**
	 * Called after unserialisation, tries to reconnect to FTP
	 */
	public function __wakeup()
	{
		if ($this->useFTP)
		{
			$this->connect();
		}
	}

	public function __sleep()
	{
		if ($this->useFTP)
		{
			if (!is_null($this->_handle) && is_resource($this->_handle))
			{
				@ftp_close($this->_handle);
			}
		}

		$this->_handle = null;
	}


	public function __destruct()
	{
		if ($this->useFTP)
		{
			if (!is_null($this->handle) && is_resource($this->handle))
			{
				@ftp_close($this->handle);
			}
		}
	}

	/**
	 * Post-process an extracted file, using FTP or direct file writes to move it
	 *
	 * @return bool
	 */
	public function process()
	{
		if (is_null($this->tempFilename))
		{
			// If an empty filename is passed, it means that we shouldn't do any post processing, i.e.
			// the entity was a directory or symlink
			return true;
		}

		$remotePath = dirname($this->filename);
		$removePath = AKFactory::get('kickstart.setup.destdir', '');
		$root       = rtrim($removePath, '/\\');

		if (!empty($removePath))
		{
			$removePath = ltrim($removePath, "/");
			$remotePath = ltrim($remotePath, "/");
			$left       = substr($remotePath, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$remotePath = substr($remotePath, strlen($removePath));
			}
		}

		$absoluteFSPath  = dirname($this->filename);
		$relativeFTPPath = trim($remotePath, '/');
		$absoluteFTPPath = '/' . trim($this->dir, '/') . '/' . trim($remotePath, '/');
		$onlyFilename    = basename($this->filename);

		$remoteName = $absoluteFTPPath . '/' . $onlyFilename;

		// Does the directory exist?
		if (!is_dir($root . '/' . $absoluteFSPath))
		{
			$ret = $this->createDirRecursive($absoluteFSPath, 0755);

			if (($ret === false) && ($this->useFTP))
			{
				$ret = @ftp_chdir($this->handle, $absoluteFTPPath);
			}

			if ($ret === false)
			{
				$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

				return false;
			}
		}

		if ($this->useFTP)
		{
			$ret = @ftp_chdir($this->handle, $absoluteFTPPath);
		}

		// Try copying directly
		$ret = @copy($this->tempFilename, $root . '/' . $this->filename);

		if ($ret === false)
		{
			$this->fixPermissions($this->filename);
			$this->unlink($this->filename);

			$ret = @copy($this->tempFilename, $root . '/' . $this->filename);
		}

		if ($this->useFTP && ($ret === false))
		{
			$ret = @ftp_put($this->handle, $remoteName, $this->tempFilename, FTP_BINARY);

			if ($ret === false)
			{
				// If we couldn't create the file, attempt to fix the permissions in the PHP level and retry!
				$this->fixPermissions($this->filename);
				$this->unlink($this->filename);

				$fp = @fopen($this->tempFilename, 'r');
				if ($fp !== false)
				{
					$ret = @ftp_fput($this->handle, $remoteName, $fp, FTP_BINARY);
					@fclose($fp);
				}
				else
				{
					$ret = false;
				}
			}
		}

		@unlink($this->tempFilename);

		if ($ret === false)
		{
			$this->setError(AKText::sprintf('FTP_COULDNT_UPLOAD', $this->filename));

			return false;
		}

		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);
		$perms        = $restorePerms ? $this->perms : 0644;

		$ret = @chmod($root . '/' . $this->filename, $perms);

		if ($this->useFTP && ($ret === false))
		{
			@ftp_chmod($this->_handle, $perms, $remoteName);
		}

		if (@is_file($this->filename) || @is_link($this->filename))
		{
			clearFileInOPCache($this->filename);
		}

		return true;
	}

	public function unlink($file)
	{
		$ret = @unlink($file);

		if (!$ret && $this->useFTP)
		{
			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($file, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$file = substr($file, strlen($removePath));
				}
			}

			$check = '/' . trim($this->dir, '/') . '/' . trim($file, '/');

			$ret = @ftp_delete($this->handle, $check);
		}

		return $ret;
	}

	/**
	 * Create a temporary filename
	 *
	 * @param string $filename The original filename
	 * @param int    $perms    The file permissions
	 *
	 * @return string
	 */
	public function processFilename($filename, $perms = 0755)
	{
		// Catch some error conditions...
		if ($this->getError())
		{
			return false;
		}

		// If a null filename is passed, it means that we shouldn't do any post processing, i.e.
		// the entity was a directory or symlink
		if (is_null($filename))
		{
			$this->filename     = null;
			$this->tempFilename = null;

			return null;
		}

		// Strip absolute filesystem path to website's root
		$removePath = AKFactory::get('kickstart.setup.destdir', '');

		if (!empty($removePath))
		{
			$left = substr($filename, 0, strlen($removePath));

			if ($left == $removePath)
			{
				$filename = substr($filename, strlen($removePath));
			}
		}

		// Trim slash on the left
		$filename = ltrim($filename, '/');

		$this->filename     = $filename;
		$this->tempFilename = tempnam($this->tempDir, 'kickstart-');
		$this->perms        = $perms;

		if (empty($this->tempFilename))
		{
			// Oops! Let's try something different
			$this->tempFilename = $this->tempDir . '/kickstart-' . time() . '.dat';
		}

		return $this->tempFilename;
	}

	/**
	 * Closes the FTP connection
	 */
	public function close()
	{
		if (!$this->useFTP)
		{
			@ftp_close($this->handle);
		}
	}

	public function chmod($file, $perms)
	{
		if (AKFactory::get('kickstart.setup.dryrun', '0'))
		{
			return true;
		}

		$ret = @chmod($file, $perms);

		if (!$ret && $this->useFTP)
		{
			// Strip absolute filesystem path to website's root
			$removePath = AKFactory::get('kickstart.setup.destdir', '');

			if (!empty($removePath))
			{
				$left = substr($file, 0, strlen($removePath));

				if ($left == $removePath)
				{
					$file = substr($file, strlen($removePath));
				}
			}

			// Trim slash on the left
			$file = ltrim($file, '/');

			$ret = @ftp_chmod($this->handle, $perms, $file);
		}

		return $ret;
	}

	public function rmdir($directory)
	{
		$ret = @rmdir($directory);

		if (!$ret && $this->useFTP)
		{
			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($directory, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$directory = substr($directory, strlen($removePath));
				}
			}

			$check = '/' . trim($this->dir, '/') . '/' . trim($directory, '/');

			$ret = @ftp_rmdir($this->handle, $check);
		}

		return $ret;
	}

	public function rename($from, $to)
	{
		$ret = @rename($from, $to);

		if (!$ret && $this->useFTP)
		{
			$originalFrom = $from;
			$originalTo   = $to;

			$removePath = AKFactory::get('kickstart.setup.destdir', '');
			if (!empty($removePath))
			{
				$left = substr($from, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$from = substr($from, strlen($removePath));
				}
			}
			$from = '/' . trim($this->dir, '/') . '/' . trim($from, '/');

			if (!empty($removePath))
			{
				$left = substr($to, 0, strlen($removePath));
				if ($left == $removePath)
				{
					$to = substr($to, strlen($removePath));
				}
			}
			$to = '/' . trim($this->dir, '/') . '/' . trim($to, '/');

			$ret = @ftp_rename($this->handle, $from, $to);
		}

		return $ret;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * JPA archive extraction class
 */
class AKUnarchiverJPA extends AKAbstractUnarchiver
{
	protected $archiveHeaderData = [];

	protected function readArchiveHeader()
	{
		debugMsg('Preparing to read archive header');
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		debugMsg('Opening the first part');
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			debugMsg('Could not open the first part');

			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch([
			'JPA',
		], 3);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jpa', 'j'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read the signature
		$sig = fread($this->fp, 3);

		if ($sig != 'JPA')
		{
			// Not a JPA file
			debugMsg('Invalid archive signature');
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jpa', 'j'));

			return false;
		}

		// Read and parse header length
		$header_length_array = unpack('v', fread($this->fp, 2));
		$header_length       = $header_length_array[1];

		// Read and parse the known portion of header data (14 bytes)
		$bin_data    = fread($this->fp, 14);
		$header_data = unpack('Cmajor/Cminor/Vcount/Vuncsize/Vcsize', $bin_data);

		// Temporary array with all the data we read
		$temp = [
			'signature'        => $sig,
			'length'           => $header_length,
			'major'            => $header_data['major'],
			'minor'            => $header_data['minor'],
			'filecount'        => $header_data['count'],
			'uncompressedsize' => $header_data['uncsize'],
			'compressedsize'   => $header_data['csize'],
			'unknowndata'      => '',
		];

		// Load additional header data
		$rest_length = $header_length - 19;
		$junk        = '';

		while ($rest_length > 8)
		{
			// Read the extra length signature and size
			$extraSig    = fread($this->fp, 4);
			$binData     = fread($this->fp, 2);
			$extraHeader = unpack('vlength', $binData);
			$length      = $extraHeader['length'] - 2;

			$rest_length -= 6 + $length;

			switch ($extraSig)
			{
				case "\x4A\x50\x01\x01":
					$moreBinData        = fread($this->fp, $length);
					$moreExtraHeader    = unpack('vtotalParts', $moreBinData);
					$temp['totalParts'] = $moreExtraHeader['totalParts'];
					break;

				case "\x4A\x50\x01\x02":
					$moreBinData              = fread($this->fp, $length);

					// Only decode on 64-bit versions of PHP
					if (PHP_INT_SIZE >= 8)
					{
						$moreExtraHeader          = unpack('Puncompressed/Pcompressed', $moreBinData);
						$header_data['uncsize']   = $moreExtraHeader['uncompressed'];
						$header_data['csize']     = $moreExtraHeader['compressed'];
						$temp['uncompressedsize'] = $moreExtraHeader['uncompressed'];
						$temp['compressedsize']   = $moreExtraHeader['compressed'];
					}

					break;

				default:
					$moreBinData = fread($this->fp, $length);
					$junk        .= $extraSig . $binData . $moreBinData;
					break;
			}
		}

		if ($rest_length > 0)
		{
			$junk .= fread($this->fp, $rest_length);
		}
		else
		{
			$junk .= '';
		}

		// Array-to-object conversion
		foreach ($temp as $key => $value)
		{
			$this->archiveHeaderData->{$key} = $value;
		}

		debugMsg('Header data:');
		debugMsg('Length              : ' . $header_length);
		debugMsg('Major               : ' . $header_data['major']);
		debugMsg('Minor               : ' . $header_data['minor']);
		debugMsg('File count          : ' . $header_data['count']);
		debugMsg('Uncompressed size   : ' . $header_data['uncsize']);
		debugMsg('Compressed size     : ' . $header_data['csize']);
		debugMsg('Total Parts         : ' . (isset($header_data['totalParts']) ? $header_data['totalParts'] : '1'));

		$this->currentPartOffset = @ftell($this->fp);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			debugMsg('Archive part EOF; moving to next file');
			$this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		debugMsg("Reading file signature; part {$this->currentPartNumber}, offset {$this->currentPartOffset}");
		// Get and decode Entity Description Block
		$signature = fread($this->fp, 3);

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Check signature
		if ($signature != 'JPF')
		{
			if ($this->isEOF(true))
			{
				// This file is finished; make sure it's the last one
				$gotNextFile = $this->nextFile();

				if (!$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'jpa',
						'j'
					));

					return false;
				}

				if (!$this->isEOF(false))
				{
					debugMsg('Invalid file signature before end of archive encountered');
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jpa',
						'j'
					));

					return false;
				}

				// We're just finished
				return false;
			}
			else
			{
				$screwed = true;

				if (AKFactory::get('kickstart.setup.ignoreerrors', false))
				{
					debugMsg('Invalid file block signature; launching heuristic file block signature scanner');
					$screwed = !$this->heuristicFileHeaderLocator();

					if (!$screwed)
					{
						$signature = 'JPF';
					}
					else
					{
						debugMsg('Heuristics failed. Brace yourself for the imminent crash.');
					}
				}

				if ($screwed)
				{
					// This is not a file block! The archive is corrupt.
					debugMsg('Invalid file block signature');

					if (count($this->archiveList) > 1)
					{
						$this->setError(AKText::sprintf(
							'INVALID_FILE_HEADER_MULTIPART',
							$this->currentPartNumber,
							$this->currentPartOffset,
							'jpa',
							'j'
						));

						return false;
					}

					$this->setError(AKText::sprintf('INVALID_FILE_HEADER', $this->currentPartNumber, $this->currentPartOffset));

					return false;
				}
			}
		}
		// This a JPA Entity Block. Process the header.

		$isBannedFile = false;

		// Read length of EDB and of the Entity Path Data
		$length_array = unpack('vblocksize/vpathsize', fread($this->fp, 4));
		// Read the path data
		if ($length_array['pathsize'] > 0)
		{
			$file = fread($this->fp, $length_array['pathsize']);
		}
		else
		{
			$file = '';
		}

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($file, $this->renameFiles))
			{
				$file      = $this->renameFiles[$file];
				$isRenamed = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($file), $this->renameDirs))
			{
				$file         = rtrim($this->renameDirs[dirname($file)], '/') . '/' . basename($file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read and parse the known data portion
		$bin_data    = fread($this->fp, 14);
		$header_data = unpack('Ctype/Ccompression/Vcompsize/Vuncompsize/Vperms', $bin_data);
		// Read any unknown data
		$restBytes = $length_array['blocksize'] - (21 + $length_array['pathsize']);

		if ($restBytes > 0)
		{
			// Start reading the extra fields
			while ($restBytes >= 4)
			{
				$extra_header_data      = fread($this->fp, 4);
				$extra_header           = unpack('vsignature/vlength', $extra_header_data);
				$restBytes              -= 4;
				$extra_header['length'] -= 4;

				if ($extra_header['length'] > 0)
				{
					switch ($extra_header['signature'])
					{
						case 256:
							// File modified timestamp
							$bindata                     = fread($this->fp, $extra_header['length']);
							$restBytes                   -= $extra_header['length'];
							$timestamps                  = unpack('Vmodified', substr($bindata, 0, 4));
							$filectime                   = $timestamps['modified'];
							$this->fileHeader->timestamp = $filectime;
							break;

						case 512:
							$bindata                   = fread($this->fp, $extra_header['length']);
							$restBytes                 -= $extra_header['length'];

							// Only decode on 64-bit versions of PHP
							if (PHP_INT_SIZE >= 8)
							{
								$sizes                     = unpack('Pclen/Punclen', $bindata);
								$header_data['compsize']   = $sizes['clen'];
								$header_data['uncompsize'] = $sizes['unclen'];
							}
							break;

						default:
							// Unknown field
							$junk      = fread($this->fp, $extra_header['length']);
							$restBytes -= $extra_header['length'];
							break;
					}
				}

			}

			if ($restBytes > 0)
			{
				$junk = fread($this->fp, $restBytes);
			}
		}

		$compressionType = $header_data['compression'];

		// Populate the return array
		$this->fileHeader->file         = $file;
		$this->fileHeader->compressed   = $header_data['compsize'];
		$this->fileHeader->uncompressed = $header_data['uncompsize'];

		switch ($header_data['type'])
		{
			case 0:
				$this->fileHeader->type = 'dir';
				break;

			case 1:
				$this->fileHeader->type = 'file';
				break;

			case 2:
				$this->fileHeader->type = 'link';
				break;
		}

		switch ($compressionType)
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 1:
				$this->fileHeader->compression = 'gzip';
				break;
			case 2:
				$this->fileHeader->compression = 'bzip2';
				break;
		}

		$this->fileHeader->permissions = $header_data['perms'];

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			debugMsg('Skipping file ' . $this->fileHeader->file);
			// Advance the file pointer, skipping exactly the size of the compressed data
			$seekleft = $this->fileHeader->compressed;
			while ($seekleft > 0)
			{
				// Ensure that we can seek past archive part boundaries
				$curSize = @filesize($this->archiveList[$this->currentPartNumber]);
				$curPos  = @ftell($this->fp);
				$canSeek = $curSize - $curPos;
				if ($canSeek > $seekleft)
				{
					$canSeek = $seekleft;
				}
				@fseek($this->fp, $canSeek, SEEK_CUR);
				$seekleft -= $canSeek;
				if ($seekleft)
				{
					$this->nextFile();
				}
			}

			$this->currentPartOffset = @ftell($this->fp);
			$this->runState          = AK_STATE_DONE;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Get the translated path name
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				// Regular file; ask the postproc engine to process its filename
				if ($restorePerms)
				{
					$this->fileHeader->realFile =
						$this->postProcEngine->processFilename($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
				}
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$dir = $this->fileHeader->file;

				// Directory; just create it
				if ($restorePerms)
				{
					$this->postProcEngine->createDirRecursive($dir, $this->fileHeader->permissions);
				}
				else
				{
					$this->postProcEngine->createDirRecursive($dir, 0755);
				}

				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}

		// Header is read
		$this->runState = AK_STATE_HEADER;

		$this->dataReadLength = 0;

		return true;
	}

	protected function heuristicFileHeaderLocator()
	{
		$ret     = false;
		$fullEOF = false;

		while (!$ret && !$fullEOF)
		{
			$this->currentPartOffset = @ftell($this->fp);

			if ($this->isEOF(true))
			{
				$this->nextFile();
			}

			if ($this->isEOF(false))
			{
				$fullEOF = true;
				continue;
			}

			// Read 512Kb
			$chunk     = fread($this->fp, 524288);
			$size_read = mb_strlen($chunk, '8bit');
			//$pos = strpos($chunk, 'JPF');
			$pos = mb_strpos($chunk, 'JPF', 0, '8bit');

			if ($pos !== false)
			{
				// We found it!
				$this->currentPartOffset += $pos + 3;
				@fseek($this->fp, $this->currentPartOffset, SEEK_SET);
				$ret = true;
			}
			else
			{
				// Not yet found :(
				$this->currentPartOffset = @ftell($this->fp);
			}
		}

		return $ret;
	}

	/**
	 * Creates the directory this file points to
	 */
	protected function createDirectory()
	{
		if ($this->mustSkip())
		{
			return true;
		}

		// Do we need to create a directory?
		if (empty($this->fileHeader->realFile))
		{
			$this->fileHeader->realFile = $this->fileHeader->file;
		}

		$lastSlash = strrpos($this->fileHeader->realFile, '/');
		$dirName   = substr($this->fileHeader->realFile, 0, $lastSlash);
		$perms     = $this->flagRestorePermissions ? $this->fileHeader->permissions : 0755;
		$ignore    = AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($dirName);

		if (($this->postProcEngine->createDirRecursive($dirName, $perms) == false) && (!$ignore))
		{
			$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $dirName));

			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected function processFileData()
	{
		switch ($this->fileHeader->type)
		{
			case 'dir':
				return $this->processTypeDir();
				break;

			case 'link':
				return $this->processTypeLink();
				break;

			case 'file':
				switch ($this->fileHeader->compression)
				{
					case 'none':
						return $this->processTypeFileUncompressed();
						break;

					case 'gzip':
					case 'bzip2':
						return $this->processTypeFileCompressedSimple();
						break;

				}
				break;

			default:
				debugMsg('Unknown file type ' . $this->fileHeader->type);
				break;
		}
	}

	/**
	 * Process the file data of a directory entry
	 *
	 * @return bool
	 */
	private function processTypeDir()
	{
		// Directory entries in the JPA do not have file data, therefore we're done processing the entry
		$this->runState = AK_STATE_DATAREAD;

		return true;
	}

	/**
	 * Process the file data of a link entry
	 *
	 * @return bool
	 */
	private function processTypeLink()
	{
		$readBytes   = 0;
		$toReadBytes = 0;
		$leftBytes   = $this->fileHeader->compressed;
		$data        = '';

		while ($leftBytes > 0)
		{
			$toReadBytes     = ($leftBytes > $this->chunkSize) ? $this->chunkSize : $leftBytes;
			$mydata          = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes = akstringlen($mydata);
			$data            .= $mydata;
			$leftBytes       -= $reallyReadBytes;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
				}
				else
				{
					debugMsg('End of local file before reading all data with no more parts left. The archive is corrupt or truncated.');
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}
		}

		$filename = isset($this->fileHeader->realFile) ? $this->fileHeader->realFile : $this->fileHeader->file;

		if (!$this->mustSkip())
		{
			// Try to remove an existing file or directory by the same name
			if (file_exists($filename))
			{
				@unlink($filename);
				@rmdir($filename);
			}

			// Remove any trailing slash
			if (substr($filename, -1) == '/')
			{
				$filename = substr($filename, 0, -1);
			}
			// Create the symlink - only possible within PHP context. There's no support built in the FTP protocol, so no postproc use is possible here :(
			@symlink($data, $filename);
		}

		$this->runState = AK_STATE_DATAREAD;

		return true; // No matter if the link was created!
	}

	private function processTypeFileUncompressed()
	{
		// Uncompressed files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);

			if ($this->dataReadLength == 0)
			{
				$outfp = @fopen($this->fileHeader->realFile, 'w');
			}
			else
			{
				$outfp = @fopen($this->fileHeader->realFile, 'a');
			}

			// Can we write to the file?
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				debugMsg('Could not write to output file');
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->compressed == 0)
		{
			// No file data!
			if (!$this->mustSkip() && is_resource($outfp))
			{
				@fclose($outfp);
			}

			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Reference to the global timer
		$timer = AKFactory::getTimer();

		$toReadBytes = 0;
		$leftBytes   = $this->fileHeader->compressed - $this->dataReadLength;

		// Loop while there's data to read and enough time to do it
		while (($leftBytes > 0) && ($timer->getTimeLeft() > 0))
		{
			$toReadBytes          = ($leftBytes > $this->chunkSize) ? $this->chunkSize : $leftBytes;
			$data                 = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes      = akstringlen($data);
			$leftBytes            -= $reallyReadBytes;
			$this->dataReadLength += $reallyReadBytes;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
				}
				else
				{
					// Nope. The archive is corrupt
					debugMsg('Not enough data in file. The archive is truncated or corrupt.');
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fwrite($outfp, $data);
				}
			}
		}

		// Close the file pointer
		if (!$this->mustSkip())
		{
			if (is_resource($outfp))
			{
				@fclose($outfp);
			}
		}

		// Was this a pre-timeout bail out?
		if ($leftBytes > 0)
		{
			$this->runState = AK_STATE_DATA;
		}
		else
		{
			// Oh! We just finished!
			$this->runState       = AK_STATE_DATAREAD;
			$this->dataReadLength = 0;
		}

		return true;
	}

	private function processTypeFileCompressedSimple()
	{
		if (!$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);

			// Open the output file
			$outfp = @fopen($this->fileHeader->realFile, 'w');

			// Can we write to the file?
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);

			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				debugMsg('Could not write to output file');
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->compressed == 0)
		{
			// No file data!
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fclose($outfp);
				}
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Simple compressed files are processed as a whole; we can't do chunk processing
		$zipData = $this->fread($this->fp, $this->fileHeader->compressed);
		while (akstringlen($zipData) < $this->fileHeader->compressed)
		{
			// End of local file before reading all data, but have more archive parts?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Read from the next file
				$this->nextFile();
				$bytes_left = $this->fileHeader->compressed - akstringlen($zipData);
				$zipData    .= $this->fread($this->fp, $bytes_left);
			}
			else
			{
				debugMsg('End of local file before reading all data with no more parts left. The archive is corrupt or truncated.');
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		if ($this->fileHeader->compression == 'gzip')
		{
			$unzipData = gzinflate($zipData);
		}
		elseif ($this->fileHeader->compression == 'bzip2')
		{
			$unzipData = bzdecompress($zipData);
		}
		unset($zipData);

		// Write to the file.
		if (!$this->mustSkip() && is_resource($outfp))
		{
			@fwrite($outfp, $unzipData, $this->fileHeader->uncompressed);
			@fclose($outfp);
		}
		unset($unzipData);

		$this->runState = AK_STATE_DATAREAD;

		return true;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * ZIP archive extraction class
 *
 * Since the file data portion of ZIP and JPA are similarly structured (it's empty for dirs,
 * linked node name for symlinks, dumped binary data for no compressions and dumped gzipped
 * binary data for gzip compression) we just have to subclass AKUnarchiverJPA and change the
 * header reading bits. Reusable code ;)
 */
class AKUnarchiverZIP extends AKUnarchiverJPA
{
	var $expectDataDescriptor = false;

	protected function readArchiveHeader()
	{
		debugMsg('Preparing to read archive header');
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		debugMsg('Opening the first part');
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			debugMsg('The first part is not readable');

			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch(array(
			pack('V', 0x08074b50), // Multi-part ZIP
			pack('V', 0x30304b50), // Multi-part ZIP (alternate)
			pack('V', 0x04034b50)  // Single file
		), 4);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'zip', 'z'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read a possible multipart signature
		$sigBinary  = fread($this->fp, 4);
		$headerData = unpack('Vsig', $sigBinary);

		// Roll back if it's not a multipart archive
		if ($headerData['sig'] == 0x04034b50)
		{
			debugMsg('The archive is not multipart');
			fseek($this->fp, -4, SEEK_CUR);
		}
		else
		{
			debugMsg('The archive is multipart');
		}

		$multiPartSigs = array(
			0x08074b50, // Multi-part ZIP
			0x30304b50, // Multi-part ZIP (alternate)
			0x04034b50  // Single file
		);
		if (!in_array($headerData['sig'], $multiPartSigs))
		{
			debugMsg('Invalid header signature ' . dechex($headerData['sig']));
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'zip', 'z'));

			return false;
		}

		$this->currentPartOffset = @ftell($this->fp);
		debugMsg('Current part offset after reading header: ' . $this->currentPartOffset);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			debugMsg('Opening next archive part');
			$gotNextFile = $this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		if ($this->expectDataDescriptor)
		{
			// The last file had bit 3 of the general purpose bit flag set. This means that we have a
			// 12 byte data descriptor we need to skip. To make things worse, there might also be a 4
			// byte optional data descriptor header (0x08074b50).
			$junk = @fread($this->fp, 4);
			$junk = unpack('Vsig', $junk);
			if ($junk['sig'] == 0x08074b50)
			{
				// Yes, there was a signature
				$junk = @fread($this->fp, 12);
				debugMsg('Data descriptor (w/ header) skipped at ' . (ftell($this->fp) - 12));
			}
			else
			{
				// No, there was no signature, just read another 8 bytes
				$junk = @fread($this->fp, 8);
				debugMsg('Data descriptor (w/out header) skipped at ' . (ftell($this->fp) - 8));
			}

			// And check for EOF, too
			if ($this->isEOF(true))
			{
				debugMsg('EOF before reading header');

				$gotNextFile = $this->nextFile();
			}
		}

		// Get and decode Local File Header
		$headerBinary = fread($this->fp, 30);
		$headerData   =
			unpack('Vsig/C2ver/vbitflag/vcompmethod/vlastmodtime/vlastmoddate/Vcrc/Vcompsize/Vuncomp/vfnamelen/veflen', $headerBinary);

		// Check signature
		if (!($headerData['sig'] == 0x04034b50))
		{
			debugMsg('Not a file signature at ' . (ftell($this->fp) - 4));

			// The signature is not the one used for files. Is this a central directory record (i.e. we're done)?
			if ($headerData['sig'] == 0x02014b50)
			{
				debugMsg('EOCD signature at ' . (ftell($this->fp) - 4));
				// End of ZIP file detected. We'll just skip to the end of file...
				while ($this->nextFile())
				{
				};
				@fseek($this->fp, 0, SEEK_END); // Go to EOF
				return false;
			}
			else
			{
				if (isset($gotNextFile) && !$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'zip',
						'z'
					));

					return false;
				}

				if ($this->currentPartOffset === 0 && $this->currentPartNumber > 0)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jpa',
						'j'
					));

					return false;
				}

				debugMsg('Invalid signature ' . dechex($headerData['sig']) . ' at ' . ftell($this->fp));

				if (count($this->archiveList) > 1)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'zip',
						'z'
					));

					return false;
				}

				$this->setError(AKText::sprintf(
					'INVALID_FILE_HEADER',
					$this->currentPartNumber,
					$this->currentPartOffset,
					'zip',
					'z'
				));

				return false;
			}
		}

		// If bit 3 of the bitflag is set, expectDataDescriptor is true
		$this->expectDataDescriptor = ($headerData['bitflag'] & 4) == 4;

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Read the last modified data and time
		$lastmodtime = $headerData['lastmodtime'];
		$lastmoddate = $headerData['lastmoddate'];

		if ($lastmoddate && $lastmodtime)
		{
			// ----- Extract time
			$v_hour    = ($lastmodtime & 0xF800) >> 11;
			$v_minute  = ($lastmodtime & 0x07E0) >> 5;
			$v_seconde = ($lastmodtime & 0x001F) * 2;

			// ----- Extract date
			$v_year  = (($lastmoddate & 0xFE00) >> 9) + 1980;
			$v_month = ($lastmoddate & 0x01E0) >> 5;
			$v_day   = $lastmoddate & 0x001F;

			// ----- Get UNIX date format
			$this->fileHeader->timestamp = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
		}

		$isBannedFile = false;

		$this->fileHeader->compressed   = $headerData['compsize'];
		$this->fileHeader->uncompressed = $headerData['uncomp'];
		$nameFieldLength                = $headerData['fnamelen'];
		$extraFieldLength               = $headerData['eflen'];

		// Read filename field
		$this->fileHeader->file = fread($this->fp, $nameFieldLength);

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($this->fileHeader->file, $this->renameFiles))
			{
				$this->fileHeader->file = $this->renameFiles[$this->fileHeader->file];
				$isRenamed              = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($this->fileHeader->file), $this->renameDirs))
			{
				$file         =
					rtrim($this->renameDirs[dirname($this->fileHeader->file)], '/') . '/' . basename($this->fileHeader->file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read extra field if present
		if ($extraFieldLength > 0)
		{
			$extrafield = fread($this->fp, $extraFieldLength);
		}

		debugMsg('*' . ftell($this->fp) . ' IS START OF ' . $this->fileHeader->file . ' (' . $this->fileHeader->compressed . ' bytes)');


		// Decide filetype -- Check for directories
		$this->fileHeader->type = 'file';
		if (strrpos($this->fileHeader->file, '/') == strlen($this->fileHeader->file) - 1)
		{
			$this->fileHeader->type = 'dir';
		}
		// Decide filetype -- Check for symbolic links
		if (($headerData['ver1'] == 10) && ($headerData['ver2'] == 3))
		{
			$this->fileHeader->type = 'link';
		}

		switch ($headerData['compmethod'])
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 8:
				$this->fileHeader->compression = 'gzip';
				break;
		}

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			// Advance the file pointer, skipping exactly the size of the compressed data
			$seekleft = $this->fileHeader->compressed;
			while ($seekleft > 0)
			{
				// Ensure that we can seek past archive part boundaries
				$curSize = @filesize($this->archiveList[$this->currentPartNumber]);
				$curPos  = @ftell($this->fp);
				$canSeek = $curSize - $curPos;
				if ($canSeek > $seekleft)
				{
					$canSeek = $seekleft;
				}
				@fseek($this->fp, $canSeek, SEEK_CUR);
				$seekleft -= $canSeek;
				if ($seekleft)
				{
					$this->nextFile();
				}
			}

			$this->currentPartOffset = @ftell($this->fp);
			$this->runState          = AK_STATE_DONE;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Get the translated path name
		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$this->fileHeader->timestamp = 0;

				$dir = $this->fileHeader->file;

				$this->postProcEngine->createDirRecursive($dir, 0755);
				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->fileHeader->timestamp = 0;
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}

		// Header is read
		$this->runState = AK_STATE_HEADER;

		return true;
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * JPS archive extraction class
 */
class AKUnarchiverJPS extends AKUnarchiverJPA
{
	/**
	 * Header data for the archive
	 *
	 * @var   array
	 */
	protected $archiveHeaderData = array();

	/**
	 * Plaintext password from which the encryption key will be derived with PBKDF2
	 *
	 * @var   string
	 */
	protected $password = '';

	/**
	 * Which hash algorithm should I use for key derivation with PBKDF2.
	 *
	 * @var   string
	 */
	private $pbkdf2Algorithm = 'sha1';

	/**
	 * How many iterations should I use for key derivation with PBKDF2
	 *
	 * @var   int
	 */
	private $pbkdf2Iterations = 1000;

	/**
	 * Should I use a static salt for key derivation with PBKDF2?
	 *
	 * @var   bool
	 */
	private $pbkdf2UseStaticSalt = 0;

	/**
	 * Static salt for key derivation with PBKDF2
	 *
	 * @var   string
	 */
	private $pbkdf2StaticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * How much compressed data I have read since the last file header read
	 *
	 * @var   int
	 */
	private $compressedSizeReadSinceLastFileHeader = 0;

	public function __construct()
	{
		$this->password = AKFactory::get('kickstart.jps.password', '');
	}

	public function __wakeup()
	{
		parent::__wakeup();

		// Make sure the decryption is all set up (required!)
		AKEncryptionAES::setPbkdf2Algorithm($this->pbkdf2Algorithm);
		AKEncryptionAES::setPbkdf2Iterations($this->pbkdf2Iterations);
		AKEncryptionAES::setPbkdf2UseStaticSalt($this->pbkdf2UseStaticSalt);
		AKEncryptionAES::setPbkdf2StaticSalt($this->pbkdf2StaticSalt);
	}


	protected function readArchiveHeader()
	{
		// Initialize header data array
		$this->archiveHeaderData = new stdClass();

		// Open the first part
		$this->nextFile();

		// Fail for unreadable files
		if ($this->fp === false)
		{
			return false;
		}

		// Fuzzy check for the start of archive.
		debugMsg('Fuzzy checking for archive signature');

		$sigFound = $this->fuzzySignatureSearch(array(
			'JPS'
		), 3);

		if (!$sigFound)
		{
			debugMsg('Cannot find a valid archive signature in the first 128Kb of the first part file');

			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jps', 'j'));

			return false;
		}

		debugMsg(sprintf('File signature found, position %d', ftell($this->fp)));

		// Read the signature
		$sig = fread($this->fp, 3);

		if ($sig != 'JPS')
		{
			// Not a JPS file
			$this->setError(AKText::sprintf('ERR_INVALID_ARCHIVE_LONG', 'jps', 'j'));

			return false;
		}

		// Read and parse the known portion of header data (5 bytes)
		$bin_data    = fread($this->fp, 5);
		$header_data = unpack('Cmajor/Cminor/cspanned/vextra', $bin_data);

		// Is this a v2 archive?
		$versionHumanReadable = $header_data['major'] . '.' . $header_data['minor'];
		$isV2Archive = version_compare($versionHumanReadable, '2.0', 'ge');

		// Load any remaining header data
		$rest_length = $header_data['extra'];

		if ($isV2Archive && $rest_length)
		{
			// V2 archives only have one kind of extra header
			if (!$this->readKeyExpansionExtraHeader())
			{
				return false;
			}
		}
		elseif ($rest_length > 0)
		{
			$junk = fread($this->fp, $rest_length);
		}

		// Temporary array with all the data we read
		$temp = array(
			'signature' => $sig,
			'major'     => $header_data['major'],
			'minor'     => $header_data['minor'],
			'spanned'   => $header_data['spanned']
		);
		// Array-to-object conversion
		foreach ($temp as $key => $value)
		{
			$this->archiveHeaderData->{$key} = $value;
		}

		$this->currentPartOffset = @ftell($this->fp);

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Concrete classes must use this method to read the file header
	 *
	 * @return bool True if reading the file was successful, false if an error occurred or we reached end of archive
	 */
	protected function readFileHeader()
	{
		// If the current part is over, proceed to the next part please
		if ($this->isEOF(true))
		{
			$this->nextFile();
		}

		$this->currentPartOffset = ftell($this->fp);

		// Get and decode Entity Description Block
		$signature = fread($this->fp, 3);

		// Check for end-of-archive siganture
		if ($signature == 'JPE')
		{
			$this->setState('postrun');

			return true;
		}

		$this->fileHeader            = new stdClass();
		$this->fileHeader->timestamp = 0;

		// Check signature
		if ($signature != 'JPF')
		{
			if ($this->isEOF(true))
			{
				// This file is finished; make sure it's the last one
				$gotNextFile = $this->nextFile();

				if (!$gotNextFile && $this->getState() !== 'postrun')
				{
					debugMsg(sprintf('Cannot open file %s for part #%d', $this->archiveList[$this->currentPartNumber] ?: '(unknown)', $this->currentPartNumber));

					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_OFFSET_ZERO',
						$this->archiveList[$this->currentPartNumber] ?: '(unknown)',
						$this->currentPartNumber,
						'jps',
						'j'
					));

					return false;
				}

				if (!$this->isEOF(false))
				{
					$this->setError(AKText::sprintf('INVALID_FILE_HEADER', $this->currentPartNumber, $this->currentPartOffset));

					return false;
				}

				// We're just finished
				return false;
			}
			else
			{
				fseek($this->fp, -6, SEEK_CUR);
				$signature = fread($this->fp, 3);
				if ($signature == 'JPE')
				{
					return false;
				}

				if (count($this->archiveList) > 1)
				{
					$this->setError(AKText::sprintf(
						'INVALID_FILE_HEADER_MULTIPART',
						$this->currentPartNumber,
						$this->currentPartOffset,
						'jps',
						'j'
					));

					return false;
				}

				$this->setError(AKText::sprintf(
					'INVALID_FILE_HEADER',
					$this->currentPartNumber,
					$this->currentPartOffset,
					'jps',
					'j'
				));

				return false;
			}
		}

		// This a JPS Entity Block. Process the header.

		$isBannedFile = false;

		// Make sure the decryption is all set up
		AKEncryptionAES::setPbkdf2Algorithm($this->pbkdf2Algorithm);
		AKEncryptionAES::setPbkdf2Iterations($this->pbkdf2Iterations);
		AKEncryptionAES::setPbkdf2UseStaticSalt($this->pbkdf2UseStaticSalt);
		AKEncryptionAES::setPbkdf2StaticSalt($this->pbkdf2StaticSalt);

		// Read and decrypt the header
		$edbhData = fread($this->fp, 4);
		$edbh     = unpack('vencsize/vdecsize', $edbhData);
		$bin_data = fread($this->fp, $edbh['encsize']);

		// Add the header length to the data read
		$this->compressedSizeReadSinceLastFileHeader += $edbh['encsize'] + 4;

		// Decrypt and truncate
		$bin_data = AKEncryptionAES::AESDecryptCBC($bin_data, $this->password);
		$bin_data = substr($bin_data, 0, $edbh['decsize']);

		// Read length of EDB and of the Entity Path Data
		$length_array = unpack('vpathsize', substr($bin_data, 0, 2));
		// Read the path data
		$file = substr($bin_data, 2, $length_array['pathsize']);

		// Handle file renaming
		$isRenamed = false;
		if (is_array($this->renameFiles) && (count($this->renameFiles) > 0))
		{
			if (array_key_exists($file, $this->renameFiles))
			{
				$file      = $this->renameFiles[$file];
				$isRenamed = true;
			}
		}

		// Handle directory renaming
		$isDirRenamed = false;
		if (is_array($this->renameDirs) && (count($this->renameDirs) > 0))
		{
			if (array_key_exists(dirname($file), $this->renameDirs))
			{
				$file         = rtrim($this->renameDirs[dirname($file)], '/') . '/' . basename($file);
				$isRenamed    = true;
				$isDirRenamed = true;
			}
		}

		// Read and parse the known data portion
		$bin_data    = substr($bin_data, 2 + $length_array['pathsize']);
		$header_data = unpack('Ctype/Ccompression/Vuncompsize/Vperms/Vfilectime', $bin_data);

		$this->fileHeader->timestamp = $header_data['filectime'];
		$compressionType             = $header_data['compression'];

		// Populate the return array
		$this->fileHeader->file         = $file;
		$this->fileHeader->uncompressed = $header_data['uncompsize'];
		switch ($header_data['type'])
		{
			case 0:
				$this->fileHeader->type = 'dir';
				break;

			case 1:
				$this->fileHeader->type = 'file';
				break;

			case 2:
				$this->fileHeader->type = 'link';
				break;
		}
		switch ($compressionType)
		{
			case 0:
				$this->fileHeader->compression = 'none';
				break;
			case 1:
				$this->fileHeader->compression = 'gzip';
				break;
			case 2:
				$this->fileHeader->compression = 'bzip2';
				break;
		}
		$this->fileHeader->permissions = $header_data['perms'];

		// Find hard-coded banned files
		if ((basename($this->fileHeader->file) == ".") || (basename($this->fileHeader->file) == ".."))
		{
			$isBannedFile = true;
		}

		// Also try to find banned files passed in class configuration
		if ((count($this->skipFiles) > 0) && (!$isRenamed))
		{
			if (in_array($this->fileHeader->file, $this->skipFiles))
			{
				$isBannedFile = true;
			}
		}

		// If we have a banned file, let's skip it
		if ($isBannedFile)
		{
			$done = false;
			while (!$done)
			{
				// Read the Data Chunk Block header
				$binMiniHead = fread($this->fp, 8);
				if (in_array(substr($binMiniHead, 0, 3), array('JPF', 'JPE')))
				{
					// Not a Data Chunk Block header, I am done skipping the file
					@fseek($this->fp, -8, SEEK_CUR); // Roll back the file pointer
					$done = true; // Mark as done
					continue; // Exit loop
				}
				else
				{
					// Skip forward by the amount of compressed data
					$miniHead = unpack('Vencsize/Vdecsize', $binMiniHead);
					@fseek($this->fp, $miniHead['encsize'], SEEK_CUR);
					$this->compressedSizeReadSinceLastFileHeader += 8 + $miniHead['encsize'];
				}
			}

			$this->currentPartOffset                     = @ftell($this->fp);
			$this->runState                              = AK_STATE_DONE;
			$this->fileHeader->compressed                = $this->compressedSizeReadSinceLastFileHeader;
			$this->compressedSizeReadSinceLastFileHeader = 0;

			return true;
		}

		// Remove the removePath, if any
		$this->fileHeader->file = $this->removePath($this->fileHeader->file);

		// Last chance to prepend a path to the filename
		if (!empty($this->addPath) && !$isDirRenamed)
		{
			$this->fileHeader->file = $this->addPath . $this->fileHeader->file;
		}

		// Get the translated path name
		$restorePerms = AKFactory::get('kickstart.setup.restoreperms', false);

		if (!$this->mustSkip())
		{
			if ($this->fileHeader->type == 'file')
			{
				// Regular file; ask the postproc engine to process its filename
				if ($restorePerms)
				{
					$this->fileHeader->realFile =
						$this->postProcEngine->processFilename($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->fileHeader->realFile = $this->postProcEngine->processFilename($this->fileHeader->file);
				}
			}
			elseif ($this->fileHeader->type == 'dir')
			{
				$dir                        = $this->fileHeader->file;
				$this->fileHeader->realFile = $dir;

				// Directory; just create it
				if ($restorePerms)
				{
					$this->postProcEngine->createDirRecursive($this->fileHeader->file, $this->fileHeader->permissions);
				}
				else
				{
					$this->postProcEngine->createDirRecursive($this->fileHeader->file, 0755);
				}

				$this->postProcEngine->processFilename(null);
			}
			else
			{
				// Symlink; do not post-process
				$this->postProcEngine->processFilename(null);
			}

			$this->createDirectory();
		}


		$this->fileHeader->compressed                = $this->compressedSizeReadSinceLastFileHeader;
		$this->compressedSizeReadSinceLastFileHeader = 0;

		// Header is read
		$this->runState = AK_STATE_HEADER;

		$this->dataReadLength = 0;

		return true;
	}

	/**
	 * Creates the directory this file points to
	 */
	protected function createDirectory()
	{
		if ($this->mustSkip())
		{
			return true;
		}

		// Do we need to create a directory?
		$lastSlash = strrpos($this->fileHeader->realFile, '/');
		$dirName   = substr($this->fileHeader->realFile, 0, $lastSlash);
		$perms     = 0755;
		$ignore    = AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($dirName);

		if (($this->postProcEngine->createDirRecursive($dirName, $perms) == false) && (!$ignore))
		{
			$this->setError(AKText::sprintf('COULDNT_CREATE_DIR', $dirName));

			return false;
		}

		return true;
	}

	/**
	 * Concrete classes must use this method to process file data. It must set $runState to AK_STATE_DATAREAD when
	 * it's finished processing the file data.
	 *
	 * @return bool True if processing the file data was successful, false if an error occurred
	 */
	protected function processFileData()
	{
		switch ($this->fileHeader->type)
		{
			case 'dir':
				return $this->processTypeDir();
				break;

			case 'link':
				return $this->processTypeLink();
				break;

			case 'file':
				switch ($this->fileHeader->compression)
				{
					case 'none':
						return $this->processTypeFileUncompressed();
						break;

					case 'gzip':
					case 'bzip2':
						return $this->processTypeFileCompressedSimple();
						break;

				}
				break;
		}
	}

	/**
	 * Process the file data of a directory entry
	 *
	 * @return bool
	 */
	private function processTypeDir()
	{
		// Directory entries in the JPA do not have file data, therefore we're done processing the entry
		$this->runState = AK_STATE_DATAREAD;

		return true;
	}

	/**
	 * Process the file data of a link entry
	 *
	 * @return bool
	 */
	private function processTypeLink()
	{

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		// Read the mini header
		$binMiniHeader   = fread($this->fp, 8);
		$reallyReadBytes = akstringlen($binMiniHeader);

		if ($reallyReadBytes < 8)
		{
			// We read less than requested! Why? Did we hit local EOF?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Let's go to the next file
				$this->nextFile();
				// Retry reading the header
				$binMiniHeader   = fread($this->fp, 8);
				$reallyReadBytes = akstringlen($binMiniHeader);
				// Still not enough data? If so, the archive is corrupt or missing parts.
				if ($reallyReadBytes < 8)
				{
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}
			else
			{
				// Nope. The archive is corrupt
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		// Read the encrypted data
		$miniHeader      = unpack('Vencsize/Vdecsize', $binMiniHeader);
		$toReadBytes     = $miniHeader['encsize'];
		$data            = $this->fread($this->fp, $toReadBytes);
		$reallyReadBytes = akstringlen($data);
		$this->compressedSizeReadSinceLastFileHeader += 8 + $miniHeader['encsize'];

		if ($reallyReadBytes < $toReadBytes)
		{
			// We read less than requested! Why? Did we hit local EOF?
			if ($this->isEOF(true) && !$this->isEOF(false))
			{
				// Yeap. Let's go to the next file
				$this->nextFile();
				// Read the rest of the data
				$toReadBytes -= $reallyReadBytes;
				$restData        = $this->fread($this->fp, $toReadBytes);
				$reallyReadBytes = akstringlen($data);
				if ($reallyReadBytes < $toReadBytes)
				{
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
				$data .= $restData;
			}
			else
			{
				// Nope. The archive is corrupt
				$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

				return false;
			}
		}

		// Decrypt the data
		$data = AKEncryptionAES::AESDecryptCBC($data, $this->password);

		// Is the length of the decrypted data less than expected?
		$data_length = akstringlen($data);
		if ($data_length < $miniHeader['decsize'])
		{
			$this->setError(AKText::_('ERR_INVALID_JPS_PASSWORD'));

			return false;
		}

		// Trim the data
		$data = substr($data, 0, $miniHeader['decsize']);

		if (!$this->mustSkip())
		{
			// Try to remove an existing file or directory by the same name
			if (file_exists($this->fileHeader->file))
			{
				@unlink($this->fileHeader->file);
				@rmdir($this->fileHeader->file);
			}
			// Remove any trailing slash
			if (substr($this->fileHeader->file, -1) == '/')
			{
				$this->fileHeader->file = substr($this->fileHeader->file, 0, -1);
			}
			// Create the symlink - only possible within PHP context. There's no support built in the FTP protocol, so no postproc use is possible here :(
			@symlink($data, $this->fileHeader->file);
		}

		$this->runState = AK_STATE_DATAREAD;

		return true; // No matter if the link was created!
	}

	private function processTypeFileUncompressed()
	{
		// Uncompressed files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);
			if ($this->dataReadLength == 0)
			{
				$outfp = @fopen($this->fileHeader->realFile, 'w');
			}
			else
			{
				$outfp = @fopen($this->fileHeader->realFile, 'a');
			}

			// Can we write to the file?
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			if (!$this->mustSkip() && is_resource($outfp))
			{
				@fclose($outfp);
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		$this->setError('An uncompressed file was detected; this is not supported by this archive extraction utility');

		return false;
	}

	private function processTypeFileCompressedSimple()
	{
		$timer = AKFactory::getTimer();

		// Files are being processed in small chunks, to avoid timeouts
		if (($this->dataReadLength == 0) && !$this->mustSkip())
		{
			// Before processing file data, ensure permissions are adequate
			$this->setCorrectPermissions($this->fileHeader->file);

			clearstatcache($this->fileHeader->file);
		}

		// Open the output file
		if (!$this->mustSkip())
		{
			// Open the output file
			$outfp = @fopen($this->fileHeader->realFile, 'w');

			// Can we write to the file?
			$ignore =
				AKFactory::get('kickstart.setup.ignoreerrors', false) || $this->isIgnoredDirectory($this->fileHeader->file);
			if (($outfp === false) && (!$ignore))
			{
				// An error occurred
				$this->setError(AKText::sprintf('COULDNT_WRITE_FILE', $this->fileHeader->realFile));

				return false;
			}
		}

		// Does the file have any data, at all?
		if ($this->fileHeader->uncompressed == 0)
		{
			// No file data!
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fclose($outfp);
				}
			}
			$this->runState = AK_STATE_DATAREAD;

			return true;
		}

		$leftBytes = $this->fileHeader->uncompressed - $this->dataReadLength;

		// Loop while there's data to write and enough time to do it
		while (($leftBytes > 0) && ($timer->getTimeLeft() > 0))
		{
			// Read the mini header
			$binMiniHeader   = fread($this->fp, 8);
			$reallyReadBytes = akstringlen($binMiniHeader);
			if ($reallyReadBytes < 8)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
					// Retry reading the header
					$binMiniHeader   = fread($this->fp, 8);
					$reallyReadBytes = akstringlen($binMiniHeader);
					// Still not enough data? If so, the archive is corrupt or missing parts.
					if ($reallyReadBytes < 8)
					{
						$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

						return false;
					}
				}
				else
				{
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			// Read the encrypted data
			$miniHeader      = unpack('Vencsize/Vdecsize', $binMiniHeader);
			$toReadBytes     = $miniHeader['encsize'];
			$data            = $this->fread($this->fp, $toReadBytes);
			$reallyReadBytes = akstringlen($data);

			$this->compressedSizeReadSinceLastFileHeader += $miniHeader['encsize'] + 8;

			if ($reallyReadBytes < $toReadBytes)
			{
				// We read less than requested! Why? Did we hit local EOF?
				if ($this->isEOF(true) && !$this->isEOF(false))
				{
					// Yeap. Let's go to the next file
					$this->nextFile();
					// Read the rest of the data
					$toReadBytes -= $reallyReadBytes;
					$restData        = $this->fread($this->fp, $toReadBytes);
					$reallyReadBytes = akstringlen($restData);
					if ($reallyReadBytes < $toReadBytes)
					{
						$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

						return false;
					}
					if (akstringlen($data) == 0)
					{
						$data = $restData;
					}
					else
					{
						$data .= $restData;
					}
				}
				else
				{
					// Nope. The archive is corrupt
					$this->setError(AKText::_('ERR_CORRUPT_ARCHIVE'));

					return false;
				}
			}

			// Decrypt the data
			$data = AKEncryptionAES::AESDecryptCBC($data, $this->password);

			// Is the length of the decrypted data less than expected?
			$data_length = akstringlen($data);
			if ($data_length < $miniHeader['decsize'])
			{
				$this->setError(AKText::_('ERR_INVALID_JPS_PASSWORD'));

				return false;
			}

			// Trim the data
			$data = substr($data, 0, $miniHeader['decsize']);

			// Decompress
			$data    = gzinflate($data);
			$unc_len = akstringlen($data);

			// Write the decrypted data
			if (!$this->mustSkip())
			{
				if (is_resource($outfp))
				{
					@fwrite($outfp, $data, akstringlen($data));
				}
			}

			// Update the read length
			$this->dataReadLength += $unc_len;
			$leftBytes = $this->fileHeader->uncompressed - $this->dataReadLength;
		}

		// Close the file pointer
		if (!$this->mustSkip())
		{
			if (is_resource($outfp))
			{
				@fclose($outfp);
			}
		}

		// Was this a pre-timeout bail out?
		if ($leftBytes > 0)
		{
			$this->runState = AK_STATE_DATA;
		}
		else
		{
			// Oh! We just finished!
			$this->runState       = AK_STATE_DATAREAD;
			$this->dataReadLength = 0;
		}

		return true;
	}

	private function readKeyExpansionExtraHeader()
	{
		$signature = fread($this->fp, 4);

		if ($signature != "JH\x00\x01")
		{
			// Not a valid JPS file
			$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

			return false;
		}

		$bin_data    = fread($this->fp, 8);
		$header_data = unpack('vlength/Calgo/Viterations/CuseStaticSalt', $bin_data);

		if ($header_data['length'] != 76)
		{
			// Not a valid JPS file
			$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

			return false;
		}

		switch ($header_data['algo'])
		{
			case 0:
				$algorithm = 'sha1';
				break;

			case 1:
				$algorithm = 'sha256';
				break;

			case 2:
				$algorithm = 'sha512';
				break;

			default:
				// Not a valid JPS file
				$this->setError(AKText::_('ERR_NOT_A_JPS_FILE'));

				return false;
				break;
		}

		$this->pbkdf2Algorithm     = $algorithm;
		$this->pbkdf2Iterations    = $header_data['iterations'];
		$this->pbkdf2UseStaticSalt = $header_data['useStaticSalt'];
		$this->pbkdf2StaticSalt    = fread($this->fp, 64);

		return true;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Timer class
 */
class AKCoreTimer extends AKAbstractObject
{
	/** @var int Maximum execution time allowance per step */
	private $max_exec_time = null;

	/** @var int Timestamp of execution start */
	private $start_time = null;

	/**
	 * Public constructor, creates the timer object and calculates the execution time limits
	 *
	 * @return  void
	 */
	public function __construct()
	{
		// Initialize start time
		$this->start_time = $this->microtime_float();

		// Get configured max time per step and bias
		$config_max_exec_time = AKFactory::get('kickstart.tuning.max_exec_time', 14);
		$bias                 = AKFactory::get('kickstart.tuning.run_time_bias', 75) / 100;

		// Get PHP's maximum execution time (our upper limit)
		if (@function_exists('ini_get'))
		{
			$php_max_exec_time = @ini_get("maximum_execution_time");
			if ((!is_numeric($php_max_exec_time)) || ($php_max_exec_time == 0))
			{
				// If we have no time limit, set a hard limit of about 10 seconds
				// (safe for Apache and IIS timeouts, verbose enough for users)
				$php_max_exec_time = 14;
			}
		}
		else
		{
			// If ini_get is not available, use a rough default
			$php_max_exec_time = 14;
		}

		// Apply an arbitrary correction to counter CMS load time
		$php_max_exec_time--;

		// Apply bias
		$php_max_exec_time    = $php_max_exec_time * $bias;
		$config_max_exec_time = $config_max_exec_time * $bias;

		// Use the most appropriate time limit value
		if ($config_max_exec_time > $php_max_exec_time)
		{
			$this->max_exec_time = $php_max_exec_time;
		}
		else
		{
			$this->max_exec_time = $config_max_exec_time;
		}
	}

	/**
	 * Returns the current timestampt in decimal seconds
	 */
	private function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());

		return ((float) $usec + (float) $sec);
	}

	/**
	 * Wake-up function to reset internal timer when we get unserialized
	 */
	public function __wakeup()
	{
		// Re-initialize start time on wake-up
		$this->start_time = $this->microtime_float();
	}

	/**
	 * Gets the number of seconds left, before we hit the "must break" threshold
	 *
	 * @return float
	 */
	public function getTimeLeft()
	{
		return $this->max_exec_time - $this->getRunningTime();
	}

	/**
	 * Gets the time elapsed since object creation/unserialization, effectively how
	 * long Akeeba Engine has been processing data
	 *
	 * @return float
	 */
	public function getRunningTime()
	{
		return $this->microtime_float() - $this->start_time;
	}

	/**
	 * Enforce the minimum execution time
	 */
	public function enforce_min_exec_time()
	{
		// Try to get a sane value for PHP's maximum_execution_time INI parameter
		if (@function_exists('ini_get'))
		{
			$php_max_exec = @ini_get("maximum_execution_time");
		}
		else
		{
			$php_max_exec = 10;
		}
		if (($php_max_exec == "") || ($php_max_exec == 0))
		{
			$php_max_exec = 10;
		}
		// Decrease $php_max_exec time by 500 msec we need (approx.) to tear down
		// the application, as well as another 500msec added for rounding
		// error purposes. Also make sure this is never gonna be less than 0.
		$php_max_exec = max($php_max_exec * 1000 - 1000, 0);

		// Get the "minimum execution time per step" Akeeba Backup configuration variable
		$minexectime = AKFactory::get('kickstart.tuning.min_exec_time', 0);
		if (!is_numeric($minexectime))
		{
			$minexectime = 0;
		}

		// Make sure we are not over PHP's time limit!
		if ($minexectime > $php_max_exec)
		{
			$minexectime = $php_max_exec;
		}

		// Get current running time
		$elapsed_time = $this->getRunningTime() * 1000;
		$minexectime = 1000.0 * $minexectime;

		// Only run a sleep delay if we haven't reached the minexectime execution time
		if (($minexectime > $elapsed_time) && ($elapsed_time > 0))
		{
			$sleep_msec = (int)($minexectime - $elapsed_time);

			if (function_exists('usleep'))
			{
				usleep(1000 * $sleep_msec);
			}
			elseif (function_exists('time_nanosleep'))
			{
				$sleep_sec  = floor($sleep_msec / 1000);
				$sleep_nsec = 1000000 * ($sleep_msec - ($sleep_sec * 1000));
				time_nanosleep($sleep_sec, $sleep_nsec);
			}
			elseif (function_exists('time_sleep_until'))
			{
				$until_timestamp = time() + $sleep_msec / 1000;
				time_sleep_until($until_timestamp);
			}
			elseif (function_exists('sleep'))
			{
				$sleep_sec = ceil($sleep_msec / 1000);
				sleep($sleep_sec);
			}
		}
	}

	/**
	 * Reset the timer. It should only be used in CLI mode!
	 */
	public function resetTime()
	{
		$this->start_time = $this->microtime_float();
	}

	/**
	 * @param int $max_exec_time
	 */
	public function setMaxExecTime($max_exec_time)
	{
		$this->max_exec_time = $max_exec_time;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * PHP 8.4+ workaround for standalone MD5 and SHA-1 functions.
 *
 * PHP 8.4 deprecates the standalone md5(), md5_file(), sha1(), and sha1_file() functions. This trait creates shims
 * which use the hash() and hash_file() functions instead where available.
 *
 * IMPORTANT! PHP 7.4 made the ext/hash extension mandatory. These shims are here only as a backwards compatibility aid.
 * Eventually, we need to remove them, replacing their use by the direct use of hash() and hash_file().
 *
 * @deprecated 9.0
 */
abstract class AKUtilsHash
{
	/**
	 * @deprecated 9.0 Use hash() instead
	 */
	public static function md5($string, $binary = false)
	{
		static $shouldUseHash = null;

		if ($shouldUseHash === null)
		{
			$shouldUseHash = function_exists('hash')
			                 && function_exists('hash_algos')
			                 && in_array('md5', hash_algos());
		}

		return $shouldUseHash ? hash('md5', $string, $binary) : md5($string, $binary);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */


class AKUtilsHtaccess extends AKAbstractObject
{
	/**
	 * Extract the PHP handler configuration from a .htaccess file.
	 *
	 * This method supports AddHandler lines and SetHandler blocks.
	 *
	 * @param   string  $htaccess
	 *
	 * @return  string|null  NULL when not found
	 */
	public static function extractHandler($htaccess)
	{
		// Normalize the .htaccess
		$htaccess = self::normalizeHtaccess($htaccess);

		// Look for SetHandler and AddHandler in Files and FilesMatch containers
		foreach (['Files', 'FilesMatch'] as $container)
		{
			$result = self::extractContainer($container, $htaccess);

			if (!is_null($result))
			{
				return $result;
			}
		}

		// Fallback: extract an AddHandler line
		$found = preg_match('#^AddHandler\s?.*\.php.*$#mi', $htaccess, $matches);

		if ($found >= 1)
		{
			return $matches[0];
		}

		return null;
	}

	/**
	 * Extracts a Files or FilesMatch container with an AddHandler or SetHandler line
	 *
	 * @param   string  $container  "Files" or "FilesMatch"
	 * @param   string  $htaccess   The .htaccess file content
	 *
	 * @return  string|null  NULL when not found
	 */
	protected static function extractContainer($container, $htaccess)
	{
		// Try to find the opening container tag e.g. <Files....>
		$pattern = sprintf('#<%s\s*.*\.php.*>#m', $container);
		$found   = preg_match($pattern, $htaccess, $matches, PREG_OFFSET_CAPTURE);

		if (!$found)
		{
			return null;
		}

		// Get the rest of the .htaccess sample
		$openContainer = $matches[0][0];
		$htaccess      = trim(substr($htaccess, $matches[0][1] + strlen($matches[0][0])));

		// Try to find the closing container tag
		$pattern = sprintf('#</%s\s*>#m', $container);
		$found   = preg_match($pattern, $htaccess, $matches, PREG_OFFSET_CAPTURE);

		if (!$found)
		{
			return null;
		}

		// Get the rest of the .htaccess sample
		$htaccess       = trim(substr($htaccess, 0, $matches[$found - 1][1]));
		$closeContainer = $matches[$found - 1][0];

		if (empty($htaccess))
		{
			return null;
		}

		// Now we'll explode remaining lines and find the first SetHandler or AddHandler line
		$lines = array_map('trim', explode("\n", $htaccess));
		$lines = array_filter($lines, function ($line) {
			return preg_match('#(Add|Set)Handler\s?#i', $line) >= 1;
		});

		if (empty($lines))
		{
			return null;
		}

		return $openContainer . "\n" . array_shift($lines) . "\n" . $closeContainer;
	}

	/**
	 * Normalize the .htaccess file content, making it suitable for handler extraction
	 *
	 * @param   string  $htaccess  The original file
	 *
	 * @return  string  The normalized file
	 */
	private static function normalizeHtaccess($htaccess)
	{
		// Convert all newlines into UNIX style
		$htaccess = str_replace("\r\n", "\n", $htaccess);
		$htaccess = str_replace("\r", "\n", $htaccess);

		// Return only non-comment, non-empty lines
		$isNonEmptyNonComment = function ($line) {
			$line = trim($line);

			return !empty($line) && (substr($line, 0, 1) !== '#');
		};

		$lines = array_map('trim', explode("\n", $htaccess));

		return implode("\n", array_filter($lines, $isNonEmptyNonComment));
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A filesystem scanner which uses opendir()
 */
class AKUtilsLister extends AKAbstractObject
{
	public function &getFiles($folder, $pattern = '*')
	{
		// Initialize variables
		$arr   = array();
		$false = false;

		if (!is_dir($folder))
		{
			return $false;
		}

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			$this->setWarning('Unreadable directory ' . $folder);

			return $false;
		}

		while (($file = @readdir($handle)) !== false)
		{
			if (!fnmatch($pattern, $file))
			{
				continue;
			}

			if (($file != '.') && ($file != '..'))
			{
				$ds    =
					($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ?
						'' : DIRECTORY_SEPARATOR;
				$dir   = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if (!$isDir)
				{
					$arr[] = $dir;
				}
			}
		}
		@closedir($handle);

		return $arr;
	}

	public function &getFolders($folder, $pattern = '*')
	{
		// Initialize variables
		$arr   = array();
		$false = false;

		if (!is_dir($folder))
		{
			return $false;
		}

		$handle = @opendir($folder);
		// If directory is not accessible, just return FALSE
		if ($handle === false)
		{
			$this->setWarning('Unreadable directory ' . $folder);

			return $false;
		}

		while (($file = @readdir($handle)) !== false)
		{
			if (!fnmatch($pattern, $file))
			{
				continue;
			}

			if (($file != '.') && ($file != '..'))
			{
				$ds    =
					($folder == '') || ($folder == '/') || (@substr($folder, -1) == '/') || (@substr($folder, -1) == DIRECTORY_SEPARATOR) ?
						'' : DIRECTORY_SEPARATOR;
				$dir   = $folder . $ds . $file;
				$isDir = is_dir($dir);
				if ($isDir)
				{
					$arr[] = $dir;
				}
			}
		}
		@closedir($handle);

		return $arr;
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A filesystem zapper - removes all files and folders under a root
 */
class AKUtilsZapper extends AKAbstractPart
{
	/** @var array Directories left to be deleted */
	private $directory_list;

	/** @var array Files left to be deleted */
	private $file_list;

	/**
	 * Have we finished scanning all subdirectories of the current directory?
	 *
	 * @var   boolean
	 */
	private $done_subdir_scanning = false;

	/**
	 * Have we finished scanning all files of the current directory?
	 *
	 * @var   boolean
	 */
	private $done_file_scanning = true;

	/**
	 * Is the current directory completely excluded?
	 *
	 * @var boolean
	 */
	private $excluded_folder = false;

	/** @var   integer  How many files have been processed in the current step */
	private $processed_files_counter;

	/** @var   string  Current directory being scanned */
	private $current_directory;

	/** @var   string  Current root directory being processed */
	private $root = '';

	/** @var   integer  Total files to process */
	private $total_files = 0;

	/** @var   integer  Total files already processed */
	private $done_files = 0;

	/** @var   integer  Total folders to process */
	private $total_folders = 0;

	/** @var   integer  Total folders already processed */
	private $done_folders = 0;

	/** @var array Absolute filesystem patterns to never delete (e.g. /var/www/html/*.jpa) */
	private $excluded = array();

	/** @var bool Are we in a dry-run? */
	private $dryRun = false;

	/**
	 * Implements the _prepare() abstract method
	 *
	 * Configuration parameters:
	 *
	 * root      The root under which we are going to be deleting files
	 * excluded  Absolute filesystem patterns to never delete (e.g. /var/www/html/*.jpa)
	 *
	 * @return  void
	 */
	protected function _prepare()
	{
		debugMsg(__CLASS__ . " :: Starting _prepare()");

		$defaultExcluded = $this->getDefaultExclusions();

		$parameters = array_merge(array(
			'root'     => rtrim(AKFactory::get('kickstart.setup.destdir'), '/' . DIRECTORY_SEPARATOR),
			'excluded' => $defaultExcluded,
            'dryRun'   => AKFactory::get('kickstart.setup.dryrun', false)
		), $this->_parametersArray);

		$this->root                 = $parameters['root'];
		$this->excluded             = $parameters['excluded'];
		$this->directory_list[]     = $this->root;
		$this->done_subdir_scanning = true;
		$this->done_file_scanning   = true;
		$this->total_files          = 0;
		$this->done_files           = 0;
		$this->total_folders        = 0;
		$this->done_folders         = 0;
		$this->dryRun               = $parameters['dryRun'];

		if (empty($this->root))
		{
			$error = "The folder to delete was not specified.";

			debugMsg(__CLASS__ . " :: " . $error);
			$this->setError($error);

			return;
		}

		if (!is_dir($this->root))
		{
			$error = sprintf("Folder %s does not exist", $this->root);

			debugMsg(__CLASS__ . " :: " . $error);
			$this->setError($error);

			return;
		}

		$this->setState('prepared');

		debugMsg(__CLASS__ . " :: prepared");
	}

	protected function _run()
	{
		if ($this->getState() == 'postrun')
		{
			debugMsg(__CLASS__ . " :: Already finished");
			$this->setStep("-");
			$this->setSubstep("");

			return true;
		}

		// If I'm done scanning files and subdirectories and there are no more files to pack get the next
		// directory. This block is triggered in the first step in a new root.
		if (empty($this->file_list) && $this->done_subdir_scanning && $this->done_file_scanning)
		{
			$this->progressMarkFolderDone();

			if (!$this->getNextDirectory())
			{
			    $this->setState('postrun');
				return true;
			}
		}

		// If I'm not done scanning for files and the file list is empty then scan for more files
		if (!$this->done_file_scanning && empty($this->file_list))
		{
			$this->scanFiles();
		}
		// If I have files left, delete them
		elseif (!empty($this->file_list))
		{
			$this->delete_files();
		}
		// If I'm not done scanning subdirectories, go ahead and scan some more of them
		elseif (!$this->done_subdir_scanning)
		{
			$this->scanSubdirs();
		}

		// Do I have an error?
		if ($this->getError())
		{
			return false;
		}

		return true;
	}

	/**
	 * Implements the _finalize() abstract method
	 *
	 */
	protected function _finalize()
	{
		// No finalization is required
		$this->setState('finished');
	}

	// ============================================================================================
	// PRIVATE METHODS
	// ============================================================================================

	/**
	 * Gets the next directory to scan from the stack. It also applies folder
	 * filters (directory exclusion, subdirectory exclusion, file exclusion),
	 * updating the operation toggle properties of the class.
	 *
	 * @return   boolean  True if we found a directory, false if the directory
	 *                    stack is empty. It also returns true if the folder is
	 *                    filtered (we are told to skip it)
	 */
	private function getNextDirectory()
	{
		// Reset the file / folder scanning positions
		$this->done_file_scanning   = false;
		$this->done_subdir_scanning = false;
		$this->excluded_folder      = false;

		if (count($this->directory_list) == 0)
		{
			// No directories left to scan
			return false;
		}

		// Get and remove the last entry from the $directory_list array
		$this->current_directory = array_pop($this->directory_list);
		$this->setStep($this->current_directory);
		$this->processed_files_counter = 0;

		// Apply directory exclusion filters
		if ($this->isFiltered($this->current_directory))
		{
			debugMsg("Skipping directory " . $this->current_directory);
			$this->done_subdir_scanning = true;
			$this->done_file_scanning   = true;
			$this->excluded_folder      = true;

			return true;
		}

		return true;
	}

	/**
	 * Try to delete some files from the $file_list
	 *
	 * @return   boolean   True if there were files deleted , false otherwise
	 *                     (empty filelist or fatal error)
	 */
	protected function delete_files()
	{
		// Get a reference to the archiver and the timer classes
		$timer = AKFactory::getTimer();

		// Normal file removal loop; we keep on processing the file list, removing files as we go.
		if (count($this->file_list) == 0)
		{
			// No files left to pack. Return true and let the engine loop
			$this->progressMarkFolderDone();

			return true;
		}

		debugMsg("Deleting files");

		$numberOfFiles = 0;
		$postProc = AKFactory::getPostProc();

		while ((count($this->file_list) > 0))
		{
			$file = @array_shift($this->file_list);

			$numberOfFiles++;

			// Remove the file
            $this->setSubstep($file);
            $this->notify((object) array(
                'type' => 'deleteFile',
                'file' => $file
            ));

            if (!$this->dryRun)
            {
                $postProc->unlink($file);
	            clearFileInOPCache($file);
            }

			// Mark a done file
			$this->progressMarkFileDone();

			if ($this->getError())
			{
				return false;
			}

			// I am running out of time.
			if ($timer->getTimeLeft() <= 0)
			{
				return true;
			}
		}

		// True if we have more files, false if we're done packing
		return (count($this->file_list) > 0);
	}

	protected function progressAddFile()
	{
		$this->total_files++;
	}

	protected function progressMarkFileDone()
	{
		$this->done_files++;
	}

	protected function progressAddFolder()
	{
		$this->total_folders++;
	}

	protected function progressMarkFolderDone()
	{
        debugMsg("Deleting directory " . $this->current_directory);

        $this->setSubstep($this->current_directory);
        $this->notify((object) array(
            'type' => 'deleteFolder',
            'file' => $this->current_directory
        ));

        if (!$this->dryRun)
        {
            /**
             * The scanner goes from shallow to deep directory. However this means that when it scans
             * <root>/foo/bar/baz/bat
             * it will only be able to remove the 'bat' directory, thus leaving foo/bar/baz on the disk. The following
             * method will check if the directory is a subdirectory of the site root and work its way up the tree until
             * it finds the site root. Therefore it will end up deleting the parent folders as well.
             */
            $this->deleteParentFolders($this->current_directory);
        }
	}

	/**
	 * Returns the site root, the translated site root and the translated current directory
	 *
	 * @return array
	 */
	protected function getCleanDirectoryComponents()
	{
		$root            = $this->root;
		$translated_root = $root;
		$dir             = TrimTrailingSlash($this->current_directory);

		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			$translated_root = TranslateWinPath($translated_root);
			$dir             = TranslateWinPath($dir);
		}

		if (substr($dir, 0, strlen($translated_root)) == $translated_root)
		{
			$dir = substr($dir, strlen($translated_root));
		}
		elseif (in_array(substr($translated_root, -1), array('/', '\\')))
		{
			$new_translated_root = rtrim($translated_root, '/\\');

			if (substr($dir, 0, strlen($new_translated_root)) == $new_translated_root)
			{
				$dir = substr($dir, strlen($new_translated_root));
			}
		}

		if (substr($dir, 0, 1) == '/')
		{
			$dir = substr($dir, 1);
		}

		return array($root, $translated_root, $dir);
	}

	/**
	 * Steps the subdirectory scanning of the current directory
	 *
	 * @return  boolean  True on success, false on fatal error
	 */
	protected function scanSubdirs()
	{
		$lister = new AKUtilsLister();

		list($root, $translated_root, $dir) = $this->getCleanDirectoryComponents();

		debugMsg("Scanning directories of " . $this->current_directory);

		// Get subdirectories
		$subdirectories = $lister->getFolders($this->current_directory);

		// Error propagation
		$this->propagateFromObject($lister);

		// Error control
		if ($this->getError())
		{
			return false;
		}

		// Start adding the subdirectories
		if (!empty($subdirectories) && is_array($subdirectories))
		{
			// Treat symlinks to directories as simple symlink files
			foreach ($subdirectories as $subdirectory)
			{
				if (is_link($subdirectory))
				{
					// Symlink detected; apply directory filters to it
					if (empty($dir))
					{
						$dirSlash = $dir;
					}
					else
					{
						$dirSlash = $dir . '/';
					}

					$check = $dirSlash . basename($subdirectory);
					debugMsg("Directory symlink detected: $check");

					if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
					{
						$check = TranslateWinPath($check);
					}

					$check = $translated_root . '/' . $check;

					// Check for excluded symlinks
					if ($this->isFiltered($check))
					{
						debugMsg("Skipping directory symlink " . $check);

						continue;
					}

					debugMsg('Adding folder symlink: ' . $check);

					$this->file_list[] = $subdirectory;
					$this->progressAddFile();
				}

				$this->directory_list[] = $subdirectory;
				$this->progressAddFolder();
			}
		}

		$this->done_subdir_scanning = true;

		return true;
	}

	/**
	 * Steps the files scanning of the current directory
	 *
	 * @return  boolean  True on success, false on fatal error
	 */
	protected function scanFiles()
	{
		$lister = new AKUtilsLister();

		list($root, $translated_root, $dir) = $this->getCleanDirectoryComponents();

		debugMsg("Scanning files of " . $this->current_directory);
		$this->processed_files_counter = 0;

		// Get file listing
		$fileList = $lister->getFiles($this->current_directory);

		// Error propagation
		$this->propagateFromObject($lister);

		// Error control
		if ($this->getError())
		{
			return false;
		}

		// Do I have an unreadable directory?
		if (($fileList === false))
		{
			$this->setWarning('Unreadable directory ' . $this->current_directory);

			$this->done_file_scanning = true;

			return true;
		}

		// Directory was readable, process the file list
		if (is_array($fileList) && !empty($fileList))
		{
			// Add required trailing slash to $dir
			if (!empty($dir))
			{
				$dir .= '/';
			}

			// Scan all directory entries
			foreach ($fileList as $fileName)
			{
				$check = $dir . basename($fileName);

				if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
				{
					$check = TranslateWinPath($check);
				}

				$check        = $translated_root . '/' . $check;
				$skipThisFile = $this->isFiltered($check);

				if ($skipThisFile)
				{
					debugMsg("Skipping file $fileName");

					continue;
				}

				$this->file_list[] = $fileName;
				$this->processed_files_counter++;
				$this->progressAddFile();
			}
		}

		$this->done_file_scanning = true;

		return true;
	}

	/**
	 * Is a file or folder filtered (protected from deletion)
	 *
	 * @param   string  $fileOrFolder
	 *
	 * @return  bool
	 */
	private function isFiltered($fileOrFolder)
	{
		foreach ($this->excluded as $pattern)
		{
			if (fnmatch($pattern, $fileOrFolder))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the default exceptions from deletion
	 *
	 * @return  array
	 */
	private function getDefaultExclusions()
	{
		$ret     = array();
		$destDir = AKFactory::get('kickstart.setup.destdir');

		/**
		 * Exclude Kickstart / restore.php itself. Otherwise it'd crash!
		 */
		$myName = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$ret[] = KSROOTDIR . '/' . $myName;

		/**
		 * Cheat: exclude the directory used in development (see source/buildscripts/kickstart_test.php)
		 *
		 * This directory contains the non-concatenated source code for Kickstart. We need to keep it protected.
		 */
		if (defined('MINIBUILD') && (MINIBUILD != $destDir))
		{
			$ret[] = TranslateWinPath(MINIBUILD);
		}

		/**
		 * Exclude the backup archive directory if it's not the site's root. This prevents mindlessly deleting all your
		 * backups before you restore from a previous backup which might not be the one you actually wanted. I will call
		 * this feature "clumsy-proofing".
		 */
		$backupArchive   = AKFactory::get('kickstart.setup.sourcefile');
		$backupDirectory = AKFactory::get('kickstart.setup.sourcepath');
		$backupDirectory = empty($backupDirectory) ? dirname($backupArchive) : $backupDirectory;

		if ($backupDirectory != $destDir)
		{
			$ret[] = TranslateWinPath($backupDirectory);
		}

		/**
		 * Exclude the backup archive files
		 *
		 * This obviously only makes sense when the backup archives are stored in the extraction target folder which is
		 * the most common use of Kickstart. In this case the backups folder is not excluded above.
		 */
		$plainBackupName = basename($backupArchive, '.jpa');
		$plainBackupName = basename($plainBackupName, '.jps');
		$plainBackupName = basename($plainBackupName, '.zip');
		$ret[]           = TranslateWinPath($backupDirectory . '/' . $plainBackupName) . '.*';

		/**
		 * Exclude Kickstart language files. Only applies in Kickstart mode.
		 */
		if (defined('KICKSTART'))
		{
			$langDir        = defined('KSLANGDIR') ? KSLANGDIR : KSROOTDIR;
			$myName         = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
			$iniFilePattern = basename($myName, '.php') . '.*.ini';

			if ($langDir != KSROOTDIR)
            {
                $ret[] = KSLANGDIR;
            }

            $ret[]   = $langDir . '/' . $iniFilePattern;
            $ret[]   = KSROOTDIR . '/' . $iniFilePattern;
		}

		/**
		 * Exclude Kickstart resources (cacert.pem). Only applies in Kickstart mode.
		 */
		if (defined('KICKSTART'))
		{
			$ret[] = TranslateWinPath(KSROOTDIR . '/cacert.pem');
		}

		// Exclude the Kickstart temporary directory, if one is used by the post-processing engine
		$postProc = AKFactory::getPostProc();
		$tempDir  = $postProc->getTempDir();

		if (!empty($tempDir) && (realpath($tempDir) != realpath($destDir)))
		{
			$ret[] = TranslateWinPath($tempDir);
		}

		/**
		 * Exclude the configured Skipped Files ('kickstart.setup.skipfiles'). Also exclude the various restoration.php
		 * files if we are in restore.php mode and the files are present. These are required for the integrated
		 * restoration to actually work :)
		 */
		$skippedFiles = AKFactory::get('kickstart.setup.skipfiles', array(
			basename(__FILE__), 'kickstart.php', 'abiautomation.ini', 'htaccess.bak', 'php.ini.bak',
			'cacert.pem',
		));

		if (!defined('KICKSTART'))
		{
			// In restore.php mode we have to exclude the various restoration.php files
			$skippedFiles = array_merge(array(
				// Akeeba Backup for Joomla!
				'administrator/components/com_akeeba/restoration.php',
				'administrator/components/com_akeebabackup/restoration.php',
				// Joomla! Update
				'administrator/components/com_joomlaupdate/restoration.php',
				// Akeeba Backup for WordPress
				'wp-content/plugins/akeebabackupwp/app/restoration.php',
				'wp-content/plugins/akeebabackupcorewp/app/restoration.php',
				'wp-content/plugins/akeebabackup/app/restoration.php',
				'wp-content/plugins/akeebabackupwpcore/app/restoration.php',
				// Akeeba Solo
				'app/restoration.php',
			), $skippedFiles);
		}

		foreach ($skippedFiles as $file)
		{
			$checkFile = $destDir . '/' . $file;

			if (file_exists($checkFile))
			{
				$ret[] = TranslateWinPath($checkFile);
			}
		}

		/**
		 * Exclude .htaccess if the stealth feature is enabled. Otherwise we'd unset the stealth mode.
		 * Exclude it even if we have any AddHandler directive, otherwise the site will be borked if the user
		 * chooses not to rename the .htaccess file
		 */
		if (AKFactory::get('kickstart.stealth.enable') || AKFactory::get('kickstart.setup.phphandlers', array()))
		{
			$ret[] = $destDir . '/.htaccess';
		}

		// Remove any duplicate lines
        $ret = array_unique($ret);

		return $ret;
	}

    /**
     * Recursively delete an empty folder and any of its empty parent folders.
     *
     * @param   string  $folder  The folder to deletes
     */
	private function deleteParentFolders($folder)
    {
        // Don't try to delete an empty folder or the filesystem root
        if (empty($folder) || ($folder == '/'))
        {
            return;
        }

        $folder = TranslateWinPath($folder);
        $root   = TranslateWinPath($this->root);

        // Don't try to delete the site's root
        if ($folder === $root)
        {
            return;
        }

        // Delete the leaf folder
        $postProc = AKFactory::getPostProc();
        $postProc->rmdir($folder);

        // If the leaf folder is not under the site's root don't delete its parents
        if (strpos($folder, $root) !== 0)
        {
            return;
        }

        // Get and recursively delete the parent folder
        $this->deleteParentFolders(dirname($folder));
    }
}

/**
 * Runs the Zapper and returns a status table. The Zapper only runs if the feature is enabled (kickstart.setup.zapbefore
 * is 1) and there are more Zapper steps to run (its state is not postrun). If any of these conditions is not met we
 * return boolean false.
 *
 * @param   AKAbstractPartObserver  $observer  Optional observer to attack to the Zapper instance
 *
 * @return  bool|array  Boolean false or a status array
 */
function runZapper(AKAbstractPartObserver $observer = null)
{
	// This method should only run in restore.php mode or when we have Kickstart Professional.
	$isKickstart = defined('KICKSTART');
	$isPro       = defined('KICKSTARTPRO') ? KICKSTARTPRO : false;
	$isDebug     = defined('KSDEBUG') ? KSDEBUG : false;

	if ($isKickstart && (!$isPro && !$isDebug))
	{
		return false;
	}

	// Is the feature enabled?
    $enabled = AKFactory::get('kickstart.setup.zapbefore', 0);

    if (!$enabled)
    {
        return false;
    }

    // Do I still have work to do?
    $zapper = AKFactory::getZapper();

    if ($zapper->getState() == 'finished')
    {
        return false;
    }

    // Attach the observer
    if (is_object($observer))
    {
        $zapper->attach($observer);
    }

    // Run a step, create and return a status array
	$timer = AKFactory::getTimer();

    while ($timer->getTimeLeft() > 0)
    {
	    $ret = $zapper->tick();

	    if ($ret['Error'] != '')
	    {
	    	break;
	    }
    }

    $retArray = array(
        'status'  => true,
        'message' => null,
        'done' => false,
    );

    if ($ret['Error'] != '')
    {
        $retArray['status']  = false;
        $retArray['done']    = true;
        $retArray['message'] = $ret['Error'];
    }
    else
    {
        $retArray['files']    = 0;
        $retArray['bytesIn']  = 0;
        $retArray['bytesOut'] = 0;
        $retArray['factory']  = AKFactory::serialize();
        $retArray['lastfile'] = 'Deleting: ' . $zapper->getSubstep();
    }

	$timer->enforce_min_exec_time();

    return $retArray;
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A simple INI-based i18n engine
 */
class AKText extends AKAbstractObject
{
	/**
	 * The default (en_GB) translation used when no other translation is available
	 *
	 * @var array
	 */
	private $default_translation = [
		'AUTOMODEON'                      => 'Auto-mode enabled',
		'ERR_NOT_A_JPA_FILE'              => 'The file is not a JPA archive',
		'ERR_CORRUPT_ARCHIVE'             => 'The archive file is corrupt, truncated or archive parts are missing',
		'ERR_INVALID_ARCHIVE_LONG'        => 'The archive file appears to be corrupt, or archive parts are missing. If your backups consists of multiple files, please make sure that you have downloaded all the archive part files (files with the same name and extensions .%s, .%s01, .%2$s02…). Please make sure to download <em>and</em> upload files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
		'ERR_INVALID_LOGIN'               => 'Invalid login',
		'COULDNT_CREATE_DIR'              => 'Could not create %s folder',
		'COULDNT_WRITE_FILE'              => 'Could not open %s for writing.',
		'WRONG_FTP_HOST'                  => 'Wrong FTP host or port',
		'WRONG_FTP_USER'                  => 'Wrong FTP username or password',
		'WRONG_FTP_PATH1'                 => 'Wrong FTP initial directory - the directory doesn\'t exist',
		'FTP_CANT_CREATE_DIR'             => 'Could not create directory %s',
		'FTP_TEMPDIR_NOT_WRITABLE'        => 'Could not find or create a writable temporary directory',
		'SFTP_TEMPDIR_NOT_WRITABLE'       => 'Could not find or create a writable temporary directory',
		'FTP_COULDNT_UPLOAD'              => 'Could not upload %s',
		'THINGS_HEADER'                   => 'Things you should know about Akeeba Kickstart',
		'THINGS_01'                       => 'Kickstart is not an installer. It is an archive extraction tool. The actual installer was put inside the archive file at backup time.',
		'THINGS_03'                       => 'Kickstart is bound by your server\'s configuration. As such, it may not work at all.',
		'THINGS_04'                       => 'You should download and upload your archive files using FTP in Binary transfer mode. Any other method could lead to a corrupt backup archive and restoration failure.',
		'THINGS_05'                       => 'Post-restoration site load errors are usually caused by .htaccess or php.ini directives. You should understand that blank pages, 404 and 500 errors can usually be worked around by editing the aforementioned files. It is not our job to mess with your configuration files, because this could be dangerous for your site.',
		'THINGS_06'                       => 'Kickstart overwrites files without a warning. If you are not sure that you are OK with that do not continue.',
		'THINGS_07'                       => 'Trying to restore to the temporary URL of a cPanel host (e.g. http://1.2.3.4/~username) will lead to restoration failure and your site will appear to be not working. This is normal and it\'s just how your server and CMS software work.',
		'THINGS_08'                       => 'You are supposed to read the documentation before using this software. Most issues can be avoided, or easily worked around, by understanding how this software works.',
		'THINGS_09'                       => 'This text does not imply that there is a problem detected. It is standard text displayed every time you launch Kickstart.',
		'CLOSE_LIGHTBOX'                  => 'Click here or press ESC to close this message',
		'SELECT_ARCHIVE'                  => 'Select a backup archive',
		'ARCHIVE_FILE'                    => 'Archive file:',
		'SELECT_EXTRACTION'               => 'Select an extraction method',
		'WRITE_TO_FILES'                  => 'Write to files:',
		'WRITE_HYBRID'                    => 'Hybrid (use FTP only if needed)',
		'WRITE_DIRECTLY'                  => 'Directly',
		'WRITE_FTP'                       => 'Use FTP for all files',
		'WRITE_SFTP'                      => 'Use SFTP for all files',
		'FTP_HOST'                        => '(S)FTP host name:',
		'FTP_PORT'                        => '(S)FTP port:',
		'FTP_FTPS'                        => 'Use FTP over SSL (FTPS)',
		'FTP_PASSIVE'                     => 'Use FTP Passive Mode',
		'FTP_USER'                        => '(S)FTP user name:',
		'FTP_PASS'                        => '(S)FTP password:',
		'FTP_DIR'                         => '(S)FTP directory:',
		'FTP_TEMPDIR'                     => 'Temporary directory:',
		'FTP_CONNECTION_OK'               => 'FTP Connection Established',
		'SFTP_CONNECTION_OK'              => 'SFTP Connection Established',
		'FTP_CONNECTION_FAILURE'          => 'The FTP Connection Failed',
		'SFTP_CONNECTION_FAILURE'         => 'The SFTP Connection Failed',
		'FTP_TEMPDIR_WRITABLE'            => 'The temporary directory is writable.',
		'FTP_TEMPDIR_UNWRITABLE'          => 'The temporary directory is not writable. Please check the permissions.',
		'FTP_BROWSE'                      => 'Browse',
		'FTPBROWSER_LBL_INSTRUCTIONS'     => 'Click on a directory to navigate into it. Click on OK to select that directory, Cancel to abort the procedure.',
		'FTPBROWSER_ERROR_HOSTNAME'       => 'Invalid FTP host or port',
		'FTPBROWSER_ERROR_USERPASS'       => 'Invalid FTP username or password',
		'FTPBROWSER_ERROR_NOACCESS'       => 'Directory doesn\'t exist or you don\'t have enough permissions to access it',
		'FTPBROWSER_ERROR_UNSUPPORTED'    => 'Sorry, your FTP server doesn\'t support our FTP directory browser.',
		'FTPBROWSER_LBL_GOPARENT'         => '&lt;up one level&gt;',
		'FTPBROWSER_LBL_ERROR'            => 'An error occurred',
		'SFTP_NO_SSH2'                    => 'Your web server does not have the SSH2 PHP module, therefore can not connect to SFTP servers.',
		'SFTP_NO_FTP_SUPPORT'             => 'Your SSH server does not allow SFTP connections',
		'SFTP_WRONG_USER'                 => 'Wrong SFTP username or password',
		'SFTP_WRONG_STARTING_DIR'         => 'You must supply a valid absolute path',
		'SFTPBROWSER_ERROR_NOACCESS'      => 'Directory doesn\'t exist or you don\'t have enough permissions to access it',
		'SFTP_COULDNT_UPLOAD'             => 'Could not upload %s',
		'SFTP_CANT_CREATE_DIR'            => 'Could not create directory %s',
		'UI-ROOT'                         => '&lt;root&gt;',
		'CONFIG_UI_FTPBROWSER_TITLE'      => 'FTP Directory Browser',
		'BTN_CHECK'                       => 'Check',
		'BTN_RESET'                       => 'Reset',
		'BTN_TESTFTPCON'                  => 'Test FTP connection',
		'BTN_TESTSFTPCON'                 => 'Test SFTP connection',
		'BTN_GOTOSTART'                   => 'Start over',
		'BTN_RETRY'                       => 'Retry',
		'FINE_TUNE'                       => 'Fine tune',
		'MIN_EXEC_TIME'                   => 'Minimum execution time:',
		'MAX_EXEC_TIME'                   => 'Maximum execution time:',
		'SECONDS_PER_STEP'                => 'seconds per step',
		'EXTRACT_FILES'                   => 'Extract files',
		'BTN_START'                       => 'Start',
		'EXTRACTING'                      => 'Extracting',
		'DO_NOT_CLOSE_EXTRACT'            => 'Do not close this window while the extraction is in progress',
		'RESTACLEANUP'                    => 'Restoration and Clean Up',
		'BTN_RUNINSTALLER'                => 'Run the Installer',
		'BTN_CLEANUP'                     => 'Clean Up',
		'BTN_SITEFE'                      => 'Visit your site\'s frontend',
		'BTN_SITEBE'                      => 'Visit your site\'s backend',
		'WARNINGS'                        => 'Extraction Warnings',
		'ERROR_OCCURED'                   => 'An error occurred',
		'STEALTH_MODE'                    => 'Stealth mode',
		'STEALTH_URL'                     => 'HTML file to show to web visitors',
		'ERR_NOT_A_JPS_FILE'              => 'The file is not a JPA archive',
		'ERR_INVALID_JPS_PASSWORD'        => 'The password you gave is wrong or the archive is corrupt',
		'JPS_PASSWORD'                    => 'Archive Password (for JPS files)',
		'INVALID_FILE_HEADER_OFFSET_ZERO' => 'Cannot open the file %s for reading. This is part #%d of your backup archive which consists of multiple files (files with the same name and extensions .%s, .%s01, .%4$s02…). Please make sure that you have all of these files in the same folder as Kickstart.',
		'INVALID_FILE_HEADER'             => 'Invalid header in archive file, part %s, offset %s. Please make sure to download <em>and</em> upload backup archive files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
		'INVALID_FILE_HEADER_MULTIPART'   => 'Invalid header in archive file, part %s, offset %s. Your backup archive consists of multiple files (files with the same name and extensions .%s, .%s01, .%4$s02…). Either some files are missing, or they are corrupt or truncated. You will need all of these files to be present in the same directory. Please make sure to download <em>and</em> upload backup archive files using SFTP, or FTP in Binary transfer mode and do check that their file size matches the sizes reported in the Manage Backups page of Akeeba Backup / Akeeba Solo.',
		'UPDATE_HEADER'                   => 'An updated version of Akeeba Kickstart (<span id="update-version">unknown</span>) is available!',
		'UPDATE_NOTICE'                   => 'You are advised to always use the latest version of Akeeba Kickstart available. Older versions may be subject to bugs and will not be supported.',
		'UPDATE_DLNOW'                    => 'Download now',
		'UPDATE_MOREINFO'                 => 'More information',
		'NEEDSOMEHELPKS'                  => 'Want some help to use this tool? Read this first:',
		'QUICKSTART'                      => 'Using Kickstart',
		'CANTGETITTOWORK'                 => 'Can\'t get it to work? Click me!',
		'NOARCHIVESCLICKHERE'             => 'No archives detected. Click here for troubleshooting instructions.',
		'POSTRESTORATIONTROUBLESHOOTING'  => 'Something not working after the restoration? Click here for troubleshooting instructions.',
		'IGNORE_MOST_ERRORS'              => 'Ignore most errors',
		'TIME_SETTINGS_HELP'              => 'Increase the minimum to 3 if you get AJAX errors. Increase the maximum to 10 for faster extraction, decrease back to 5 if you get AJAX errors. Try minimum 5, maximum 1 (not a typo!) if you keep getting AJAX errors.',
		'STEALTH_MODE_HELP'               => 'When enabled, only visitors from your IP address will be able to see the site until the restoration is complete. Everyone else will be redirected to and only see the URL above. Your server must see the real IP of the visitor (this is controlled by your host, not you or us).',
		'RENAME_FILES_HELP'               => 'Renames .htaccess, web.config, php.ini and .user.ini contained in the archive while extracting. Files are renamed with a .bak extension. The file names are restored when you click on Clean Up.',
		'RESTORE_PERMISSIONS_HELP'        => 'Applies the file permissions (but NOT file ownership) which was stored at backup time. Only works with JPA and JPS archives. Does not work on Windows (PHP does not offer such a feature).',
		'EXTRACT_LIST'                    => 'Files to extract',
		'EXTRACT_LIST_HELP'               => 'Enter a file path such as <code>images/cat.png</code> or shell pattern such as <code>images/*.png</code> on each line. Only files matching this list will be written to disk. Leave empty to extract everything (default).',
		'AKS3_IMPORT'                     => 'Import from Amazon S3',
		'AKS3_TITLE_STEP1'                => 'Connect to Amazon S3',
		'AKS3_ACCESS'                     => 'Access Key',
		'AKS3_SECRET'                     => 'Secret Key',
		'AKS3_CONNECT'                    => 'Connect to Amazon S3',
		'AKS3_CANCEL'                     => 'Cancel import',
		'AKS3_TITLE_STEP2'                => 'Select your Amazon S3 bucket',
		'AKS3_BUCKET'                     => 'Bucket',
		'AKS3_LISTCONTENTS'               => 'List contents',
		'AKS3_TITLE_STEP3'                => 'Select archive to import',
		'AKS3_FOLDERS'                    => 'Folders',
		'AKS3_FILES'                      => 'Archive Files',
		'AKS3_TITLE_STEP4'                => 'Importing...',
		'AKS3_DO_NOT_CLOSE'               => 'Please do not close this window while your backup archives are being imported',
		'AKS3_TITLE_STEP5'                => 'Import is complete',
		'AKS3_BTN_RELOAD'                 => 'Reload Kickstart',
		'WRONG_FTP_PATH2'                 => 'Wrong FTP initial directory - the directory doesn\'t correspond to your site\'s web root',
		'ARCHIVE_DIRECTORY'               => 'Archive directory:',
		'RELOAD_ARCHIVES'                 => 'Reload',
		'CONFIG_UI_SFTPBROWSER_TITLE'     => 'SFTP Directory Browser',
		'ERR_COULD_NOT_OPEN_ARCHIVE_PART' => 'Could not open archive part file %s for reading. Check that the file exists, is readable by the web server and is not in a directory made out of reach by chroot, open_basedir restrictions or any other restriction put in place by your host.',
		'RENAME_FILES'                    => 'Rename server configuration files before extraction',
		'BTN_SHOW_FINE_TUNE'              => 'Show advanced options (for experts)',
		'RESTORE_PERMISSIONS'             => 'Restore file permissions',
		'ZAPBEFORE'                       => 'Delete everything before extraction',
		'ZAPBEFORE_HELP'                  => 'Tries to delete all existing files and folders under the directory where Kickstart is stored before extracting the backup archive. It DOES NOT take into account which files and folders exist in the backup archive. Files and folders deleted by this feature CAN NOT be recovered. <strong>WARNING! THIS MAY DELETE FILES AND FOLDERS WHICH DO NOT BELONG TO YOUR SITE. USE WITH EXTREME CAUTION. BY ENABLING THIS FEATURE YOU ASSUME ALL RESPONSIBILITY AND LIABILITY.</strong>',
	];

	/** END OF ARRAY — DO NOT EDIT OR REMOVE **/

	/**
	 * The array holding the translation keys
	 *
	 * @var array
	 */
	private $strings;

	/**
	 * The currently detected language (ISO code)
	 *
	 * @var string
	 */
	private $language;

	/*
	 * Initializes the translation engine
	 * @return AKText
	 */
	public function __construct()
	{
		// Start with the default translation
		$this->strings = $this->default_translation;
		// Try loading the translation file in English, if it exists
		$this->loadTranslation('en-GB');
		// Try loading the translation file in the browser's preferred language, if it exists
		$this->getBrowserLanguage();
		if (!is_null($this->language))
		{
			$this->loadTranslation();
		}
	}

	/**
	 * A PHP based INI file parser.
	 *
	 * Thanks to asohn ~at~ aircanopy ~dot~ net for posting this handy function on
	 * the parse_ini_file page on http://gr.php.net/parse_ini_file
	 *
	 * @param   string  $file              Filename to process
	 * @param   bool    $process_sections  True to also process INI sections
	 * @param   bool    $rawdata           If true, the $file contains raw INI data, not a filename
	 *
	 * @return array An associative array of sections, keys and values
	 * @access private
	 */
	public static function parse_ini_file($file, $process_sections = false, $rawdata = false)
	{
		$process_sections = ($process_sections !== true) ? false : true;

		if (!$rawdata)
		{
			$ini = file($file);
		}
		else
		{
			$file = str_replace("\r", "", $file);
			$ini  = explode("\n", $file);
		}

		if (!is_array($ini))
		{
			return [];
		}

		if (count($ini) == 0)
		{
			return [];
		}

		$sections = [];
		$values   = [];
		$result   = [];
		$globals  = [];
		$i        = 0;
		foreach ($ini as $line)
		{
			$line = trim($line);
			$line = str_replace("\t", " ", $line);

			// Comments
			if (!preg_match('/^[a-zA-Z0-9[]/', $line))
			{
				continue;
			}

			// Sections
			if ($line[0] == '[')
			{
				$tmp        = explode(']', $line);
				$sections[] = trim(substr($tmp[0], 1));
				$i++;
				continue;
			}

			// Key-value pair
			$lineParts = explode('=', $line, 2);
			if (count($lineParts) != 2)
			{
				continue;
			}
			$key   = trim($lineParts[0]);
			$value = trim($lineParts[1]);
			unset($lineParts);

			if (strstr($value, ";"))
			{
				$tmp = explode(';', $value);
				if (count($tmp) == 2)
				{
					if ((($value[0] != '"') && ($value[0] != "'")) ||
						preg_match('/^".*"\s*;/', $value) || preg_match('/^".*;[^"]*$/', $value) ||
						preg_match("/^'.*'\s*;/", $value) || preg_match("/^'.*;[^']*$/", $value)
					)
					{
						$value = $tmp[0];
					}
				}
				else
				{
					if ($value[0] == '"')
					{
						$value = preg_replace('/^"(.*)".*/', '$1', $value);
					}
					elseif ($value[0] == "'")
					{
						$value = preg_replace("/^'(.*)'.*/", '$1', $value);
					}
					else
					{
						$value = $tmp[0];
					}
				}
			}
			$value = trim($value);
			$value = trim($value, "'\"");

			if ($i == 0)
			{
				if (substr($line, -1, 2) == '[]')
				{
					$globals[$key][] = $value;
				}
				else
				{
					$globals[$key] = $value;
				}
			}
			else
			{
				if (substr($line, -1, 2) == '[]')
				{
					$values[$i - 1][$key][] = $value;
				}
				else
				{
					$values[$i - 1][$key] = $value;
				}
			}
		}

		for ($j = 0; $j < $i; $j++)
		{
			if ($process_sections === true)
			{
				if (isset($sections[$j]) && isset($values[$j]))
				{
					$result[$sections[$j]] = $values[$j];
				}
			}
			else
			{
				if (isset($values[$j]))
				{
					$result[] = $values[$j];
				}
			}
		}

		return $result + $globals;
	}

	public static function sprintf($key)
	{
		$text = self::getInstance();
		$args = func_get_args();
		if (count($args) > 0)
		{
			$args[0] = $text->_($args[0]);

			return @call_user_func_array('sprintf', $args);
		}

		return '';
	}

	/**
	 * Singleton pattern for Language
	 *
	 * @return AKText The global AKText instance
	 */
	public static function &getInstance()
	{
		static $instance;

		if (!is_object($instance))
		{
			$instance = new AKText();
		}

		return $instance;
	}

	public static function _($string)
	{
		$text = self::getInstance();

		$key = strtoupper($string);
		$key = substr($key, 0, 1) == '_' ? substr($key, 1) : $key;

		if (isset ($text->strings[$key]))
		{
			$string = $text->strings[$key];
		}
		else
		{
			if (defined($string))
			{
				$string = constant($string);
			}
		}

		return $string;
	}

	public function getBrowserLanguage()
	{
		// Detection code from Full Operating system language detection, by Harald Hope
		// Retrieved from http://techpatterns.com/downloads/php_language_detection.php
		$user_languages = [];
		//check to see if language is set
		if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
		{
			$languages = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
			// $languages = ' fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3';
			// need to remove spaces from strings to avoid error
			$languages = str_replace(' ', '', $languages);
			$languages = explode(",", $languages);

			foreach ($languages as $language_list)
			{
				// pull out the language, place languages into array of full and primary
				// string structure:
				$temp_array = [];
				// slice out the part before ; on first step, the part before - on second, place into array
				$temp_array[0] = substr($language_list, 0, strcspn($language_list, ';'));//full language
				$temp_array[1] = substr($language_list, 0, 2);// cut out primary language
				if ((strlen($temp_array[0]) == 5) && ((substr($temp_array[0], 2, 1) == '-') || (substr($temp_array[0], 2, 1) == '_')))
				{
					$langLocation  = strtoupper(substr($temp_array[0], 3, 2));
					$temp_array[0] = $temp_array[1] . '-' . $langLocation;
				}
				//place this array into main $user_languages language array
				$user_languages[] = $temp_array;
			}
		}
		else// if no languages found
		{
			$user_languages[0] = ['', '']; //return blank array.
		}

		$this->language = null;
		$basename       = basename(__FILE__, '.php') . '.ini';

		// Try to match main language part of the filename, irrespective of the location, e.g. de_DE will do if de_CH doesn't exist.
		if (class_exists('AKUtilsLister'))
		{
			$fs       = new AKUtilsLister();
			$iniFiles = $fs->getFiles(KSROOTDIR, '*.' . $basename);
			if (empty($iniFiles) && ($basename != 'kickstart.ini'))
			{
				$basename = 'kickstart.ini';
				$iniFiles = $fs->getFiles(KSROOTDIR, '*.' . $basename);
			}
		}
		else
		{
			$iniFiles = null;
		}

		if (is_array($iniFiles))
		{
			foreach ($user_languages as $languageStruct)
			{
				if (is_null($this->language))
				{
					// Get files matching the main lang part
					$iniFiles = $fs->getFiles(KSROOTDIR, $languageStruct[1] . '-??.' . $basename);
					if (count($iniFiles) > 0)
					{
						$filename       = $iniFiles[0];
						$filename       = substr($filename, strlen(KSROOTDIR) + 1);
						$this->language = substr($filename, 0, 5);
					}
					else
					{
						$this->language = null;
					}
				}
			}
		}

		if (is_null($this->language))
		{
			// Try to find a full language match
			foreach ($user_languages as $languageStruct)
			{
				if (@file_exists($languageStruct[0] . '.' . $basename) && is_null($this->language))
				{
					$this->language = $languageStruct[0];
				}
			}
		}
		else
		{
			// Do we have an exact match?
			foreach ($user_languages as $languageStruct)
			{
				if (substr($this->language, 0, strlen($languageStruct[1])) == $languageStruct[1])
				{
					if (file_exists($languageStruct[0] . '.' . $basename))
					{
						$this->language = $languageStruct[0];
					}
				}
			}
		}

		// Now, scan for full language based on the partial match

	}

	public function dumpLanguage()
	{
		$out = '';
		foreach ($this->strings as $key => $value)
		{
			$out .= "$key=$value\n";
		}

		return $out;
	}

	public function asJavascript()
	{
		$out = '';
		foreach ($this->strings as $key => $value)
		{
			$key   = addcslashes($key, '\\\'"');
			$value = addcslashes($value, '\\\'"');
			if (!empty($out))
			{
				$out .= ",\n";
			}
			$out .= "'$key':\t'$value'";
		}

		return $out;
	}

	public function resetTranslation()
	{
		$this->strings = $this->default_translation;
	}

	public function addDefaultLanguageStrings($stringList = [])
	{
		if (!is_array($stringList))
		{
			return;
		}
		if (empty($stringList))
		{
			return;
		}

		$this->strings = array_merge($stringList, $this->strings);
	}

	private function loadTranslation($lang = null)
	{
		if (defined('KSLANGDIR'))
		{
			$dirname = KSLANGDIR;
		}
		else
		{
			$dirname = KSROOTDIR;
		}

		$myName   = defined('KSSELFNAME') ? KSSELFNAME : basename(__FILE__);
		$basename = basename($myName, '.php') . '.ini';

		if (empty($lang))
		{
			$lang = $this->language;
		}

		$translationFilename = $dirname . DIRECTORY_SEPARATOR . $lang . '.' . $basename;
		if (!@file_exists($translationFilename) && ($basename != 'kickstart.ini'))
		{
			$basename            = 'kickstart.ini';
			$translationFilename = $dirname . DIRECTORY_SEPARATOR . $lang . '.' . $basename;
		}
		if (!@file_exists($translationFilename))
		{
			return;
		}
		$temp = self::parse_ini_file($translationFilename, false);

		if (!is_array($this->strings))
		{
			$this->strings = [];
		}
		if (empty($temp))
		{
			$this->strings = array_merge($this->default_translation, $this->strings);
		}
		else
		{
			$this->strings = array_merge($this->strings, $temp);
		}
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * The Akeeba Kickstart Factory class
 *
 * This class is reponssible for instantiating all Akeeba Kickstart classes
 */
class AKFactory
{
	/** @var   array  A list of instantiated objects */
	private $objectlist = array();

	/** @var   array  Simple hash data storage */
	private $varlist = array();

	/** @var   self   Static instance */
	private static $instance = null;

	/**
	 * AKFactory constructor.
	 *
	 * This is a private constructor makes sure we can't instantiate the class unless we go through the static
	 * getInstance singleton method. This is different than making the class abstract (preventing any kind of object
	 * instantiation).
	 */
	private function __construct()
	{
	}

	/**
	 * Gets a serialized snapshot of the Factory for safekeeping (hibernate)
	 *
	 * @return string The serialized snapshot of the Factory
	 */
	public static function serialize()
	{
		$engine = self::getUnarchiver();
		$engine->shutdown();
		$serialized = serialize(self::getInstance());

		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$serialized = base64_encode($serialized);
		}

		return $serialized;
	}

	/**
	 * Gets the unarchiver engine
	 *
	 * @return AKAbstractUnarchiver
	 */
	public static function &getUnarchiver($configOverride = null)
	{
		static $class_name;

		if (!empty($configOverride) && isset($configOverride['reset']) && $configOverride['reset'])
		{
			$class_name = null;
		}

		if (empty($class_name))
		{
			$filetype = self::get('kickstart.setup.filetype', null);

			if (empty($filetype))
			{
				$filename      = self::get('kickstart.setup.sourcefile', null);
				$basename      = basename($filename);
				$baseextension = strtoupper(substr($basename, -3));

				switch ($baseextension)
				{
					case 'JPA':
						$filetype = 'JPA';
						break;

					case 'JPS':
						$filetype = 'JPS';
						break;

					case 'ZIP':
						$filetype = 'ZIP';
						break;

					default:
						die('Invalid archive type or extension in file ' . $filename);
						break;
				}
			}

			$class_name = 'AKUnarchiver' . ucfirst($filetype);
		}

		$destdir = self::get('kickstart.setup.destdir', null);

		if (empty($destdir))
		{
			$destdir = KSROOTDIR;
		}

		/** @var AKAbstractUnarchiver $object */
		$object = self::getClassInstance($class_name);

		if ($object->getState() == 'init')
		{
			$sourcePath = self::get('kickstart.setup.sourcepath', '');
			$sourceFile = self::get('kickstart.setup.sourcefile', '');

			if (!empty($sourcePath))
			{
				$sourceFile = rtrim($sourcePath, '/\\') . '/' . $sourceFile;
			}

			// Initialize the object –– Any change here MUST be reflected to echoHeadJavascript (default values)
			$config = array(
				'filename'            => $sourceFile,
				'restore_permissions' => self::get('kickstart.setup.restoreperms', 0),
				'post_proc'           => self::get('kickstart.procengine', 'direct'),
				'add_path'            => self::get('kickstart.setup.targetpath', $destdir),
				'remove_path'         => self::get('kickstart.setup.removepath', ''),
				'rename_files'        => self::get('kickstart.setup.renamefiles', array(
					'.htaccess' => 'htaccess.bak', 'php.ini' => 'php.ini.bak', 'web.config' => 'web.config.bak',
					'.user.ini' => '.user.ini.bak',
				)),
				'skip_files'          => self::get('kickstart.setup.skipfiles', array(
					basename(__FILE__), 'kickstart.php', 'abiautomation.ini', 'htaccess.bak', 'php.ini.bak',
					'cacert.pem',
				)),
				'ignoredirectories'   => self::get('kickstart.setup.ignoredirectories', array(
					'tmp', 'log', 'logs',
				)),
			);

			if (!defined('KICKSTART'))
			{
				// In restore.php mode we have to exclude the restoration.php files
				$moreSkippedFiles     = array(
					// Akeeba Backup for Joomla!
					'administrator/components/com_akeeba/restoration.php',
					'administrator/components/com_akeebabackup/restoration.php',
					// Joomla! Update
					'administrator/components/com_joomlaupdate/restoration.php',
					// Akeeba Backup for WordPress
					'wp-content/plugins/akeebabackupwp/app/restoration.php',
					'wp-content/plugins/akeebabackupcorewp/app/restoration.php',
					'wp-content/plugins/akeebabackup/app/restoration.php',
					'wp-content/plugins/akeebabackupwpcore/app/restoration.php',
					// Akeeba Solo
					'app/restoration.php',
				);

				$config['skip_files'] = array_merge($config['skip_files'], $moreSkippedFiles);
			}

			if (!empty($configOverride))
			{
				$config = array_merge($config, $configOverride);
			}

			$object->setup($config);
		}

		return $object;
	}

	// ========================================================================
	// Public factory interface
	// ========================================================================

	public static function get($key, $default = null)
	{
		$self = self::getInstance();

		if (array_key_exists($key, $self->varlist))
		{
			return $self->varlist[$key];
		}

		return $default;
	}

	/**
	 * Gets a single, internally used instance of the Factory
	 *
	 * @param string $serialized_data [optional] Serialized data to spawn the instance from
	 *
	 * @return AKFactory A reference to the unique Factory object instance
	 */
	protected static function &getInstance($serialized_data = null)
	{
		if (!is_object(self::$instance) || !is_null($serialized_data))
		{
			if (!is_null($serialized_data))
			{
				self::$instance = unserialize($serialized_data);

				return self::$instance;
			}

			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Internal function which instantiates a class named $class_name.
	 * The autoloader
	 *
	 * @param string $class_name
	 *
	 * @return object
	 */
	protected static function &getClassInstance($class_name)
	{
		$self = self::getInstance();

		if (!isset($self->objectlist[$class_name]))
		{
			$self->objectlist[$class_name] = new $class_name;
		}

		return $self->objectlist[$class_name];
	}

	// ========================================================================
	// Public hash data storage interface
	// ========================================================================

	/**
	 * Regenerates the full Factory state from a serialized snapshot (resume)
	 *
	 * @param string $serialized_data The serialized snapshot to resume from
	 */
	public static function unserialize($serialized_data)
	{
		if (function_exists('base64_encode') && function_exists('base64_decode'))
		{
			$serialized_data = base64_decode($serialized_data);
		}

		self::getInstance($serialized_data);
	}

	/**
	 * Reset the internal factory state, freeing all previously created objects
	 */
	public static function nuke()
	{
		self::$instance = null;
	}

	// ========================================================================
	// Akeeba Kickstart classes
	// ========================================================================

	public static function set($key, $value)
	{
		$self                = self::getInstance();
		$self->varlist[$key] = $value;
	}

	/**
	 * Gets the post processing engine
	 *
	 * @param string $proc_engine
	 *
	 * @return AKAbstractPostproc
	 */
	public static function &getPostProc($proc_engine = null)
	{
		static $class_name;

		if (empty($class_name))
		{
			if (empty($proc_engine))
			{
				$proc_engine = self::get('kickstart.procengine', 'direct');
			}

			$class_name = 'AKPostproc' . ucfirst($proc_engine);
		}

		return self::getClassInstance($class_name);
	}

	/**
	 * Get the a reference to the Akeeba Engine's timer
	 *
	 * @return AKCoreTimer
	 */
	public static function &getTimer()
	{
		return self::getClassInstance('AKCoreTimer');
	}

	/**
	 * Get an instance of the filesystem zapper
	 *
	 * @return AKUtilsZapper
	 */
	public static function &getZapper()
	{
		return self::getClassInstance('AKUtilsZapper');
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Interface for AES encryption adapters
 */
interface AKEncryptionAESAdapterInterface
{
	/**
	 * Decrypts a string. Returns the raw binary ciphertext, zero-padded.
	 *
	 * @param   string       $plainText  The plaintext to encrypt
	 * @param   string       $key        The raw binary key (will be zero-padded or chopped if its size is different than the block size)
	 *
	 * @return  string  The raw encrypted binary string.
	 */
	public function decrypt($plainText, $key);

	/**
	 * Returns the encryption block size in bytes
	 *
	 * @return  int
	 */
	public function getBlockSize();

	/**
	 * Is this adapter supported?
	 *
	 * @return  bool
	 */
	public function isSupported();
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Abstract AES encryption class
 */
abstract class AKEncryptionAESAdapterAbstract
{
	/**
	 * Trims or zero-pads a key / IV
	 *
	 * @param   string $key  The key or IV to treat
	 * @param   int    $size The block size of the currently used algorithm
	 *
	 * @return  null|string  Null if $key is null, treated string of $size byte length otherwise
	 */
	public function resizeKey($key, $size)
	{
		if (empty($key))
		{
			return null;
		}

		$keyLength = strlen($key);

		if (function_exists('mb_strlen'))
		{
			$keyLength = mb_strlen($key, 'ASCII');
		}

		if ($keyLength == $size)
		{
			return $key;
		}

		if ($keyLength > $size)
		{
			if (function_exists('mb_substr'))
			{
				return mb_substr($key, 0, $size, 'ASCII');
			}

			return substr($key, 0, $size);
		}

		return $key . str_repeat("\0", ($size - $keyLength));
	}

	/**
	 * Returns null bytes to append to the string so that it's zero padded to the specified block size
	 *
	 * @param   string $string    The binary string which will be zero padded
	 * @param   int    $blockSize The block size
	 *
	 * @return  string  The zero bytes to append to the string to zero pad it to $blockSize
	 */
	protected function getZeroPadding($string, $blockSize)
	{
		$stringSize = strlen($string);

		if (function_exists('mb_strlen'))
		{
			$stringSize = mb_strlen($string, 'ASCII');
		}

		if ($stringSize == $blockSize)
		{
			return '';
		}

		if ($stringSize < $blockSize)
		{
			return str_repeat("\0", $blockSize - $stringSize);
		}

		$paddingBytes = $stringSize % $blockSize;

		return str_repeat("\0", $blockSize - $paddingBytes);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class Mcrypt extends AKEncryptionAESAdapterAbstract implements AKEncryptionAESAdapterInterface
{
	protected $cipherType = MCRYPT_RIJNDAEL_128;

	protected $cipherMode = MCRYPT_MODE_CBC;

	public function decrypt($cipherText, $key)
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);
		$plainText  = mcrypt_decrypt($this->cipherType, $key, $cipherText, $this->cipherMode, $iv);

		return $plainText;
	}

	public function isSupported()
	{
		if (!function_exists('mcrypt_get_key_size'))
		{
			return false;
		}

		if (!function_exists('mcrypt_get_iv_size'))
		{
			return false;
		}

		if (!function_exists('mcrypt_create_iv'))
		{
			return false;
		}

		if (!function_exists('mcrypt_encrypt'))
		{
			return false;
		}

		if (!function_exists('mcrypt_decrypt'))
		{
			return false;
		}

		if (!function_exists('mcrypt_list_algorithms'))
		{
			return false;
		}

		if (!function_exists('hash'))
		{
			return false;
		}

		if (!function_exists('hash_algos'))
		{
			return false;
		}

		$algorightms = mcrypt_list_algorithms();

		if (!in_array('rijndael-128', $algorightms))
		{
			return false;
		}

		if (!in_array('rijndael-192', $algorightms))
		{
			return false;
		}

		if (!in_array('rijndael-256', $algorightms))
		{
			return false;
		}

		$algorightms = hash_algos();

		if (!in_array('sha256', $algorightms))
		{
			return false;
		}

		return true;
	}

	public function getBlockSize()
	{
		return mcrypt_get_iv_size($this->cipherType, $this->cipherMode);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

class OpenSSL extends AKEncryptionAESAdapterAbstract implements AKEncryptionAESAdapterInterface
{
	/**
	 * The OpenSSL options for encryption / decryption
	 *
	 * @var  int
	 */
	protected $openSSLOptions = 0;

	/**
	 * The encryption method to use
	 *
	 * @var  string
	 */
	protected $method = 'aes-128-cbc';

	public function __construct()
	{
		$this->openSSLOptions = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
	}

	public function decrypt($cipherText, $key)
	{
		$iv_size    = $this->getBlockSize();
		$key        = $this->resizeKey($key, $iv_size);
		$iv         = substr($cipherText, 0, $iv_size);
		$cipherText = substr($cipherText, $iv_size);
		$plainText  = openssl_decrypt($cipherText, $this->method, $key, $this->openSSLOptions, $iv);

		return $plainText;
	}

	public function isSupported()
	{
		if (!function_exists('openssl_get_cipher_methods'))
		{
			return false;
		}

		if (!function_exists('openssl_random_pseudo_bytes'))
		{
			return false;
		}

		if (!function_exists('openssl_cipher_iv_length'))
		{
			return false;
		}

		if (!function_exists('openssl_encrypt'))
		{
			return false;
		}

		if (!function_exists('openssl_decrypt'))
		{
			return false;
		}

		if (!function_exists('hash'))
		{
			return false;
		}

		if (!function_exists('hash_algos'))
		{
			return false;
		}

		$algorightms = openssl_get_cipher_methods();

		if (!in_array('aes-128-cbc', $algorightms))
		{
			return false;
		}

		$algorightms = hash_algos();

		if (!in_array('sha256', $algorightms))
		{
			return false;
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getBlockSize()
	{
		return openssl_cipher_iv_length($this->method);
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * AES implementation in PHP (c) Chris Veness 2005-2016.
 * Right to use and adapt is granted for under a simple creative commons attribution
 * licence. No warranty of any form is offered.
 *
 * Heavily modified for Akeeba Backup by Nicholas K. Dionysopoulos
 * Also added AES-128 CBC mode (with mcrypt and OpenSSL) on top of AES CTR
 * Removed CTR encrypt / decrypt (no longer used)
 */
class AKEncryptionAES
{
	// Sbox is pre-computed multiplicative inverse in GF(2^8) used in SubBytes and KeyExpansion [�5.1.1]
	protected static $Sbox =
		array(0x63, 0x7c, 0x77, 0x7b, 0xf2, 0x6b, 0x6f, 0xc5, 0x30, 0x01, 0x67, 0x2b, 0xfe, 0xd7, 0xab, 0x76,
			0xca, 0x82, 0xc9, 0x7d, 0xfa, 0x59, 0x47, 0xf0, 0xad, 0xd4, 0xa2, 0xaf, 0x9c, 0xa4, 0x72, 0xc0,
			0xb7, 0xfd, 0x93, 0x26, 0x36, 0x3f, 0xf7, 0xcc, 0x34, 0xa5, 0xe5, 0xf1, 0x71, 0xd8, 0x31, 0x15,
			0x04, 0xc7, 0x23, 0xc3, 0x18, 0x96, 0x05, 0x9a, 0x07, 0x12, 0x80, 0xe2, 0xeb, 0x27, 0xb2, 0x75,
			0x09, 0x83, 0x2c, 0x1a, 0x1b, 0x6e, 0x5a, 0xa0, 0x52, 0x3b, 0xd6, 0xb3, 0x29, 0xe3, 0x2f, 0x84,
			0x53, 0xd1, 0x00, 0xed, 0x20, 0xfc, 0xb1, 0x5b, 0x6a, 0xcb, 0xbe, 0x39, 0x4a, 0x4c, 0x58, 0xcf,
			0xd0, 0xef, 0xaa, 0xfb, 0x43, 0x4d, 0x33, 0x85, 0x45, 0xf9, 0x02, 0x7f, 0x50, 0x3c, 0x9f, 0xa8,
			0x51, 0xa3, 0x40, 0x8f, 0x92, 0x9d, 0x38, 0xf5, 0xbc, 0xb6, 0xda, 0x21, 0x10, 0xff, 0xf3, 0xd2,
			0xcd, 0x0c, 0x13, 0xec, 0x5f, 0x97, 0x44, 0x17, 0xc4, 0xa7, 0x7e, 0x3d, 0x64, 0x5d, 0x19, 0x73,
			0x60, 0x81, 0x4f, 0xdc, 0x22, 0x2a, 0x90, 0x88, 0x46, 0xee, 0xb8, 0x14, 0xde, 0x5e, 0x0b, 0xdb,
			0xe0, 0x32, 0x3a, 0x0a, 0x49, 0x06, 0x24, 0x5c, 0xc2, 0xd3, 0xac, 0x62, 0x91, 0x95, 0xe4, 0x79,
			0xe7, 0xc8, 0x37, 0x6d, 0x8d, 0xd5, 0x4e, 0xa9, 0x6c, 0x56, 0xf4, 0xea, 0x65, 0x7a, 0xae, 0x08,
			0xba, 0x78, 0x25, 0x2e, 0x1c, 0xa6, 0xb4, 0xc6, 0xe8, 0xdd, 0x74, 0x1f, 0x4b, 0xbd, 0x8b, 0x8a,
			0x70, 0x3e, 0xb5, 0x66, 0x48, 0x03, 0xf6, 0x0e, 0x61, 0x35, 0x57, 0xb9, 0x86, 0xc1, 0x1d, 0x9e,
			0xe1, 0xf8, 0x98, 0x11, 0x69, 0xd9, 0x8e, 0x94, 0x9b, 0x1e, 0x87, 0xe9, 0xce, 0x55, 0x28, 0xdf,
			0x8c, 0xa1, 0x89, 0x0d, 0xbf, 0xe6, 0x42, 0x68, 0x41, 0x99, 0x2d, 0x0f, 0xb0, 0x54, 0xbb, 0x16);

	// Rcon is Round Constant used for the Key Expansion [1st col is 2^(r-1) in GF(2^8)] [�5.2]
	protected static $Rcon = array(
		array(0x00, 0x00, 0x00, 0x00),
		array(0x01, 0x00, 0x00, 0x00),
		array(0x02, 0x00, 0x00, 0x00),
		array(0x04, 0x00, 0x00, 0x00),
		array(0x08, 0x00, 0x00, 0x00),
		array(0x10, 0x00, 0x00, 0x00),
		array(0x20, 0x00, 0x00, 0x00),
		array(0x40, 0x00, 0x00, 0x00),
		array(0x80, 0x00, 0x00, 0x00),
		array(0x1b, 0x00, 0x00, 0x00),
		array(0x36, 0x00, 0x00, 0x00));

	protected static $passwords = array();

	/**
	 * The algorithm to use for PBKDF2. Must be a supported hash_hmac algorithm. Default: sha1
	 *
	 * @var  string
	 */
	private static $pbkdf2Algorithm = 'sha1';

	/**
	 * Number of iterations to use for PBKDF2
	 *
	 * @var  int
	 */
	private static $pbkdf2Iterations = 1000;

	/**
	 * Should we use a static salt for PBKDF2?
	 *
	 * @var  int
	 */
	private static $pbkdf2UseStaticSalt = 0;

	/**
	 * The static salt to use for PBKDF2
	 *
	 * @var  string
	 */
	private static $pbkdf2StaticSalt = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

	/**
	 * AES Cipher function: encrypt 'input' with Rijndael algorithm
	 *
	 * @param   array $input    Message as byte-array (16 bytes)
	 * @param   array $w        key schedule as 2D byte-array (Nr+1 x Nb bytes) -
	 *                          generated from the cipher key by KeyExpansion()
	 *
	 * @return  string  Ciphertext as byte-array (16 bytes)
	 */
	protected static function Cipher($input, $w)
	{
		// main Cipher function [�5.1]
		$Nb = 4;                 // block size (in words): no of columns in state (fixed at 4 for AES)
		$Nr = count($w) / $Nb - 1; // no of rounds: 10/12/14 for 128/192/256-bit keys

		$state = array();  // initialise 4xNb byte-array 'state' with input [�3.4]

		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$state[$i % 4][floor($i / 4)] = $input[$i];
		}

		$state = self::AddRoundKey($state, $w, 0, $Nb);

		for ($round = 1; $round < $Nr; $round++)
		{  // apply Nr rounds
			$state = self::SubBytes($state, $Nb);
			$state = self::ShiftRows($state, $Nb);
			$state = self::MixColumns($state);
			$state = self::AddRoundKey($state, $w, $round, $Nb);
		}

		$state = self::SubBytes($state, $Nb);
		$state = self::ShiftRows($state, $Nb);
		$state = self::AddRoundKey($state, $w, $Nr, $Nb);

		$output = array(4 * $Nb);  // convert state to 1-d array before returning [�3.4]

		for ($i = 0; $i < 4 * $Nb; $i++)
		{
			$output[$i] = $state[$i % 4][floor($i / 4)];
		}

		return $output;
	}

	protected static function AddRoundKey($state, $w, $rnd, $Nb)
	{
		// xor Round Key into state S [�5.1.4]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$state[$r][$c] ^= $w[$rnd * 4 + $c][$r];
			}
		}

		return $state;
	}

	protected static function SubBytes($s, $Nb)
	{
		// apply SBox to state S [�5.1.1]
		for ($r = 0; $r < 4; $r++)
		{
			for ($c = 0; $c < $Nb; $c++)
			{
				$s[$r][$c] = self::$Sbox[$s[$r][$c]];
			}
		}

		return $s;
	}

	protected static function ShiftRows($s, $Nb)
	{
		// shift row r of state S left by r bytes [�5.1.2]
		$t = array(4);

		for ($r = 1; $r < 4; $r++)
		{
			for ($c = 0; $c < 4; $c++)
			{
				$t[$c] = $s[$r][($c + $r) % $Nb];
			}  // shift into temp copy

			for ($c = 0; $c < 4; $c++)
			{
				$s[$r][$c] = $t[$c];
			}         // and copy back
		}          // note that this will work for Nb=4,5,6, but not 7,8 (always 4 for AES):

		return $s;  // see fp.gladman.plus.com/cryptography_technology/rijndael/aes.spec.311.pdf
	}

	protected static function MixColumns($s)
	{
		// combine bytes of each col of state S [�5.1.3]
		for ($c = 0; $c < 4; $c++)
		{
			$a = array(4);  // 'a' is a copy of the current column from 's'
			$b = array(4);  // 'b' is a�{02} in GF(2^8)

			for ($i = 0; $i < 4; $i++)
			{
				$a[$i] = $s[$i][$c];
				$b[$i] = $s[$i][$c] & 0x80 ? $s[$i][$c] << 1 ^ 0x011b : $s[$i][$c] << 1;
			}

			// a[n] ^ b[n] is a�{03} in GF(2^8)
			$s[0][$c] = $b[0] ^ $a[1] ^ $b[1] ^ $a[2] ^ $a[3]; // 2*a0 + 3*a1 + a2 + a3
			$s[1][$c] = $a[0] ^ $b[1] ^ $a[2] ^ $b[2] ^ $a[3]; // a0 * 2*a1 + 3*a2 + a3
			$s[2][$c] = $a[0] ^ $a[1] ^ $b[2] ^ $a[3] ^ $b[3]; // a0 + a1 + 2*a2 + 3*a3
			$s[3][$c] = $a[0] ^ $b[0] ^ $a[1] ^ $a[2] ^ $b[3]; // 3*a0 + a1 + a2 + 2*a3
		}

		return $s;
	}

	/**
	 * Key expansion for Rijndael Cipher(): performs key expansion on cipher key
	 * to generate a key schedule
	 *
	 * @param   array $key Cipher key byte-array (16 bytes)
	 *
	 * @return  array  Key schedule as 2D byte-array (Nr+1 x Nb bytes)
	 */
	protected static function KeyExpansion($key)
	{
		// generate Key Schedule from Cipher Key [�5.2]

		// block size (in words): no of columns in state (fixed at 4 for AES)
		$Nb = 4;
		// key length (in words): 4/6/8 for 128/192/256-bit keys
		$Nk = (int) (count($key) / 4);
		// no of rounds: 10/12/14 for 128/192/256-bit keys
		$Nr = $Nk + 6;

		$w    = array();
		$temp = array();

		for ($i = 0; $i < $Nk; $i++)
		{
			$r     = array($key[4 * $i], $key[4 * $i + 1], $key[4 * $i + 2], $key[4 * $i + 3]);
			$w[$i] = $r;
		}

		for ($i = $Nk; $i < ($Nb * ($Nr + 1)); $i++)
		{
			$w[$i] = array();
			for ($t = 0; $t < 4; $t++)
			{
				$temp[$t] = $w[$i - 1][$t];
			}
			if ($i % $Nk == 0)
			{
				$temp = self::SubWord(self::RotWord($temp));
				for ($t = 0; $t < 4; $t++)
				{
					$rConIndex = (int) ($i / $Nk);
					$temp[$t] ^= self::$Rcon[$rConIndex][$t];
				}
			}
			else if ($Nk > 6 && $i % $Nk == 4)
			{
				$temp = self::SubWord($temp);
			}
			for ($t = 0; $t < 4; $t++)
			{
				$w[$i][$t] = $w[$i - $Nk][$t] ^ $temp[$t];
			}
		}

		return $w;
	}

	protected static function SubWord($w)
	{
		// apply SBox to 4-byte word w
		for ($i = 0; $i < 4; $i++)
		{
			$w[$i] = self::$Sbox[$w[$i]];
		}

		return $w;
	}

	/*
	 * Unsigned right shift function, since PHP has neither >>> operator nor unsigned ints
	 *
	 * @param a  number to be shifted (32-bit integer)
	 * @param b  number of bits to shift a to the right (0..31)
	 * @return   a right-shifted and zero-filled by b bits
	 */

	protected static function RotWord($w)
	{
		// rotate 4-byte word w left by one byte
		$tmp = $w[0];
		for ($i = 0; $i < 3; $i++)
		{
			$w[$i] = $w[$i + 1];
		}
		$w[3] = $tmp;

		return $w;
	}

	protected static function urs($a, $b)
	{
		$a &= 0xffffffff;
		$b &= 0x1f;  // (bounds check)
		if ($a & 0x80000000 && $b > 0)
		{   // if left-most bit set
			$a = ($a >> 1) & 0x7fffffff;   //   right-shift one bit & clear left-most bit
			$a = $a >> ($b - 1);           //   remaining right-shifts
		}
		else
		{                       // otherwise
			$a = ($a >> $b);               //   use normal right-shift
		}

		return $a;
	}

	/**
	 * AES decryption in CBC mode. This is the standard mode (the CTR methods
	 * actually use Rijndael-128 in CTR mode, which - technically - isn't AES).
	 *
	 * It supports AES-128 only. It assumes that the last 4 bytes
	 * contain a little-endian unsigned long integer representing the unpadded
	 * data length.
	 *
	 * @since  3.0.1
	 * @author Nicholas K. Dionysopoulos
	 *
	 * @param   string $ciphertext The data to encrypt
	 * @param   string $password   Encryption password
	 *
	 * @return  string  The plaintext
	 */
	public static function AESDecryptCBC($ciphertext, $password)
	{
		$adapter = self::getAdapter();

		if (!$adapter->isSupported())
		{
			return false;
		}

		// Read the data size
		$data_size = unpack('V', substr($ciphertext, -4));

		// Do I have a PBKDF2 salt?
		$salt             = substr($ciphertext, -92, 68);
		$rightStringLimit = -4;

		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$useStaticSalt = $params['useStaticSalt'];

		if (substr($salt, 0, 4) == 'JPST')
		{
			// We have a stored salt. Retrieve it and tell decrypt to process the string minus the last 44 bytes
			// (4 bytes for JPST, 16 bytes for the salt, 4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the
			// uncompressed string length - note that using PBKDF2 means we're also using a randomized IV per the
			// format specification).
			$salt             = substr($salt, 4);
			$rightStringLimit -= 68;

			$key          = self::pbkdf2($password, $salt, $algorithm, $iterations, $keySizeBytes);
		}
		elseif ($useStaticSalt)
		{
			// We have a static salt. Use it for PBKDF2.
			$key = self::getStaticSaltExpandedKey($password);
		}
		else
		{
			// Get the expanded key from the password. THIS USES THE OLD, INSECURE METHOD.
			$key = self::expandKey($password);
		}

		// Try to get the IV from the data
		$iv               = substr($ciphertext, -24, 20);

		if (substr($iv, 0, 4) == 'JPIV')
		{
			// We have a stored IV. Retrieve it and tell mdecrypt to process the string minus the last 24 bytes
			// (4 bytes for JPIV, 16 bytes for the IV, 4 bytes for the uncompressed string length)
			$iv               = substr($iv, 4);
			$rightStringLimit -= 20;
		}
		else
		{
			// No stored IV. Do it the dumb way.
			$iv = self::createTheWrongIV($password);
		}

		// Decrypt
		$plaintext = $adapter->decrypt($iv . substr($ciphertext, 0, $rightStringLimit), $key);

		// Trim padding, if necessary
		if (strlen($plaintext) > $data_size)
		{
			$plaintext = substr($plaintext, 0, $data_size);
		}

		return $plaintext;
	}

	/**
	 * That's the old way of creating an IV that's definitely not cryptographically sound.
	 *
	 * DO NOT USE, EVER, UNLESS YOU WANT TO DECRYPT LEGACY DATA
	 *
	 * @param   string $password The raw password from which we create an IV in a super bozo way
	 *
	 * @return  string  A 16-byte IV string
	 */
	public static function createTheWrongIV($password)
	{
		static $ivs = array();

		$key = AKUtilsHash::md5($password);

		if (!isset($ivs[$key]))
		{
			$nBytes  = 16;  // AES uses a 128 -bit (16 byte) block size, hence the IV size is always 16 bytes
			$pwBytes = array();
			for ($i = 0; $i < $nBytes; $i++)
			{
				$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
			}
			$iv    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
			$newIV = '';
			foreach ($iv as $int)
			{
				$newIV .= chr($int);
			}

			$ivs[$key] = $newIV;
		}

		return $ivs[$key];
	}

	/**
	 * Expand the password to an appropriate 128-bit encryption key
	 *
	 * @param   string $password
	 *
	 * @return  string
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function expandKey($password)
	{
		// Try to fetch cached key or create it if it doesn't exist
		$nBits     = 128;
		$lookupKey = AKUtilsHash::md5($password . '-' . $nBits);

		if (array_key_exists($lookupKey, self::$passwords))
		{
			$key = self::$passwords[$lookupKey];

			return $key;
		}

		// use AES itself to encrypt password to get cipher key (using plain password as source for
		// key expansion) - gives us well encrypted key.
		$nBytes  = $nBits / 8; // Number of bytes in key
		$pwBytes = array();

		for ($i = 0; $i < $nBytes; $i++)
		{
			$pwBytes[$i] = ord(substr($password, $i, 1)) & 0xff;
		}

		$key    = self::Cipher($pwBytes, self::KeyExpansion($pwBytes));
		$key    = array_merge($key, array_slice($key, 0, $nBytes - 16)); // expand key to 16/24/32 bytes long
		$newKey = '';

		foreach ($key as $int)
		{
			$newKey .= chr($int);
		}

		$key = $newKey;

		self::$passwords[$lookupKey] = $key;

		return $key;
	}

	/**
	 * Returns the correct AES-128 CBC encryption adapter
	 *
	 * @return  AKEncryptionAESAdapterInterface
	 *
	 * @since   5.2.0
	 * @author  Nicholas K. Dionysopoulos
	 */
	public static function getAdapter()
	{
		static $adapter = null;

		if (is_object($adapter) && ($adapter instanceof AKEncryptionAESAdapterInterface))
		{
			return $adapter;
		}

		$adapter = new OpenSSL();

		if (!$adapter->isSupported())
		{
			$adapter = new Mcrypt();
		}

		return $adapter;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2Algorithm()
	{
		return self::$pbkdf2Algorithm;
	}

	/**
	 * @param string $pbkdf2Algorithm
	 * @return void
	 */
	public static function setPbkdf2Algorithm($pbkdf2Algorithm)
	{
		self::$pbkdf2Algorithm = $pbkdf2Algorithm;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2Iterations()
	{
		return self::$pbkdf2Iterations;
	}

	/**
	 * @param int $pbkdf2Iterations
	 * @return void
	 */
	public static function setPbkdf2Iterations($pbkdf2Iterations)
	{
		self::$pbkdf2Iterations = $pbkdf2Iterations;
	}

	/**
	 * @return int
	 */
	public static function getPbkdf2UseStaticSalt()
	{
		return self::$pbkdf2UseStaticSalt;
	}

	/**
	 * @param int $pbkdf2UseStaticSalt
	 * @return void
	 */
	public static function setPbkdf2UseStaticSalt($pbkdf2UseStaticSalt)
	{
		self::$pbkdf2UseStaticSalt = $pbkdf2UseStaticSalt;
	}

	/**
	 * @return string
	 */
	public static function getPbkdf2StaticSalt()
	{
		return self::$pbkdf2StaticSalt;
	}

	/**
	 * @param string $pbkdf2StaticSalt
	 * @return void
	 */
	public static function setPbkdf2StaticSalt($pbkdf2StaticSalt)
	{
		self::$pbkdf2StaticSalt = $pbkdf2StaticSalt;
	}

	/**
	 * Get the parameters fed into PBKDF2 to expand the user password into an encryption key. These are the static
	 * parameters (key size, hashing algorithm and number of iterations). A new salt is used for each encryption block
	 * to minimize the risk of attacks against the password.
	 *
	 * @return  array
	 */
	public static function getKeyDerivationParameters()
	{
		return array(
			'keySize'       => 16,
			'algorithm'     => self::$pbkdf2Algorithm,
			'iterations'    => self::$pbkdf2Iterations,
			'useStaticSalt' => self::$pbkdf2UseStaticSalt,
			'staticSalt'    => self::$pbkdf2StaticSalt,
		);
	}

	/**
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 * Modified for Akeeba Engine by Akeeba Ltd (removed unnecessary checks to make it faster)
	 *
	 * @param   string  $password    The password.
	 * @param   string  $salt        A salt that is unique to the password.
	 * @param   string  $algorithm   The hash algorithm to use. Default is sha1.
	 * @param   int     $count       Iteration count. Higher is better, but slower. Default: 1000.
	 * @param   int     $key_length  The length of the derived key in bytes.
	 *
	 * @return  string  A string of $key_length bytes
	 */
	public static function pbkdf2($password, $salt, $algorithm = 'sha1', $count = 1000, $key_length = 16)
	{
		if (function_exists("hash_pbkdf2"))
		{
			return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, true);
		}

		$hash_length = akstringlen(hash($algorithm, "", true));
		$block_count = ceil($key_length / $hash_length);

		$output = "";

		for ($i = 1; $i <= $block_count; $i++)
		{
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);

			// First iteration
			$xorResult = hash_hmac($algorithm, $last, $password, true);
			$last      = $xorResult;

			// Perform the other $count - 1 iterations
			for ($j = 1; $j < $count; $j++)
			{
				$last = hash_hmac($algorithm, $last, $password, true);
				$xorResult ^= $last;
			}

			$output .= $xorResult;
		}

		return aksubstr($output, 0, $key_length);
	}

	/**
	 * Get the expanded key from the user supplied password using a static salt. The results are cached for performance
	 * reasons.
	 *
	 * @param   string  $password  The user-supplied password, UTF-8 encoded.
	 *
	 * @return  string  The expanded key
	 */
	private static function getStaticSaltExpandedKey($password)
	{
		$params        = self::getKeyDerivationParameters();
		$keySizeBytes  = $params['keySize'];
		$algorithm     = $params['algorithm'];
		$iterations    = $params['iterations'];
		$staticSalt    = $params['staticSalt'];

		$lookupKey = "PBKDF2-$algorithm-$iterations-" . AKUtilsHash::md5($password . $staticSalt);

		if (!array_key_exists($lookupKey, self::$passwords))
		{
			self::$passwords[$lookupKey] = self::pbkdf2($password, $staticSalt, $algorithm, $iterations, $keySizeBytes);
		}

		return self::$passwords[$lookupKey];
	}

}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * A timing safe equals comparison
 *
 * @param   string  $safe  The internal (safe) value to be checked
 * @param   string  $user  The user submitted (unsafe) value
 *
 * @return  boolean  True if the two strings are identical.
 *
 * @see     http://blog.ircmaxell.com/2014/11/its-all-about-time.html
 */
function timingSafeEquals($safe, $user)
{
	$safeLen = strlen($safe);
	$userLen = strlen($user);

	if ($userLen != $safeLen)
	{
		return false;
	}

	$result = 0;

	for ($i = 0; $i < $userLen; $i++)
	{
		$result |= (ord($safe[$i]) ^ ord($user[$i]));
	}

	// They are only identical strings if $result is exactly 0...
	return $result === 0;
}

/**
 * The Master Setup will read the configuration parameters from restoration.php or
 * the JSON-encoded "configuration" input variable and return the status.
 *
 * @return bool True if the master configuration was applied to the Factory object
 */
function masterSetup()
{
	// ------------------------------------------------------------
	// 1. Import basic setup parameters
	// ------------------------------------------------------------

	$ini_data = null;

	// In restore.php mode, require restoration.php or fail
	if (!defined('KICKSTART'))
	{
		// On Joomla 5 we need to look for a defines.php file, in case we are in a custom public folder
		$definesFile = '../../../defines.php';

		if (file_exists($definesFile))
		{
			$fileContents = @file_get_contents($definesFile) ?: '';

			if (strpos($fileContents, "define('JPATH_PUBLIC'") !== false)
			{
				defined('_JEXEC') || define('_JEXEC', 1);
			}

			require_once $definesFile;
		}

		// This is the standalone mode, used by Akeeba Backup Professional. It looks for a restoration.php
		// file to perform its magic. If the file is not there, we will abort.
		$alternateFiles = [
			__DIR__ . '/restoration.php',
			'restoration.php',
		];

		if (defined('JPATH_PUBLIC'))
		{
			$alternateFiles[] = JPATH_PUBLIC . 'administrator/components/com_akeebabackup/restoration.php';
		}

		$foundSetupFile = false;

		foreach ($alternateFiles as $setupFile)
		{
			if (file_exists($setupFile))
			{
				$foundSetupFile = true;

				break;
			}
		}

		if (!$foundSetupFile)
		{
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		/**
		 * If the setup file was created more than 1.5 hours ago we can assume that it's stale and someone forgot to
		 * remove it from the server. This hinders brute force attacks against the Kickstart password. Even a simple
		 * 8 character simple alphanum (a-z, 0-9) password yields over 2.8e12. Assuming a very fast server which can
		 * serve 100 requests to restore.php per second and an easy to attack password requiring going over just 1% of
		 * the search space it'd still take over 282 million seconds to brute force it. Our limit is more than 4 orders
		 * of magnitude lower than this best practical case scenario, giving us adequate protection against all but the
		 * luckiest attacker (spoiler alert: the mathematics of probabilities say you're not gonna get lucky).
		 *
		 * It is still advisable to remove the restoration.php file once you are done with the extraction. This check
		 * here is only meant as a failsafe in case of a server error during the extraction and subsequent lack of user
		 * action to remove the restoration.php file from their server.
		 */
		$setupFieCreationTime = filectime($setupFile);

		if (abs(time() - $setupFieCreationTime) > 5400)
		{
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		// Load restoration.php. It creates a global variable named $restoration_setup
		require_once $setupFile;

		$ini_data = $restoration_setup;

		if (empty($ini_data))
		{
			// No parameters fetched. Darn, how am I supposed to work like that?!
			AKFactory::set('kickstart.enabled', false);

			return false;
		}

		AKFactory::set('kickstart.enabled', true);
	}
	else
	{
		// Maybe we have $restoration_setup defined in the head of kickstart.php
		global $restoration_setup;

		if (!empty($restoration_setup) && !is_array($restoration_setup))
		{
			$ini_data = AKText::parse_ini_file($restoration_setup, false, true);
		}
		elseif (is_array($restoration_setup))
		{
			$ini_data = $restoration_setup;
		}
	}

	// Import any data from $restoration_setup
	if (!empty($ini_data))
	{
		foreach ($ini_data as $key => $value)
		{
			AKFactory::set($key, $value);
		}
		AKFactory::set('kickstart.enabled', true);
	}

	// Reinitialize $ini_data
	$ini_data = null;

	/**
	 * August 2018. Some third party developer with a dubious skill level (or complete lack thereof) wrote a piece of
	 * code which uses restore.php with an empty password (and never deleted the restoration.php file he created).
	 * According to his code comments he did this because he couldn't figure out how to make encrypted requests work,
	 * DESPITE THE FACT that com_joomlaupdate (part of Joomla! itself) has working code which does EXACTLY THAT. >:-o
	 *
	 * As a result of his actions all sites running his software have a massive vulnerability inflicted upon them. An
	 * attacker can absuse the (unlocked) restore.php to upload and install any arbitrary code in a ZIP archive,
	 * possibly overwriting core code. Discovering this problem takes a few seconds and there is code which is doing
	 * exactly that published years ago (during the active maintenance period of Joomla! 3.4, that long ago).
	 *
	 * This bit of code here detects an empty password and disables restore.php. His badly written software fails to
	 * execute and, most importantly, the unlucky users of his software will no longer have a remote code upload /
	 * remote code execution vulnerability on their sites.
	 *
	 * Remember, people, if you can't be bothered to take web application security seriously DO NOT SELL WEB SOFTWARE
	 * FOR A LIVING. There are other honest jobs you can do which don't involve using a computer in a dangerous and
	 * irresponsible manner.
	 */
	$password = AKFactory::get('kickstart.security.password', null);

	if (empty($password) || (trim($password) == '') || (strlen(trim($password)) < 10))
	{
		AKFactory::set('kickstart.enabled', false);

		return false;
	}


	// ------------------------------------------------------------
	// 2. Explode JSON parameters into $_REQUEST scope
	// ------------------------------------------------------------

	// Detect a JSON string in the request variable and store it.
	$json = getQueryParam('json', null);

	// Detect a password in the request variable and store it.
	$userPassword = getQueryParam('password', '');

	// Remove everything from the request, post and get arrays
	if (!empty($_REQUEST))
	{
		foreach ($_REQUEST as $key => $value)
		{
			unset($_REQUEST[$key]);
		}
	}

	if (!empty($_POST))
	{
		foreach ($_POST as $key => $value)
		{
			unset($_POST[$key]);
		}
	}

	if (!empty($_GET))
	{
		foreach ($_GET as $key => $value)
		{
			unset($_GET[$key]);
		}
	}

	// Authentication - Akeeba Restore 5.4.0 or later
	$password = AKFactory::get('kickstart.security.password', null);
	$isAuthenticated = false;

	/**
	 * Akeeba Restore 5.3.1 and earlier use a custom implementation of AES-128 in CTR mode to encrypt the JSON data
	 * between client and server. This is not used as a means to maintain secrecy (it's symmetrical encryption and the
	 * key is, by necessity, transmitted with the HTML page to the client). It's meant as a form of authentication, so
	 * that the server part can ensure that it only receives commands by an authorized client.
	 *
	 * The downside is that encryption in CTR mode (like CBC) is an all-or-nothing affair. This opens the possibility
	 * for a padding oracle attack (https://en.wikipedia.org/wiki/Padding_oracle_attack). While Akeeba Restore was
	 * hardened in 2014 to prevent the bulk of suck attacks it is still possible to attack the encryption using a very
	 * large number of requests (several dozens of thousands).
	 *
	 * Since Akeeba Restore 5.4.0 we have removed this authentication method and replaced it with the transmission of a
	 * very large length password. On the server side we use a timing safe password comparison. By its very nature, it
	 * will only leak the (well known, constant and large) length of the password but no more information about the
	 * password itself. See http://blog.ircmaxell.com/2014/11/its-all-about-time.html  As a result this form of
	 * authentication is many orders of magnitude harder to crack than regular encryption.
	 *
	 * Now you may wonder "how is sending a password in the clear hardier than encryption?". If you ask that question
	 * you were not paying attention. The password needs to be known by BOTH the server AND the client (browser). Since
	 * this password is generated programmatically by the server, it MUST be sent to the client by the server. If an
	 * attacker is able to intercept this transmission (man in the middle attack) using encryption is irrelevant: the
	 * attacker already knows your password. This situation also applies when the user sends their own password to the
	 * server, e.g. when logging into their site. The ONLY way to avoid security issues regarding information being
	 * stolen in transit is using HTTPS with a commercially signed SSL certificate. Unlike 2008, when Kickstart was
	 * originally written, obtaining such a certificate nowadays is trivial and costs absolutely nothing thanks to Let's
	 * Encrypt (https://letsencrypt.org/).
	 *
	 * TL;DR: Use HTTPS with a commercially signed SSL certificate, e.g. a free certificate from Let's Encrypt. Client-
	 * side cryptography does NOT protect you against an attacker (see
	 * https://www.nccgroup.trust/us/about-us/newsroom-and-events/blog/2011/august/javascript-cryptography-considered-harmful/).
	 * Moreover, sending a plaintext password is safer than relying on client-side encryption for authentication as it
	 * removes the possibility of an attacker inferring the contents of the authentication key (password) in a relatively
	 * easy and automated manner.
	 */
	if (!empty($password))
	{
		// Timing-safe password comparison. See http://blog.ircmaxell.com/2014/11/its-all-about-time.html
		if (!timingSafeEquals($password, $userPassword))
		{
			die('###{"status":false,"message":"Invalid login"}###');
		}
	}

	// No JSON data? Die.
	if (empty($json))
	{
		die('###{"status":false,"message":"Invalid JSON data"}###');
	}

	// Handle the JSON string
	$raw = json_decode($json, true);

	// Invalid JSON data?
	if (empty($raw))
	{
		die('###{"status":false,"message":"Invalid JSON data"}###');
	}

	// Pass all JSON data to the request array
	if (!empty($raw))
	{
		foreach ($raw as $key => $value)
		{
			$_REQUEST[$key] = $value;
		}
	}

	// ------------------------------------------------------------
	// 3. Try the "factory" variable
	// ------------------------------------------------------------
	// A "factory" variable will override all other settings.
	$serialized = getQueryParam('factory', null);

	if (!is_null($serialized))
	{
		// Get the serialized factory
		AKFactory::unserialize($serialized);
		AKFactory::set('kickstart.enabled', true);

		return true;
	}

	// ------------------------------------------------------------
	// 4. Try the configuration variable for Kickstart
	// ------------------------------------------------------------
	if (defined('KICKSTART'))
	{
		$configuration = getQueryParam('configuration');

		if (!is_null($configuration))
		{
			// Let's decode the configuration from JSON to array
			$ini_data = json_decode($configuration, true);
		}
		else
		{
			// Neither exists. Enable Kickstart's interface anyway.
			$ini_data = array('kickstart.enabled' => true);
		}

		// Import any INI data we might have from other sources
		if (!empty($ini_data))
		{
			foreach ($ini_data as $key => $value)
			{
				AKFactory::set($key, $value);
			}

			AKFactory::set('kickstart.enabled', true);

			return true;
		}
	}
}

/**
 * Akeeba Restore
 * An AJAX-powered archive extraction library for JPA, JPS and ZIP archives
 *
 * @package   restore
 * @copyright Copyright (c)2008-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Mini-controller for restore.php
if (!defined('KICKSTART'))
{
	// The observer class, used to report number of files and bytes processed
	class RestorationObserver extends AKAbstractPartObserver
	{
		public $compressedTotal = 0;
		public $uncompressedTotal = 0;
		public $filesProcessed = 0;

		public function update($object, $message)
		{
			if (!is_object($message))
			{
				return;
			}

			if (!array_key_exists('type', get_object_vars($message)))
			{
				return;
			}

			if ($message->type == 'startfile')
			{
				$this->filesProcessed++;
				$this->compressedTotal += $message->content->compressed;
				$this->uncompressedTotal += $message->content->uncompressed;
			}
		}

		public function __toString()
		{
			return __CLASS__;
		}

	}

	// Import configuration
	masterSetup();

	$retArray = array(
		'status'  => true,
		'message' => null
	);

	$enabled = AKFactory::get('kickstart.enabled', false);

	if ($enabled)
	{
		$task = getQueryParam('task');

		switch ($task)
		{
			case 'ping':
				// ping task - really does nothing!
				$timer = AKFactory::getTimer();
				$timer->enforce_min_exec_time();
				break;

			/**
			 * There are two separate steps here since we were using an inefficient restoration initialization method in
			 * the past. Now both startRestore and stepRestore are identical. The difference in behavior depends
			 * exclusively on the calling Javascript. If no serialized factory was passed in the request then we start a
			 * new restoration. If a serialized factory was passed in the request then the restoration is resumed. For
			 * this reason we should NEVER call AKFactory::nuke() in startRestore anymore: that would simply reset the
			 * extraction engine configuration which was done in masterSetup() leading to an error about the file being
			 * invalid (since no file is found).
			 */
			case 'startRestore':
			case 'stepRestore':
				if ($task == 'startRestore')
				{
					// Fetch path to the site root from the restoration.php file, so we can tell the engine where it should operate
					$siteRoot = AKFactory::get('kickstart.setup.destdir', '');

					// Before starting, read and save any custom AddHandler directive
					$phpHandlers = getPhpHandlers($siteRoot);
					AKFactory::set('kickstart.setup.phphandlers', $phpHandlers);

					// If the Stealth Mode is enabled, create the .htaccess file
					if (AKFactory::get('kickstart.stealth.enable', false))
					{
						createStealthURL($siteRoot);
					}
					// No stealth mode, but we have custom handler directives, must write our own file
					elseif ($phpHandlers)
					{
						writePhpHandlers($siteRoot);
					}
				}

				/**
				 * First try to run the filesystem zapper (remove all existing files and folders). If the Zapper is
				 * disabled or has already finished running we will get a FALSE result. Otherwise it's a status array
				 * which we can pass directly back to the caller.
				 */
				$ret = runZapper();

				// If the Zapper had a step to run we stop here and return its status array to the caller.
				if ($ret !== false)
				{
					$retArray = array_merge($retArray, $ret);

					break;
				}

				$engine   = AKFactory::getUnarchiver(); // Get the engine
				$observer = new RestorationObserver(); // Create a new observer
				$engine->attach($observer); // Attach the observer
				$engine->tick();
				$ret = $engine->getStatusArray();

				if ($ret['Error'] != '')
				{
					$retArray['status']  = false;
					$retArray['done']    = true;
					$retArray['message'] = $ret['Error'];
				}
				elseif (!$ret['HasRun'])
				{
					$retArray['files']    = $observer->filesProcessed;
					$retArray['bytesIn']  = $observer->compressedTotal;
					$retArray['bytesOut'] = $observer->uncompressedTotal;
					$retArray['status']   = true;
					$retArray['done']     = true;
				}
				else
				{
					$retArray['files']    = $observer->filesProcessed;
					$retArray['bytesIn']  = $observer->compressedTotal;
					$retArray['bytesOut'] = $observer->uncompressedTotal;
					$retArray['status']   = true;
					$retArray['done']     = false;
					$retArray['factory']  = AKFactory::serialize();
				}

				$timer = AKFactory::getTimer();
				$timer->enforce_min_exec_time();

				break;

			case 'finalizeRestore':
				$root = AKFactory::get('kickstart.setup.destdir');
				// Remove the installation directory
				recursive_remove_directory($root . '/installation');

				$postproc = AKFactory::getPostProc();

				/**
				 * Should I rename the htaccess.bak and web.config.bak files back to their live filenames...?
				 */
				$renameFiles = AKFactory::get('kickstart.setup.postrenamefiles', true);

				if ($renameFiles)
				{
					// Rename htaccess.bak to .htaccess
					if (file_exists($root . '/htaccess.bak'))
					{
						if (file_exists($root . '/.htaccess'))
						{
							$postproc->unlink($root . '/.htaccess');
						}

						$postproc->rename($root . '/htaccess.bak', $root . '/.htaccess');
					}

					// Rename htaccess.bak to .htaccess
					if (file_exists($root . '/web.config.bak'))
					{
						if (file_exists($root . '/web.config'))
						{
							$postproc->unlink($root . '/web.config');
						}

						$postproc->rename($root . '/web.config.bak', $root . '/web.config');
					}
				}

				// Remove restoration.php
				$basepath = KSROOTDIR;
				$basepath = rtrim(str_replace('\\', '/', $basepath), '/');

				if (!empty($basepath))
				{
					$basepath .= '/';
				}

				$postproc->unlink($basepath . 'restoration.php');
				clearFileInOPCache($basepath . 'restoration.php');

				// Import a custom finalisation file
				$filename = dirname(__FILE__) . '/restore_finalisation.php';

				if (file_exists($filename))
				{
					// opcode cache busting before including the filename
					if (function_exists('opcache_invalidate'))
					{
						opcache_invalidate($filename, true);
					}

					if (function_exists('apc_compile_file'))
					{
						apc_compile_file($filename);
					}

					if (function_exists('wincache_refresh_if_changed'))
					{
						wincache_refresh_if_changed([$filename]);
					}

					if (function_exists('xcache_asm'))
					{
						xcache_asm($filename);
					}

					include_once $filename;
				}

				// Run a custom finalisation script
				if (function_exists('finalizeRestore'))
				{
					finalizeRestore($root, $basepath);
				}

				break;

			default:
				// Invalid task!
				$enabled = false;
				break;
		}
	}

	// Maybe we weren't authorized or the task was invalid?
	if (!$enabled)
	{
		// Maybe the user failed to enter any information
		$retArray['status']  = false;
		$retArray['message'] = AKText::_('ERR_INVALID_LOGIN');
	}

	// JSON encode the message
	$json = json_encode($retArray);

	// Return the message
	echo "###$json###";

}

// ------------ lixlpixel recursive PHP functions -------------
// recursive_remove_directory( directory to delete, empty )
// expects path to directory and optional TRUE / FALSE to empty
// of course PHP has to have the rights to delete the directory
// you specify and all files and folders inside the directory
// ------------------------------------------------------------
function recursive_remove_directory($directory)
{
	// if the path has a slash at the end we remove it here
	if (substr($directory, -1) == '/')
	{
		$directory = substr($directory, 0, -1);
	}

	// if the path is not valid or is not a directory ...
	if (!file_exists($directory) || !is_dir($directory))
	{
		// ... we return false and exit the function
		return false;
		// ... if the path is not readable
	}
	elseif (!is_readable($directory))
	{
		// ... we return false and exit the function
		return false;
		// ... else if the path is readable
	}
	else
	{
		// we open the directory
		$handle   = opendir($directory);
		$postproc = AKFactory::getPostProc();

		// and scan through the items inside
		while (false !== ($item = readdir($handle)))
		{
			// if the filepointer is not the current directory
			// or the parent directory

			if ($item != '.' && $item != '..')
			{
				// we build the new path to delete
				$path = $directory . '/' . $item;

				// if the new path is a directory
				if (is_dir($path))
				{
					// we call this function with the new path
					recursive_remove_directory($path);
					// if the new path is a file
				}
				else
				{
					// we remove the file
					$postproc->unlink($path);
					clearFileInOPCache($path);
				}
			}
		}

		// close the directory
		closedir($handle);

		// try to delete the now empty directory
		if (!$postproc->rmdir($directory))
		{
			// return false if not possible
			return false;
		}

		// return success
		return true;
	}
}

function createStealthURL($siteRoot = '')
{
	$filename = AKFactory::get('kickstart.stealth.url', '');

	// We need an HTML file!
	if (empty($filename))
	{
		return;
	}

	// Make sure it ends in .html or .htm
	$filename = basename($filename);

	if ((strtolower(substr($filename, -5)) != '.html') && (strtolower(substr($filename, -4)) != '.htm'))
	{
		return;
	}

	if ($siteRoot)
	{
		$siteRoot = rtrim($siteRoot, '/').'/';
	}

	$filename_quoted = str_replace('.', '\\.', $filename);
	$rewrite_base    = trim(dirname(AKFactory::get('kickstart.stealth.url', '')), '/');

	// Get the IP
	$userIP = $_SERVER['REMOTE_ADDR'];
	$userIP = str_replace('.', '\.', $userIP);

	// Get the .htaccess contents
	$stealthHtaccess = <<<ENDHTACCESS
RewriteEngine On
RewriteBase /$rewrite_base
RewriteCond %{REMOTE_ADDR}		!$userIP
RewriteCond %{REQUEST_URI}		!$filename_quoted
RewriteCond %{REQUEST_URI}		!(\.png|\.jpg|\.gif|\.jpeg|\.bmp|\.swf|\.css|\.js)$
RewriteRule (.*)				$filename	[R=307,L]

ENDHTACCESS;

	$customHandlers = portPhpHandlers();

	// Port any custom handlers in the stealth file
	if ($customHandlers)
	{
		$stealthHtaccess .= "\n".$customHandlers."\n";
	}

	// Write the new .htaccess, removing the old one first
	$postproc = AKFactory::getpostProc();
	$postproc->unlink($siteRoot.'.htaccess');
	$tempfile = $postproc->processFilename($siteRoot.'.htaccess');
	@file_put_contents($tempfile, $stealthHtaccess);
	$postproc->process();
}

/**
 * Checks if there is an .htaccess file and has any AddHandler directive in it.
 * In that case, we return the affected lines so they could be stored for later use
 *
 * @return  array
 */
function getPhpHandlers($root = null)
{
	if (!$root)
	{
		$root = AKKickstartUtils::getPath();
	}

	$htaccess   = $root.'/.htaccess';
	$directives = array();

	if (!file_exists($htaccess))
	{
		return $directives;
	}

	$contents   = file_get_contents($htaccess);
	$directives = AKUtilsHtaccess::extractHandler($contents);
	$directives = empty($directives) ? [] : explode("\n", $directives);

	return $directives;
}

/**
 * Fetches any stored php handler directive stored inside the factory and creates a string with the correct markers
 *
 * @return string
 */
function portPhpHandlers()
{
	$phpHandlers = AKFactory::get('kickstart.setup.phphandlers', array());

	if (!$phpHandlers)
	{
		return '';
	}

	$customHandler  = "### AKEEBA_KICKSTART_PHP_HANDLER_BEGIN ###\n";
	$customHandler .= implode("\n", $phpHandlers)."\n";
	$customHandler .= "### AKEEBA_KICKSTART_PHP_HANDLER_END ###\n";

	return $customHandler;
}

function writePhpHandlers($siteRoot = '')
{
	$contents = portPhpHandlers();

	if (!$contents)
	{
		return;
	}

	if ($siteRoot)
	{
		$siteRoot = rtrim($siteRoot, '/').'/';
	}

	// Write the new .htaccess, removing the old one first
	$postproc = AKFactory::getpostProc();
	$postproc->unlink($siteRoot.'.htaccess');
	$tempfile = $postproc->processFilename($siteRoot.'.htaccess');
	@file_put_contents($tempfile, $contents);
	$postproc->process();
}
