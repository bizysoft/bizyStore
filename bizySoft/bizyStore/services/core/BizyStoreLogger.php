<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\Logger;

/**
 * Log to the bizyStore log file specified by:
 * 
 * <ul>
 * <li>bizySoftConfig &lt;logFile&gt;</li>
 * <li>The bizySoft/bizyStore/logs/&lt;appName&gt;.log if bizySoftConfig does not specify a logFile.</li>
 * <li>PHP error_log if bizySoft/bizyStore/logs/&lt;appName&gt;.log is not writeable.</li>
 * </ul>
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class BizyStoreLogger extends Logger
{
	protected function __construct()
	{
		/*
		 *  <appName> is a mandatory field, it must exist or we wouldn't get to here.
		 */
		$appName = BizyStoreConfig::getProperty(BizyStoreOptions::APP_NAME_TAG);
		/*
		 *  bizySoftConfig contains the full path name of the logFile
		 */
		$logFile = BizyStoreConfig::getProperty(BizyStoreOptions::OPTION_LOG_FILE);
		if (!$logFile)
		{
			/*
			 * Default to bizySoft/bizyStore/logs/$appName.log if possible.
			 */
			$bizyStoreInstallDir = BizyStoreConfig::getProperty(BizyStoreOptions::INSTALL_DIR);
			$bizyStoreLogDir = $bizyStoreInstallDir . DIRECTORY_SEPARATOR . "logs";
				
			if (file_exists($bizyStoreLogDir))
			{
				$bizyStoreLogFile = $bizyStoreLogDir . DIRECTORY_SEPARATOR . $appName . ".log";
				$logFile = is_writable($bizyStoreLogDir) ? $bizyStoreLogFile : null;
			}
		}
		parent::__construct($appName, $logFile);
	}
	
	/**
	 * Configure the BizyStoreLogger.
	 *
	 * Note that it is a requirement that bizySoftConfig is initialised before configure() is called.
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new BizyStoreLogger();
		}
	}
}

BizyStoreLogger::configure();
?>