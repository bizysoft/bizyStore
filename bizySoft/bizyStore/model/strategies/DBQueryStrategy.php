<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\core\PDODB;

/**
 * Concrete Strategy class for querying sql on a database.
 * 
 * Typically used for queries that return a result set. ie. select queries
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class DBQueryStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy to execute a query on the database.
	 * 
	 * @param Statement $statement
	 * @param string 
	 */
	public function __construct($db, $queryString)
	{
		parent::__construct(new DBExecuteHarness($db), $queryString);
	}

	/**
	 * Executes the sql and returns a PDOStatement with a result set.
	 *
	 * @return PDOStatement the statement ready to fetch results from.
	 * @throws ModelException
	 */
	public function execute($properties = array())
	{
		$sql = $this->queryString;
		return $this->harness->harness(
				function (PDODB $db) use ($sql)
				{
					$pdo = $db->getConnection();
					return $pdo->query($sql);
				});
	}
}
?>