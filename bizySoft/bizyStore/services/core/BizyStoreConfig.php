<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\AppConfig;
use bizySoft\common\AppOptions;
use bizySoft\common\XMLToArrayTransformer;

/**
 * Class for configuring bizyStore from the bizySoftConfig file.
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
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 * @codeCoverageIgnore
 */
final class BizyStoreConfig extends AppConfig
{
	/**
	 * Searches the include_path to locate the bizySoftConfig file.
	 * 
	 * Passes the bizySoftConfig filename to the parent constructor.
	 * 
	 * @throws Exception if the file cannot be opened or is not valid.
	 */
	protected function __construct()
	{
		/*
		 * Search include path for bizySoftConfig file.
		 */ 
		$fileToLoad = null;
		$fileNameBase = "bizySoft" . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR;
		$defaultFile = "{$fileNameBase}bizySoftConfig.xml";
		$filesToSearch = array();
		
		$serverName = $_SERVER["SERVER_NAME"];
		if (!empty($serverName))
		{
			/*
			 * We have a populated SERVER_NAME variable.
			 * 
			 * What we generally want is the domain part with 'www' stripped.
			 * $serverName may be a contrived entry for CLI programs.
			 */
			$domainPart = str_replace("www.", "", $serverName);

			if ($domainPart)
			{
				$filesToSearch[] = "$fileNameBase$domainPart.xml"; // domain.xml if exists
			}
		}

		/*
		 * Include the default config file name as a fallback.
		 */
		$filesToSearch[] = $defaultFile; // bizySoftConfig.xml if exists
					
		// File names are in order of preference with the domain based one first
		foreach ($filesToSearch as $file)
		{
			if ($fileToLoad = stream_resolve_include_path($file))
			{
				break;
			}
		}
		/*
		 * If we get here with a non-null $fileToLoad then it is guaranteed to exist.
		 */
		parent::__construct($fileToLoad);
	}

	/**
	 * Get the XML transformer for our config file.
	 * 
	 * @return GrinderI
	 * @see \bizySoft\common\AppConfig::getTransformer()
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
					BizyStoreOptions::BIZYSOFT_TAG => "bizySoft\common\BizySoftTag",
					BizyStoreOptions::BIZYSTORE_TAG => "bizySoft\bizyStore\services\core\BizyStoreTag",
					BizyStoreOptions::DATABASE_TAG => "bizySoft\bizyStore\services\core\DatabaseTag",
					BizyStoreOptions::DB_RELATIONSHIPS_TAG => "bizySoft\bizyStore\services\core\RelationshipsTag",
					BizyStoreOptions::REL_FOREIGN_KEYS_TAG => "bizySoft\bizyStore\services\core\ForeignKeysTag",
					BizyStoreOptions::REL_RECURSIVE_TAG => "bizySoft\bizyStore\services\core\RecursiveTag",
					BizyStoreOptions::DB_TABLES_TAG => "bizySoft\bizyStore\services\core\TablesTag",
					BizyStoreOptions::PDO_OPTIONS_TAG => "bizySoft\common\ConstantOptionsTag",
					BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG => "bizySoft\common\ConstantOptionsTag",
					BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG => "bizySoft\common\ConstantOptionsTag",
					BizyStoreOptions::OPTIONS_TAG => "bizySoft\common\ConstantOptionsTag"
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
	 * @see \bizySoft\bizyStore\services\core\AppConfig::getDerivedConfig()
	 */
	protected function getDerivedConfig(array &$parentConfig)
	{
		/*
		 * Determine the install directory for bizyStore.
		 *
		 * There will always be a "config" part in the file name we have plucked
		 * from the include_path or else BizyStoreConfig could not resolve itself.
		 */
		$delimiter = DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR;
		$fileNameBits = explode($delimiter, $parentConfig[BizyStoreOptions::CONFIG_FILE_NAME]);
		$bizyStoreInstallDir = $fileNameBits[0] . DIRECTORY_SEPARATOR . "bizyStore";
		$parentConfig[BizyStoreOptions::INSTALL_DIR] = $bizyStoreInstallDir;
		
		$namespace = $parentConfig[BizyStoreOptions::APP_NAME_TAG];
		
		$modelDir = $bizyStoreInstallDir . DIRECTORY_SEPARATOR . "model" . ($namespace ? DIRECTORY_SEPARATOR . $namespace : "");
		$parentConfig[BizyStoreOptions::BIZYSTORE_MODEL_DIR] = $modelDir;
		
		$modelNamespace = "bizySoft\\bizyStore\\model" . ($namespace ? "\\$namespace" : "");
		$parentConfig[BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE] = $modelNamespace;
	}
	
	/**
	 * Configure the App by initialising the singleton BizyStoreConfig object and all database config specified in the bizySoftConfig file.
	 *
	 * Called by bootstrap.php
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new BizyStoreConfig();
			/*
			 * Only call Logger after static config is initialised, we need to read the bizySoftConfig file for a logFile entry.
			 */
			$config = self::getAppProperties();
			$referencedFile = isset($config[AppOptions::REFERENCED_CONFIG_FILE]) ? $config[AppOptions::REFERENCED_CONFIG_FILE] : null;
			$configFile = self::getFileName();
			BizyStoreLogger::log(__METHOD__ . ": loaded config from " . ($referencedFile ? "$referencedFile through $configFile" : $configFile));
			BizyStoreLogger::log(__METHOD__ . ": include path is : " . get_include_path());
			BizyStoreLogger::log(__METHOD__ . ": Model classes will be loaded from : " . BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_DIR));
			//BizyStoreLogger::log(__METHOD__ . ": config = " . print_r(BizyStoreConfig::getAppProperties(), true));
		}
	}
}
?>