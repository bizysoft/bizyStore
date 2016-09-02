<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\statements\Statement;
use bizySoft\bizyStore\model\core\Model;

/**
 * Concrete Strategy class for fetching a set of data as associative arrays from a Model statement. The result set is 
 * either a zero based array or an array indexed on the database table key depending on the statement options.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelAssocSetFetchStrategy extends ModelSetFetchStrategy
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
	 * @param PDOStatement $pdoStatement
	 * @param array $keyFields
	 * @param Model $prototype
	 * @return array
	 */
	protected function keyIndexSet(Statement $statement, array $keyFields, Model $prototype = null)
	{
		$result = array();
		$pdoStatement = $statement->getStatement();
		while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			/*
			 * Set the index to the Model key fields concatenated with '.'.
			 */
			$key = implode(".", array_intersect_key($row, $keyFields));
			$result[$key] = $row;
		}
		return $result;
	}
	
	/**
	 * Index the result set with a zero based integer.
	 *
	 * @param PDOStatement $pdoStatement
	 * @param Model $prototype
	 * @return array
	 */
	protected function intIndexSet(Statement $statement, Model $prototype = null)
	{
		$pdoStatement = $statement->getStatement();
		return $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
	}
}
?>