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
abstract class AppConfigLoader implements AppConstants
{
	/**
	 * Holds the properties from the AppConfig file.
	 *
	 * @var array
	 */
	private $appProperties = null;
	
	/**
	 * Gets any other config that the derived class requires and sets if into the $parentConfig.
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
	 * @return GrinderI
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
			$referencedConfigFile = isset($this->appProperties[self::REFERENCED_CONFIG_FILE]) ? 
			                              $this->appProperties[self::REFERENCED_CONFIG_FILE] : null;
			if ($referencedConfigFile)
			{
				$xml = trim(file_get_contents($referencedConfigFile));
				if ($xml)
				{
					$configTransformer->setXML($xml);
					$this->appProperties = $configTransformer->grind();
					$this->appProperties[self::REFERENCED_CONFIG_FILE] = $referencedConfigFile;
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
		$this->appProperties[self::CONFIG_FILE_NAME] = $appConfigFileName;
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
		if (isset($this->appProperties[self::OPTIONS_TAG]))
		{
			$options = $this->appProperties[self::OPTIONS_TAG];
			if (isset($options[self::OPTION_INCLUDE_PATH]))
			{
				self::appendIncludePath($options[self::OPTION_INCLUDE_PATH]);
			}
		}
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
	/**
	 * Get all the properties specified in the AppConfig file.
	 *
	 * @return array an associative array of all the config.
	 */
	public function getAppProperties()
	{
		return $this->appProperties;
	}
}
?>