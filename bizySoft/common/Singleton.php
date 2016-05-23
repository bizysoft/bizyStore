<?php
namespace bizySoft\common;

/**
 * Provides basics for Singleton pattern based on derived class.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class Singleton
{
	/**
	 * Singleton instances based on the derived class.
	 *
	 * @var mixed
	 */
	private static $singletons = array();
	
	/**
	 * Base the instance on the derived class name.
	 * 
	 * Initialise the singleton instance.
	 */
	protected function __construct()
	{
		self::$singletons[get_class($this)] = $this;
	}

	/**
	 * Gets the singleton instance.
	 * 
	 * Calls getDerivedInstance() in the static:: scope to invoke late binding.
	 */
	protected static function getInstance()
	{
		return static::getDerivedInstance();
	}
	
	/**
	 * Get the singleton based on the derived class.
	 */
	private static function getDerivedInstance()
	{
		$class = get_called_class();
		return isset(self::$singletons[$class]) ? self::$singletons[$class] : null;
	}
}

?>