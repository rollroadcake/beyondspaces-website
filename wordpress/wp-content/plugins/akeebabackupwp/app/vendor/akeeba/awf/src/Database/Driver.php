<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Database\Iterator\AbstractIterator;
use Awf\Utils\HashHelper;
use RuntimeException;

/**
 * Database Driver Class
 *
 * This class is adapted from the Joomla! Framework
 *
 * @method      string  q()   q($text, $escape = true)  Alias for quote method
 * @method      string  qn()  qn($name, $as = null)     Alias for quoteName method
 */
#[\AllowDynamicProperties]
abstract class Driver implements DatabaseInterface
{
	/**
	 * The name of the database.
	 *
	 * @var    string
	 */
	protected $_database;

	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 */
	public $name;

	/**
	 * @var    \PDO  The database connection resource.
	 */
	protected $connection;

	/**
	 * @var    string  The database technology family supported, e.g. mysql, mssql
	 */
	public static $dbtech = '';

	/**
	 * @var    integer  The number of SQL statements executed by the database driver.
	 */
	protected $count = 0;

	/**
	 * @var    resource  The database connection cursor from the last query.
	 */
	protected $cursor;

	/**
	 * @var    boolean  The database driver debugging state.
	 */
	protected $debug = false;

	/**
	 * @var    integer  The affected row limit for the current SQL statement.
	 */
	protected $limit = 0;

	/**
	 * @var    array  The log of executed SQL statements by the database driver.
	 */
	protected $log = array();

	/**
	 * @var    string  The character(s) used to quote SQL statement names such as table names or field names,
	 *                 etc.  The child classes should define this as necessary.  If a single character string the
	 *                 same character is used for both sides of the quoted name, else the first character will be
	 *                 used for the opening quote and the second for the closing quote.
	 */
	protected $nameQuote;

	/**
	 * @var    string  The null or zero representation of a timestamp for the database driver.  This should be
	 *                 defined in child classes to hold the appropriate value for the engine.
	 */
	protected $nullDate;

	/**
	 * @var    integer  The affected row offset to apply for the current SQL statement.
	 */
	protected $offset = 0;

	/**
	 * @var    array  Passed in upon instantiation and saved.
	 */
	protected $options;

	/**
	 * @var    mixed  The current SQL statement to execute.
	 */
	protected $sql;

	/**
	 * @var    string  The common database table prefix.
	 */
	protected $tablePrefix;

	/**
	 * @var    boolean  True if the database engine supports UTF-8 character encoding.
	 */
	protected $utf = true;

	/**
	 * @var    integer  The database error number
	 */
	protected $errorNum = 0;

	/**
	 * @var    string  The database error message
	 */
	protected $errorMsg;

	/**
	 * @var    array[Driver]  Driver instances container.
	 */
	protected static $instances = array();

	/**
	 * @var    string  The minimum supported database version.
	 */
	protected static $dbMinimum;

	/**
	 * Get a list of available database connectors.  The list will only be populated with connectors that both
	 * the class exists and the static test method returns true.  This gives us the ability to have a multitude
	 * of connector classes that are self-aware as to whether or not they are able to be used on a given system.
	 *
	 * @param   string  $tech  The technology of the database driver we're interested in, e.g. 'mysql'
	 *
	 * @return  array  An array of available database connectors.
	 */
	public static function getConnectors($tech = null)
	{
		$connectors = array();

		// Get an iterator and loop trough the driver classes.
		$iterator = new \DirectoryIterator(__DIR__ . '/Driver');

		foreach ($iterator as $file)
		{
			$fileName = $file->getFilename();

			// Only load for php files.
			// Note: DirectoryIterator::getExtension only available PHP >= 5.3.6
			if (!$file->isFile() || substr($fileName, strrpos($fileName, '.') + 1) != 'php')
			{
				continue;
			}

			// Derive the class name from the type.
			$class = str_ireplace('.php', '', '\\Awf\\Database\\Driver\\' . ucfirst(trim($fileName)));

			// If the class doesn't exist we have nothing left to do but look at the next type. We did our best.
			if (!class_exists($class))
			{
				continue;
			}

			// Sweet!  Our class exists, so now we just need to know if it passes its test method.
			if ($class::isSupported())
			{
				// If we have database technology filtering, is the database technology supported by this driver?
				if (!is_null($tech))
				{
					if ($tech != $class::$dbtech)
					{
						continue;
					}
				}

				// Connector names should not have file extensions.
				$connectors[] = str_ireplace('.php', '', $fileName);
			}
		}

		return $connectors;
	}

