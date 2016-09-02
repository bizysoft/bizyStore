<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\AppConfig;

/**
 * Config for bizyStore.
 * 
 * BizyStoreConfig is initialised by including bootstrap.php first in your entry level php file.
 * 
 * eg.
 * 
 * include str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");
 * 
 * str_replace() provides operating system compatibility. Your include_path should have an entry that
 * allows the bizySoft directory to be resolved. eg. if you install the bizySoft directory into /var/www
 * then your include_path should include /var/www.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
final class BizyStoreConfig extends AppConfig implements Config, BizyStoreConstants
{
	/**
	 * Logger instance for the application.
	 * 
	 * @var Logger
	 */
	private $logger;
	/**
	 * DBManager instance for the application.
	 * 
	 * @var DBManager
	 */
	private $dbManager;
	
	/**
	 * Initialises class varables
	 */
	protected function __construct()
	{
		parent::__construct();
		/*
		 * Set up class vars only after initialisation.
		 * 
		 * Get the logger
		 */
		$appName = $this->getAppName();
		$options = $this->getProperty(self::OPTIONS_TAG, true);
		$logFile = $options ? (isset($options[self::OPTION_LOG_FILE]) ? $options[self::OPTION_LOG_FILE] : null) : null;
		
		if (!$logFile)
		{
			/*
			 * Default to bizySoft/bizyStore/logs/$appName.log if possible.
			 */
			$bizyStoreInstallDir = $this->getProperty(self::INSTALL_DIR, true);
			$bizyStoreLogDir = $bizyStoreInstallDir . DIRECTORY_SEPARATOR . "logs";
				
			$logFile = $bizyStoreLogDir . DIRECTORY_SEPARATOR . $appName . ".log";
		}
		$defaultLogger = 'bizySoft\bizyStore\services\core\BizyStoreLogger';
		$logger = $options ? (isset($options[self::OPTION_LOGGER]) ? $options[self::OPTION_LOGGER] : $defaultLogger) : $defaultLogger;

		$this->logger = new $logger($appName, $logFile);
		$this->dbManager = new DBManager($this);
	}

	/**
	 * Gets the Logger instance. 
	 * 
	 * Satisfies the Config interface.
	 * 
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
	
	/**
	 * Gets the namespace of the Model's for the application. 
	 * 
	 * Satisfies the Config interface.
	 * 
	 * @return string
	 */
	public function getModelNamespace()
	{
		return $this->getProperty(self::BIZYSTORE_MODEL_NAMESPACE , true);
	}
	
	/**
	 * Gets the DB instance for the id passed in. 
	 * 
	 * Satisfies the Config interface.
	 * 
	 * @return DB
	 */
	public function getDB($dbId = null)
	{
		return $this->dbManager->getDB($dbId);
	}
	
	/**
	 * Gets the DB configuration for the id passed in. 
	 * 
	 * Satisfies the Config interface.
	 * 
	 * @return array
	 */
	public function getDBConfig($dbId = null)
	{
		return $this->dbManager->getDBConfig($dbId);
	}
	
	/**
	 * Closes all DB instances for the application.
	 *
	 * Satisfies the Config interface.
	 *
	 */
	public function closeDBs()
	{
		$this->dbManager->close();
	}
	
	/**
	 * Gets the config class name for the application, independent of whether BizyStoreConfig has
	 * been initialised.
	 *
	 * This is the fully qualified class name of the generated config, called by AppConfig to 
	 * initialise this class instance.
	 */
	public function getConfigClass()
	{
		$domain = self::getDomain();
		
		return self::getConfigClassName($domain);
	}
	
	/**
	 * Gets the config class name via the domain this server runs on independent of whether BizyStoreConfig has 
	 * been initialised.
	 * 
	 * @return string
	 */
	public static function getConfigClassName($domain)
	{
		$domainClass = self::camelCapsDomain($domain);
		
		return 'bizySoft\bizyStore\config' . "\\{$domainClass}Config";
	}
	
	/**
	 * Configure the App by initialising the singleton BizyStoreConfig object and all database config specified in the 
	 * bizySoftConfig file.
	 *
	 * Called by bootstrap.php
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new BizyStoreConfig();
		}
	}
}

?>