<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\statements\Statement;

/**
 * Concrete Strategy class for fetching a set of data from a statement that will return values processed by a function.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class FuncSetFetchStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to fetch a row processed by a function.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement)
	{
		parent::__construct(new StatementSetFetchHarness($statement));
	}

	/**
	 * Fetch a set of data processed by a function.
	 *
	 * Does NOT use PDO's standard implementation with fetchAll(PDO::FETCH_FUNC, ...) which uses fixed function parameters. 
	 * We use an array version of the row as the function parameter, so the signature of your function/method 
	 * will always take a single array.
	 *
	 * @return array
	 */
	public function execute($properties = array())
	{
		return $this->harness->harness(
				function (Statement $statement) use ($properties)
				{
					$result = false; //PDO error indicator
					$statementFunction = $statement->getFunction();
					if ($statementFunction)
					{
						$result = array();
						$pdoStatement = $statement->query($properties);
						while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
						{
							$result[] = call_user_func($statementFunction, $row);
						}
					}
					return $result;
				});
	}
}
?>