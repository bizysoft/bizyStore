<?php
namespace bizySoft\bizyStore\model\strategies;

use \Exception;
use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\core\StatementException;

/**
 * Provides a fault tolerant harness for database fetches.
 *
 * Specifcally for handling set's of data.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class StatementSetFetchHarness extends StatementAccessHarness
{
	/**
	 * Pass through the statement to parent.
	 *
	 * @param Statement $statement
	 */
	public function __construct($statement)
	{
		parent::__construct($statement);
	}

	/**
	 * Harness the database access and provide some fault tolerance.
	 * 
	 * Error conditions are signalled by either a PDOException or a return value of false, in which case the 
	 * statement is closed and a ModelException is thrown.
	 * 
	 * This method will always throw a ModelException in an error condition, no matter what is specified for PDO::ATTR_ERRMODE.
	 *
	 * Clean up by closing the statement.
	 *
	 * @param callable $closure
	 * @return mixed
	 * @throws ModelException
	 */
	public function harness($closure)
	{
		$result = false;
		$statement = $this->statement;
		
		try
		{
			$result = $closure($statement);
		}
		catch (PDOException $pdoe)
		{
			$statement->close();
			/*
			 * Add some bizyStore info and bubble up with a ModelException
			 */
			throw new StatementException($statement, __METHOD__, $pdoe);
		}
		catch (ModelException $me)
		{
			$statement->close();
			/*
			 * Non-Model SetStrategies usually call Statement::query() using a StatementExecuteHarness
			 * which throws a ModelException, so just rethrow. 
			 * 
			 * Model SetStrategies don't call other Strategies, they call PDOStatement::execute() directly
			 * which throws a PDOException.
			 */
			throw $me;
		}
		catch (Exception $e)
		{
			$statement->close();
			/*
			 * It could be something else has gone wrong so build a ModelException.
			*/
			throw new ModelException($e->getMessage(), $e->getCode(), $e);
		}
		/*
		 * In case PDO::ATTR_ERRMODE not set to PDO::ERRMODE_EXCEPTION.
		 */
		if ($result === false)
		{
			$statement->close();
			
			/*
			 * Defer to a higher level
			 */
			throw new StatementException($statement, __METHOD__);
		}
		/*
		 * Close the statement because we have finished fetching the set of data.
		 */
		$statement->close();
		
		return $result;
	}
}
?>