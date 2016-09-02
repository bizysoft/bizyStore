<?php
namespace bizySoft\bizyStore\services\core;

/**
 * Specifies behaviour for connecting to databases.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface ConnectorI extends BizyStoreConstants
{
	/**
	 * Connect to the database with a configuration.
	 * 
	 * Configurations are normally from bizySoftConfig, in which case they will be validated. Otherwise it is up to the user
	 * to make sure that all fields are correct for the interface used.
	 * 
	 * @param string $dbId.
	 * @return \PDO
	 * @param array $dbConfig Use this config to connect.
	 */
	public function connect(array $dbConfig);
	
	/**
	 * Do specific config validations for this Connector.
	 * 
	 * @param array $dbConfig
	 */
	public function validate(array $dbConfig);
}
?>