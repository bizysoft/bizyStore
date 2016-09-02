<?php
namespace bizySoft\bizyStore\services\core;

use \Exception;

/**
 * Interface specifing methods for accessing an application's config.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface Config
{
	/**
	 * Gets the appName for the application. 
	 * 
	 * @return string
	 */
	public function getAppName();
	/**
	 * Get a property value from a Config instance.
	 *
	 * @param string $property the property name.
	 * @return mixed either a string or array representing
	 *         the property value or null if the property
	 *         is not found or has no value.
	 */
	public function getProperty($property, $root = false);
	
	/**
	 * Gets the namespace of the Model's for the application. 
	 * 
	 * @return string
	 */
	public function getModelNamespace();
	
	/**
	 * Gets the Logger instance. 
	 * 
	 * @return Logger
	 */
	public function getLogger();
	
	/**
	 * Gets the DB instance for the id passed in. 
	 * 
	 * @return DB
	 */
	public function getDB($dbId = null);
	
	/**
	 * Gets the DB configuration for the id passed in 
	 * or an array of all the db's config.
	 *
	 * @return array
	 */
	public function getDBConfig($dbId = null);
}
?>