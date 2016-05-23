<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\statements\Statement;

/**
 * Concrete Strategy class for fetching data from a database statement that will return a single value.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ScalarFetchStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to fetch a single value.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement)
	{
		/*
		 * We explicitly use StatementSetFetchHarness here to manage the statement.
		 */
		parent::__construct(new StatementSetFetchHarness($statement));
	}

	/**
	 * Fetch a single value.
	 *
	 * Excecute the statement and return the result.
	 *
	 * @see \bizySoft\bizyStore\model\statements\DBAccessStrategyI::execute()
	 */
	public function execute($properties = array())
	{
		return $this->harness->harness(
				function (Statement $statement) use ($properties)
				{
					$pdoStatement = $statement->query($properties);
					return $pdoStatement->fetchColumn();
				});
	}
}
?>