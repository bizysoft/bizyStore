<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\AppConfigLoader;
use bizySoft\common\XMLToArrayTransformer;

/**
 * Called by the autoLoader to build an array based on the contents of bizySoftConfig xml file.
 *
 * The default bizySoftConfig file name is 'bizySoft/config/bizySoftConfig.xml'. The file name can also be 
 * based on the domain part of the $\_SERVER["SERVER_NAME"] PHP variable. It takes the general form of 
 * [domain].xml.
 * 
 * In your case it may be yourDomainName.com.xml or localhost.xml etc. giving you the 
 * option of providing configurations for different environments/situations instead of a statically named config file.
 * 
 * It can work in conjunction with the virtual hosts config of your web server to allow different databases to be 
 * configured based on the requested domain. This can allow you to have a single bizyStore installation specified on your 
 * include_path configured for any number of virtual hosts.
 * 
 * Potentially, it can also give you added security. Using a domain based config file with the default config file left 
 * empty will, in effect, black-list an unconfigured domain request from running bizyStore code because the white-list 
 * is embedded in the domain based config file name.
 * 
 * You can also use this feature for PHP CLI programs. In this case, manipulating the SERVER_NAME variable in your CLI 
 * script can provide the means. This is how our unit tests are configured.
 * 
 * <span style="color:orange">bizySoftConfig files can contain SENSITIVE INFORMATION and should be SECURED so that the web server 
 * never serves them as content. The recommended way of doing this is to install the 'bizySoft' directory outside the 
 * DOCUMENT_ROOT of your web server.</span>
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. Details at</span> <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 * @codeCoverageIgnore
 */
final class BizyStoreConfigLoader extends AppConfigLoader implements BizyStoreConstants
{
	/**
	 * 
	 * @var string
	 */
	private $domain;
	
	/**
	 * 
	 * @var string
	 */
	private $bizyStorePath;
	
	/**
	 * Searches the include_path to locate the bizySoftConfig file.
	 * 
	 * Passes the bizySoftConfig file name to the parent constructor.
	 * 
	 * @param string $domain The domain for this application.
	 * @throws Exception if the file cannot be opened or is not valid.
	 */
	public function __construct($domain)
	{
		$this->domain = $domain;
		parent::__construct($this->getConfigFileName());
	}
	
	/**
	 * Get the XML transformer for our config file.
	 *
	 * @param string $appConfigFileName The file name to load.
	 * @return GrinderI
	 */
	protected function getTransformer($appConfigFileName)
	{
		$xml = trim(file_get_contents($appConfigFileName));
		$configTransformer = null;
	
		if ($xml)
		{
			/*
			 * Tag classes we use to validate/transform the XML.
			 */
			$tagClasses = array(
					self::BIZYSOFT_TAG => 'bizySoft\common\BizySoftTag',
					self::BIZYSTORE_TAG => 'bizySoft\bizyStore\services\core\BizyStoreTag',
					self::DATABASE_TAG => 'bizySoft\bizyStore\services\core\DatabaseTag',
					self::DB_RELATIONSHIPS_TAG => 'bizySoft\bizyStore\services\core\RelationshipsTag',
					self::REL_FOREIGN_KEYS_TAG => 'bizySoft\bizyStore\services\core\ForeignKeysTag',
					self::REL_RECURSIVE_TAG => 'bizySoft\bizyStore\services\core\RecursiveTag',
					self::DB_TABLES_TAG => 'bizySoft\bizyStore\services\core\TablesTag',
					self::PDO_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::PDO_PREPARE_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::MODEL_PREPARE_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::REST_SERVICES_TAG => 'bizySoft\common\ConstantOptionsTag',
			);
			$configTransformer = new XMLToArrayTransformer($xml, $tagClasses);
		}
		return $configTransformer;
	}
	
	/**
	 * Gets the config that may be required but does not appear in the bizySoftConfig file.
	 *
	 * The required config is set into the parent's config parameter passed in.
	 * 
	 * @param array $parentConfig A reference to the parent config.
	 * @return array
	 */
	protected function getDerivedConfig(array &$parentConfig)
	{
		/*
		 * Determine the install directory for bizyStore.
		 */
		$bizyStoreInstallDir = $this->bizyStorePath . DIRECTORY_SEPARATOR . "bizySoft" . DIRECTORY_SEPARATOR . "bizyStore";
		$parentConfig[self::INSTALL_DIR] = $bizyStoreInstallDir;
		/*
		 * Set up the namespace based properties
		 */
		$appName = $parentConfig[self::APP_NAME_TAG];
		
		$configDir = $bizyStoreInstallDir . DIRECTORY_SEPARATOR . "config";
		
		$modelBaseDir = $bizyStoreInstallDir . DIRECTORY_SEPARATOR . "app";
		$modelDir = $modelBaseDir . DIRECTORY_SEPARATOR . $appName;
		
		$parentConfig[self::BIZYSTORE_MODEL_BASE_DIR] = $modelBaseDir;
		$parentConfig[self::BIZYSTORE_MODEL_DIR] = $modelDir;
		
		$parentConfig[self::BIZYSTORE_CONFIG_DIR] = $configDir;
		
		$parentConfig[self::BIZYSTORE_MODEL_NAMESPACE] = 'bizySoft\bizyStore\app' . "\\$appName";
		$parentConfig[self::BIZYSTORE_CONFIG_NAMESPACE] = 'bizySoft\bizyStore\config';
		
		$configClass = BizyStoreConfig::camelCapsDomain($this->domain);

		$parentConfig[self::BIZYSTORE_CONFIG_CLASS] = $configClass . "Config";
	}
	
	/**
	 * Gets the bizySoftConfig file name to load from the include path.
	 * 
	 * @return string
	 */
	private function getConfigFileName()
	{
		/*
		 * Search include path for bizySoftConfig file.
		 */ 
		$fileToLoad = null;
		$fileNameBase = "bizySoft" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR;
		$defaultFile = "{$fileNameBase}bizySoftConfig.xml";
		$filesToSearch = array();
		
		$filesToSearch[] = "$fileNameBase{$this->domain}.xml"; // domain.xml if exists
		
		/*
		 * Include the default config file name as a fallback.
		 */
		$filesToSearch[] = $defaultFile; // bizySoftConfig.xml if exists
			
		// File names are in order of preference with the domain based one first
		$file = null;
		foreach ($filesToSearch as $file)
		{
			if ($fileToLoad = stream_resolve_include_path($file))
			{
				break;
			}
		}
		
		if ($fileToLoad && $file)
		{
			$path = explode(DIRECTORY_SEPARATOR . $file, $fileToLoad);
			if (isset($path[0]))
			{
				$this->bizyStorePath = $path[0];
			}
		}
		/*
		 * If we get here with a non-null $fileToLoad then it is guaranteed to exist.
		 */
		return $fileToLoad;
	}
}
?>