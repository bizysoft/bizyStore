<?php
/**
 * This is the bootstrap file for bizyStore.
 * 
 * Registers the bizyStore auto-loader and initialises the required components on a once off basis. 
 * The directory that contains the 'bizySoft' directory must be on your include_path so that the bizySoftConfig file 
 * can be found and class files can be loaded by the auto-loader.
 * 
 * It's recommended practice for the 'bizySoft' directory to be OUTSIDE the DOCUMENT_ROOT of your web server to protect 
 * sensitive information in your bizySoftConfig file. You must still give your web server or cli program write access 
 * to the bizySoft directories for Schema file generation or logging if required.
 * 
 * eg. for a local installation, if the standard DOCUMENT_ROOT for Apache on Ubuntu is '/var/www/html' then you may want 
 * to install the 'bizySoft' directory into '/var/www' and add '/var/www' to the PHP include_path so that the 'bizySoft' 
 * directory is resolvable. This directory varies for hosted environments and will usually be your hosted account's
 * home directory which is a level above your public_html directory (DOCUMENT_ROOT).
 * 
 * It is only needed to include this file in your entry level php files that are directly called by a web server or CLI 
 * program. You should not need to include/require any other file if your code uses 'namespace' and 'use' statements 
 * that comply to PSR-4.
 */
use bizySoft\bizyStore\generator\ModelGenerator;
use bizySoft\bizyStore\generator\BizyStoreConfigGenerator;
use bizySoft\bizyStore\services\core\BizyStoreConfigLoader;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
/*
 * Explicitly include the base class auto-loader, it's needed before registration.
 */
include str_replace("/", DIRECTORY_SEPARATOR, 'bizySoft/common/IncludePathAutoloader.php');

/**
 * bizyStore implementation of the IncludePathAutoloader with an over-ridden appLoad() method. 
 * 
 * Config/Model class generation is automatic, the mechanism is implemented in the appLoad() method.
 * 
 * For Config, the auto-loader will generate a config class file based on the bizySoftConfig xml file and try to load again. 
 * bizyStore will use the config class file for all subsequent runs unless it is removed and re-generated by the auto-loader.
 * 
 * For Models, if the autoloader can't load the definition of a 'Model' class, we generate them from the 
 * database(s) specified in the bizySoftConfig file and try to load again.
 *
 * This is a once off for the bizyStore installation, unless your database schema or the bizySoftConfig file changes, in which case
 * you should remove the bizySoft\bizyStore\app\&lt;appName&gt; directory and the domain based config file in generated into
 * bizySoft\bizyStore\config.
 *
 * Please note that automatic Model/Schema class generation via BizyStoreAutoloader is not possible without the fully 
 * qualified Model class name. There must be a namespace or use statement or an explicit specification of the Model class 
 * for auto class generation to work properly.
 * 
 * If you require your code to dynamically create a Model class then you can explicitly specify the fully qualified name when 
 * instantiating your object. In this case the fully qualified path to your Model object would be:
 * bizySoft\bizyStore\app\&lt;appName&gt;. You can also get this from BizyStoreConfig. 
 * 
 * eg. 
 * 
 * <code>
 * $config = BizyStoreConfig::getInstance();
 * $modelNamespace = $config->getModelNamespace();
 * $modelClass = "$modelNamespace\\SomeModelClass";
 * $modelObj = new $modelClass();
 * </code>
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License.
 */
final class BizyStoreAutoloader extends IncludePathAutoloader
{
	/**
	 * Indicator of successful class generation. 
	 * 
	 * Stops recursion on failed generation attempts.
	 *
	 * @var boolean
	 */
	private static $schemaGenerated = false;
	private static $configGenerated = false;
	
	/**
	 * This method attempts to generate the Config/Model classes wrt the envronment/bizySoftConfig file and call the load() 
	 * method again.
	 * 
	 * @param string $className
	 */
	protected function appLoad($className)
	{
		$loaded = false;
		$domain = BizyStoreConfig::getDomain();
		
		if ($className == BizyStoreConfig::getConfigClassName($domain))
		{
			if (!self::$configGenerated)
			{
				self::$configGenerated = true;
				/*
				 * The BizyStoreConfig class has been auto-loaded but not initialised, we are in the constructor of AppConfig
				 * at this point, so care must be taken to only call static BizyStoreConfig methods that don't use 
				 * the instance.
				 * 
				 * The xml config file is converted to a class for optimum performance.
				 * 
				 * If your config changes then you should delete the <Domain>Config.php class file from
				 * bizySoft\bizyStore\config\ to reflect the changes.
				 */
				$configLoader = new BizyStoreConfigLoader($domain);
				$configGenerator = new BizyStoreConfigGenerator();
				
				$configGenerator->generate($configLoader->getAppProperties());
				/*
				 * Now the BizyStoreConfig instance can be set via the generated <Domain>Config.php class.
				 * 
				 * Have another go at loading the config.
				 */
				$loaded =  $this->load($className);
			}
		}
		else
		{
			if (!self::$schemaGenerated)
			{
				/*
				 * Make sure BizyStoreConfig has been initialised so we can use methods that address the instance.
				 * 
				 * Note, as relationship specifics can't be determined from here, ALL the Model and Schema files must be generated 
				 * to handle any foreign key declarations, which need to be processed from both ends of the relationship.
	 			 * 
	 			 * Also note that once generation occurs, it is persistent in that the auto-loader will always find ALL the Model classes
	 			 * specified by the database(s)/bizySoftConfig file and this method will never be called for a Model class 
	 			 * again (unless the generated files are removed).
				 */
				$config = BizyStoreConfig::getInstance();
				$modelNamespace = $config->getModelNamespace();
				if (strstr($className, $modelNamespace))
				{
					self::$schemaGenerated = true;
					/*
					 * We are missing the definition of a Model class, so Model class generation has not happened yet.
					 *
					 * This generates ALL the Model and Schema files for ALL databases in bizySoftConfig, so only generate it once.
					 */
					$modelGenerator = new ModelGenerator($config);
					$modelGenerator->generate();
					/*
					 * Have another go at loading the class.
					 */
					$loaded =  $this->load($className);
				}
			}
		}
		return $loaded;
	}
}
/*
 * Register the loader.
 */
spl_autoload_register( array( new BizyStoreAutoloader(), 'load'));
/*
 * BizyStoreConfig is configured straight away.
 */
BizyStoreConfig::configure();
/*
 * Hold a reference to the BizyStoreConfig instance for the full
 * request life-cycle then close the configured db's nicely.
 */
register_shutdown_function(array(BizyStoreConfig::getInstance(), "closeDBs"));

?>