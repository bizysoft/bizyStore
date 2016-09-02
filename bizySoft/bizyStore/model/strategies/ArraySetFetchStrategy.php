<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\statements\Statement;

/**
 * Concrete class for fetching a set of data from a database statement into an array of zero-based arrays.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ArraySetFetchStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to fetch a set of data from the statement in associative array format.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement)
	{
		parent::__construct(new StatementSetFetchHarness($statement));
	}

	/**
	 * Fetch a set of column data into an array of zero-based column values in ordinal position.
	 *
	 * Excecutes the statement and returns the results.
	 *
	 * @return array
	 */
	public function execute($properties = array())
	{
		return $this->harness->harness(
				function (Statement $statement) use ($properties)
				{
					$pdoStatement = $statement->query($properties);
					return $pdoStatement->fetchAll(PDO::FETCH_NUM);
				});
	}
}
?>