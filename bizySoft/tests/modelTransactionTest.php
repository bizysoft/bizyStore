<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\model\core\DBTransaction;
use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\app\unitTest\UniqueKeyMember;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case class for Member Transactions.
 *
 * Uses various techniques for manipulating transactions to test the DBTransaction object and its associated db.
 *
 * These test methods are closures, of which the invoking method passes various parameters.
 *
 * The $outerTxn parameter is the DBTransaction started by runTransactionOnAllDatabasesAndTables().
 * It can be manipulated directly.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelTransactionTestCase extends ModelTestCase
{
	/**
	 * Test nested transaction and check the relevant class variables ie. the updatePolicy. 
	 * 
	 * Here we test beginTransaction() and endTransaction() from the database reference.
	 * 
	 */
	public function testNested()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$defaultUpdatePolicy = $db->getTransaction()->getUpdatePolicy();
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			
			$member1Created = new $className($formData, $db);
			
			/*
			 * Create the first member from the form data.
			 * Jack Hill
			 */
			$member1Created->create();
			// Create another one with lastName/email changed to avoid duplicates for some classes.
			$member1Created->setValue("lastName", "Dill");
			$member1Created->setValue("email", "jack@thedills.com");
			$member1Created->create();
			
			// Create a nested transaction
			$db->beginTransaction();
			$db->getTransaction()->setUpdatePolicy(DBTransaction::UPDATE_POLICY_MULTIPLE);
			
			/*
			 * With an update policy of DBTransaction::UPDATE_POLICY_MULTIPLE
			 * an Exception should not thrown when multiple records are found for the update.
			 *
			 * Here we attempt to update all Jack's in the db to Jill's
			 */
			$jack = new $className(array("firstName" => "Jack"), $db);
			$jack->update(array("firstName" => "Jill"));
			// Check the update
			$jill = new $className(array(
					"firstName" => "Jill" 
			), $db);
			$allJills = $jill->find();
			$this->assertEquals(2, count($allJills)); // Should be all Jill's
			                                          // Check for Jack's
			$jack = new $className(array(
					"firstName" => "Jack" 
			), $db);
			$allJacks = $jack->find();
			$this->assertEquals(0, count($allJacks)); // Should be no Jack's
			
			$db->endTransaction(PDODB::ROLLBACK); // Now lets rollback the inner transaction
			                                      
			// Check the update policy because it's now back to the outer transaction which has
			// a default update policy.
			$this->assertEquals($defaultUpdatePolicy, $db->getTransaction()->getUpdatePolicy());
			
			// Check that the inner transaction has rolled back
			$jill = new $className(array(
					"firstName" => "Jill" 
			), $db);
			$allJills = $jill->find();
			$this->assertEquals(0, count($allJills)); // Should be no Jill's
			
			$jack = new $className(array(
					"firstName" => "Jack" 
			), $db);
			$allJacks = $jack->find();
			$this->assertEquals(2, count($allJacks)); // Should be all Jack's
		});
	}
	
	/**
	 * Test the count for a transaction with a commit interval set. 
	 * 
	 * Counts are useful in themselves for counting the number of records changed. 
	 *
	 * We use the reference to the transaction so we can manipulate it directly. We don't use the
	 * runTransactionOnAllDatabases method here because we need access to the outermost transaction
	 * at the commit stage.
	 */
	public function testCount()
	{
		$classNames = self::getTestClasses();
		$testCase = $this;
		
		$config = self::getTestcaseConfig();
		
		// Do for all db's
		foreach ($config->getDBConfig() as $dbId => $dbConfig)
		{
			$db = $config->getDB($dbId);
			
			$db->transact(function ($db, $outerTxn) use ($testCase, $classNames)
			{
				$iterations = 1;
				// Do for all our test classes (tables)
				foreach ($classNames as $className)
				{
					$formData = $testCase->formData->getJackFormData();
					$createDate = $db->getConstantDateTime();
					$formData["dateCreated"] = $createDate;
					$dbId = $db->getDBId();
					
					for ($i = 0; $i < ModelTestCase::ITERATIONS; $i++)
					{
						// Create with unique lastName/email to avoid duplicates.
						$member1Created = new $className($formData, $db);
						$member1Created->setValue("lastName", "Hill$i");
						$member1Created->setValue("email", "email$i");
						
						$statement = $member1Created->create();
						/*
						 * Should be one member created so bump count by the rowCount of the create.
						 */
						$count = $statement->rowCount();
						$testCase->assertEquals(1, $count);
						
						$outerTxn->count($count);
					}
					// Check how many we have.
					$membersCreated = new $className(array("firstName" => "Jack" ), $db);
					$allMembers = $membersCreated->find();
					$testCase->assertEquals(ModelTestCase::ITERATIONS, count($allMembers));
					$testCase->assertEquals(ModelTestCase::ITERATIONS * $iterations++, $outerTxn->getCount() );
					/*
					 * The outer transaction is not complete yet so the database count will still be zero here.
					 */
					$testCase->assertEquals(0, $db->getCount());
				}
			});
			/*
			 * The outer transaction is now complete so the database count will be bumped by the total amount.
			 */
			$this->assertEquals(ModelTestCase::ITERATIONS * count($classNames), $db->getCount());
		}
	}
	
	/**
	 * Test nested transaction and check the relevant class variables ie. the count.
	 * 
	 * Here we use the reference to a new transaction so we can manipulate it directly.
	 */
	public function testCountNested()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) 
		{
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			$member1Created = new $className($formData, $db);
			
			/*
			 * Create the first member from the form data.
			 * Jack Hill
			 */
			$member1Created->create();
			// Create another one with lastName/email changed to avoid duplicates for some test classes.
			$member1Created->setValue("lastName", "Dill");
			$member1Created->setValue("email", "jack@thedills.com");
			$member1Created->create();
			
			$innerTxn = null;
			$innerCount = 0;
			// Save the outer transaction's count for later
			$outerCount = $outerTxn->getCount();
			try
			{
				/*
				 * Begin another transaction on the $db. Inner transactions don't 'commit' they just manipulate savepoints.
				 */ 
				$innerTxn = $db->beginTransaction();
				$innerTxn->setUpdatePolicy(DBTransaction::UPDATE_POLICY_MULTIPLE);
				
				/*
				 * With an update policy of DBTransaction::UPDATE_POLICY_MULTIPLE
				 * an Exception should not be thrown when multiple records
				 * are found for the update.
				 *
				 * Here we attempt to update all "Jack" rows in the db to "Jill"
				 */
				$jack = new $className(array("firstName" => "Jack"), $db);
				$statement = $jack->update(array("firstName" => "Jill"));
				$innerTxn->count($statement->rowCount());
				// Save the inner count for later check.
				$innerCount = $innerTxn->getCount();
				// Commit the transaction
				$innerTxn->commit();
			}
			catch ( ModelException $nestedEx )
			{
				$message = "Test Exception: inner = " . $nestedEx->getMessage();
				$this->logger->log($message);
				if ($innerTxn)
				{
					$innerTxn->rollBack();
				}
				$this->fail($message);
			}
			// Check that the outer transaction's count has been bumped.
			$this->assertEquals($outerTxn->getCount(), $outerCount + $innerCount);
			// Check that the update has worked.
			$jill = new $className(array(
					"firstName" => "Jill" 
			), $db);
			$allJills = $jill->find();
			$this->assertEquals(count($allJills), $innerCount);
		});
	}
	
	/**
	 * Force a duplicate key error on insert
	 */
	public function testForceDuplicateKey()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
				
			$txn = null;
			try
			{
				$txn = $db->beginTransaction();
				$member = new UniqueKeyMember($formData, $db);
				$member->create();
				// Force a duplicate key error
				$member->create();
				$txn->commit();
				$this->fail("Failed to cause duplicate key error");
			}
			catch (ModelException $e)
			{
				$this->assertTrue(true); // Indicate we have passed another test portion
	
				if ($txn)
				{
					$txn->rollBack();
				}
			}
			
			/*
			 * Test that the transaction has been rolled back
			 */
			$member = new UniqueKeyMember($formData, $db);
			$members = $member->find();
			$this->assertTrue(empty($members));
		});
	}	
	
	/**
	 * Same as above but run within db->transact() which has transaction management.
	 */
	public function testForceDuplicateKeyTransact()
	{
		$formData = $this->formData->getJackFormData();
		$config = self::getTestcaseConfig();
		
		foreach ($config->getDBConfig() as $dbId => $dbConfig)
		{
			$db = $config->getDB($dbId);
			try
			{
				$db->transact(function ($db, $txn) use ($formData) 
				{
					$createDate = $db->getConstantDateTime();
					$formData["dateCreated"] = $createDate;
					$member = new UniqueKeyMember($formData, $db);
					$member->create();
					// Force a duplicate key error
					$member->create();
				});
				$this->fail("Failed to cause duplicate key error");
			}
			catch (ModelException $me)
			{
				$this->assertTrue(true); // Indicate we have passed another test portion
			}
			
			/*
			 * Test that the transaction has been rolled back
			 */
			$member = new UniqueKeyMember($formData, $db);
			$members = $member->find();
			$this->assertTrue(empty($members));
		}
	}
}
?>