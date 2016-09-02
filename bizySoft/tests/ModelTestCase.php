<?php
namespace bizySoft\tests;

use \Exception;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreConstants;
use bizySoft\tests\services\TestLogger;

/**
 *
 * PHPUnit test case base class for Member database storage.
 *
 * Declares common functions to test Member objects.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class ModelTestCase extends \PHPUnit_Framework_TestCase implements BizyStoreConstants
{
	const ITERATIONS = 10; // Default to 10 iterations
	const SUFFIX_FORMAT = "%06d";
	const TEST_SEPARATOR = "********************************";

	protected $formData;
	protected $logger;
	
	protected static $testCaseConfig;

	/**
	 * These are the main class names that are used by most testcases.
	 * 
	 * Used internally in this base class but cannot be used as a dataProvider because they have different array structures.
	 *
	 * @return array
	 */
	public static function getTestClasses()
	{
		/*
		 * These are used for dynamic instantiation, so must be fully qualified class names
		 */
		$config = BizyStoreConfig::getInstance();
		$modelNamespace = $config->getModelNamespace();
				
		return array(
				"$modelNamespace\\Member",
				"$modelNamespace\\UniqueKeyMember",
				"$modelNamespace\\OverlappedUniqueKeyMember" 
		);
	}

	/**
	 * Populates all tables via the configured test classes.
	 *
	 * @param DB $db
	 * @param int $iterations
	 */
	public function populateBulkDB($db, $iterations = ModelTestCase::ITERATIONS)
	{
		$formData = $this->formData->getJackFormData();
		$formData["dateCreated"] = $db->getConstantDateTime();
		/*
		 * Populate all our test tables with some data.
		 */ 
		$txn = $db->beginTransaction();
		$classNames = self::getTestClasses();
		foreach ($classNames as $className)
		{
			/*
			 * Create a prototype we can use to store a heap of Model's in the $db
			 */
			$prototype = new $className($formData, $db);
			/*
			 * Create the Model's in the db based on the prototype.
			 */
			$statement = $prototype->getCreateStatement();
			for ($i = 0; $i < $iterations; $i++)
			{
				/*
				 * Change the lastName/email to avoid key clashes on some test classes.
				 */
				$newProperties = array(
						"lastName" => "name_" . sprintf(self::SUFFIX_FORMAT, $i),
						"email" => "email_" . sprintf(self::SUFFIX_FORMAT, $i) 
				);
				/*
				 * Use the Statement from the create() to populate more data.
				 */
				$properties = $newProperties + $formData;
				$statement->execute($properties);
			}
		}
		$txn->commit();
	}

	/**
	 * Frame-work method to run testcase closures on all configured databases and tables.
	 */
	public function runTransactionOnAllDatabasesAndTables($closure)
	{
		$classNames = self::getTestClasses();
		$config = self::getTestcaseConfig();
		
		// Do for all db's
		foreach ($config->getDBConfig() as $dbId => $dbConfig)
		{
			$txn = null;
			try
			{
				$db = $config->getDB($dbId);
				$txn = $db->beginTransaction();
				// Do for all our test classes (tables)
				foreach ($classNames as $className)
				{
					$closure($db, $txn, $className);
				}
				$txn->commit();
			}
			catch (Exception $e)
			{
				$this->logger->log(__METHOD__ . ": We caught an outer Exception of type " . get_class($e));
				$this->logger->log($e->getMessage());
				if ($txn)
				{
					$txn->rollback();
				}
				throw $e;
			}
		}
	}

	/**
	 * Frame-work method to run testcase closures on all configured databases.
	 */
	public function runTransactionOnAllDatabases($closure)
	{
		$config = self::getTestcaseConfig();
		
		// Do for all db's
		foreach ($config->getDBConfig() as $dbId => $dbConfig)
		{
			$txn = null;
			try
			{
				$db = $config->getDB($dbId);
				$txn = $db->beginTransaction();
				$closure($db, $txn);
				$txn->commit();
			}
			catch (Exception $e)
			{
				$this->logger->log(__METHOD__ . ": We caught an outer Exception of type " . get_class($e));
				$this->logger->log($e->getMessage());
				if ($txn)
				{
					$txn->rollback();
				}
				throw $e;
			}
		}
	}

	public static function getTestcaseConfig()
	{
		if (self::$testCaseConfig === null)
		{
			self::$testCaseConfig = BizyStoreConfig::getInstance();
		}
		return self::$testCaseConfig;
	}
	
	/**
	 * Clean out the databases to start each test from a known point.
	 * Sets up a timer for the test.
	 */
	public function setUp()
	{		
		$this->formData = new ModelFormData();
		$config = self::getTestcaseConfig();
		$this->logger = $config->getLogger();
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) {
			$model = new $className(null, $db);
			$qualifiedTableName = $db->qualifyEntity($model->getTableName());
			
			/*
			 * Delete the dependencies on the parent Model first
			 */
			$dependentTableNames = array();
			$references = $model->getForeignKeyRefereeSchema()->get($model->getDBId());
			foreach($references as $indexName => $properties)
			{
				foreach($properties as $parentColumName => $foreignKey)
				{
					foreach($foreignKey as $tableName => $columnName)
					{
						$dependentTableName = $db->qualifyEntity($tableName);
						$dependentTableNames[] = $dependentTableName;
					}
				}
			}
			foreach($dependentTableNames as $dependentTableName)
			{
				$truncate = "DELETE FROM " . $dependentTableName;
				$result = $db->execute($truncate);
			}
			
			$truncate = "DELETE FROM " . $qualifiedTableName;
			$result = $db->execute($truncate);
		});
		
		$this->logger->log(self::TEST_SEPARATOR . " " . $this->getName() . " starting. ");
		$this->logger->startTimer(get_class($this) . "::" . $this->getName());
	}

	/**
	 * Log a message that the test has finished with timing.
	 */
	public function tearDown()
	{
		$this->logger->stopTimer(get_class($this) . "::" . $this->getName(), self::TEST_SEPARATOR . " " . $this->getName() . " complete.");
	}
	
	/**
	 * Print a Model to a string
	 * 
	 * @param Model $model
	 * @param string $thisName
	 * @param int $tabDepth used for indentation starting at zero.
	 */
	public static function sprintModel(Model $model, $thisName = null, $tabDepth = 0)
	{
		$result = null;
		
		$message = self::indentMessage($thisName,  get_class($model) . ", db::" . $model->getDBId() . " (", $tabDepth);
		foreach ($model->get() as $name => $value)
		{
			if ($value instanceof Model)
			{
				/*
				 * Recurse the Model.
				 */
				$message .= self::sprintModel($value, $name, $tabDepth+1);
			}
			else
			{
				if (is_array($value))
				{
					$message .= self::indentMessage("", "$name => array(", $tabDepth+1);
					$message .= self::sprintArray($name, $value, $tabDepth+1);
					$message .= self::indentMessage("", ")", $tabDepth+1);
				}
				else 
				{
					$message .= self::indentMessage($name, $value, $tabDepth+1);
				}
			}
		}
		$message .= self::indentMessage("", ")", $tabDepth);
		
		return $message;
	}
	
	/**
	 * Print an array (which may contain Model's) to a string.
	 * 
	 * @param string $name
	 * @param string $property
	 * @param int $tabDepth
	 */
	private static function sprintArray($name, $property, $tabDepth)
	{
		$message = "";
		foreach($property as $propName => $propValue)
		{
			if ($propValue instanceof Model)
			{
				$message .= self::indentMessage("", "[$propName] => (", $tabDepth+1);
				/*
				 * Recurse the Model.
				 */
				$message .= self::sprintModel($propValue, "", $tabDepth+2);
				$message .= self::indentMessage("", ")", $tabDepth+1);
			}
			else
			{
				if (is_array($propValue))
				{
					$message .= self::indentMessage("", "[$propName] => array(", $tabDepth+1);
					$message .= self::sprintArray($propName, $propValue, $tabDepth+1);
					$message .= self::indentMessage("", ")", $tabDepth+1);
				}
				else
				{
					$message .= self::indentMessage($name, $propValue, $tabDepth+1);
				}
			}
		}
		
		return $message;
	}
	
	private static function indentMessage($name, $message, $tabDepth)
	{
		$indentedMessage = "\n" . str_repeat("\t", $tabDepth) . ($name ? "$name => $message" : $message);
		return $indentedMessage;
	}

}

?>