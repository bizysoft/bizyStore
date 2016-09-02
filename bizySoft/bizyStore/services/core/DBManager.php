<?php
namespace bizySoft\bizyStore\services\core;

use \Exception;
use bizySoft\bizyStore\model\core\DB;
use bizySoft\common\ValidationErrors;

/**
 * Service class to provide application access to database interfaces.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class DBManager implements BizyStoreConstants
{
	/**
	 * A cache of the DB instances keyed on the "id" from bizySoftConfig.
	 *
	 * @var array
	 */
	private $dbs = array();
	
	/**
	 * A reference to bizyStoreConfig.
	 * 
	 * @var Config
	 */
	private $config;
	
	/**
	 * The database configuration items for fast lookup.
	 * 
	 * @var array
	 */
	private $dbConfig = array();
	
	/**
	 * Handle new connections on demand.
	 * 
	 * @var ConnectionManager
	 */
	private $connectionManager;
	
	/**
	 * Protected constructor for singleton.
	 * 
	 * Initialise the singleton instance.
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;
		$this->dbConfig = $config->getProperty(self::DATABASE_TAG);
		$this->connectionManager = new ConnectionManager($this->dbConfig);
	}

	/**
	 * Connect to the database specified and return the instance of the PDODB class.
	 *
	 * This method is called under controlled conditions by getInstanceDB() for on demand connection.
	 * 
	 * @param string $dbId
	 * @return PDODB 
	 */
	private function connect($dbId)
	{
		$result = null;
		/*
		 * If the database id is in config then try to construct the interface.
		 */
		$dbConfig = isset($this->dbConfig[$dbId]) ? $this->dbConfig[$dbId] : null;
		if ($dbConfig)
		{
			$dbInterface = $dbConfig[self::DB_INTERFACE_TAG]; // This is a mandatory field, checked when config loaded.
			/*
			 * Create the database instance from the <interface> specified.
			 */
			$dbClass = 'bizySoft\bizyStore\model\plugins' . "\\{$dbInterface}_PDODB";
			if (class_exists($dbClass))
			{
				try
				{
					$conn = $this->connectionManager->getConnection($dbId);
					$result = new $dbClass($conn, $dbId, $this->config);
					$logger = $this->config->getLogger();
					$logger->log("Connected to database '$dbId' via $dbClass");
				}
				catch(Exception $e)
				{
					/*
					 * Validation or connection problems.
					 */
					ValidationErrors::addError($e->getMessage());
				}
			}
			else 
			{
				/*
				 * No interface plugin for the database.
				 */
				ValidationErrors::addError("Database plugin '$dbClass' not found");				
			}
		}
		else 
		{
			/*
			 * Unconfigured database.
			 */
			ValidationErrors::addError("Database '$dbId' not in config.");
		}
		
		if (ValidationErrors::hasErrors())
		{
			$message = ValidationErrors::getErrorsAsString();
			throw new Exception($message);
		}
		return $result;
	}

	/**
	 * Get a db based on the &lt;id&gt; field from bizySoftConfig.
	 * 
	 * This method supplies an on-demand concrete interface to the database specified by the $dbId passed in. It is not a 
	 * requirement that all databases in config construct a PDODB interface, ie. your particular application may or may 
	 * not use all of them. Once an interface is obtained, it has a lifecycle of the PHP request.
	 *
	 * 99.9% of the time an application will only require one database interface to a particular db within a PHP request. 
	 *
	 * If neccessary, you may configure multiple interfaces to a single database through bizySoftConfig by using different 
	 * database id's. You may need to make sure that persistent connections are disabled for this to work as intended.
	 *
	 * @param string $dbId the database "id" from bizySoftConfig.
	 * @throws ModelException if on demand interface to a database cannot be made.
	 * @return DB the database reference. Returns the same DB instance for a particular dbId.
	 */
	public function getDB($dbId = null)
	{
		$result = null;
		/*
		 * Resolve the database id, defaulting to the first in $dbConfig if not specified.
		 */
		if (!$dbId)
		{
			reset($this->dbConfig);
			$dbId = key($this->dbConfig);
		}
	
		if (isset($this->dbs[$dbId]))
		{
			/*
			 * Already exists, return it.
			*/
			$result = $this->dbs[$dbId];
		}
		else
		{
			/*
			 * Get an instance on demand and store it for later use.
			 */
			$result = $this->connect($dbId);
			$this->dbs[$dbId] = $result;
		}
		return $result;
	}
	
	/**
	 * Close a database connection specified by a database id.
	 * 
	 * On-demand requests for a database that has been closed will re-initialise the connection.
	 * 
	 * @param string $dbId the database id to close.
	 * @param string $mode One of DB::COMMIT or DB::ROLLBACK, defaults to DB::COMMIT.
	 */
	private function closeInstance($dbId, $mode = DB::COMMIT)
	{
		if (isset($this->dbs[$dbId]))
		{
			$db = $this->dbs[$dbId];
			$db->close($mode);
			unset($this->dbs[$dbId]);
			
			$logger = $this->config->getLogger();
			$logger->log("Closed database '$dbId' with $mode");
		}
	}
	
	/**
	 * Gets the db configuration as specified in BizyStoreConfig.
	 *
	 * @return array
	 */
	public function getDBConfig($dbId = null)
	{
		return $dbId ? $this->dbConfig[$dbId] : $this->dbConfig;
	}

	/**
	 * Close all the database connections nicely.
	 * 
	 * On-demand requests for database's via getDB() will re-initialise.
	 * 
	 * @param string $mode One of DB::COMMIT or DB::ROLLBACK, defaults to the cleanUp option if specified in bizySoftConfig.
	 */
	public function close($mode = null)
	{
		// Close all the databases nicely.
		$cleanUpMode = $mode ? $mode : $this->getCleanUpMode();
	
		$dbIds = array_keys($this->dbConfig);
		foreach ($dbIds as $dbId)
		{
			$this->closeInstance($dbId, $cleanUpMode);
		}
		/*
		 * Make sure we have dropped all references
		 */
		$this->dbs = array();
	}

	/**
	 * Get the cleanup mode from bizySoftConfig.
	 *
	 * Defaults to DB:COMMIT if not specified in bizySoftConfig.
	 *
	 * @return string Either DB::COMMIT or DB::ROLLBACK
	 */
	private function getCleanUpMode()
	{
		/*
		 * Get the options under the bizyStore config tag.
		 */
		$globalOptions = $this->config->getProperty(self::BIZYSTORE_TAG, true);
		$globalOptions = isset($globalOptions[self::OPTIONS_TAG]) ? $globalOptions[self::OPTIONS_TAG] : array();
		
		return isset($globalOptions[self::OPTION_CLEAN_UP]) ? $globalOptions[self::OPTION_CLEAN_UP] : DB::COMMIT;
	}
	
}

?>