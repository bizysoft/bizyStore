<?php

namespace bizySoft\tests\services;

use bizySoft\common\Logger;

/**
 * Provides support for logging with timer functionality.
 * 
 * Used in our unit tests.
 *         
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 * @codeCoverageIgnore
 */
final class TestLogger extends Logger
{
	private static $showInterval = false;
	const MICRO = 1000000;
	
	/**
	 * Store the last time log() was called
	 *
	 * @var number
	 */
	private static $lastTime = 0;
	
	/**
	 * Allow multiple log timers to be used
	 *
	 * @var array
	 */
	private static $timers = array();
	
	protected function __construct()
	{
		$testDir = "bizySoft" . DIRECTORY_SEPARATOR . "tests";
		$testDir = stream_resolve_include_path($testDir);
		
		parent::__construct("unitTest", $testDir . DIRECTORY_SEPARATOR . "unitTest.log");
	}
	
	/**
	 * Set a new prefix for the message.
	 *
	 * If TestLogger::$showInterval is true, prepends an interval to the log message since the last 
	 * TestLogger::log() call in micro-seconds.
	 */
	protected function getPrefix()
	{
		$time = self::getTimeMicro();
		$timePrefix = self::$showInterval ? (self::$lastTime != 0 ? sprintf("% 8d", $time - self::$lastTime) : 0) . " us:" : "";
		$newPrefix = parent::getPrefix() . $timePrefix;
		self::$lastTime = self::getTimeMicro(); // Don't count the above time to format.
		return $newPrefix;
	}
	
	/**
	 * Get time in micro-seconds.
	 * 
	 * @return number
	 */
	private static function getTimeMicro()
	{
		/*
		 * microtime() is supposedly accurate to microseconds,
		 * so internally store as microsecs
		 */
		return microtime(true) * self::MICRO;
	}
	
	/**
	 * Format a time or interval as a string.
	 *
	 * @param float $time in microsecs.
	 * @param int $divisor to show time in other units eg millisecs, secs etc.
	 */
	private static function format($time, $divisor = 1)
	{
		return sprintf("%9.2f", $time / $divisor);
	}
	/**
	 * Time parameters are in micro-seconds.
	 *
	 * @param float $start
	 */
	private static function getElapsed($start)
	{
		return self::getTimeMicro() - $start;
	}
	
	/**
	 * Set display of log fractional seconds.
	 *
	 * @param boolean $showInterval if true then the logs shows an interval since the last log call
	 */
	public static function showInterval($showInterval)
	{
		self::$showInterval = $showInterval;
	}
	
	/**
	 * Start a named timer.
	 * 
	 * Very useful for timing portions of code. Gives you the capability of using nested timers.
	 *
	 * @param string $name
	 */
	public static function startTimer($name)
	{
		self::$timers[$name] = self::getTimeMicro();
	}
	
	/**
	 * Stop a named timer and log the elapsed time in milliseconds since
	 * startTimer($name) was called
	 *
	 * @param string $name
	 * @return float the elapsed time in micro seconds
	 */
	public static function stopTimer($name)
	{
		$microElapsed = self::elapsedTimer($name);
		$elapsed = self::format($microElapsed, 1000);
		
		if (isset(self::$timers[$name]))
		{
			self::log("Stopped timer ---'$name'---, elapsed = $elapsed ms");
			// Destroy the timer
			unset(self::$timers[$name]);
		}
		else
		{
			self::log("No timer ---'$name'---");
		}
		
		return $microElapsed;
	}
	
	/**
	 * Get the elapsed time in micro-secs since the timer was started
	 *
	 * @return float
	 */
	public static function elapsedTimer($name)
	{
		return isset(self::$timers[$name]) ? self::getElapsed(self::$timers[$name]) : -1;
	}
	
	/**
	 * Configure the TestLogger.
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new TestLogger();
			TestLogger::showInterval(true);
		}
	}
}

?>