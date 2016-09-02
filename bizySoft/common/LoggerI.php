<?php
namespace bizySoft\common;

/**
 * Simple Logger interface.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface LoggerI
{
	/**
	 * Log a message to the configured file.
	 * 
	 * @param string $message
	 */
	public function log($message);
	
	/**
	 * Start a named timer.
	 * 
	 * This can be an empty method if not required in your implementation.
	 */
	public function startTimer($name);
	
	/**
	 * Stop a named timer.
	 * 
	 * This can be an empty method if not required in your implementation.
	 * 
	 * @return float elapsed time in microseconds.
	 */
	public function stopTimer($name);
	
	/**
	 * Turn logging on or off.
	 *
	 * @param boolean $onOff
	 * @return boolean the previous state.
	 */
	public function logging($onOff);
}

?>