<?php
namespace bizySoft\common;

/**
 * Interface which declares the behaviour of all grinders.
 * 
 * In most implementations, grinding can include validation or transformations.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
interface GrinderI
{
	/**
	 * Grind is used as a generic work-horse method.
	 * 
	 * @return mixed Usually an associative array but may return anything that an implementation desires.
	 */
	public function grind();
}
?>