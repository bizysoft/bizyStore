<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\statements\Statement;

/**
 * Concrete Strategy class for fetching a set of data from a database statement via an array composed of the specified class.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ObjectSetFetchStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to fetch a set of data from the statement in object format.
	 * 
	 * This is typically used with stdClass as a default.
	 * 
	 * @param Statement $statement
	 * @param string 
	 */
	public function __construct($statement)
	{
		parent::__construct(new StatementSetFetchHarness($statement));
	}

	/**
	 * Fetch a set of data into an object array.
	 *
	 * Excecutes the statement and returns a result set of the objects specified by statement->getClassName().
	 * Note that this method will use the magic _set method to set the properties BEFORE the constructor for the
	 * object is called.
	 *
	 * @see \bizySoft\bizyStore\model\statements\DBAccessStrategyI::execute()
	 */
	public function execute($properties = array())
	{
		return $this->harness->harness(
				function (Statement $statement) use ($properties)
				{					
					$class = $statement->getClassName();
					$constructorArgs = $class ? $statement->getClassArgs() : null;
					/*
					 * Default to stdClass if non specified.
					 */
					$class = $class ? $class : "\\stdClass";
					$pdoStatement = $statement->query($properties);
					
					return $pdoStatement->fetchAll(PDO::FETCH_CLASS, $class, $constructorArgs);
				});
	}
}
?>