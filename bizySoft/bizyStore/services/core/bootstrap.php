<?php
/**
 * This is the bootstrap file for bizyStore.
 * 
 * Registers the bizyStore auto-loader and initialises the required components on a once off basis (BizyStoreConfig). 
 * The directory that contains the 'bizySoft' directory must be on your include_path so that the bizySoftConfig file 
 * can be found and class files can be loaded by the auto-loader.
 * 
 * It's recommended practice for the 'bizySoft' directory to be OUTSIDE the DOCUMENT_ROOT of your web server to protect 
 * sensitive information in your bizySoftConfig file. You must still give your web server or cli program write access 
 * to the bizySoft directories for Schema file generation or logging if required.
 * 
 * eg. for a local installation, if the standard DOCUMENT_ROOT for Apache on Ubuntu is '/var/www/html' then you may want 
 * to install the 'bizySoft' directory into '/var/www' and add '/var/www' to the PHP include_path so that the 'bizySoft' 
 * directory is resolvable. This will directory will vary for hosted environments and will usually be your hosted account's
 * home directory which is a level above your DOCUMENT_ROOT (public_html directory).
 * 
 * It is only needed to include this file in your entry level php files that are directly called by a web server or CLI 
 * program. You should not need to include/require any other file if your code uses 'namespace' and 'use' statements 
 * that comply to PSR-4.
 */
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\generator\ModelGenerator;
/*
 * We have to explicitly include the base class auto-loader because we need it before registration.
 */
include str_replace("/", DIRECTORY_SEPARATOR, 'bizySoft/common/IncludePathAutoloader.php');

/**
 * bizyStore implementation of the IncludePathAutoloader with an over-ridden appLoad() method. 
 * 
 * If IncludePathAutoloader can't load the definition of a 'Model' class, we generate them from the 
 * database(s) specified in the bizySoftConfig file and try to load again.
 *
 * This is a once off for the bizyStore installation, unless your database schema or the bizySoftConfig file changes.
 *
 * Please note that automatic Model/Schema class generation via BizyStoreAutoloader is not possible without the fully 
 * qualified Model class name. There must be a namespace or use statement or an explicit specification of the Model class 
 * for auto class generation to work properly.
 * 
 * If you require your code to dynamically create a Model class then you can explicitly specify the fully qualified name when 
 * instantiating your object. In this case the fully qualified path to your Model object would be:
 * bizySoft\bizyStore\model\&lt;appName&gt;, where &lt;appName&gt; is from the bizySoftConfig file. You can also 
 * get this from BizyStoreConfig. 
 * 
 * eg. 
 * 
 * <code>
 *     $modelNamespace = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE);
 *     $modelClass = "$modelNamespace\\SomeModelClass";
 *     $modelObj = new $modelClass();
 * </code>
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
final class BizyStoreAutoloader extends IncludePathAutoloader
{
	/**
	 * Indicator of succesful class generation. We only want to attempt generation once if required at all.
	 *
	 * @var boolean
	 */
	private static $generated = false;

	/**
	 * Model class generation is automatic, the mechanism is implemented in the auto-loader by using this method over-ride.
	 *
	 * This method attempts to generate the Model classes wrt the bizySoftConfig file and call the load() method again.
	 *
	 * Note, as relationship specifics can't be determined from here, ALL the Model and Schema files must be generated 
	 * to handle any foreign key declarations, which need to be processed from both ends of the relationship.
	 * 
	 * Also note that once generation occurs, it is persistent in that the auto-loader will always find ALL the Model classes
	 * specified by the database(s)/bizySoftConfig file and this method will never be called for a Model class 
	 * again (unless the generated files are removed).
	 */
	protected function appLoad($className)
	{
		$loaded = false;
		if (!self::$generated)
		{
			/*
			 * BizyStoreConfig is guaranteed to be configured before appLoad() is called.
			 */
			$modelNamespace = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE);
			if (strstr($className, $modelNamespace))
			{
				/*
				 * We are missing the definition of a Model class, so Model class generation has not happened yet.
				 *
				 * This generates ALL the Model and Schema files for ALL databases in bizySoftConfig, so only generate it once.
				 */
				$modelGenerator = new ModelGenerator();
				$modelGenerator->generate();
				self::$generated = true;
				/*
				 * Have another go at loading the classes.
				 */
				$loaded =  $this->load($className);
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
 * Configure bizyStore
 */
BizyStoreConfig::configure();

?>