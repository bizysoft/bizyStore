<?php
namespace bizySoft\bizyStore\model\core;

use \Exception;
use bizySoft\bizyStore\model\statements\QueryStatement;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\bizyStore\model\strategies\DBQueryStrategy;
use bizySoft\bizyStore\model\strategies\DBExecuteStrategy;
use bizySoft\bizyStore\model\strategies\DBPrepareStrategy;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\ConnectionManager;

/**
 * Acts as an intermediary between PDO and the implementing bizyStore classes.
 *
 * This holds the reference to the PDO connection instance and all methods that directly use the instance.
 * 
 * You must have the appropriate PDO driver for your database(s) configured in your php.ini
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class PDODB extends DB
{	
	/**
	 * Constant key for caching getDateTime() query.
	 * 
	 * @var string
	 */
	const GET_DATE_TIME_KEY = "getDateTime";
	
	/**
	 * Constant key for caching getCurrentSequence() query.
	 * 
	 * @var string
	 */
	const GET_CURRENT_SEQUENCE_KEY = "getCurrentSequence";
	
	/**
	 * Constant key for caching getSchema() query.
	 * 
	 * @var string
	 */
	const GET_SCHEMA_KEY = "getSchema";
	
	/**
	 * Constant key for caching getDBTableNames() query.
	 * 
	 * @var string
	 */
	const GET_DB_TABLE_NAMES_KEY = "getDBTableNames";
	
	/**
	 * Default query for getDBTableNames().
	 * 
	 * @var string
	 */
	const DEFAULT_DB_TABLE_NAMES_QUERY = "SELECT
			table_name 
			FROM information_schema.tables
			WHERE table_schema = :schemaName";
	
	/**
	 * Default query for use by  getDateTime().
	 * 
	 * @var string
	 */
	const DEFAULT_GET_DATE_TIME_QUERY = "SELECT CURRENT_TIMESTAMP";
	
	/**
	 * Transaction isolation constants. 
	 * 
	 * Used to set what this transaction can 'read' while others transaction's are excecuting. An isolation level is set on the database 
	 * connection itself.
	 * 
	 * Isolation levels work on the base transaction so they don't have any effect in nested transactions.
	 * 
	 * Derived classes use these to map to values supported by the database vendor.
	 * 
	 * @var string
	 */
	const TRANSACTION_READ_UNCOMMITTED = "readUncommitted";
	const TRANSACTION_READ_COMMITTED = "readCommitted";
	const TRANSACTION_REPEATABLE_READ = "repeatebleRead";
	const TRANSACTION_SERIALIZABLE = "serializable";
	
	/**
	 * The raw connection to the database.
	 *
	 * @var PDO
	 */
	private $db = null;
	
	/**
	 * A constant timestamp for the instance lifecycle.
	 *
	 * This can be handy for time-stamping all inserts/updates with the same value within a single PHP request.
	 *
	 * @var string
	 */
	private $timestamp;
	
	/**
	 * The transaction holder for this database connection.
	 * 
	 * For emulation of nested transactions.
	 * 
	 * @var array
	 */
	private $transactions = array();

	/**
	 * Keep a count.
	 * 
	 * You can use the count however you wish. Counts can be used to trigger other actions if your application requires.
	 * eg. Counts can be useful to keep track of the number of rows affected by database activity within the transaction.
	 * 
	 * @var int
	 */
	private $count = 0;

	/**
	 * Maps sql standard isolation levels to those supported by the database vendor.
	 * 
	 * @var array
	 */
	protected $isolationLevelMap = array();
	
	/**
	 * Construct with database parameters from bizySoftConfig.
	 *
	 * Just pass through the config.
	 *
	 * @param array $dbConfig an associative array containing the specific database config information supplied in bizySoftConfig.
	 * @throws Exception if no connection can be established with current config.
	 */
	public function __construct($dbConfig)
	{
		parent::__construct($dbConfig);

		$this->connect();
		$this->timestamp = $this->getDateTime(); // Set a timestamp for consistent CRUD dates/times.
		$this->setIsolationLevelMap();
	}
	
	/**
	 * This maps the SQL standard isolation levels to the database vendors implementation.
	 *
	 * Most database vendors support the SQL standard. This is the default, override for specialist behaviour.
	 * 
	 * The values here represent what the vendor has implemented and is available for use, although they may be 
	 * mapped internally to other levels at the discretion of the vendor.
	 * 
	 * These values are used to construct the database query to set the isolation level.
	 */
	protected function setIsolationLevelMap()
	{
		$this->isolationLevelMap[self::TRANSACTION_READ_UNCOMMITTED] = "read uncommitted";
		$this->isolationLevelMap[self::TRANSACTION_READ_COMMITTED] = "read committed";
		$this->isolationLevelMap[self::TRANSACTION_REPEATABLE_READ] = "repeatable read";
		$this->isolationLevelMap[self::TRANSACTION_SERIALIZABLE] = "serializable";
	}
	
	/**
	 * Sets the isolation level of the base transaction to the level specified.
	 *
	 * This is specific to beginTransaction(), it sets the isolation level BEFORE starting the transaction.
	 *
	 * Available levels are:
	 * self::TRANSACTION_READ_UNCOMMITTED
	 * self::TRANSACTION_READ_COMMITTED
	 * self::TRANSACTION_REPEATABLE_READ
	 * self::TRANSACTION_SERIALIZABLE
	 * 
	 * Note that not all databases support isolation levels, this method should be over-ridden where required.
	 *
	 * @param string $isolationLevel
	 * @throws ModelException if a failure occurs.
	 */
	protected function setIsolationLevel($isolationLevel)
	{
		$vendorLevel = $this->getVendorIsolationLevel($isolationLevel);
		if ($vendorLevel)
		{
			$statement = $this->getVendorIsolationLevelStatement($vendorLevel);
			$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => $isolationLevel);
			/*
			 * This will be cached in the $db instance using the SQL standard isolation level as the key.
			 */
			$isolationLevelStatement = new QueryPreparedStatement($this, $statement, array(), $options);
			
			$isolationLevelStatement->execute();
		}
	}
	
	/**
	 * Gets the vendors transaction isolation level associated with the SQL standard isolation level.
	 * 
	 * @param string $isolationLevel
	 * @return string the vendor specific isolation level.
	 */
	public function getVendorIsolationLevel($isolationLevel)
	{
		return isset($this->isolationLevelMap[$isolationLevel]) ? $this->isolationLevelMap[$isolationLevel] : null;
	}
	
	/**
	 * Gets the statement that is required to set the transaction isolation level on the database connection (session).
	 * 
	 * This is the default behaviour, override where required.
	 * 
	 * @param string $isolationLevel the vendor's isolation level as per the isolationMap.
	 * @return string the isolation level statement ready to be executed.
	 */
	public function getVendorIsolationLevelStatement($isolationLevel)
	{
		return "set session transaction isolation level " . $isolationLevel;
	}
			
	/**
	 * Establish a connection to the database if required.
	 * 
	 * @throws Exception if no connection can be established with current config.
	 */
	public function connect()
	{
		if (!$this->db)
		{
			$this->db = ConnectionManager::getConnection($this->getDBId());
		}
	}
	/**
	 * Get the PDO connection to the database that this DB refers to.
	 *
	 * @return PDO the database connection reference for this instance.
	 */
	public function getConnection()
	{
		return $this->db;
	}
	
	/**
	 * Get the constant timestamp at the time of connection.
	 *
	 * This is useful to provide a single database compatible timestamp throughout your
	 * transactions for create dates etc.
	 *
	 * @return string formatted string YYYY-MM-DD HH24:MI:SS as returned by getDateTime() for your database.
	 */
	public function getConstantDateTime()
	{
		return $this->timestamp;
	}
	
	/**
	 * Gets the date/time in YYYY:MM:DD HH24:MI:SS.
	 *
	 * This is the default. Override in implementation classes if required.
	 *
	 * @return string the time represented in YYYY:MM:DD HH24:MI:SS
	 */
	public function getDateTime()
	{
		/*
		 * Specifying a key will turn the prepared statement cache on for this statement, subsequent calls may be faster 
		 * because prepare() is not required.
		 */
		$stmt = new QueryPreparedStatement($this, self::DEFAULT_GET_DATE_TIME_QUERY, array(), 
					array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_DATE_TIME_KEY));
		
		return $stmt->scalar();
	}

	/**
	 * PDO version of this method to provide a database escaped version of a string property.
	 *
	 * Used in conjunction with DB::formatProperty() to help combat SQL injection attacks on
	 * your data if you are using non-prepared statements. Proper support by all database drivers is not
	 * guaranteed.
	 *
	 * Consider using the *PreparedStatement classes provided by bizyStore to secure statements
	 * that are based on user input.
	 *
	 * @param string $propertyValue
	 * @return string the escaped property value ready to be used in database statements.
	 */
	public function escapeProperty($propertyValue)
	{
		$quoted = $this->db->quote($propertyValue);
		return $quoted === false ? $propertyValue : $quoted;
	}
	
	/**
	 * Begin a transaction on the database.
	 *
	 * Use PDO's implementation with additional support for nested transactions and isolation levels. 
	 * 
	 * The isolation level is ignored in nested transactions, they take on the isolation level of the base transaction.
	 * 
	 * @param string $isolationLevel The isolation level to set. See setIsolationLevel()
	 * @return DBTransaction for explicit access to transaction instance.
	 * @throws ModelException if transaction cannot be started.
	 */
	public function beginTransaction($isolationLevel = null)
	{
		$txnLevel = count($this->transactions);
		if ($txnLevel == 0)
		{
			/*
			 * This is the base $txnLevel which has real commit/roll-back capability.
			 */
			if ($isolationLevel)
			{
				/*
				 * Sets the isolation level on the database connection BEFORE we begin the transaction.
				 * Subsequent base level transactions will have the same level unless you change it through
				 * beginTransaction() again.
				 */
				$this->setIsolationLevel($isolationLevel);
			}
			if ($this->db->beginTransaction() === false)
			{
				throw new DatabaseException($this, __METHOD__ . ": Failed to begin transaction");
			}
		}
		else
		{
			/*
			 * This is a nested level.
			 */
			$this->savePointCreate("level" . $txnLevel);
		}
		$txn = new DBTransaction($this);
		$this->transactions[] = $txn;
		
		return $txn;
	}

	/**
	 * End a transaction on the database.
	 *
	 * Use PDO commit/rollBack with additional support for nested transactions. 
	 * 
	 * You should ensure that transactions are ended in the reverse order that they were started.  
	 * This can be done by 'try'ing  beginTransaction() and your specific database code, then calling endTransaction(self::COMMIT) 
	 * on success or endTransaction(self::ROLLBACK) on failure.
	 * 
	 * The same can be done with DBTransaction::commit() and rollBack() if you hold a reference to the transaction returned by 
	 * beginTransaction().
	 *
	 * @param const $endMode one of PDODB::COMMIT, PDODB::ROLLBACK.
	 */
	public function endTransaction($endMode)
	{
		if ($this->hasTransaction())
		{
			$oldTxn = array_pop($this->transactions);
			$txnLevel = count($this->transactions);
			switch ($endMode)
			{
				case self::COMMIT :
					if ($txnLevel == 0)
					{
						/*
						 * We are at the original transaction level so do a real commit
						 * to the database and bubble the count to the database instance 
						 * from the previous transaction.
						 */
						$this->count($oldTxn->getCount());
						
						if ($this->db->commit() === false)
						{
							/*
							 * At this stage we have cleaned up all the transactons, so there is not
							 * much else we can do except let someone know.
							 */
							throw new DatabaseException($this, __METHOD__ . ": Failed to commit transaction.");
						}
					}
					else
					{
						/*
						 * Bubble the count out to the next transaction level
						 */
						$thisTxn = $this->getTransaction();
						$thisTxn->count($oldTxn->getCount());
						/*
						 * Release the save point created by beginTransaction()
						 */
						$this->savePointRelease("level" . $txnLevel);
					}
					break;
				default:
					/*
					 * Anything other than self::COMMIT and we roll-back
					 */
					if ($txnLevel == 0)
					{
						/*
						 * We are at the original transaction level so do a real roll-back
						 * on the database.
						 */
						if ($this->db->rollBack() === false)
						{
							throw new DatabaseException($this, __METHOD__ . ": Failed to roll-back transaction.");
						}
					}
					else
					{
						/*
						 * Roll-back to the save point created by beginTransaction()
						 */
						$this->savePointRollBack("level" . $txnLevel);
					}
			}
		}
	}
	
	/**
	 * Run code inside a transaction at the specified isolation level.
	 * 
	 * Note that isolation levels only have an effect on the base transaction.
	 * 
	 * @see bizySoft\bizyStore\model\core.DBI::transact()
	 */
	public function transact($closure, $isolationLevel = null)
	{
		$result = null;
		$txn = null;
		try
		{
			$txn = $this->beginTransaction($isolationLevel);
			
			/*
			 * Pass over the DB reference and the transaction
			 */
			$result = $closure($this, $txn);
			
			$txn->commit();
		}
		catch (Exception $e)
		{
			if ($txn)
			{
				$txn->rollBack();
			}
			throw $e;
		}
		return $result;
	}
	
	/**
	 * Create a save point with a name to allow rollback to a particular point in the base transaction.
	 *
	 * QueryStatement is used in all the savepoint methods here because some
	 * statements just don't work when prepared ie. with forced quoted parameters.
	 *
	 * This is the default implementation that most SQL compliant databases support.
	 * Should be overridden for specialised behaviour.
	 *
	 * @param string $name
	 * @throws ModelException on failure.
	 */
	protected function savePointCreate($name)
	{
		$query = new QueryStatement($this, "savepoint $name");
		$query->execute();
	}

	/**
	 * Release a savepoint when done.
	 *
	 * This is the default implementation that most SQL compliant databases support.
	 * Should be overridden for specialised behaviour.
	 *
	 * @param string $name
	 * @throws ModelException on failure.
	 */
	protected function savePointRelease($name)
	{
		$query = new QueryStatement($this, "release savepoint $name");
		$query->execute();
	}

	/**
	 * Rollback database changes to a specific save point.
	 *
	 * This is the default implementation that most SQL compliant databases support.
	 * Should be overridden for specialised behaviour.
	 *
	 * @param string $name
	 * @throws ModelException on failure.
	 */
	protected function savePointRollBack($name)
	{
		$query = new QueryStatement($this, "rollback to savepoint $name");
		$query->execute();
	}
	
	/**
	 * Gets the most recent transaction in progress.
	 *
	 * You can use the returned DBTransaction to set various parameters
	 * such as update policy, counts etc.
	 *
	 * @return DBTransaction
	 */
	public function getTransaction()
	{
		return end($this->transactions);
	}

	/**
	 * Is there a transaction active on the database.
	 *
	 * Uses PDO's impementation
	 *
	 * @return true if there is a transaction in progress, false otherwise.
	 */
	public function hasTransaction()
	{
		return $this->db->inTransaction();
	}

	/**
	 * Bumps the count by the increment specified.
	 *
	 * You can count anything you like, for example you could count the number of successful transaction boundaries
	 * by bumping the count when you commit an inner transaction etc... The most useful increment can be determined by using the 
	 * PDOStatement row count returned via a Statement from a Model create(), update() or delete() method, or the result 
	 * of a PDODB::execute().
	 * 
	 * Counts are one-out-all-out when done from a DBTransaction instance, they are only bubbled out to this level from 
	 * inner transactions that complete successfully.
	 *
	 * @param number $increment bump the count by this increment.
	 */
	public function count($increment = 1)
	{
		$this->count += $increment;
	}
	
	/**
	 * Gets the number of counts that have occurred.
	 * 
	 * @return number
	 */
	public function getCount()
	{
		return $this->count;
	}
	
	/**
	 * Reset the transactional count for this database.
	 */
	public function resetCount()
	{
		$this->count = 0;
	}
	
	/**
	 * Get the last sequence value (usually a primary key) that has been allocated by the database on this connection.
	 *
	 * Some databases may require a name for the sequence. Others don't support named sequences and are defaulted to null.
	 *
	 * @param string $name the name of the sequence to get.
	 * @return string the last sequence value for the name generated on this connection.
	 */
	public function getInsertId($name = null)
	{
		return $this->db->lastInsertId($name);
	}

	/**
	 * Close the database connection
	 *
	 * @return int the mode we closed with.
	 */
	public function close($mode = self::COMMIT)
	{
		while ($this->hasTransaction())
		{
			$this->endTransaction($mode);
		}
		ConnectionManager::close($this->getDBId());
		$this->db = null;
		
		return $mode;
	}

	/**
	 * Gets the table names either from the bizySoftConfig file or the database.
	 *
	 * Used in generation of Model and Schema files.
	 *
	 * @return array a zero based array of table names for class file generation.
	 */
	public function getTableNames()
	{
		$result = array();
		$dbConfig = ConnectionManager::getDBConfig($this->getDBId());
	
		/*
		 * Only use the tables specified in the bizySoftConfig file if any.
		 */
		$configTables = isset($dbConfig[BizyStoreOptions::DB_TABLES_TAG]) ? $dbConfig[BizyStoreOptions::DB_TABLES_TAG] : null;
	
		if ($configTables)
		{
			$result = $configTables;
		}
		else
		{
			/*
			 * Get all the table names from the database
			 */
			$tables = $this->getDBTableNames();
			/*
			 * Normalise into a zero based array of table names.
			 */
			foreach($tables as $table)
			{
				list($key, $tableName) = each($table);
				$result[] = $tableName;
			}
		}
		return $result;
	}
	
	/**
	 * Execute a read query on the database.
	 *
	 * Strategy execute()'s are harnessed for fault tolerance.
	 *
	 * @param string $sql the sql query to run on the database.
	 * @return PDOStatement allowing access to the result set via PDO fetch methods.
	 * @throws ModelException
	 */
	public function query($sql)
	{
		$strategy = new DBQueryStrategy($this, $sql);
		return $strategy->execute();
	}

	/**
	 * Execute a write statement on the database.
	 *
	 * Strategy execute()'s are harnessed for fault tolerance.
	 *
	 * @param string $sql the sql statement to run on the database.
	 * @return int the number of rows affected.
	 * @throws ModelException
	 */
	public function execute($sql)
	{
		$strategy = new DBExecuteStrategy($this, $sql);
		return $strategy->execute();
	}

	/**
	 * Call the raw prepare() method on the PDO connection.
	 *
	 * Strategy execute()'s are harnessed for fault tolerance.
	 * 
	 * @param string $sql the raw statement to prepare.
	 * @param array $options the pdoPrepareOptions.
	 * @return PDOStatement the prepared statement ready to execute.
	 * @throws ModelException
	 */
	public function prepare($sql, array $options = array())
	{
		$strategy = new DBPrepareStrategy($this, $sql, $options);
		return $strategy->execute();
	}

	/**
	 * Get the current value of a sequence.
	 * 
	 * Default behaviour is to return null. Override in concrete class if required.
	 *
	 * @param string $sequenceName
	 *
	 * @return string|NULL
	 */
	public function getCurrentSequence($sequenceName)
	{
		return null;
	}

	/**
	 * Gets the error code produced by the underlying database.
	 *
	 * @return string
	 */
	public function errorCode()
	{
		return $this->db->errorCode();
	}
	
	/**
	 * Gets the error information from the underlying database.
	 *
	 * @return array
	 */
	public function errorInfo()
	{
		return $this->db->errorInfo();
	}
}
?>