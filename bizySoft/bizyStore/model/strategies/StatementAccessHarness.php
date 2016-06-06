<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\statements\Statement;

/**
 * Harness access to the statement passed into the constructor.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
abstract class StatementAccessHarness implements DBAccessHarnessI
{
	/**
	 * The statement to do the work on.
	 *
	 * @var Statement
	 */
	protected $statement = null;

	/**
	 * Construct with a Statement reference.
	 *
	 * @param Statement $statement
	 */
	protected function __construct(Statement $statement)
	{
		$this->statement = $statement;
	}
}
?>