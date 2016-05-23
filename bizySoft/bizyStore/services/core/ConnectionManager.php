<?php
namespace bizySoft\bizyStore\services\core;

use \Exception;
use bizySoft\common\Singleton;
use bizySoft\common\ValidationErrors;

/**
 * Singleton class to provide access to database connections.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ConnectionManager extends Singleton
{
	/**
	 * A cache of the connector instances keyed on the "dbId" specified.
	 *
	 * @var array
	 */
	private $connectors = array();
	
	/**
	 * Store the configurations from BizyStoreConfig for easy lookup.
	 * 
	 * @var array
	 */
	private $dbConfigurations = array();
	
	/**
	 * Configures all the connectors for the databases specified in the bizySoftConfig file.
	 * 
	 * Protected constructor for singleton.
	 * 
	 * @param array $dbConfig all the database config from the bizySoftConfig file.
	 */
	protected function __construct(array $dbConfig = array())
	{
		parent::__construct();
		$this->dbConfigurations = $dbConfig;
		$this->setConnectors();
	}

	/**
	 * Create the Connector's from the config but don't connect until demanded.
	 * 
	 * @throws Exception if an &lt;interface&gt; tag from the bizySoftConfig file does not resolve to a Connector class or
	 *                   the Connector's config is not valid.
	 */
	private function setConnectors()
	{
		foreach ($this->dbConfigurations as $dbId => $config)
		{
			$connectorName = $config[BizyStoreOptions::DB_INTERFACE_TAG]; // This is a mandatory field, validated when config loaded.
			BizyStoreLogger::log("Configuring $connectorName for $dbId");
			$connectorClass = "bizySoft\\bizyStore\\model\\plugins\\$connectorName";
			
			if(class_exists($connectorClass))
			{
				$connector = new $connectorClass();
				$this->connectors[$dbId] = $connector;
				/*
				 * ValidationErrors are accumulated here as well.
				 */
				$connector->validate($config);
			}
			else
			{
				/*
				 * Accumulate in ValidationErrors.
				 */
				ValidationErrors::addError("Connector class $connectorName not found");
			}
		}
		if (ValidationErrors::hasErrors())
		{
			throw new Exception(ValidationErrors::getErrorsAsString());
		}
	}
	
	/**
	 * Gets a connection to the database specified.
	 *
	 * It is up to the user to configure the connection behaviour through bizySoftConfig, in which case the config will be validated. 
	 * 
	 * @param string $dbId The database id.
	 * @throws Exception if no connection is able to be made.
	 */
	public static function getConnection($dbId)
	{
		$result = null;
		
		$connectionManager = self::getInstance();
		if (isset($connectionManager->connectors[$dbId]))
		{
			$connector = $connectionManager->connectors[$dbId];
			if ($connector->isConnected())
			{
				$result = $connector->getConnection();
			}
			else
			{
				try
				{
					$result = $connector->connect($connectionManager->dbConfigurations[$dbId]);
					$connector->setConnection($result);
					BizyStoreLogger::log("Connected to database '$dbId' via " . get_class($connector));
				}
				catch (Exception $e)
				{
					/*
					 * Can't connect to the database with the parameters configured.
					 * A good reason to bail.
					 */
					$message = "Connection to database '$dbId' failed via " . get_class($connector) . " :" . $e->getMessage();
					throw new Exception($message , $e->getCode(), $e);
				}
			}
		}
		else 
		{
			throw new Exception("Database $dbId is not configured.");
		}

		return $result;
	}

	/**
	 * Get the db config info as specified in bizySoftConfig.
	 *
	 * @return array
	 */
	public static function getDBConfig($dbId = null)
	{
		$connectionManager = self::getInstance();
		$config = $connectionManager->dbConfigurations;
	
		if ($dbId)
		{
			$config = isset($config[$dbId]) ? $config[$dbId] : null;
		}
		return $config;
	}
	
	/**
	 * Configure by initialising the Singleton ConnectionManager object
	 *
	 * This does specific config validations on the Connector interface's, and bail's on any failures.
	 * 
	 * @throws Exception if current confguration is invalid.
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new ConnectionManager(BizyStoreConfig::getProperty(BizyStoreOptions::DATABASE_TAG));
		}
	}

	/**
	 * Close a database connection nicely.
	 * 
	 * @param string $dbId The database id to close.
	 */
	public static function close($dbId)
	{
		$connectionManager = self::getInstance();
		if (isset($connectionManager->connectors[$dbId]))
		{
			$connector = $connectionManager->connectors[$dbId];
			$connector->setConnection(null);
		}
	}
}

ConnectionManager::configure();

?>