	/**
	 * Create a database object from the provided DI container instance
	 *
	 * @param   Container  $container
	 *
	 * @return  self
	 * @since   1.1.0
	 */
	public static function fromContainer(Container $container): self
	{
		$config = $container->appConfig;

		$options = [
			'driver'   => $config->get('dbdriver', 'mysqli'),
			'database' => $config->get('dbname', 'solo'),
			'select'   => $config->get('dbselect', true),
			'host'     => $config->get('dbhost', 'localhost'),
			'user'     => $config->get('dbuser', ''),
			'password' => $config->get('dbpass', ''),
			'charset'  => $config->get('dbcharset', 'utf8'),
			'prefix'   => $config->get('prefix', ''),
			'ssl'      => [],
		];

		$options['driver'] = (isset($options['driver'])) ? preg_replace('/[^A-Z0-9_\.-]/i', '', $options['driver'])
			: 'mysqli';

		// Apply the MySQL SSL configuration, if necessary
		if ($config->get('dbencryption', 0) && in_array(strtolower($options['driver']), ['mysqli', 'pdomysql']))
		{
			$options['ssl'] = [
				'enable'             => true,
				'cipher'             => $config->get('dbsslcipher', '') ?: '',
				'ca'                 => $config->get('dbsslca', '') ?: '',
				'capath'             => $config->get('dbsslcapath', '') ?: '',
				'key'                => $config->get('dbsslkey', '') ?: '',
				'cert'               => $config->get('dbsslcert', '') ?: '',
				'verify_server_cert' => $config->get('dbsslverifyservercert', true) ?: false,
			];
		}

		$connection = $config->get('connection', null);

		if (!empty($connection))
		{
			$options['connection'] = $connection;
		}

		return self::fromOptions($options);
	}

	/**
	 * Create a database object from the provided connection options
	 *
	 * @param   array  $options  The connection options
	 *
	 * @return  self
	 * @since   1.1.0
	 */
	public static function fromOptions(array $options): self
	{
		// Derive the class name from the driver.
		$class = '\\Awf\\Database\\Driver\\' . ucfirst(strtolower($options['driver']));

		// If the class still doesn't exist we have nothing left to do but throw an exception.  We did our best.
		if (!class_exists($class))
		{
			throw new RuntimeException(sprintf('Unable to load Database Driver: %s', $options['driver']));
		}

		// Create our new Driver connector based on the options given.
		try
		{
			return new $class($options);
		}
		catch (RuntimeException $e)
		{
			throw new RuntimeException(sprintf('Unable to connect to the Database: %s', $e->getMessage()));
		}
	}

	/**
	 * Method to return a Driver instance based on the given options.  There are three global options and then
	 * the rest are specific to the database driver.  The 'driver' option defines which Driver class is
	 * used for the connection -- the default is 'mysqli'.  The 'database' option determines which database is to
	 * be used for the connection.  The 'select' option determines whether the connector should automatically select
	 * the chosen database.
	 *
	 * Instances are unique to the given options and new objects are only created when a unique options array is
	 * passed into the method.  This ensures that we don't end up with unnecessary database connection resources.
	 *
	 * @param   array|Container $options Parameters to be passed to the database driver or a DI Container to read app
	 *                                   config from.
	 *
	 * @return  Driver  A database object.
	 *
	 * @throws  RuntimeException  When the driver cannot be instantiated
	 * @deprecated 2.0 Go through the Container, or use the fromOptions and fromContainer methods
	 * @see self::fromOptions()
	 * @see self::fromContainer()
	 * @see Container
	 */
	public static function getInstance($options = []): self
	{
		trigger_error(
			sprintf(
				'Calling %s is deprecated. Use the Container, the %s::fromOptions() method, or the %s::fromContainer() method.',
				__METHOD__,
				__CLASS__,
				__CLASS__
			),
			E_USER_DEPRECATED
		);

		// In no options are set, use the default application's DI container
		$options = $options ?? Application::getInstance();

		// Get the options signature for the database connector.
		$signature = HashHelper::md5(serialize($options));

		self::$instances[$signature] = self::$instances[$signature] ??
			($options instanceof Container ? self::fromContainer($options) : self::fromOptions($options));

		return self::$instances[$signature];
	}

