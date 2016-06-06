<?php
namespace bizySoft\bizyStore\model\strategies;

/**
 * Part of Strategy pattern for harnessing database access.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
interface DBAccessHarnessI
{
	/**
	 * Execute a strategy's code in the harness.
	 * 
	 * @param callable $closure
	 */
	public function harness($closure);
}
?>