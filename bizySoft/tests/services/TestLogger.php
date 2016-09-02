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
 * @license LICENSE MIT License
 * @codeCoverageIgnore
 */
final class TestLogger extends Logger
{
	const MICRO = 1000000;
	
	/**
	 * Store the last time log() was called
	 *
	 * @var number
	 */
	private $lastTime = 0;
	
	/**
	 * Allow multiple log timers to be used
	 *
	 * @var array
	 */
	private $timers = array();
	
	public function __construct($appName, $logFile)
	{
		parent::__construct($appName, $logFile);
	}
	
	/**
	 * Start a named timer.
	 *
	 * Very useful for timing portions of code. Gives you the capability of using nested timers.
	 *
	 * @param string $name
	 */
	public function startTimer($name)
	{
		$this->timers[$name] = $this->getTimeMicro();
	}
	
	/**
	 * Stop a named timer and log the elapsed time in milliseconds since
	 * startTimer($name) was called
	 *
	 * @param string $name
	 * @return float the elapsed time in micro seconds
	 */
	public function stopTimer($name, $message = null)
	{
		$microElapsed = $this->elapsedTimer($name);
		$elapsed = $this->format($microElapsed, 1000);
	
		if (isset($this->timers[$name]))
		{
			$timerMessage = "Stopped timer ---'$name'---, elapsed = $elapsed ms";
			$this->log($message ? $message . " $timerMessage" : $timerMessage);
			// Destroy the timer
			unset($this->timers[$name]);
		}
		else
		{
			$this->log("No timer ---'$name'---");
		}
	
		return $microElapsed;
	}
	
	
	/**
	 * Set a new prefix for the message.
	 *
	 * Prepends an interval to the log message since the last 
	 * $this->logger->log() call in micro-seconds.
	 */
	protected function getPrefix()
	{
		$time = $this->getTimeMicro();
		$timePrefix = ($this->lastTime != 0 ? sprintf("% 8d", $time - $this->lastTime) : 0) . " us:";
		$newPrefix = parent::getPrefix() . $timePrefix;
		$this->lastTime = self::getTimeMicro(); // Don't count the above time to format.
		return $newPrefix;
	}
	
	/**
	 * Get time in micro-seconds.
	 * 
	 * @return number
	 */
	private function getTimeMicro()
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
	private function format($time, $divisor = 1)
	{
		return sprintf("%9.2f", $time / $divisor);
	}
	/**
	 * Time parameters are in micro-seconds.
	 *
	 * @param float $start
	 */
	private function getElapsed($start)
	{
		return $this->getTimeMicro() - $start;
	}
	
	/**
	 * Get the elapsed time in micro-secs since the timer was started
	 *
	 * @return float
	 */
	private function elapsedTimer($name)
	{
		return isset($this->timers[$name]) ? $this->getElapsed($this->timers[$name]) : -1;
	}

}

?>