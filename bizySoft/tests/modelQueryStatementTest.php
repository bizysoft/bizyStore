<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\statements\Statement;
use bizySoft\bizyStore\model\statements\QueryStatement;
use bizySoft\bizyStore\model\statements\StatementBuilder;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case class for QueryStatement.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelQueryStatementTestCase extends ModelTestCase
{
	public function testQueryStatement()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) 
		{
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			$member1Created = new $className($formData, $db);
			
			$member1Created->create();
			// We need to get the qualified table name here because we are outside the safety of a
			// Model object and cannot be sure if the database has a schema associated with it.
			$tableName = $db->qualifyEntity($member1Created->getTableName());
			// We also cant be sure if the column and value don't need some tweaking as well
			$columnName = $db->formatEntity("firstName");
			$propertyValue = $db->formatProperty($member1Created->getValue("firstName"));
			$query = new QueryStatement($db, "SELECT * from $tableName WHERE $columnName = $propertyValue");
			$memberRows = $query->assocSet();
			$this->assertEquals(1, count($memberRows));
			// property names are the same as column names in this data set so this works
			$diff = $this->formData->checkMemberDetails($member1Created, $memberRows[0]);
			$this->assertTrue(empty($diff));
		});
	}

	public function testQueryStatementWithBadQuery()
	{
		/*
		 * We are deliberately causing an error here with the configuration of one of our
		 * databases to have a PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT or PDO::ERRMODE_WARNING
		 * and we have configured PHPunit to not convert errors to exceptions.
		 */
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			try
			{
				$query = new QueryStatement($db, "SELECT rubbish from trash");
				$memberRows = $query->assocSet();
				$this->fail("Failed to recognise bad statement");
			}
			catch (ModelException $e)
			{
				// bump the assertionCount
				$this->assertTrue(true);
			}
		});
	}

	public function testQueryStatementSets()
	{
		// Populate all our db's.
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) 
		{
			$builder = new StatementBuilder($db);
			TestLogger::startTimer("$className stdClass");
			$options = array(
					Statement::OPTION_CLASS_NAME => "\\stdClass" 
			);
			// Usually you would know what table you want to address in a query, all we have here is the
			// $className. Let's construct a Model object so we can easily get the table info.
			$modelObj = new $className(null, $db);
			$tableName = $modelObj->getTableName();
			$selectQuery = $builder->translate("SELECT * FROM <Q{$tableName}Q> order by <ElastNameE>");
			$queryStatement = new QueryStatement($db, $selectQuery);
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				/*
				 * Check that the dbData has all the formData fields
				 */
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
				
			TestLogger::stopTimer("$className stdClass");
			/*
			 * Now test the existing statement to see if we can call assocSet()
			 */
			TestLogger::startTimer("$className existing arraySet");
			$members = $queryStatement->assocSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
				
			/*
			 * Test a Model result set
			 */
			$options = array(
					Statement::OPTION_CLASS_NAME => "$className",
					Statement::OPTION_CLASS_ARGS => array(
							null,
							$db 
					) 
			);
			TestLogger::startTimer("$className Model");
			$queryStatement = new QueryStatement($db, $selectQuery, $options);
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$this->assertTrue($member->isPersisted());
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = $member->get();
				/*
				 * Check that the dbData has all the formaData fields.
				 */
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}	
			TestLogger::stopTimer("$className Model");
			TestLogger::stopTimer("$className existing arraySet");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
		});
	}

	public function testQueryStatementIterators()
	{
		// Populate all our db's.
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$method = __METHOD__;
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($method) 
		{
			TestLogger::startTimer("$method: $className");
			$builder = new StatementBuilder($db);
			/*
			 * Specify a class in the $options to get back a stdClass object result set
			 * for the iterator.
			 */
			$options = array(
					Statement::OPTION_CLASS_NAME => "\\stdClass" 
			);
			/*
			 * Usually you would know what table you want to address in a query, all we have here is the
			 * $className. Let's construct a Model object so we can easily get the table info no matter
			 * what $className we have.
			 */
			$modelObj = new $className(null, $db);
			// Order by lastName for correct comparison
			$tableName = $modelObj->getTableName();
			$query = "SELECT * FROM <Q{$tableName}Q> order by <ElastNameE>";
			$selectQuery = $builder->translate($query);
			TestLogger::startTimer("$className objectIterator");
			$queryStatement = new QueryStatement($db, $selectQuery, $options);
			foreach ($queryStatement->iterator() as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className objectIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * We won't specify any options this time so we expect back an array result set.
			 * Throw in a where clause via the properties for code coverage.
			 */
			$properties = array(
					"firstName" => "Jack" 
			);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> order by <ElastNameE>";
			$selectQuery = $builder->translate($query, $properties);
			$queryStatement = new QueryStatement($db, $selectQuery);
			TestLogger::startTimer("$className arrayIterator");
			foreach ($queryStatement->iterator() as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className arrayIterator");
			/*
			 * Grab another iterator from the same QueryStatement
			 * to see if we can cleanly iterate again.
			 */
			TestLogger::startTimer("$className arrayIterator2");
			$iterator = $queryStatement->iterator();
			foreach ($iterator as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			$this->assertEquals(ModelTestCase::ITERATIONS, $iterator->count());
			TestLogger::stopTimer("$className arrayIterator2");
			/*
			 * Grab the same iterator and check if we can still cleanly iterate.
			 */
			TestLogger::startTimer("$className arrayIterator3");
			foreach ($iterator as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			$this->assertEquals(ModelTestCase::ITERATIONS, $iterator->count());
			TestLogger::stopTimer("$className arrayIterator3");
			TestLogger::stopTimer("$method: $className");
		});
	}
}
?>