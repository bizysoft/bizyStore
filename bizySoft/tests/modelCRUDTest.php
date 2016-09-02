<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\statements\PreparedStatement;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case class for Model database storage.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelCRUDTestCase extends ModelTestCase
{	
	/**
	 * Use the model objects to do multiple creates, an update, then a delete
	 * in one atomic transaction.
	 */
	public function testModelCRUDAtomic()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			$jack = new $className($formData, $db);
			/*
			 * Check the persisted flag
			 */
			$this->assertFalse($jack->isPersisted());
			/*
			 * Create the first member from the form data.
			 * Jack Hill
			 */
			$jack->create();
			/*
			 * Check the persisted flag
			 */
			$this->assertTrue($jack->isPersisted());
				
			/*
			 * Find the created member from the db. 
			 * Use first and last name, not all classes have id's.
			 */
			$jackArgs = array(
					"firstName" => $jack->getValue("firstName"),
					"lastName" => $jack->getValue("lastName") 
			);
			$jackFound = new $className($jackArgs, $db);
			$jacksFound = $jackFound->find();
			$this->assertFalse(empty($jacksFound));
			$jackFound = reset($jacksFound); // First element
			/*
			 * Check the persisted flag
			 */
			$this->assertTrue($jackFound->isPersisted());
			// Check against the form data
			
			$diff = $this->formData->checkMemberDetails($jackFound, $formData);
			$this->assertTrue(empty($diff));
			// Check against the explicitly created member.
			$this->assertTrue($jackFound->equals($jack));
			/*
			 * Create the second member from the form data.
			 * Jill
			 */
			$formData = $this->formData->getJillFormData();
			$formData["dateCreated"] = $createDate;
			$jill = new $className($formData, $db);
			// Check the create is valid
			$jill->create();
			// Find the created member from the db.
			$jillArgs = array(
					"firstName" => $formData["firstName"],
					"lastName" => $formData["lastName"]
			);
			$jillFound = new $className($jillArgs, $db);
			$jillsFound = $jillFound->find();
			$this->assertFalse(empty($jillsFound));
			$jillFound = reset($jillsFound);
			// Check against the form data
			$diff = $this->formData->checkMemberDetails($jillFound, $formData);
			$this->assertTrue(empty($diff));
			// Check against the explicitly created member.
			$this->assertTrue($jillFound->equals($jill));
			/*
			 * Change the values and update the second member
			 * to Joe Blow
			 */
			$formData = $this->formData->getJoeFormData();
			$formData["dateCreated"] = $createDate;
			
			$statement = $jillFound->update($formData);
			$this->assertTrue($statement !== false);
			$this->assertEquals($statement->rowCount(), 1);
			// Check the update worked
			$joeArgs =  array(
					"firstName" => $formData["firstName"],
					"lastName" => $formData["lastName"]
			);
			$joe = new $className($joeArgs, $db);
			$joes = $joe->find();
			$this->assertFalse(empty($joes));
			$joe = reset($joes);
			// Check against the form data
			$diff = $this->formData->checkMemberDetails($joe, $formData);
			$this->assertTrue(empty($diff));
			// Check against the explicitly updated member.
			$this->assertTrue($jillFound->equals($joe));
			// Check that Jill does not exist anymore
			$jill = new $className(array("firstName" => "Jill"), $db);
			$jills = $jill->find();
			$this->assertEquals(array(), $jills);
			/*
			 * Delete the first member created
			 */
			$jack->delete();
			// Check that the first member has been deleted
			$deletedMember = $jack->findUnique();
			$this->assertEquals(false, $deletedMember);
		});
	}
	
	/**
	 * Test an update to a model created with a subset of the schema properties.
	 *
	 * The update includes some properties that were not in the original create.
	 */
	public function testModelSubsetUpdate()
	{
		$createProperties = array(
				"firstName" => "Jack",
				"lastName" => "Hill" 
		);
		$updateProperties = array(
				"email" => "jack@theHills.com" 
		);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($createProperties, $updateProperties) 
		{
			$createdMember = new $className($createProperties, $db);
			$createdMember->create();
			$createdMember = $createdMember->findUnique();
			$this->assertTrue($createdMember != false);
			$this->assertTrue($createdMember instanceof Model);
			/*
			 * You have to be quite careful when using data coming back from a database
			 * when you intend to do an update.
			 *
			 * find methods will bring back null properties if column defaults
			 * are configured to do so on insert with no values for them, as we are doing
			 * here with a subset of the full data.
			 *
			 * This is not what we want for a model we want to update, as the model properties are used for the
			 * where clause, this is especially true for models populated with incomplete keys.
			 *
			 * In this particular case, we need to strip the nulls from the Model before we do an update,
			 * only including the properties which have a definite value.
			 *
			 * It matters less for the values we want to update the Model with, but you would
			 * probably not want to overwite definite values with nulls so be careful.
			 */
			$createdMember->strip();
			// Update the created member with new properties
			$result = $createdMember->update($updateProperties);
			
			$updatedMember = new $className($updateProperties, $db);
			
			$updatedMember = $updatedMember->findUnique();
			$this->assertTrue($updatedMember instanceof Model);
				
			// Find the only one with the email set
			$expectedProperties = array_merge($createProperties, $updateProperties);
			$updatedProperties = $updatedMember->get($expectedProperties);
			$this->assertEquals($expectedProperties, $updatedProperties);
		});
	}

	/**
	 * Test dirty properties.
	 */
	public function testModelDirty()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$jackData = $this->formData->getJackFormData();
			$createdMember = new $className($jackData, $db);
			$createdMember->create();
			$createdMember = $createdMember->findUnique();
			$this->assertTrue($createdMember != false);
			$this->assertTrue($createdMember instanceof Model);
			$this->assertTrue($createdMember->isPersisted());
			/*
			 * Set a temp variable not associated with the schema
			 */
			$createdMember->set(array("tmp" => "hello"));
			$this->assertEquals(array(), $createdMember->getDirty());
			/*
			 * Change Jack To Jill along with the tmp variable
			 * Only the firstName should be dirty.
			 */
			$changed = $createdMember->set(array("firstName" => "Jill", "tmp" => "hello1"));
			$this->assertEquals(array("firstName" => "Jack", "tmp" => "hello"), $changed);
			$this->assertEquals(array("firstName" => "Jack"), $createdMember->getDirty());
			/*
			 * Change lastName and dob.
			 * firstName, lastName and dob should be dirty.
			 */
			$changed = $createdMember->set(array("lastName" =>"Gill", "dob" => "1985-11-10"));
			$this->assertEquals(array("lastName" =>"Hill", "dob" => "1973-05-01"), $changed);
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Hill", "dob" => "1973-05-01"), $createdMember->getDirty());
			/*
			 * Now change the firstName back to Jack.
			 * It should all still be dirty.
			 */
			$changed = $createdMember->set(array("firstName" => "Jack"));
			$this->assertEquals(array("firstName" => "Jill"), $changed);
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Hill", "dob" => "1973-05-01"), $createdMember->getDirty());
			/*
			 * Change the firstName to Jill and change back the lastName and dob
			 * They should all still be dirty.
			 */
			$changed = $createdMember->set(array("firstName" => "Jill", "lastName" =>"Hill", "dob" => "1973-05-01"));
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Gill", "dob" => "1985-11-10"), $changed);
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Hill", "dob" => "1973-05-01"), $createdMember->getDirty());
		});
	}
	
	/**
	 * Test dirty properties with an update.
	 */
	public function testModelDirtyUpdate()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$jackData = $this->formData->getJackFormData();
			$createdMember = new $className($jackData, $db);
			$createdMember->create();
			$createdMember = $createdMember->findUnique();
			$this->assertTrue($createdMember != false);
			$this->assertTrue($createdMember instanceof Model);
			/*
			 * Change Jack To Jill.
			 */
			$changed = $createdMember->set(array("firstName" => "Jill", "lastName" =>"Gill", "dob" => "1985-11-10"));
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Hill", "dob" => "1973-05-01"), $changed);
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Hill", "dob" => "1973-05-01"), $createdMember->getDirty());
			/*
			 * Do an update with just dirty properties
			 */
			$result = $createdMember->update();
			$this->assertEquals(1, $result->rowCount());
			$this->assertEquals(array("firstName" => "Jill", "lastName" =>"Gill", "dob" => "1985-11-10"),
				$createdMember->get(array("firstName" => null, "lastName" => null, "dob" => null)));
			/*
			 * Check what's in the database
			 */
			$updatedMember = $createdMember->findUnique();
			$this->assertTrue($updatedMember != false);
			$this->assertTrue($updatedMember instanceof Model);
			$this->assertEquals(array("firstName" => "Jill", "lastName" =>"Gill", "dob" => "1985-11-10"),
			$updatedMember->get(array("firstName" => null, "lastName" => null, "dob" => null)));
			/*
			 * Do an update with just new properties
			 */
			$newProperties = array("gender" => "Female");
			$result = $createdMember->update($newProperties);
			$this->assertEquals(1, $result->rowCount());
			$this->assertEquals(array("firstName" => "Jill", "lastName" =>"Gill", "dob" => "1985-11-10", "gender" => "Female"),
				$createdMember->get(array("firstName" => null, "lastName" => null, "dob" => null, "gender" => null)));
			/*
			 * Check what's in the database
			 */
			$updatedMember = $createdMember->findUnique();
			$this->assertTrue($updatedMember != false);
			$this->assertTrue($updatedMember instanceof Model);
			$this->assertEquals(array("firstName" => "Jill", "lastName" =>"Gill", "dob" => "1985-11-10", "gender" => "Female"),
			$updatedMember->get(array("firstName" => null, "lastName" => null, "dob" => null, "gender" => null)));
			/*
			 * Do an update with both dirty and new properties
			 */
			$newProperties = array("gender" => "Male");
			$createdMember->set(array("firstName" => "Jack"));
			$result = $createdMember->update($newProperties);
			$this->assertEquals(1, $result->rowCount());
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Gill", "dob" => "1985-11-10", "gender" => "Male"),
				$createdMember->get(array("firstName" => null, "lastName" => null, "dob" => null, "gender" => null)));
			/*
			 * Check what's in the database
			 */
			$updatedMember = $createdMember->findUnique();
			$this->assertTrue($updatedMember != false);
			$this->assertTrue($updatedMember instanceof Model);
			$this->assertEquals(array("firstName" => "Jack", "lastName" =>"Gill", "dob" => "1985-11-10", "gender" => "Male"),
					$updatedMember->get(array("firstName" => null, "lastName" => null, "dob" => null, "gender" => null)));
			
		});
	}
	
	/**
	 * Test that we can copy from one database to another.
	 * 
	 * This only does any work when there are more than one databases in config
	 */
	public function testModelCopy()
	{
		$config = self::getTestcaseConfig();
		$dbConfig = array_keys($config->getDbConfig());
		$srcId = reset($dbConfig);
		
		$srcDB = $config->getDB($srcId);
		
		// Populate the default db
		$this->populateBulkDB($srcDB);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($destDB, $outerTxn, $className) use ($srcId, $srcDB)
		{
			$destId = $destDB->getDBId();
			if ($destId == $srcId)
			{
				/*
				 * Don't want to copy the $srcDB to itself.
				 */
				return;
			}
			/*
			 * Copy from $src to $destDB.
			 * $src is the first db defined in bizySoftConfig,
			 * $destDB is all the other db's
			 */
			$this->logger->startTimer("query timer src");
			// All members from the src database
			$allMembers = new $className(null, $srcDB);
			// Get them all out
			$allSrcMembers = $allMembers->find();
			$this->logger->stopTimer("query timer src");
			$this->assertEquals(ModelTestCase::ITERATIONS, count($allSrcMembers));
			// Fix the date/time formatting between databases
			$formData = $this->formData->getJackFormData();
			$dateCreated = $destDB->getConstantDateTime();
			$formData["dateCreated"] = $dateCreated;
			// construct a model for the $destDB database
			$memberCopy = new $className(null, $destDB);
			
			$this->logger->startTimer("copy timer");
			/*
			 * Copy all the members to the $destDB database
			 * 
			 * We don't need a transaction here, $outerTxn is already active on the $destDB
			 */
			foreach ($allSrcMembers as $srcMember)
			{
				$memberCopy->set($srcMember->get());
				$memberCopy->set(array(
						"dateCreated" => $dateCreated 
				));
				$memberCopy->create();
			}
			$this->logger->stopTimer("copy timer");
			
			// Check the $destDB with FindPreparedStatement()
			$this->logger->startTimer("query timer dest");
			$allMembers = new $className(null, $destDB);
			
			$i = 0;
			foreach ($allMembers->iterator() as $key => $destMember)
			{
				$this->assertEquals($i, $key);
				$i++;
				// Massage lastName/email, they are populated to avoid duplicate keys
				$formData["lastName"] = $destMember->getValue("lastName");
				$formData["email"] = $destMember->getValue("email");
				$diff = $this->formData->checkMemberDetails($destMember, $formData);
				$this->assertTrue(empty($diff));
			}
			$this->logger->stopTimer("query timer dest");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i);
			
			// Check the $destDB using QueryPreparedStatement and object iterator
			$tableName = $destDB->qualifyEntity($allMembers->getTableName());
			$lastName = $destDB->formatEntity("lastName");
			$this->logger->startTimer("query timer dest stdClass");
			$options = array(
					PreparedStatement::OPTION_CLASS_NAME => $className 
			);
			$statement = new QueryPreparedStatement($destDB, "SELECT * from $tableName order by $lastName", array(), $options);
			
			$i = 0;
			// Do some iterations as above
			foreach ($statement->iterator() as $key => $destMember)
			{
				$this->assertEquals($i, $key);
				$i++;
				// Massage lastName/email, they are populated to avoid duplicate keys
				$formData["lastName"] = $destMember->getValue("lastName");
				$formData["email"] = $destMember->getValue("email");
				$diff = $this->formData->checkMemberDetails($destMember, $formData);
				$this->assertTrue(empty($diff));
			}
			$this->logger->stopTimer("query timer dest stdClass");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i);
		});
	}
}
?>