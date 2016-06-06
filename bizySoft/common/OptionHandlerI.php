<?php
namespace bizySoft\common;

/**
 * Interface of all OptionHandler classes.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
interface OptionHandlerI
{
	/**
	 * Get the required option specified by the $name.
	 * 
	 * @param string $name
	 */
	public function getOption($name = null);
}
?>