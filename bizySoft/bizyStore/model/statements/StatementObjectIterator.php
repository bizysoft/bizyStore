<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Allow Iteration via foreach through the result set rows of a Statement, returning the row as
 * an instance of the class specified in the OPTION_CLASS_NAME option.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class StatementObjectIterator extends StatementIterator
{
	/**
	 * Just pass through $statement to parent constructor.
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
	 * @return mixed the object representing the database row.
	 */
	protected function fetchNext()
	{
		return $this->statement->objectRow();
	}
}
?>