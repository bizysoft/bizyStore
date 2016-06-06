<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\tests\services\TestLogger;
use bizySoft\bizyStore\services\core\BizyStoreOptions;


/**
 * PHPUnit test case class for setting isolation levels.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelIsolationTestCase extends ModelTestCase
{
	public function testIsolationLevels()
	{
		$db = null;
		$txn = null;
		$isolationLevels = array(PDODB::TRANSACTION_READ_UNCOMMITTED,
				PDODB::TRANSACTION_READ_COMMITTED,
				PDODB::TRANSACTION_REPEATABLE_READ,
				PDODB::TRANSACTION_SERIALIZABLE);
		/*
		 * SQLite does not have isolation levels in PHP PDO.
		 */
		$testableInterfaces = array("MySQL" => "MySQL", "PgSQL" => "PgSQL");
		$testableIsolationLevels = 0;
		$testedIsolationLevels = 0;
		/*
		 * Do for all db's
		 */
		foreach (DBManager::getDBIds() as $dbId)
		{
			try
			{
				$db = DBManager::getDB($dbId);
				$dbConfig = DBManager::getDBConfig($dbId);
				
				$testableInterface = isset($testableInterfaces[$dbConfig[BizyStoreOptions::DB_INTERFACE_TAG]]);
				$testableIsolationLevels += $testableInterface ? count($isolationLevels) : 0;
				/*
				 * It's difficult to test isolation levels in this environment, so we set each isolation level
				 * for the base transaction on each db under test at least for code coverage.
				 */
				foreach($isolationLevels as $isolationLevel)
				{
					$txn = $db->beginTransaction($isolationLevel);
					$txn->commit();
					
					$vendorIsolationLevel = $db->getVendorIsolationLevel($isolationLevel);
					if ($vendorIsolationLevel)
					{
						/*
						 * There should be a statement cached in the db instance.
						 */
						$cachedStatement = $db->getCachedStatement($isolationLevel);
						$pdoStatement = $cachedStatement->pdoStatement;
						$rawStatement = $pdoStatement->queryString;
						/*
						 * The raw statement should be equal to the vendors statement.
						 */
						$this->assertEquals($rawStatement, $db->getVendorIsolationLevelStatement($vendorIsolationLevel));
						$testedIsolationLevels++;
						TestLogger::log("Tested db '$dbId' with isolation level '$isolationLevel'.");
					}
				}
			}
			catch (Exception $e)
			{
				TestLogger::log(__METHOD__ . ": We caught an outer Exception of type " . get_class($e));
				
				if ($db)
				{
					if ($txn)
					{
						$txn->rollback();
					}
				}
				$this->fail("Got an unexpected Exception: " . $e->getMessage());
			}
		}
		$this->assertEquals($testableIsolationLevels, $testedIsolationLevels);
	}
}
?>