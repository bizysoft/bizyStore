<?php
namespace bizySoft\tests;

/**
 * PHPUnit test class.
 *
 * Provide some functions to test Statement code.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 * @codeCoverageIgnore
 */
use bizySoft\tests\services\TestLogger;

class MemberFunc
{
	static function processStatic($row)
	{
		return "static_" . implode("_", $row);
	}
	
	public function processInstance($row)
	{
		return "instance_" . implode("_", $row);
	}
}

?>