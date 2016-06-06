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
 * @license  See the LICENSE file with this distribution.
 */
abstract class AppConfig extends Singleton
{
	/**
	 * Holds the properties from the AppConfig file.
	 *
	 * @var array
	 */
	private $appProperties = null;
	
	/**
	 * Gets any other config that the derived class requires.
	 * 
	 * @param array $parentConfig reference to this config.
	 */
	protected abstract function getDerivedConfig(array &$parentConfig);
	
	/**
	 * Get the config transformer from the derived class.
	 * 
	 * Used for transforming the AppConfig file to a form required by the application.
	 * This is application specific, usually an XMLToArrayTransformer.
	 * 
	 * @param string $fileName the filename to get the transformer on.
	 * 
	 * @returns GrinderI
	 */
	protected abstract function getTransformer($fileName);
	
	/**
	 * Initialise with the config file name.
	 * 
	 * Can only be constructed from derived classes.
	 * 
	 * @throws Exception if the file cannot be opened or is not valid.
	 */
	protected function __construct($appConfigFileName)
	{
		if (!$appConfigFileName)
		{
			throw new Exception("AppConfig file could not be resolved.");
		}
		parent::__construct();
		$this->initialise($appConfigFileName);
	}
	
	/**
	 * Initialises the in memory config entries from the AppConfig file.
	 * 
	 * @param $appConfigFileName the full path of the AppConfig file name.
	 * @throws Exception if the file cannot be opened or validated.
	 */
	private function initialise($appConfigFileName)
	{
		/*
		 * Read AppConfig file and set class variables
		 */
		$configTransformer = $this->getTransformer($appConfigFileName);
		$referencedConfigFile = null;
		if ($configTransformer)
		{
			$this->appProperties = $configTransformer->grind();
			$referencedConfigFile = isset($this->appProperties[AppOptions::REFERENCED_CONFIG_FILE]) ? 
			                              $this->appProperties[AppOptions::REFERENCED_CONFIG_FILE] : null;
			if ($referencedConfigFile)
			{
				$xml = trim(file_get_contents($referencedConfigFile));
				if ($xml)
				{
					$configTransformer->setXML($xml);
					$this->appProperties = $configTransformer->grind();
					$this->appProperties[AppOptions::REFERENCED_CONFIG_FILE] = $referencedConfigFile;
				}
				else
				{
					$class = get_class($this);
					throw new Exception("Unable to resolve $class file : $referencedConfigFile");
				}
			}
		}
		else
		{
			$class = get_class($this);
			throw new Exception("Unable to resolve $class file : $appConfigFileName");
		}
		/*
		 * Add the file name we processed into the config.
		 */
		$this->appProperties[AppOptions::CONFIG_FILE_NAME] = $appConfigFileName;
		/*
		 * Fill up the config with specific goodies.
		 * 
		 * We send over a reference to the appProperties to allow direct setting of config.
		 */
		$this->getDerivedConfig($this->appProperties);
		/*
		 * Now get the additional includePath from AppConfig if any is specified
		 * and set into the app include path.
		 */
		if (isset($this->appProperties[AppOptions::OPTION_INCLUDE_PATH]))
		{
			self::appendIncludePath($this->appProperties[AppOptions::OPTION_INCLUDE_PATH]);
		}
	}
	
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
	 * Get all the properties specified in the AppConfig file.
	 *
	 * @return array an associative array of all the config.
	 */
	public static function getAppProperties()
	{
		$appConfig = self::getInstance();
		
		return $appConfig ? $appConfig->appProperties : null;
	}
	
	/**
	 * Get a property value from AppConfig.
	 *
	 * @param string $property the property name.
	 * @return mixed either a string or array representing
	 *         the property value or null if the property
	 *         is not found or has no value.
	 */
	public static function getProperty($property)
	{
		$result = null;
		
		$properties = self::getAppProperties();
		
		$optionHandler = new ArrayOptionHandler($properties);
		$option = $optionHandler->getOption($property);
		
		if ($option)
		{
			$result = $option->value;
		}
		
		return $result;
	}
	
	/**
	 * Gets the name of the application
	 *
	 * @return string
	 */
	public static function getAppName()
	{
		return self::getProperty(AppOptions::APP_NAME_TAG);
	}
	
	/**
	 * Gets the full path of the AppConfig file name loaded;
	 *
	 * @return string
	 */
	public static function getFileName()
	{
		return self::getProperty(AppOptions::CONFIG_FILE_NAME);
	}
	
	/**
	 * Typically used to append the AppConfig &lt;includePath&gt; option to the PHP include_path.
	 *
	 * @param string $includePath
	 */
	public static function appendIncludePath($includePath)
	{
		if ($includePath)
		{
			$path = get_include_path() . PATH_SEPARATOR . $includePath;
			set_include_path($path);
		}
	}
}
?>