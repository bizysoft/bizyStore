<?php
namespace bizySoft\bizyStore\services\core;

use \Exception;
use bizySoft\common\ValidationErrors;

/**
 * Class to provide access to database connections on demand.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ConnectionManager implements BizyStoreConstants
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
	 * @param array $dbConfig all the database config from the bizySoftConfig file.
	 */
	public function __construct(array $dbConfig)
	{
		$this->dbConfigurations = $dbConfig;
		$this->setConnectors();
	}

	/**
	 * Create the Connector's from the config but don't connect until demanded.
	 * 
	 * A Connector can't be fully validated until constructed, so we do that here.
	 *
	 * @throws Exception if an &lt;interface&gt; tag from the bizySoftConfig file does not resolve to a Connector class or
	 *                   the Connector's config is not valid.
	 */
	private function setConnectors()
	{
		foreach ($this->dbConfigurations as $dbId => $config)
		{
			$connectorName = $config[self::DB_INTERFACE_TAG]; // This is a mandatory field, validated when config loaded.
			$connectorClass = 'bizySoft\bizyStore\model\plugins' . "\\$connectorName";
			
			$connector = new $connectorClass();
			$this->connectors[$dbId] = $connector;
			/*
			 * ValidationErrors are accumulated here as well.
			 */
			$connector->validate($config);
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
	 * @return \PDO
	 * @throws Exception if no connection is able to be made.
	 */
	public function getConnection($dbId)
	{
		$result = null;
		
		if (isset($this->connectors[$dbId]))
		{
			$connector = $this->connectors[$dbId];
			try
			{
				$result = $connector->connect($this->dbConfigurations[$dbId]);
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
		else 
		{
			throw new Exception("Database $dbId is not configured.");
		}

		return $result;
	}
}

?>