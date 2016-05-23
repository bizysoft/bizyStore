<?php
namespace bizySoft\tests;

use \PDOStatement;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\statements\PreparedStatement;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\bizyStore\model\statements\CRUDPreparedStatementBuilder;
use bizySoft\bizyStore\model\statements\CreatePreparedStatement;
use bizySoft\bizyStore\model\statements\FindPreparedStatement;
use bizySoft\bizyStore\model\statements\UpdatePreparedStatement;
use bizySoft\bizyStore\model\statements\DeletePreparedStatement;
use bizySoft\bizyStore\model\statements\PreparedStatementBuilder;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\tests\services\TestLogger;

/**
 * Test the internals of Model statements are working correctly via the CRUD statement classes.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelStatementTestCase extends ModelTestCase
{
	public function testModelCreateStatement()
	{
		$values = '(firstName,lastName) VALUES (:firstName,:lastName)';
		$pgValues = '("firstName","lastName") VALUES (:firstName,:lastName)';
		$expected = array(
			'SQLite' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $values,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $values,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $values
			),
			'MySQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $values,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $values,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $values
			),
			'PgSQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $pgValues,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $pgValues,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $pgValues
			)
		);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) use ($expected)
		{
			$jackHillProps = array(
					"firstName" => "Jack",
					"lastName" => "Hill" 
			);
			
			$dbId = $db->getDBId();
			$dbConfig = DBManager::getDBConfig($dbId);
			$pdoPrepareOptions = isset($dbConfig[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] : null;
			$modelPrepareOptions = isset($dbConfig[BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG] : null;
			$jackHill = new $model($jackHillProps, $db);
			$statement = new CreatePreparedStatement($jackHill);
			// Test the options are set correctly
			$statementOptions = $statement->getOptions();
			$expectedOptions = array(
					PreparedStatement::OPTION_CLASS_NAME => get_class($jackHill)
			);
			if ($pdoPrepareOptions)
			{
				$expectedOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] = $pdoPrepareOptions;
			}
			if ($modelPrepareOptions)
			{
				if (isset($modelPrepareOptions[BizyStoreOptions::OPTION_CACHE]))
				{
					$cache = $modelPrepareOptions[BizyStoreOptions::OPTION_CACHE];
					if ($cache)
					{
						$expectedOptions[BizyStoreOptions::OPTION_CACHE] = $cache;
					}
				}
			}
			$this->assertEquals($expectedOptions, $statementOptions);
			$dbInterface = $dbConfig[BizyStoreOptions::DB_INTERFACE_TAG];
			$query = $statement->getQuery();
			// Check if the statement key is constructed from our properties
			$expectedQuery = "INSERT INTO " . $db->qualifyEntity($jackHill->getTableName()) . " " . $expected[$dbInterface][$model];
			$this->assertEquals($expectedQuery, $query);
			$createStatement = $statement->execute();
			$this->assertTrue($createStatement !== false);
			$this->assertEquals(1, $createStatement->rowCount());
			
			// Find Jack Hill
			$jackHill = $jackHill->findUnique();
			// Test the create worked
			$this->assertEquals($jackHill->getValue("firstName"), "Jack");
			$this->assertEquals($jackHill->getValue("lastName"), "Hill");
		});
	}
	
	public function testModelDeleteStatement()
	{
		$sequencedWhere = 'WHERE email = :email AND firstName = :firstName AND id = :id AND lastName = :lastName';
		$unsequencedWhere = 'WHERE email = :email AND firstName = :firstName AND lastName = :lastName';
		$sequencedPgWhere = 'WHERE "email" = :email AND "firstName" = :firstName AND "id" = :id AND "lastName" = :lastName';
		$unsequencedPgWhere = 'WHERE "email" = :email AND "firstName" = :firstName AND "lastName" = :lastName';
		$expected = array(
			'SQLite' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedWhere,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unsequencedWhere,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unsequencedWhere
			),
			'MySQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedWhere,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unsequencedWhere,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unsequencedWhere
			),
			'PgSQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedPgWhere,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unsequencedPgWhere,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unsequencedPgWhere
			)
		);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) use($expected) 
		{
			$jackHillProps = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"email" => "jack@thehills.com"
			);
			
			$jackHill = new $model($jackHillProps, $db);
			$jackHill->create();
			
			$statement = new DeletePreparedStatement($jackHill);
			$dbId = $db->getDBId();
			$dbConfig = DBManager::getDBConfig($dbId);
			$dbInterface = $dbConfig[BizyStoreOptions::DB_INTERFACE_TAG];
			$query = $statement->getQuery();
			// Check if the statement key is constructed from our properties
			$expectedQuery = "DELETE FROM " . $db->qualifyEntity($jackHill->getTableName()) . " " . $expected[$dbInterface][$model];
			$this->assertEquals($expectedQuery, $query);
			
			$deleteStatement = $statement->execute();
			$this->assertTrue($deleteStatement !== false);
			$this->assertEquals(1, $deleteStatement->rowCount());
			
			// Try to find Jack Hill
			$jackHill = $jackHill->findUnique();
			$this->assertEquals(false, $jackHill);
		});
	}
	
	
	public function testModelUpdateStatement()
	{
		$sequencedSet = 'SET email = :_email,firstName = :_firstName WHERE email = :email AND firstName = :firstName AND id = :id AND lastName = :lastName';
		$unSequencedSet = 'SET email = :_email,firstName = :_firstName WHERE email = :email AND firstName = :firstName AND lastName = :lastName';
		$sequencedPgSet = 'SET "email" = :_email,"firstName" = :_firstName WHERE "email" = :email AND "firstName" = :firstName AND "id" = :id AND "lastName" = :lastName';
		$unsqequencedPgSet = 'SET "email" = :_email,"firstName" = :_firstName WHERE "email" = :email AND "firstName" = :firstName AND "lastName" = :lastName';
		$expected = array(
			'SQLite' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedSet,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unSequencedSet,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unSequencedSet
			), 
			'MySQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedSet,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unSequencedSet,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unSequencedSet
			), 
			'PgSQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $sequencedPgSet,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $unsqequencedPgSet,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $unsqequencedPgSet
			)
		);
		
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) use($expected) 
		{
			$jackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"email" => "jack@thehills.com" 
			);
			
			$jillProperties = array(
					"firstName" => "Jill",
					"email" => "jill@thehills.com" 
			);
			
			$jack = new $model($jackProperties, $db);
			$jack->create();
			
			$statement = new UpdatePreparedStatement($jack, $jillProperties);
			$dbId = $db->getDBId();
			$dbConfig = DBManager::getDBConfig($dbId);
			$dbInterface = $dbConfig[BizyStoreOptions::DB_INTERFACE_TAG];
			$query = $statement->getQuery();
			$expectedQuery = "UPDATE " . $db->qualifyEntity($jack->getTableName()) . " " . $expected[$dbInterface][$model];
			$this->assertEquals($expectedQuery, $query);
			$updateStatement = $statement->execute();
			$this->assertTrue($updateStatement !== false);
			$this->assertEquals(1, $updateStatement->rowCount());
			// Find the updated member from the firstName
			$jill = new $model($jillProperties, $db);
			$jill = $jill->findUnique();
				
			// Test the update worked
			$this->assertEquals($jill->getValue("firstName"), "Jill");
			$this->assertEquals($jill->getValue("lastName"), "Hill");
			$this->assertEquals($jill->getValue("email"), "jill@thehills.com");
			/*
			 * There should be no Jack
			 */
			$jack = $jack->findUnique();
			$this->assertFalse($jack);
		});
	}
	
	public function testModelFindStatement()
	{
		$where = 'WHERE firstName = :firstName AND lastName = :lastName';
		$pgWhere = 'WHERE "firstName" = :firstName AND "lastName" = :lastName';
		$expected = array(
			'SQLite' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $where,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $where,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $where
			),
			'MySQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $where,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' => $where,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $where
			),
			'PgSQL' => array(
				'bizySoft\bizyStore\model\unitTest\Member' => $pgWhere,
				'bizySoft\bizyStore\model\unitTest\UniqueKeyMember' =>  $pgWhere,
				'bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember' => $pgWhere
			)
		);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) use($expected) 
		{
			$jackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill" 
			);
			/*
			 * Create Jack in the $db
			 */
			$jack = new $model($jackProperties, $db);
			$jack->create();
			// make a Model with just the first and last name
			$jack = new $model($jackProperties, $db);
			$statement = new FindPreparedStatement($jack);
			
			$dbId = $db->getDBId();
			$dbConfig = DBManager::getDBConfig($dbId);
			$dbInterface = $dbConfig[BizyStoreOptions::DB_INTERFACE_TAG];
			$query = $statement->getQuery();
			$expectedQuery = "SELECT * FROM " . $db->qualifyEntity($jack->getTableName()) . " " . $expected[$dbInterface][$model];
			$this->assertEquals($expectedQuery, $query);
			$jackResultSet = $statement->objectSet();
			$this->assertEquals(1, count($jackResultSet));
			// Test the find worked
			$jackResult = reset($jackResultSet); // get first element
			$this->assertTrue($jackResult->isPersisted());
			$this->assertEquals($jackResult->getValue("firstName"), "Jack");
			$this->assertEquals($jackResult->getValue("lastName"), "Hill");
		});
	}
	
	public function testModelFindStatementWithNullProperties()
	{		
		$jackProperties = array(
				"firstName" => "Jack",
				"lastName" => "Hill",
				"email" => "jack@thehills.com",
				"dob" => null
		);
		$jillProperties = array(
				"firstName" => "Jill",
				"lastName" => "Hill",
				"email" => "jill@thehills.com",
				"dob" => null
		);
		
		$jillsOtherProperties = array(
				"firstName" => "Jill",
				"lastName" => "Hill",
				"email" => "jill@thehills.com",
				"dob" => "1985-11-10"
		);
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) use($jackProperties, $jillProperties, $jillsOtherProperties) 
		{
			/*
			 * Create Jack and Jill
			 */ 
			$jack = new $model($jackProperties, $db);
			$jack->create();
			$jill = new $model($jillProperties, $db);
			$jill->create();
			$otherJill = new $model($jillsOtherProperties, $db);
			$otherJill->create();
			/*
			 * Make a Model with Jack's properties and try to find him.
			 */
			$jack = new $model($jackProperties, $db);
			$statement = new FindPreparedStatement($jack);
			/*
			 * objectSet() here uses the properties in the Model which have been
			 * synchronised with the statement.
			 */
			$jackResultSet = $statement->objectSet();
			$this->assertEquals(1, count($jackResultSet));
			// Test the find worked
			$jackResult = reset($jackResultSet); // get first element
			$this->assertEquals("Jack", $jackResult->getValue("firstName"));
			$this->assertEquals("Hill", $jackResult->getValue("lastName"));
			$this->assertEquals(null, $jackResult->getValue("dob"));
			$this->assertEquals("jack@thehills.com", $jackResult->getValue("email"));
			/*
			 * Try a statement iterator
			 */
			foreach($statement->iterator() as $jack)
			{
				$this->assertEquals("Jack", $jack->getValue("firstName"));
				$this->assertEquals("Hill", $jack->getValue("lastName"));
				$this->assertEquals(null, $jack->getValue("dob"));
				$this->assertEquals("jack@thehills.com", $jack->getValue("email"));
			}
			/* 
			 * CRUDPreparedStatement properties are syncronised with the statement, so
			 * the null from jack's properties will be in force.
			 * 
			 * We should be able to execute the same statement with
			 * Jill's properties and only get the single Model with null dob.
			 */
			$jillResultSet = $statement->objectSet($jillProperties);
			$this->assertEquals(1, count($jillResultSet));
			$jill = reset($jillResultSet);
			$this->assertEquals("Jill", $jill->getValue("firstName"));
			$this->assertEquals("Hill", $jill->getValue("lastName"));
			$this->assertEquals(null, $jill->getValue("dob"));
			$this->assertEquals("jill@thehills.com", $jill->getValue("email"));
			/*
			 * Try an iterator on Jill. Model iterator()'s use the properties set in the Model to do a find().
			 */
			foreach($statement->iterator() as $jillFound)
			{
				$this->assertEquals("Jill", $jillFound->getValue("firstName"));
				$this->assertEquals("Hill", $jillFound->getValue("lastName"));
				$this->assertEquals(null, $jillFound->getValue("dob"));
				$this->assertEquals("jill@thehills.com", $jillFound->getValue("email"));
			}
			/*
			 * See if we can get Jack again with the same statement.
			 */ 
			$jackResultSet = $statement->objectSet($jackProperties);
			$this->assertFalse(empty($jackResultSet));
			$jackResult = reset($jackResultSet);
			$this->assertEquals("Jack", $jack->getValue("firstName"));
			$this->assertEquals("Hill", $jack->getValue("lastName"));
			$this->assertEquals(null, $jack->getValue("dob"));
			$this->assertEquals("jack@thehills.com", $jack->getValue("email"));
			/*
			 * Do again for array sets.
			 * 
			 * Get Jack.
			 */ 
			$jackResultSet = $statement->assocSet();
			$this->assertFalse(empty($jackResultSet));
			// Test the find worked
			$jackResult = reset($jackResultSet);
			$this->assertEquals("Jack", $jack->getValue("firstName"));
			$this->assertEquals("Hill", $jack->getValue("lastName"));
			$this->assertEquals(null, $jack->getValue("dob"));
			$this->assertEquals("jack@thehills.com", $jack->getValue("email"));
			/*
			 * Get Jill
			 */
			$jillResultSet = $statement->assocSet($jillProperties);
			$this->assertFalse(empty($jillResultSet));
			$jillResult = reset($jillResultSet);
			$this->assertEquals("Jill", $jill->getValue("firstName"));
			$this->assertEquals("Hill", $jill->getValue("lastName"));
			$this->assertEquals(null, $jill->getValue("dob"));
			$this->assertEquals("jill@thehills.com", $jill->getValue("email"));
			/*
			 * Get Jack again.
			 */
			$jackResultSet = $statement->assocSet($jackProperties);
			$this->assertFalse(empty($jackResultSet));
			$jackResult = reset($jackResultSet);
			$this->assertEquals("Jack", $jack->getValue("firstName"));
			$this->assertEquals("Hill", $jack->getValue("lastName"));
			$this->assertEquals(null, $jack->getValue("dob"));
			$this->assertEquals("jack@thehills.com", $jack->getValue("email"));
			
		});
	}
	
	public function testModelCreateStatementWithNullProperties()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model) 
		{
			$jackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"dob" => "1973-05-01",
					"email" => "jack@thehills.com",
					"postCode" => null 
			);
			
			/*
			 * Create Jack
			 */
			$jack = new $model($jackProperties, $db);
			$jack->create();
			// See if we can get a null back out
			$jack = new $model($jackProperties, $db);
			$statement = new FindPreparedStatement($jack);
			/*
			 * Model prepared statements take care of handling nulls
			 * so we won't have to explicitly remove them from the
			 * properties before we execute.
			 */
			$jackResultSet = $statement->objectSet();
			$this->assertFalse(empty($jackResultSet));
			// Test the find worked
			$jackResult = reset($jackResultSet);
			$this->assertEquals("Jack", $jackResult->getValue("firstName"));
			$this->assertEquals("Hill", $jackResult->getValue("lastName"));
			$this->assertEquals("1973-05-01", $jackResult->getValue("dob"));
			$this->assertEquals(null, $jackResult->getValue("postCode"));
			$this->assertEquals("jack@thehills.com", $jackResult->getValue("email"));
			/*
			 * Code coverage for some methods that we may not use in tests.
			 */
			$statementQuery = $statement->getQuery();
			$pdoStatement = $statement->getStatement();
			$this->assertTrue($pdoStatement instanceof PDOStatement);
			$pdoQuery = $pdoStatement->queryString;
			$this->assertEquals($pdoQuery, $statementQuery);
		});
	}
	
	
	public function testFindStatementWithKeyIndex()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$this->populateBulkDB($db);
		});
	
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$model = new $className(null, $db);
			$keyCandidateSchema = $model->getKeyCandidateSchema();
			$keyFields = $keyCandidateSchema->get($db->getDBId());
			$keyFields = reset($keyFields); // Get first key fields
			/*
			 * Set the options on the statement before we start.
			 */
			$options = array(
					Model::OPTION_INDEX_KEY => true
			);
			/*
			 * Check the assocSet() with key index by using getFindStatement().
			 * 
			 * Save the result for later.
			 */
			$statement = $model->getFindStatement($options);
			$assocMembers = $statement->assocSet();
			$this->assertEquals(count($assocMembers), ModelTestCase::ITERATIONS);
			foreach($assocMembers as $key => $member)
			{
				$expecetdKey = implode(".", array_intersect_key($member, $keyFields));
				$this->assertEquals($expecetdKey, $key);
			}
			/*
			 * Check the objectSet() with key index.
			 */
			$members = $statement->objectSet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			foreach($members as $key => $member)
			{
				$this->assertTrue($member->isPersisted());
				$expecetdKey = implode(".", array_intersect_key($member->get(), $keyFields));
				$this->assertEquals($expecetdKey, $key);
			}	
			/*
			 * For arraySet() we check against the assocSet() keys.
			 */
			$members = $statement->arraySet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			foreach($members as $key => $member)
			{
				/*
				 * The order should be the same as the assocSet().
				 */
				$this->assertTrue(isset($assocMembers[$key]));
				$assocMember = $assocMembers[$key];
				$i = 0;
				foreach ($assocMember as $column => $value)
				{
					$this->assertEquals($value, $member[$i++]);
				}
			}
			/*
			 * Test a lambda function.
			 */
			$options = array(
					PreparedStatement::OPTION_FUNCTION =>
					function ($row)
					{
						return "lambda_" . implode("_", $row);
					},
					Model::OPTION_INDEX_KEY => true
			);
			
			$statement->setOptions($options);
			$members = $statement->funcSet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			foreach($members as $key => $member)
			{
				$pos = strpos($member, "lambda_");
				$this->assertTrue(false !== $pos);
				$this->assertEquals(0, $pos);
				$this->assertTrue(isset($assocMembers[$key]));
			}
		});
	}
	
	public function testFindStatementWithIntIndex()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$this->populateBulkDB($db);
		});
	
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$model = new $className(null, $db);
			/*
			 * Check the assocSet() with int index by using getFindStatement().
			 * 
			 * Save the result for later.
			 */
			$statement = $model->getFindStatement();
			$assocMembers = $statement->assocSet();
			$this->assertEquals(count($assocMembers), ModelTestCase::ITERATIONS);
			$i = 0;
			foreach($assocMembers as $key => $member)
			{
				$this->assertEquals($i++, $key);
			}
			/*
			 * Check the objectSet() with int index.
			 */
			$members = $statement->objectSet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			$i = 0;
			foreach($members as $key => $member)
			{
				$this->assertTrue($member->isPersisted());
				$this->assertEquals($i++, $key);
			}	
			/*
			 * For arraySet() we check against the assocSet() keys.
			 */
			$members = $statement->arraySet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			$i = 0;
			foreach($members as $key => $member)
			{
				/*
				 * The order should be the same as the assocSet().
				 */
				$this->assertTrue(isset($assocMembers[$key]));
				$assocMember = $assocMembers[$key];
				$j = 0;
				foreach ($assocMember as $column => $value)
				{
					$this->assertEquals($value, $member[$j++]);
				}
			}
			/*
			 * Test a lambda function.
			 */
			$options = array(
					PreparedStatement::OPTION_FUNCTION =>
					function ($row)
					{
						return "lambda_" . implode("_", $row);
					}
			);
			
			$statement->setOptions($options);
			$members = $statement->funcSet();
			$this->assertEquals(count($members), ModelTestCase::ITERATIONS);
			foreach($members as $key => $member)
			{
				$pos = strpos($member, "lambda_");
				$this->assertTrue(false !== $pos);
				$this->assertEquals(0, $pos);
				$this->assertTrue(isset($assocMembers[$key]));
			}
		});
	}

	/**
	 * Test the performance of various fetch methods.
	 * 
	 * Occasionally, this test may fail depending on system resources. Notably on Windows machines where timings 
	 * can become inaccurate. Network latency, even wireless vs cable connection to database machine(s), can have an effect.
	 */
	public function testFindPerformance()
	{
		TestLogger::startTimer("performance populate");
		$iterations = 1000;
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)  use($iterations) 
		{
			$this->populateBulkDB($db, $iterations);
		});
		TestLogger::stopTimer("performance populate");
		
		$formData = $this->formData->getJackFormData();
		TestLogger::startTimer("performance fetch");
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData, $iterations) 
		{
			TestLogger::log("Running $iterations $className's on database " . $db->getDBId());
			/*
			 * Specify the where clause. 
			 * All database tables are populated with Jack as the firstName. Using this property in where clause will bring back
			 * all table rows.
			 */
			$properties = array("firstName" => "Jack");
			/*
			 * Here we test QueryPreparedStatement against a Model object find()
			 * 
			 * The Model::find() should be significantly faster because QueryPreparedStatement relies
			 * on fetchAll. Model objects use an optimised technique.
			 */
			$model = new $className($properties, $db);
			/*
			 * Test the find method on our Model object
			 */
			$keyOption = array(PreparedStatement::OPTION_PREPARE_KEY => "find key for $className");
			TestLogger::startTimer("$className model prepare");
			$members = $model->find($keyOption);
			$modelElapsed = TestLogger::stopTimer("$className model prepare");
			$this->assertEquals(count($members), $iterations);
			$this->assertTrue(reset($members) instanceof Model);
			/*
			 * Now do the same for QueryPreparedStatement and compare times
			 */
			$queryOptions = array(
					PreparedStatement::OPTION_CLASS_NAME => $className,
					PreparedStatement::OPTION_CLASS_ARGS => array(
							null,
							$db 
					),
					PreparedStatement::OPTION_PREPARE_KEY => "query key for $className" 
			);
			$classMember = new $className(null, $db);
			$tableName = $classMember->getTableName();
			/*
			 * Build a prepared statement with the original model properties
			 */
			$builder = new CRUDPreparedStatementBuilder($db);
			$query = $builder->buildModelSelectStatement($tableName, $properties);
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties, $queryOptions);
			TestLogger::startTimer("$className query model prepare");
			$members = $queryStatement->objectSet();
			$queryElapsed = TestLogger::stopTimer("$className query model prepare");
			$this->assertEquals(count($members), $iterations);
			/*
			 * Do again to eliminate timing issues for prepares.
			 */
			TestLogger::startTimer("$className model fetch stable");
			$members = $model->find($keyOption);
			$modelStableElapsed = TestLogger::stopTimer("$className model fetch stable");
			$this->assertEquals(count($members), $iterations);
			TestLogger::startTimer("$className query model fetch stable");
			$members = $queryStatement->objectSet();
			$queryStableElapsed = TestLogger::stopTimer("$className query model fetch stable");
			/*
			 * Model find() should be significantly faster than a QueryPreparedStatement objectSet();
			 */	
			$this->assertTrue($queryStableElapsed > $modelStableElapsed);
			/*
			 * Do an assocSet() and compare with Model
			 */
			TestLogger::startTimer("$className query assoc fetch");
			$members = $queryStatement->assocSet();
			$queryAssocElapsed = TestLogger::stopTimer("$className query assoc fetch");
			/*
			 * assocSet() should be significantly faster than a Model find();
			 */
			$this->assertTrue($modelStableElapsed > $queryAssocElapsed);
				
		});
		TestLogger::stopTimer("performance fetch");
	}
	
	public function testFindStatementWithBadProperties()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$jackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"dob" => "1973-05-01",
					"email" => "jackthehills.com",
					"phoneNo" => "0123456789"		
			);
		
			$lessJackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"dob" => "1973-05-01",
					"email" => "jackthehills.com"
			);
		
			$moreJackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"dob" => "1973-05-01",
					"email" => "jackthehills.com",
					"phoneNo" => "1234567890",
					"suburb" => "Hilldene"
			);
			
			/*
			 * Create Jack
			 */
			$jack = new $className($jackProperties, $db);
			$jack->create();
			
			/*
			 * Make a new Model with Jack's properties to get the table name 
			 * easily from the $className.
			 */
			$jack = new $className($jackProperties, $db);
	
			$builder = new PreparedStatementBuilder($db);
			$taggedQuery = "SELECT * from <Q" . $jack->getTableName() . "Q> 
			WHERE <EfirstNameE> = <PfirstNameP> AND <ElastNameE> = <PlastNameP> 
			AND <EdobE> = <PdobP> AND <EemailE> = <PemailP> AND <EphoneNoE> = <PphoneNoP>";
			$query = $builder->translate($taggedQuery, $jackProperties);
			$statement = new QueryPreparedStatement($db, $query, $jackProperties);
			$jacks = $statement->assocSet();
			$this->assertFalse(empty($jacks));
			// Test the find worked
			$jack = reset($jacks);
			$this->assertEquals($jackProperties, array_intersect_key($jack, $jackProperties));

			/*
			 * Most drivers under test don't catch deficient number of keys correctly for a prepared statement.
			 * So check that our execute strategy corrects this behaviour.
			 */ 
			try
			{
				$jacks = $statement->assocSet($lessJackProperties);
				$this->fail("Failed to recognise smaller no of properties than required.");
			}
			catch (ModelException $e)
			{
				TestLogger::log("Exception OK for test");
				// bump the assertionCount for another check passed.
				$this->assertTrue(true);
			}
			
			/*
			 * 
			 * All drivers under test will correctly throw an exception with more parameters than needed.
			 */
			try
			{
				$jacks = $statement->assocSet($moreJackProperties);
				$this->fail("Failed to recognise larger no of properties than required.");
			}
			catch (ModelException $e)
			{
				TestLogger::log("Exception OK for test");
				// bump the assertionCount for another check passed.
				$this->assertTrue(true);
			}
		});
	}	
	
	public function testDirtyUpdate()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $model)
		{
			$jackProperties = array(
					"firstName" => "Jack",
					"lastName" => "Hill",
					"email" => "jack@thehills.com",
					"postCode" => "3333",
					"dob" => "1973-05-01",
					"phoneNo" => "0333333333"
			);
				
			/*
			 * Jill changes key fields for some Models under test
			 */
			$jillProperties = array(
					"firstName" => "Jill",
					"email" => "jill@thehills.com"
			);
			$jack = new $model($jackProperties, $db);
			$jack->create();
			$this->assertTrue($jack->isPersisted());
			/*
			 * Dirty the original properties
			 */
			$jack->set($jillProperties);
			/*
			 * Update Jack with a null to test that they are handled correctly.
			 */
			$jack->update(array("postCode" => null));
			/*
			 * The update would have used the dirty properties to re-establish 
			 * the original Model.
			 * 
			 * Take a look at Jack now.
			 */
			$this->assertEquals("Jill", $jack->getValue("firstName"));
			$this->assertEquals("Hill", $jack->getValue("lastName"));
			$this->assertEquals("jill@thehills.com", $jack->getValue("email"));
			$this->assertEquals(null, $jack->getValue("postCode"));
			$this->assertEquals("1973-05-01", $jack->getValue("dob"));
			$this->assertEquals("0333333333", $jack->getValue("phoneNo"));
			/*
			 * Get a clean Model from the database and check.
			 */
			$jillsKey = $jack->getKeyProperties();
			$jill = new $model($jillsKey, $db);
			$jill = $jill->findUnique();
			$this->assertTrue($jill !== false);
			$this->assertEquals("Jill", $jill->getValue("firstName"));
			$this->assertEquals("Hill", $jill->getValue("lastName"));
			$this->assertEquals("jill@thehills.com", $jill->getValue("email"));
			$this->assertEquals(null, $jill->getValue("postCode"));
			$this->assertEquals("1973-05-01", $jill->getValue("dob"));
			$this->assertEquals("0333333333", $jill->getValue("phoneNo"));
			/*
			 * There should be no Jack.
			 */
			$jack = new $model(array("firstName" => "Jack"), $db);
			$jacks = $jack->find();
			$this->assertTrue(0 == count($jacks));
			/*
			 * Check that we can update back to Jack with just the key properties for the where clause
			 * and some new properties.
			 */
			$jill = new $model($jillsKey, $db);
			$jill->update($jackProperties);
			$jacksKey = $jill->getKeyProperties();
			/*
			 * Check Jack out.
			 */
			$jack = new $model($jacksKey, $db);
			$jack = $jack->findUnique();
			$this->assertTrue($jack !== false);
			$this->assertEquals($jackProperties, array_intersect_key($jack->get(), $jackProperties));
			/*
			 * There should be no Jill.
			 */
			$jill = new $model(array("firstName" => "Jill"), $db);
			$jills = $jill->find();
			$this->assertTrue(0 == count($jills));
		});
	}
}
?>