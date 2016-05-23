<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDOException;
use bizySoft\bizyStore\model\statements\PreparedStatement;

/**
 * Concrete Strategy class for executing a PreparedStatement on a database and returning a PDOStatement.
 *
 * This is for both queries that may or may not return a result set, which includes those that can change your data.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class PreparedStatementExecuteStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to execute a query on the database.
	 *
	 * @param Statement $statement
	 * @param string
	 */
	public function __construct(PreparedStatement $statement)
	{
		parent::__construct(new StatementExecuteHarness($statement));
	}

	/**
	 * Executes and returns the statement ready to fetch data or get execution info.
	 *
	 * @return PDOStatement The statement with data or execution info or false if statement cannot be executed.
	 *        
	 * @throws PDOException
	 */
	public function execute($properties = array())
	{
		/*
		 * Statement properties are set by PreparedStatement::execute() which calls this method.
		 */
		return $this->harness->harness(
				function (PreparedStatement $statement) 
				{
					$properties = $statement->getProperties();
					$executableProperties = $statement->getExecutableProperties();
					$diff = array_diff_key($executableProperties, $properties);
					if ($diff)
					{
						/*
						 * Here we throw a PDOException into our own harness because some database drivers don't
						 * handle a deficient number of bind parameters well.
						 */
						$execKeys = implode(",", array_keys($executableProperties));
						$propKeys = implode(",", array_keys($properties));
						throw new PDOException(" The statement is expecting the following bind parameters: ($execKeys) : got ($propKeys)");
					}
					$pdoStatement = $statement->getStatement();
					$status = $pdoStatement->execute($properties);
					
					return $status ? $pdoStatement : $status;
				});
	}
}
?>