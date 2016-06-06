<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Allow Iteration via foreach through the result set rows of a Statement using the function specified in the OPTION_FUNCTION option.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class StatementFunctionIterator extends StatementIterator
{
	/**
	 * Just pass through statement to parent.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement, $properties = array())
	{
		parent::__construct($statement, $properties);
	}
	
	/**
	 * Fetch the next row from the statement result set.
	 *
	 * @return mixed the return value of the function representing the database row.
	 */
	protected function fetchNext()
	{
		return $this->statement->funcRow();
	}
}
?>