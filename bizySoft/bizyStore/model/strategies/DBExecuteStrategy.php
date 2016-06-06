<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\core\PDODB;

/**
 * Concrete class for executing sql on a database.
 * 
 * Typically used for queries that do some work on the database but don't return a result set.
 * This includes queries that can change your data. ie. insert's, update's, delete's. etc...
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class DBExecuteStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to execute an sql query on the database.
	 * 
	 * @param PDODB $db
	 * @param string $queryString
	 */
	public function __construct($db, $queryString)
	{
		parent::__construct(new DBExecuteHarness($db), $queryString);
	}

	/**
	 * Executes the sql on the database and returns the no of rows affected.
	 *
	 * @return int the no of rows affected.
	 * @throws ModelException
	 * @see \bizySoft\bizyStore\model\statements\DBAccessStrategyI::execute()
	 */
	public function execute($properties = array())
	{
		$sql = $this->queryString;
		return $this->harness->harness(
				function (PDODB $db) use ($sql)
				{
					$pdo = $db->getConnection();
					return $pdo->exec($sql);
				});
	}
}
?>