<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\generator\ModelGenerator;
use bizySoft\bizyStore\app\unitTest\TestTable;
use bizySoft\tests\services\TestLogger;

/**
 * Test we can generate Model/Schema files when creating a table.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class CreateTablesTestCase extends ModelTestCase
{

	public function testCreateTables()
	{
		$config = self::getTestcaseConfig();
		
		$dbConfigs = $config->getDBConfig();
		$dbId = null;
		foreach($dbConfigs as $dbConfig)
		{
			/*
			 * Get an SQLite database id. Creating a table is database specific so we'll
			 * pick our default unit test database which comes with the distribution. 
			 * 
			 * We want to test that we can dynamically create a table and have it recognised 
			 * in bizyStore's Schema.
			 */
			if ($dbConfig[self::DB_INTERFACE_TAG] == "SQLite")
			{
				$dbId = $dbConfig[self::DB_ID_TAG];
				break;
			}
		}
		
		if ($dbId)
		{
			$txn = null;
			try 
			{
				$db = $config->getDB($dbId);
				
				$createTableStatement =
				"CREATE TABLE testTable (
				id INTEGER PRIMARY KEY NOT NULL,
				someData varchar(80)
				)";
				
				$txn = $db->beginTransaction();
				/*
				 * Most databases support transactional DDL, SQLite is one of them so we can just roll-back the
				 * transaction to remove the table created.
				 *
				 * You have to use care when creating tables (or any DDL), as some databases will start an implicit transaction 
				 * on the database side for DDL statement execution which will commit any pending data as well. 
				 * 
				 * It's best in these situations not to mix DDL and DML write statements in the one transaction.
				 */
				$db->execute($createTableStatement);
				/*
				 * Generate the Model/Schema files.
				 * 
				 * When creating tables and you expect Model/Schema support then the Model and Schema files need to be
				 * generated. 
				 * 
				 * Note that if you have any foreign keys in the table that is being created, then you must 
				 * generate the Model and Schema files for the tables on both sides of the relationship(s) by 
				 * calling ModelGenerator::generate() with all table names concerned even if the other table(s) exist
				 * already. The same goes for other table modifications. 
				 * 
				 * The following technique can be used to generate/re-generate the schema if required on any particular $db/table(s).
				 */
				$generator = new ModelGenerator($db->getConfig());
				$generator->generate(array($dbId => array("testTable")));
				/*
				 * Done, now we have full Model/Schema support...
				 * 
				 * ...so lets test it.
				 */
				$newModel = new TestTable(array("someData" => "this is some data"), $db);
				/*
				 * Test the Model's db methods...
				 */
				$defaultDBId = $newModel->getDefaultDBId();
				$currentDBId = $newModel->getDBId();
				/*
				 * against the compatible.
				 */
				$compatibleDBIds = $newModel->getCompatibleDBIds();
				$this->assertTrue(isset($compatibleDBIds[$currentDBId]));
				$this->assertTrue(isset($compatibleDBIds[$defaultDBId]));
				
				$newModel->create();
				/*
				 * Check the primary key...
				 */
				$primaryKey = $newModel->getValue("id");
				$this->assertTrue($primaryKey !== null) ;
				/*
				 * and the data with a find.
				 */
				$findModel = new TestTable(array("id" => $primaryKey), $db);
				$foundModel = $findModel->findUnique();
				if ($foundModel)
				{
					$this->assertEquals("this is some data", $foundModel->getValue("someData"));
				}
				else
				{
					$this->fail(__METHOD__. ": Failed to find TestTable Model");
				}
				
				/*
				 * Delete the table, it was only a test. Schema files are still available in /bizySoft/bizyStore/app/unitTest.
				 */
				$txn->rollBack();
			}
			catch (Exception $e)
			{
				$this->logger->log(__METHOD__ . ": " . $e->getMessage());
				if ($txn)
				{
					$txn->rollBack();
				}
				$this->fail(__METHOD__ . ": Unexpected Exception when testing TestTable");
			}
		}
	}
}
?>