	/**
	 * Splits a string of multiple queries into an array of individual queries.
	 *
	 * @param   string  $sql  Input SQL string with which to split into individual queries.
	 *
	 * @return  array  The queries from the input string separated into an array.
	 */
	public static function splitSql($sql)
	{
		$start = 0;
		$open = false;
		$char = '';
		$end = strlen($sql);
		$queries = array();

		for ($i = 0; $i < $end; $i++)
		{
			$current = substr($sql, $i, 1);
			if (($current == '"' || $current == '\''))
			{
				$n = 2;

				while (substr($sql, $i - $n + 1, 1) == '\\' && $n < $i)
				{
					$n++;
				}

				if ($n % 2 == 0)
				{
					if ($open)
					{
						if ($current == $char)
						{
							$open = false;
							$char = '';
						}
					}
					else
					{
						$open = true;
						$char = $current;
					}
				}
			}

			if (($current == ';' && !$open) || $i == $end - 1)
			{
				$queries[] = substr($sql, $start, ($i - $start + 1));
				$start = $i + 1;
			}
		}

		return $queries;
	}

	/**
	 * Magic method to provide method alias support for quote() and quoteName().
	 *
	 * @param   string  $method  The called method.
	 * @param   array   $args    The array of arguments passed to the method.
	 *
	 * @return  mixed|void  The aliased method's return value or null.
	 */
	public function __call($method, $args)
	{
		if (empty($args))
		{
			return;
		}

		switch ($method)
		{
			case 'q':
				return $this->quote($args[0], $args[1] ?? true);
				break;
			case 'qn':
			case 'nq':
				return $this->quoteName($args[0], $args[1] ?? null);
				break;
		}
	}

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 */
	public function __construct($options)
	{
		// Initialise object variables.
		$this->_database = (isset($options['database'])) ? $options['database'] : '';

		$this->tablePrefix = (isset($options['prefix'])) ? $options['prefix'] : '';
		$this->count = 0;
		$this->errorNum = 0;
		$this->log = array();

		// Did we get passed a connection resource?
		if (isset($options['connection']))
		{
			$this->connection = $options['connection'];
		}

		// Set class options.
		$this->options = $options;
	}

	/**
	 * Alter database's character set, obtaining query string from protected member.
	 *
	 * @param   string  $dbName  The database name that will be altered
	 *
	 * @return  string  The query that alter the database query string
	 *
	 * @throws  RuntimeException
	 */
	public function alterDbCharacterSet($dbName)
	{
		if (is_null($dbName))
		{
			throw new RuntimeException('Database name must not be null.');
		}

		$this->setQuery($this->getAlterDbCharacterSet($dbName));

		return $this->execute();
	}

	/**
	 * Connects to the database if needed.
	 *
	 * @return  void  Returns void if the database connected successfully.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function connect();

	/**
	 * Determines if the connection to the server is active.
	 *
	 * @return  boolean  True if connected to the database engine.
	 */
	abstract public function connected();

	/**
	 * Create a new database using information from $options object, obtaining query string
	 * from protected member.
	 *
	 * @param   stdClass  $options  Object used to pass user and database name to database driver.
	 * 									This object must have "db_name" and "db_user" set.
	 * @param   boolean   $utf      True if the database supports the UTF-8 character set.
	 *
	 * @return  string  The query that creates database
	 *
	 * @throws  RuntimeException
	 */
	public function createDatabase($options, $utf = true)
	{
		if (is_null($options))
		{
			throw new RuntimeException('$options object must not be null.');
		}
		elseif (empty($options->db_name))
		{
			throw new RuntimeException('$options object must have db_name set.');
		}
		elseif (empty($options->db_user))
		{
			throw new RuntimeException('$options object must have db_user set.');
		}

		$this->setQuery($this->getCreateDatabaseQuery($options, $utf));

		return $this->execute();
	}

	/**
	 * Disconnects the database.
	 *
	 * @return  void
	 */
	abstract public function disconnect();

	/**
	 * Drops a table from the database.
	 *
	 * @param   string   $table     The name of the database table to drop.
	 * @param   boolean  $ifExists  Optionally specify that the table must exist before it is dropped.
	 *
	 * @return  Driver     Returns this object to support chaining.
	 *
	 * @throws  RuntimeException
	 */
	public abstract function dropTable($table, $ifExists = true);

	/**
	 * Method to escape a string for usage in an SQL statement.
	 *
	 * @param   string   $text   The string to be escaped.
	 * @param   boolean  $extra  Optional parameter to provide extra escaping.
	 *
	 * @return  string   The escaped string.
	 */
	abstract public function escape($text, $extra = false);

	/**
	 * Method to fetch a row from the result set cursor as an array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 */
	abstract protected function fetchArray($cursor = null);

