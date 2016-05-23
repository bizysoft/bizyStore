<?php
namespace bizySoft\bizyStore\model\strategies;

use \Exception;
use \PDOException;
use bizySoft\bizyStore\model\core\StatementException;

/**
 * Provides a fault tolerant harness to execute database statements.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class StatementExecuteHarness extends StatementAccessHarness
{
	/**
	 * Pass through db to the parent.
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
	 * @param callable $closure
	 * @throws ModelException
	 */
	public function harness($closure)
	{
		$result = false; // Default to PDO error indicator
		$statement = $this->statement;
		
		try
		{
			$result = $closure($statement);
		}
		catch (PDOException $pdoe)
		{
			/*
			 * Add some bizyStore info and bubble up with a ModelException
			 */
			throw new StatementException($statement, __METHOD__, $pdoe);
		}
		catch (Exception $e)
		{
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
			/*
			 * Bubble up with a ModelException
			 */
			throw new StatementException($statement, __METHOD__);
		}
		
		return $result;
	}
}
?>