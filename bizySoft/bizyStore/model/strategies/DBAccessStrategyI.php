<?php
namespace bizySoft\bizyStore\model\strategies;

/**
 * Specification of method use in the Strategy pattern for accessing a database.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface DBAccessStrategyI
{
	/**
	 * Execute the access strategy
	 * 
	 * @param array the properties that the strategy uses in the database access.
	 * 
	 * @return mixed
	 */
	public function execute($properties = array());
}
?>