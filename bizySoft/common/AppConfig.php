<?php
namespace bizySoft\common;

use \Exception;
/**
 * Base class for configuring an application from its config file.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class AppConfig extends Singleton implements AppConstants
{
	/**
	 * Holds the properties from the AppConfig file.
	 *
	 * @var array
	 */
	private $appProperties = null;
	
	/**
	 * Initialise with the config file name.
	 * 
	 * Can only be constructed from derived classes.
	 * 
	 * @throws Exception if the file cannot be opened or is not valid.
	 */
	protected function __construct()
	{
		/**
		 * build a class name
		 */
		$configClass = $this->getConfigClass();
		/*
		 * If the class does not exist then the autoloader implementation will generate it
		 * by reading the bizySoftConfig file.
		 */
		$config = new $configClass();
		
		$this->appProperties = $config->getConfig();
		
		parent::__construct();
	}
	
	/**
	 * Gets the fully qualified name of the generated confg class.
	 * 
	 * @return string
	 */
	abstract public function getConfigClass();
	
	/**
	 * Allow implementations to set properties.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	protected function setProperty($name, $value)
	{
		$this->appProperties[$name] = $value;
	}
	
	/**
	 * 
	 * @param string $property The property key to find.
	 * @param boolean $root Search only the root of the appProperties.
	 * @return mixed
	 */
	public function getProperty($property, $root = false)
	{
		$result = null;
		
		$properties = $this->appProperties;
		if ($root)
		{
			/*
			 * Just search the root of the array. Use this if your are sure that
			 * the property is in the root of the appProperties.
			 */
			$result = isset($properties[$property]) ? $properties[$property] : null;
		}
		else 
		{
			/*
			 * Search the whole of appProperties for a matching name
			 */
			$optionHandler = new ArrayOptionHandler($properties);
			$option = $optionHandler->getOption($property);
			
			if ($option)
			{
				$result = $option->value;
			}
		}
		return $result;
	}
	
	/**
	 * Gets the name of the application
	 *
	 * @return string
	 */
	public function getAppName()
	{
		return $this->getProperty(self::APP_NAME_TAG, true);
	}
	
	/**
	 * Gets the domain name from the PHP env.
	 * 
	 * @return string
	 */
	public static function getDomain()
	{
		$prefix = "www.";
		$domain = $_SERVER["SERVER_NAME"];
		
		if (substr($domain, 0, strlen($prefix)) == $prefix) {
			$domain = substr($domain, strlen($prefix));
		}
		
		return $domain;
	}
	
	/**
	 * This may be used as a basis for the config class file.
	 *
	 * @param string $domain
	 * @return string
	 */
	public static function camelCapsDomain($domain)
	{
		$domainName = "";
		$domainParts = explode(".", $domain);
		foreach ($domainParts as $domainPart)
		{
			$domainName .= ucfirst($domainPart);
		}
		
		return $domainName;
	}
	
}
?>