	/**
	 * Method to fetch a row from the result set cursor as an associative array.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 *
	 */
	abstract protected function fetchAssoc($cursor = null);

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @param   mixed   $cursor  The optional result set cursor from which to fetch the row.
	 * @param   string  $class   The class name to use for the returned row object.
	 *
	 * @return  mixed   Either the next row from the result set or false if there are no more rows.
	 *
	 */
	abstract protected function fetchObject($cursor = null, $class = 'stdClass');

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @param   mixed  $cursor  The optional result set cursor from which to fetch the row.
	 *
	 * @return  void
	 *
	 */
	abstract protected function freeResult($cursor = null);

	/**
	 * Get the number of affected rows for the previous executed SQL statement.
	 *
	 * @return  integer  The number of affected rows.
	 */
	abstract public function getAffectedRows();

	/**
	 * Return the query string to alter the database character set.
	 *
	 * @param   string  $dbName  The database name
	 *
	 * @return  string  The query that alter the database query string
	 *
	 */
	protected function getAlterDbCharacterSet($dbName)
	{
		$query = 'ALTER DATABASE ' . $this->quoteName($dbName) . ' CHARACTER SET `utf8`';

		return $query;
	}

	/**
	 * Return the query string to create new Database.
	 * Each database driver, other than MySQL, need to override this member to return correct string.
	 *
	 * @param   stdClass  $options  Object used to pass user and database name to database driver.
	 * 									This object must have "db_name" and "db_user" set.
	 * @param   boolean   $utf      True if the database supports the UTF-8 character set.
	 *
	 * @return  string  The query that creates database
	 *
	 */
	protected function getCreateDatabaseQuery($options, $utf)
	{
		if ($utf)
		{
			$query = 'CREATE DATABASE ' . $this->quoteName($options->db_name) . ' CHARACTER SET `utf8`';
		}
		else
		{
			$query = 'CREATE DATABASE ' . $this->quoteName($options->db_name);
		}

		return $query;
	}

	/**
	 * Method to get the database collation in use by sampling a text field of a table in the database.
	 *
	 * @return  mixed  The collation in use by the database or boolean false if not supported.
	 *
	 */
	abstract public function getCollation();

	/**
	 * Method that provides access to the underlying database connection. Useful for when you need to call a
	 * proprietary method such as postgresql's lo_* methods.
	 *
	 * @return  mixed  The underlying database connection resource.
	 *
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Set the underlying database connection object / resource.
	 *
	 * @param   mixed $connection
	 *
	 * @return  void
	 */
	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Get the total number of SQL statements executed by the database driver.
	 *
	 * @return  integer
	 *
	 */
	public function getCount()
	{
		return $this->count;
	}

	/**
	 * Gets the name of the database used by this connection.
	 *
	 * @return  string
	 *
	 */
	public function getDatabase()
	{
		return $this->_database;
	}

	/**
	 * Returns a PHP date() function compliant date format for the database driver.
	 *
	 * @return  string  The format string.
	 *
	 */
	public function getDateFormat()
	{
		return 'Y-m-d H:i:s';
	}

	/**
	 * Get the database driver SQL statement log.
	 *
	 * @return  array  SQL statements executed by the database driver.
	 *
	 */
	public function getLog()
	{
		return $this->log;
	}

	/**
	 * Get the minimum supported database version.
	 *
	 * @return  string  The minimum version number for the database driver.
	 *
	 */
	public function getMinimum()
	{
		return static::$dbMinimum;
	}

	/**
	 * Get the null or zero representation of a timestamp for the database driver.
	 *
	 * @return  string  Null or zero representation of a timestamp.
	 *
	 */
	public function getNullDate()
	{
		return $this->nullDate;
	}

	/**
	 * Get the number of returned rows for the previous executed SQL statement.
	 *
	 * @param   resource  $cursor  An optional database cursor resource to extract the row count from.
	 *
	 * @return  integer   The number of returned rows.
	 *
	 */
	abstract public function getNumRows($cursor = null);

	/**
	 * Get the common table prefix for the database driver.
	 *
	 * @return  string  The common database table prefix.
	 *
	 */
	public function getPrefix()
	{
		return $this->tablePrefix;
	}

