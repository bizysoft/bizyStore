<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\statements\Statement;

/**
 * Concrete Strategy class for fetching a set of data as the result of applying a user supplied function 
 * to each row from a Model statement. The result set is either a zero based array or an array indexed on the database 
 * table key depending on the statement options.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelFuncSetFetchStrategy extends ModelSetFetchStrategy
{
	/**
	 * Pass the statement to the parent.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement)
	{
		parent::__construct($statement);
	}
	
	/**
	 * Index the result set with the database row's key.
	 *
	 * @param Statement $statement
	 * @param array $keyFields
	 * @param Model $prototype
	 * @return array
	 */
	protected function keyIndexSet(Statement $statement, array $keyFields, Model $prototype = null)
	{
		$result = array();
		$pdoStatement = $statement->getStatement();
		$statementFunction = $statement->getFunction();
		while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			/*
			 * Set the index to the Model key fields concatenated with '.'.
			 */
			$key = implode(".", array_intersect_key($row, $keyFields));
			$result[$key] = call_user_func($statementFunction, $row);
		}
		return $result;
	}
	
	/**
	 * Index the result set with a zero based integer.
	 *
	 * @param Statement $statement
	 * @param Model $prototype
	 * @return array
	 */
	protected function intIndexSet(Statement $statement, Model $prototype = null)
	{
		$result = array();
		$pdoStatement = $statement->getStatement();
		$statementFunction = $statement->getFunction();
		while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			$result[] = call_user_func($statementFunction, $row);
		}
		return $result;
	}
}
?>