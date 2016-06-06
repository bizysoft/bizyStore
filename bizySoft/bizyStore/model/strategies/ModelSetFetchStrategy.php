<?php
namespace bizySoft\bizyStore\model\strategies;

use \PDO;
use bizySoft\bizyStore\model\statements\CRUDPreparedStatement;
use bizySoft\bizyStore\model\statements\Statement;
use bizySoft\bizyStore\model\core\Model;

/**
 * Concrete Strategy class for fetching a set of Model's from a Model statement. The result set is either a zero based array or 
 * an array indexed on the database table key depending on the statement options.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelSetFetchStrategy extends DBAccessStrategy
{
	/**
	 * Construct the strategy with a harness for fault tolerance.
	 *
	 * @param Statement $statement
	 */
	public function __construct(CRUDPreparedStatement $statement)
	{
		parent::__construct(new StatementSetFetchHarness($statement));
	}

	/**
	 * Fetch a set of data into a Model array.
	 *
	 * Optimised fetch technique which can be > 3X as fast as 'fetchAll' with FETCH_CLASS for larger data sets
	 * (see ObjectSetFetchStrategy). It uses our own set method as opposed to 'fetchAll's overhead of using magic setters 
	 * for individual properties. 
	 * 
	 * Can also index the returned array on the most appropriate database key (if specified) with an overhead of around 20%
	 * when compared to an integer indexed array.
	 *
	 * @see \bizySoft\bizyStore\model\statements\DBAccessStrategyI::execute()
	 */
	public function execute($properties = array())
	{
		return $this->harness->harness(
			function (CRUDPreparedStatement $statement) use ($properties)
			{
				$result = array();
				
				$options = $statement->getOptions();
				$keyFields = array();
				
				$indexOnKey = isset($options[Model::OPTION_INDEX_KEY]) ? $options[Model::OPTION_INDEX_KEY] : false;
				$indexOnInt = ! $indexOnKey;
				/*
				 * Make a clean prototype to avoid overhead in the constructor.
				 */
				$model = $statement->getModel();
				$modelClass = get_class($model);
				$prototype = new $modelClass(null, $statement->getDB());
				$prototype->setPersisted(true);
				/*
				 * We can call PDOStatement::execute() here instead of Statement::query() because we are always using
				 * a prepared statement for Models with a little less overhead.
				 * 
				 * Because of that we have to call set properties to synchronise with the statement.
				 */
				$statement->setProperties($properties);
				$pdoStatement = $statement->getStatement();
				$pdoStatement->execute($statement->getProperties());
				if ($indexOnKey)
				{
					/*
					 * Get the named set of key fields from the Model or the first set if the name does not exist.
					 * All key fields (if any) will be filled or they would not be in the database.
					 */
					$keyCandidates = $prototype->getKeyCandidateSchema()->get($prototype->getDBId());
					
					$keyFields = isset($keyCandidates[$indexOnKey]) ? $keyCandidates[$indexOnKey] : reset($keyCandidates);
					if (empty($keyFields))
					{
						/*
						 * We can't index on key fields, just do zero-based indexing.
						 */
						$indexOnInt = true;
					}
					else
					{
						$result = $this->keyIndexSet($statement, $keyFields, $prototype);
					}
				}
				if ($indexOnInt)
				{
					$result = $this->intIndexSet($statement, $prototype);
				}
				return $result;
			});
	}
	
	/**
	 * Index the result set with the database row's key.
	 * 
	 * @param Statement $statement
	 * @param array $keyFields the key fields for the Model
	 * @param Model $prototype base the Model on the prototype
	 * @return array
	 */
	protected function keyIndexSet(Statement $statement, array $keyFields, Model $prototype = null)
	{
		$result = array();
		$pdoStatement = $statement->getStatement();
		while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			/*
			 * Instantiate a Model object from the prototype and set the index to its key fields
			 * concatenated with '.'.
			 */
			$rowInstance = clone $prototype;
			$rowInstance->set($row);
			$key = implode(".", array_intersect_key($row, $keyFields));
			$result[$key] = $rowInstance;
		}
		return $result;
	}
	
	/**
	 * Index the result set with a zero based integer.
	 * 
	 * @param Statement $statement
	 * @param Model $prototype base the Model on the prototype.
	 * @return array
	 */
	protected function intIndexSet(Statement $statement, Model $prototype = null)
	{
		$result = array();
		$pdoStatement = $statement->getStatement();
		while ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			/*
			 * Instantiate a Model object from the prototype and use normal indexing.
			 */
			$rowInstance = clone $prototype;
			$rowInstance->set($row);
			$result[] = $rowInstance;
		}
		return $result;
	}
}
?>