	/**
	 * Get the current query object or a new Query object.
	 *
	 * @param   boolean  $new  False to return the current query object, True to return a new Query object.
	 *
	 * @return  Query  The current query object or a new object extending the Query class.
	 *
	 * @throws  RuntimeException
	 */
	public function getQuery($new = false)
	{
		if ($new)
		{
			// Derive the class name from the driver.
			$class = '\\Awf\\Database\\Query\\' . ucfirst($this->name);

			// Make sure we have a query class for this driver.
			if (!class_exists($class))
			{
				// If it doesn't exist we are at an impasse so throw an exception.
				throw new RuntimeException('Database Query Class not found.');
			}

			return new $class($this);
		}
		else
		{
			return $this->sql;
		}
	}

	/**
	 * Retrieves field information about the given tables.
	 *
	 * @param   string   $table     The name of the database table.
	 * @param   boolean  $typeOnly  True (default) to only return field types.
	 *
	 * @return  array  An array of fields by table.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function getTableColumns($table, $typeOnly = true);

	/**
	 * Shows the table CREATE statement that creates the given tables.
	 *
	 * @param   mixed  $tables  A table name or a list of table names.
	 *
	 * @return  array  A list of the create SQL for the tables.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function getTableCreate($tables);

	/**
	 * Retrieves field information about the given tables.
	 *
	 * @param   mixed  $tables  A table name or a list of table names.
	 *
	 * @return  array  An array of keys for the table(s).
	 *
	 * @throws  RuntimeException
	 */
	abstract public function getTableKeys($tables);

	/**
	 * Method to get an array of all tables in the database.
	 *
	 * @return  array  An array of all the tables in the database.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function getTableList();

	/**
	 * Determine whether or not the database engine supports UTF-8 character encoding.
	 *
	 * @return  boolean  True if the database engine supports UTF-8 character encoding.
	 */
	public function hasUTFSupport()
	{
		return $this->utf;
	}

	/**
	 * Get the version of the database connector
	 *
	 * @return  string  The database connector version.
	 */
	abstract public function getVersion();

	/**
	 * Method to get the auto-incremented value from the last INSERT statement.
	 *
	 * @return  integer  The value of the auto-increment field from the last inserted row.
	 */
	abstract public function insertid();

	/**
	 * Inserts a row into a table based on an object's properties.
	 *
	 * @param   string  $table    The name of the database table to insert into.
	 * @param   object  &$object  A reference to an object whose public properties match the table fields.
	 * @param   string  $key      The name of the primary key. If provided the object property is updated.
	 *
	 * @return  boolean    True on success.
	 *
	 * @throws  RuntimeException
	 */
	public function insertObject($table, &$object, $key = null)
	{
		$fields = array();
		$values = array();

		// Iterate over the object variables to build the query fields and values.
		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process non-null scalars.
			if (is_array($v) or is_object($v) or $v === null)
			{
				continue;
			}

			// Ignore any internal fields.
			if ($k[0] == '_')
			{
				continue;
			}

			// Prepare and sanitize the fields and values for the database query.
			$fields[] = $this->quoteName($k);
			$values[] = $this->quote($v);
		}

		// Create the base insert statement.
		$query = $this->getQuery(true);
		$query->insert($this->quoteName($table))
				->columns($fields)
				->values(implode(',', $values));

		// Set the query and execute the insert.
		$this->setQuery($query);
		if (!$this->execute())
		{
			return false;
		}

		// Update the primary key if it exists.
		$id = $this->insertid();
		if ($key && $id && is_string($key))
		{
			$object->$key = $id;
		}

