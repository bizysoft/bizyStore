<?php
namespace bizySoft\common;

/**
 * Simple Logger class. Uses error_log functionality to log to different files.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class Logger extends Singleton
{
	const SEND_TO_FILE = 3;
	
	/**
	 * Name of the file to log to.
	 * 
	 * @var string
	 */
	private $logFile = null;
	
	/**
	 * The time zone that date() uses.
	 * 
	 * @var string
	 */
	private $timeZone = null;
	
	/**
	 * The appName to use in log messages.
	 * 
	 * @var string
	 */
	private $appName = null;
	
	/**
	 * Turn logging capabilities on/off.
	 * 
	 * @var boolean
	 */
	private $logging = true;
	
	/**
	 * Set up singleton instance variables.
	 */
	protected function __construct($appName, $logFile)
	{
		parent::__construct();
		$this->timeZone = date_default_timezone_get(); // Get the time zone that date() uses
		$this->appName = $appName;
		$this->logFile = $logFile;
	}
		
	
	/**
	 * Get the default prefix for the message.
	 * 
	 * You can override this in implementations to produce a custom prefix.
	 */
	protected function getPrefix()
	{
		/*
		 * This is similar to the standard log prefix used in the php error_log
		 */
		return "[" . date('Y-m-d H:i:s') . " " . $this->timeZone . "]:" . $this->appName . ":";
	}
	
	/**
	 * Log a message either to the file specified or the php.ini error_log.
	 * 
	 * @param string $message
	 * @param array $context
	 */
	private function logMessage($message, array $context = array())
	{
		if ($this->logFile)
		{
			/*
			 * Log to the specified file
			 */
			error_log($this->getPrefix() . $message . PHP_EOL, self::SEND_TO_FILE, $this->logFile);
		}
		else
		{
			/*
			 * Log to php log file
			 */
			error_log($this->appName . ": " . $message);
		}
	}
	
	/**
	 * Public interface to log to the singleton instance.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public static function log($message, array $context = array())
	{
		$logger = self::getInstance();
		if ($logger->logging)
		{
			$logger->logMessage($message, $context);
		}
	}
	
	/**
	 * Public interface to turn logging on or off via the singleton instance.
	 *
	 * @param boolean $onOff
	 * @returns boolean the previous state.
	 */
	public static function logging($onOff)
	{
		$logger = self::getInstance();
		$oldLogging = $logger->logging;
		$logger->logging = $onOff;
		return $oldLogging;
	}
}

?>