<?php
namespace bizySoft\bizyStore\model\strategies;

use bizySoft\bizyStore\model\core\PDODB;

/**
 * Concrete Strategy class for preparing a database statement from sql.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class DBPrepareStrategy extends DBAccessStrategy
{
	private $options = array();
	
	/**
	 * Construct the strategy to prepare the sql.
	 * 
	 * @param Statement $statement
	 * @param string 
	 */
	public function __construct($db, $queryString, array $options = array())
	{
		$this->options = $options;
		parent::__construct(new DBExecuteHarness($db), $queryString);
	}

	/**
	 * Prepares the statement from the sql with its options.
	 *
	 * @return PDOStatement the statement ready to be executed
	 * @throws ModelException if the statement cannot be prepared.
	 * @see \bizySoft\bizyStore\model\statements\DBAccessStrategyI::execute()
	 */
	public function execute($properties = array())
	{
		$sql = $this->queryString;
		$options = $this->options;
		return $this->harness->harness(
				function (PDODB $db) use ($sql, $options)
				{
					$pdo = $db->getConnection();
					return $pdo->prepare($sql, $options);
				});
	}
}
?>