		return true;
	}

	/**
	 * Method to check whether the installed database version is supported by the database driver
	 *
	 * @return  boolean  True if the database version is supported
	 */
	public function isMinimumVersion()
	{
		return version_compare($this->getVersion(), static::$dbMinimum) >= 0;
	}

	/**
	 * Method to get the first row of the result set from the database query as an associative array
	 * of ['field_name' => 'row_value'].
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadAssoc()
	{
		$this->connect();

		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get the first row from the result set as an associative array.
		if ($array = $this->fetchAssoc($cursor))
		{
			$ret = $array;
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an associative array
	 * of ['field_name' => 'row_value'].  The array of rows can optionally be keyed by a field name, but defaults to
	 * a sequential numeric array.
	 *
	 * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key     The name of a field on which to key the result array.
	 * @param   string  $column  An optional column name. Instead of the whole row, only this column value will be in
	 *                           the result array.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadAssocList($key = null, $column = null)
	{
		$this->connect();

		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set.
		while ($row = $this->fetchAssoc($cursor))
		{
			$value = ($column) ? (isset($row[$column]) ? $row[$column] : $row) : $row;
			if ($key)
			{
				$array[$row[$key]] = $value;
			}
			else
			{
				$array[] = $value;
			}
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

	/**
	 * Method to get an array of values from the <var>$offset</var> field in each row of the result set from
	 * the database query.
	 *
	 * @param   integer  $offset  The row offset to use to build the result array.
	 *
	 * @return  mixed    The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadColumn($offset = 0)
	{
		$this->connect();

		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set as arrays.
		while ($row = $this->fetchArray($cursor))
		{
			$array[] = $row[$offset];
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

	/**
	 * Method to get the first row of the result set from the database query as an object.
	 *
	 * @param   string  $class  The class name to use for the returned row object.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadObject($class = 'stdClass')
	{
		$this->connect();

		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get the first row from the result set as an object of type $class.
		if ($object = $this->fetchObject($cursor, $class))
		{
			$ret = $object;
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an object.  The array
	 * of objects can optionally be keyed by a field name, but defaults to a sequential numeric array.
	 *
	 * NOTE: Choosing to key the result array by a non-unique field name can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key    The name of a field on which to key the result array.
	 * @param   string  $class  The class name to use for the returned row objects.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadObjectList($key = '', $class = 'stdClass')
	{
		$this->connect();

		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set as objects of type $class.
		while ($row = $this->fetchObject($cursor, $class))
		{
			if ($key)
			{
				$array[$row->$key] = $row;
			}
			else
			{
				$array[] = $row;
			}
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

	/**
	 * Method to get the first field of the first row of the result set from the database query.
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadResult()
	{
		$this->connect();

		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get the first row from the result set as an array.
		if ($row = $this->fetchArray($cursor))
		{
			$ret = $row[0];
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $ret;
	}

	/**
	 * Method to get the first row of the result set from the database query as an array.  Columns are indexed
	 * numerically so the first column in the result set would be accessible via <var>$row[0]</var>, etc.
	 *
	 * @return  mixed  The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadRow()
	{
		$this->connect();

		$ret = null;

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get the first row from the result set as an array.
		if ($row = $this->fetchArray($cursor))
		{
			$ret = $row;
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $ret;
	}

	/**
	 * Method to get an array of the result set rows from the database query where each row is an array.  The array
	 * of objects can optionally be keyed by a field offset, but defaults to a sequential numeric array.
	 *
	 * NOTE: Choosing to key the result array by a non-unique field can result in unwanted
	 * behavior and should be avoided.
	 *
	 * @param   string  $key  The name of a field on which to key the result array.
	 *
	 * @return  mixed   The return value or null if the query failed.
	 *
	 * @throws  RuntimeException
	 */
	public function loadRowList($key = null)
	{
		$this->connect();

		$array = array();

		// Execute the query and get the result set cursor.
		if (!($cursor = $this->execute()))
		{
			return null;
		}

		// Get all of the rows from the result set as arrays.
		while ($row = $this->fetchArray($cursor))
		{
			if ($key !== null)
			{
				$array[$row[$key]] = $row;
			}
			else
			{
				$array[] = $row;
			}
		}

		// Free up system resources and return.
		$this->freeResult($cursor);

		return $array;
	}

	/**
	 * Locks a table in the database.
	 *
	 * @param   string  $tableName  The name of the table to unlock.
	 *
	 * @return  Driver     Returns this object to support chaining.
	 *
	 * @throws  RuntimeException
	 */
	public abstract function lockTable($tableName);

	/**
	 * Method to quote and optionally escape a string to database requirements for insertion into the database.
	 *
	 * @param   string|array   $text    The string to quote.
	 * @param   boolean  $escape  True (default) to escape the string, false to leave it unchanged.
	 *
	 * @return  string  The quoted input string.
	 */
	public function quote($text, $escape = true)
	{
		if (is_array($text))
		{
			$ret = array();

			foreach ($text as $k => $v)
			{
				$ret[$k] = $this->quote($v, $escape);
			}

			return $ret;
		}

		return '\'' . ($escape ? $this->escape($text) : $text) . '\'';
	}

	/**
	 * Wrap an SQL statement identifier name such as column, table or database names in quotes to prevent injection
	 * risks and reserved word conflicts.
	 *
	 * @param   mixed  $name  The identifier name to wrap in quotes, or an array of identifier names to wrap in quotes.
	 *                        Each type supports dot-notation name.
	 * @param   mixed  $as    The AS query part associated to $name. It can be string or array, in latter case it has to be
	 *                        same length of $name; if is null there will not be any AS part for string or array element.
	 *
	 * @return  mixed  The quote wrapped name, same type of $name.
	 */
	public function quoteName($name, $as = null)
	{
		if (is_string($name))
		{
			$quotedName = $this->quoteNameStr(explode('.', $name));

			$quotedAs = '';
			if (!is_null($as))
			{
				settype($as, 'array');
				$quotedAs .= ' AS ' . $this->quoteNameStr($as);
			}

			return $quotedName . $quotedAs;
		}
		else
		{
			$fin = array();

			if (is_null($as))
			{
				foreach ($name as $str)
				{
					$fin[] = $this->quoteName($str);
				}
			}
			elseif (is_array($name) && (count($name) == count($as)))
			{
				$count = count($name);
				for ($i = 0; $i < $count; $i++)
				{
					$fin[] = $this->quoteName($name[$i], $as[$i]);
				}
			}

			return $fin;
		}
	}

	/**
	 * Quote strings coming from quoteName call.
	 *
	 * @param   array  $strArr  Array of strings coming from quoteName dot-explosion.
	 *
	 * @return  string  Dot-imploded string of quoted parts.
	 */
	protected function quoteNameStr($strArr)
	{
		$parts = array();
		$q = $this->nameQuote;

		foreach ($strArr as $part)
		{
			if (is_null($part))
			{
				continue;
			}

			if (strlen($q) == 1)
			{
				$parts[] = $q . $part . $q;
			}
			else
			{
				$parts[] = $q[0] . $part . $q[1];
			}
		}

		return implode('.', $parts);
	}

	/**
	 * This function replaces a string identifier <var>$prefix</var> with the string held is the
	 * <var>tablePrefix</var> class variable.
	 *
	 * @param   string  $sql     The SQL statement to prepare.
	 * @param   string  $prefix  The common table prefix.
	 *
	 * @return  string  The processed SQL statement.
	 */
	public function replacePrefix($sql, $prefix = '#__')
	{
		$escaped = false;
		$startPos = 0;
		$quoteChar = '';
		$literal = '';

		$sql = trim($sql);
		$n = strlen($sql);

		while ($startPos < $n)
		{
			$ip = strpos($sql, $prefix, $startPos);
			if ($ip === false)
			{
				break;
			}

			$j = strpos($sql, "'", $startPos);
			$k = strpos($sql, '"', $startPos);
			if (($k !== false) && (($k < $j) || ($j === false)))
			{
				$quoteChar = '"';
				$j = $k;
			}
			else
			{
				$quoteChar = "'";
			}

			if ($j === false)
			{
				$j = $n;
			}

			$literal .= str_replace($prefix, $this->tablePrefix, substr($sql, $startPos, $j - $startPos));
			$startPos = $j;

			$j = $startPos + 1;

			if ($j >= $n)
			{
				break;
			}

			// Quote comes first, find end of quote
			while (true)
			{
				$k = strpos($sql, $quoteChar, $j);
				$escaped = false;
				if ($k === false)
				{
					break;
				}
				$l = $k - 1;
				while ($l >= 0 && $sql[$l] == '\\')
				{
					$l--;
					$escaped = !$escaped;
				}
				if ($escaped)
				{
					$j = $k + 1;
					continue;
				}
				break;
			}
			if ($k === false)
			{
				// Error in the query - no end quote; ignore it
				break;
			}
			$literal .= substr($sql, $startPos, $k - $startPos + 1);
			$startPos = $k + 1;
		}
		if ($startPos < $n)
		{
			$literal .= substr($sql, $startPos, $n - $startPos);
		}

		return $literal;
	}

	/**
	 * Renames a table in the database.
	 *
	 * @param   string  $oldTable  The name of the table to be renamed
	 * @param   string  $newTable  The new name for the table.
	 * @param   string  $backup    Table prefix
	 * @param   string  $prefix    For the table - used to rename constraints in non-mysql databases
	 *
	 * @return  Driver    Returns this object to support chaining.
	 *
	 * @throws  RuntimeException
	 */
	public abstract function renameTable($oldTable, $newTable, $backup = null, $prefix = null);

	/**
	 * Select a database for use.
	 *
	 * @param   string  $database  The name of the database to select for use.
	 *
	 * @return  boolean  True if the database was successfully selected.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function select($database);

	/**
	 * Sets the database debugging state for the driver.
	 *
	 * @param   boolean  $level  True to enable debugging.
	 *
	 * @return  boolean  The old debugging level.
	 *
	 */
	public function setDebug($level)
	{
		$previous = $this->debug;
		$this->debug = (bool) $level;

		return $previous;
	}

	/**
	 * Sets the SQL statement string for later execution.
	 *
	 * @param   mixed    $query   The SQL statement to set either as a Query object or a string.
	 * @param   integer  $offset  The affected row offset to set.
	 * @param   integer  $limit   The maximum affected rows to set.
	 *
	 * @return  Driver  This object to support method chaining.
	 *
	 */
	public function setQuery($query, $offset = 0, $limit = 0)
	{
		$this->sql = $query;
		$this->limit = (int) max(0, $limit);
		$this->offset = (int) max(0, $offset);

		return $this;
	}

	/**
	 * Set the connection to use UTF-8 character encoding.
	 *
	 * @return  boolean  True on success.
	 *
	 */
	abstract public function setUTF();

	/**
	 * Method to commit a transaction.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	abstract public function transactionCommit();

	/**
	 * Method to roll back a transaction.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	abstract public function transactionRollback();

	/**
	 * Method to initialize a transaction.
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	abstract public function transactionStart();

	/**
	 * Method to truncate a table.
	 *
	 * @param   string  $table  The table to truncate
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException
	 */
	public function truncateTable($table)
	{
		$this->setQuery('TRUNCATE TABLE ' . $this->quoteName($table));
		$this->execute();
	}

	/**
	 * Updates a row in a table based on an object's properties.
	 *
	 * @param   string   $table    The name of the database table to update.
	 * @param   object   &$object  A reference to an object whose public properties match the table fields.
	 * @param   array    $key      The name of the primary key.
	 * @param   boolean  $nulls    True to update null fields or false to ignore them.
	 *
	 * @return  boolean  True on success.
	 *
	 * @throws  RuntimeException
	 */
	public function updateObject($table, &$object, $key, $nulls = false)
	{
		$fields = array();
		$where = array();

		if (is_string($key))
		{
			$key = array($key);
		}

		if (is_object($key))
		{
			$key = (array) $key;
		}

		// Create the base update statement.
		$statement = 'UPDATE ' . $this->quoteName($table) . ' SET %s WHERE %s';

		// Iterate over the object variables to build the query fields/value pairs.
		foreach (get_object_vars($object) as $k => $v)
		{
			// Only process scalars that are not internal fields.
			if (is_array($v) or is_object($v) or $k[0] == '_')
			{
				continue;
			}

			// Set the primary key to the WHERE clause instead of a field to update.
			if (in_array($k, $key))
			{
				$where[] = $this->quoteName($k) . '=' . $this->quote($v);
				continue;
			}

			// Prepare and sanitize the fields and values for the database query.
			if ($v === null)
			{
				// If the value is null and we want to update nulls then set it.
				if ($nulls)
				{
					$val = 'NULL';
				}
				// If the value is null and we do not want to update nulls then ignore this field.
				else
				{
					continue;
				}
			}
			// The field is not null so we prep it for update.
			else
			{
				$val = $this->quote($v);
			}

			// Add the field to be updated.
			$fields[] = $this->quoteName($k) . '=' . $val;
		}

		// We don't have any fields to update.
		if (empty($fields))
		{
			return true;
		}

		// Set the query and execute the update.
		$this->setQuery(sprintf($statement, implode(",", $fields), implode(' AND ', $where)));
		return $this->execute();
	}

	/**
	 * Get a new iterator on the current query.
	 *
	 * @param   string|null  $column  An option column to use as the iterator key.
	 * @param   string       $class   The class of object that is returned.
	 *
	 * @return  AbstractIterator  A new database iterator.
	 *
	 * @throws  RuntimeException
	 */
	public function getIterator(string $column = null, string $class = 'stdClass'): AbstractIterator
	{
		// Derive the class name from the driver.
		$iteratorClass = __NAMESPACE__ . '\\Iterator\\' . ucfirst($this->name);

		// Make sure we have an iterator class for this driver.
		if (!class_exists($iteratorClass))
		{
			// If it doesn't exist we are at an impasse so throw an exception.
			throw new RuntimeException(sprintf('Database iterator class ‘%s’ is not defined', $iteratorClass));
		}

		// Return a new iterator
		return new $iteratorClass($this->execute(), $column, $class);
	}


	/**
	 * Execute the SQL statement.
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 *
	 * @throws  RuntimeException
	 */
	abstract public function execute();

	/**
	 * Unlocks tables in the database.
	 *
	 * @return  Driver  Returns this object to support chaining.
	 *
	 * @throws  RuntimeException
	 */
	public abstract function unlockTables();
}
