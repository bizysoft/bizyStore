<?php
namespace bizySoft\bizyStore\services\core;

use \Exception;
use bizySoft\bizyStore\model\core\DB;
use bizySoft\common\Singleton;
use bizySoft\common\ValidationErrors;

/**
 * Service class to provide application access to database interfaces.
 *
 * Requires bizySoftConfig to be configured first via bootstrap.php.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class DBManager extends Singleton
{
	/**
	 * A cache of the DB instances keyed on the "id" from bizySoftConfig.
	 *
	 * @var array
	 */
	private $dbs = array();
	
	/**
	 * Protected constructor for singleton.
	 * 
	 * Initialise the singleton instance.
	 */
	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Connect to the database specified and return an interface to it.
	 *
	 * This method is called under controlled conditions by getDB(). It is up to the user to configure the connection 
	 * behaviour through bizySoftConfig.
	 * 
	 * A connection can't be fully validated until connection is attempted, so we do that here.
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
		$dbConfig = ConnectionManager::getDBConfig($dbId);
		if ($dbConfig)
		{
			$dbInterface = $dbConfig[BizyStoreOptions::DB_INTERFACE_TAG]; // This is a mandatory field, checked when config loaded.
			/*
			 * Create the database interface
			 */
			$dbClass = "bizySoft\\bizyStore\\model\\plugins\\$dbInterface" . "_PDODB";
			if (class_exists($dbClass))
			{
				try
				{
					$result = new $dbClass($dbConfig);
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
	 * Configure the db's by initialising the singleton DBManager object
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new DBManager();
		}
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
	public static function getDB($dbId = null)
	{
		$result = null;
		/*
		 * Resolve the database id.
		 */
		$dbId = $dbId ? $dbId : self::getDefaultDBId();
		
		$dbManager = self::getInstance();
		if (isset($dbManager->dbs[$dbId]))
		{
			/*
			 * Already exists, return it.
			 */
			$result = $dbManager->dbs[$dbId];
		}
		else
		{
			/*
			 * Get an interface on demand and store it for later use.
			 */
			$result = $dbManager->connect($dbId);
			$dbManager->dbs[$dbId] = $result;
		}
		return $result;
	}

	/**
	 * Gets the db configuration as specified in BizyStoreConfig.
	 *
	 * @return array
	 */
	public static function getDBConfig($dbId = null)
	{
		return ConnectionManager::getDBConfig($dbId);
	}

	/**
	 * Close a database connection specified by a database id.
	 * 
	 * On-demand requests for a database that has been closed will re-initialise the connection.
	 * 
	 * @param string $dbId the database id to close.
	 * @param string $mode One of DB::COMMIT or DB::ROLLBACK, defaults to DB::COMMIT.
	 */
	public static function closeInstance($dbId, $mode = DB::COMMIT)
	{
		$dbManager = self::getInstance();
		if (isset($dbManager->dbs[$dbId]))
		{
			$db = $dbManager->dbs[$dbId];
			$db->close($mode);
			$dbManager->dbs[$dbId] = null;
			//unset($dbManager->dbs[$dbId]);
			
			BizyStoreLogger::log("Closed database '$dbId' with $mode");
		}
	}
	
	/**
	 * Close all the database connections nicely.
	 * 
	 * On-demand requests for database's via self::getDB() will re-initialise.
	 * 
	 * @param string $mode One of DB::COMMIT or DB::ROLLBACK, defaults to the cleanUp option if specified in bizySoftConfig.
	 */
	public static function close($mode = null)
	{
		// Close all the databases nicely.
		$cleanUpMode = $mode ? $mode : self::getCleanUpMode();
	
		$dbManager = self::getInstance();
		/*
		 * Make a copy of cached db's so we can iterate without fear that the entry
		 * is going to be unset via closeInstance().
		 */
		$dbs = $dbManager->dbs;
		foreach ($dbs as $dbId => $db)
		{
			self::closeInstance($dbId, $cleanUpMode);
		}
		/*
		 * Make sure we have dropped all references
		 */
		$dbManager->dbs = array();
	}
	
	/**
	 * Reset all the database connections.
	 * 
	 * Closes all databases expicitly with DB::COMMIT, on-demand requests for database's will re-initialise.
	 */
	public static function reset()
	{
		self::close(DB::COMMIT);
	}

	/**
	 * Get the bizySoftConfig id's of the databases that have been configured.
	 *
	 * @return array the array of database names
	 */
	public static function getDBIds()
	{
		$config = ConnectionManager::getDBConfig();
		return array_keys($config);
	}

	/**
	 * Get the id of the default database for the application.
	 * 
	 * This is the first database specified in the bizySoftConfig file.
	 *
	 * @return string the default database id.
	 */
	public static function getDefaultDBId()
	{
		$dbId = null;
		
		$config = ConnectionManager::getDBConfig();
		if ($config)
		{
			$db = reset($config);
			$dbId = key($config);
		}
		return $dbId;
	}

	/**
	 * Get the cleanup mode from bizySoftConfig.
	 *
	 * Defaults to DB:COMMIT if not specified in bizySoftConfig.
	 *
	 * @return string Either DB::COMMIT or DB::ROLLBACK
	 */
	private static function getCleanUpMode()
	{
		/*
		 * Get the options under the bizyStore config tag.
		 */
		$globalOptions = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_TAG);
		$globalOptions = isset($globalOptions[BizyStoreOptions::OPTIONS_TAG]) ? $globalOptions[BizyStoreOptions::OPTIONS_TAG] : array();
		
		return isset($globalOptions[BizyStoreOptions::OPTION_CLEAN_UP]) ? $globalOptions[BizyStoreOptions::OPTION_CLEAN_UP] : DB::COMMIT;
	}
}

register_shutdown_function('bizySoft\bizyStore\services\core\DBManager::close');

DBManager::configure();

?>