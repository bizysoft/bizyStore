<?php
namespace bizySoft\bizyStore\model\core;

use \Exception;

/**
 * Base class bizyStore Exception. You can use this to catch any bizyStore related exceptions.
 *
 * bizyStore throws exceptions derived from ModelException on error conditions.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelException extends Exception
{
	/**
	 * Just pass through params to parent.
	 *
	 * @param string $message
	 * @param number $code
	 * @param Exception $previous
	 */
	public function __construct($message, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, (int) $code, $previous);
	}
}
?>