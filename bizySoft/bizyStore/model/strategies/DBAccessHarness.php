<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\core\DB;

/**
 * Harness access to the database passed into the constructor.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class DBAccessHarness implements DBAccessHarnessI
{
	/**
	 * The db to do the work on.
	 * 
	 * @var DB
	 */
	protected $db = null;

	/**
	 * Construct with a database reference.
	 * 
	 * @param PDODB $db
	 */
	protected function __construct(DB $db)
	{		
		$this->db = $db;
	}
}
?>