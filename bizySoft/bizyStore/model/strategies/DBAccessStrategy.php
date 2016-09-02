<?php
namespace bizySoft\bizyStore\model\strategies;

/**
 * Strategy pattern for accessing data from a database via an sql query
 * 
 * Strategies can help de-couple implementation details from your code. In this case we 
 * can adopt a strategy and run it within a harness to provide fault tolerance.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class DBAccessStrategy implements DBAccessStrategyI
{
	/**
	 * The raw query string to execute.
	 *
	 * @var string
	 */
	protected $queryString = null;
	
	/**
	 * A harness for this strategy.
	 * 
	 * Harness the database access to provide fault tolerance.
	 * 
	 * @var DBAccessHarnessI
	 */
	protected $harness = null;

	/**
	 * Set the harness for this queryString.
	 * 
	 * @param DBAccessHarnessI $harness
	 * @param string $queryString
	 */
	protected function __construct(DBAccessHarnessI $harness, $queryString = null)
	{		
		$this->harness = $harness;
		$this->queryString = $queryString;
	}
}
?>