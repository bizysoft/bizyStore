<?php
namespace bizySoft\bizyStore\services;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\statements\InsertBulkStatement;
use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * This is as service layer class and is not used by bizyStore core code. Call it an example that can be used to create
 * multiple Models in multiple databases if required.
 * 
 * This supports a larger number of inserts as opposed to normal Model object create's which are per row. A speed
 * increase may result for large amounts of data. Model's don't have to be for a particular database or table they will 
 * be created wherever they need to go.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class CreateModels
{
	/**
	 * Array of Model objects to create key on database/schema/table.
	 * 
	 * @var array
	 */
	private $statements;

	/**
	 * Construct create statement's using the Model objects passed in.
	 *
	 * @param array $models The array of Model objects to create.
	 */
	public function __construct(array $models = array())
	{
		/*
		 * Store the models associated with a particular database/schema/table
		 */
		$this->statements = array();
		
		if ($models)
		{
			foreach ($models as $model)
			{
				$this->add($model);
			}
		}
	}

	/**
	 * Adds the model ready to be processed by execute().
	 * 
	 * @param Model $model
	 */
	public function add(Model $model)
	{
		$db = $model->getDB();
		$dbId = $model->getDBId();
		$tableName = $db->qualifyEntity($model->getTableName());
		$properties = $model->get();
		ksort($properties);
		
		/*
		 * Use the dbId as the root key
		 */
		if (!isset($this->statements[$dbId]))
		{
			$this->statements[$dbId] = array();
		}
		if (!isset($this->statements[$dbId][$tableName]))
		{
			$this->statements[$dbId][$tableName] = new InsertBulkStatement($db, $tableName);
		}
		$insertStatement = $this->statements[$dbId][$tableName];
		$insertStatement->add($properties);
	}
	
	/**
	 * Here we use a transaction per database in a one-out all-out scenario across all databases.
	 *
	 * @throws ModelException if a statement execution failure occurs.
	 */
	public function excecute()
	{
		$pendingTransactions = array();
		$result = 0;
		
		$config = BizyStoreConfig::getInstance();
		foreach ($this->statements as $dbId => $tableNames)
		{
			$txn = null;
			try 
			{
				$db = $config->getDB($dbId);
				$txn = $db->beginTransaction();
				foreach ($tableNames as $tableName => $insertBulkStatement)
				{
					$result += $insertBulkStatement->execute();
				}
				/*
				 * Successful, store the transaction so we can commit later if nothing else goes wrong.
				 */
				$pendingTransactions[] = $txn;
			}
			catch (ModelException $me)
			{
				if ($txn)
				{
					$txn->rollback();
				}
				/*
				 * Rollback the transactions already pending
				 */
				foreach($pendingTransactions as $txn)
				{
					$txn->rollBack();
				}
				throw $me;
			}
		}
		/*
		 * Commit all the pending transactions
		 */
		foreach($pendingTransactions as $txn)
		{
			$txn->commit();
		}
		
		return $result;
	}
}
?>