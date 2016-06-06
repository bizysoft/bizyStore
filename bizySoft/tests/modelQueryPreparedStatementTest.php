<?php
namespace bizySoft\tests;

use \PDO;
use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\statements\PreparedStatement;
use bizySoft\bizyStore\model\statements\PreparedStatementBuilder;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\bizyStore\model\statements\Statement;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case for QueryPreparedStatement.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelQueryPreparedStatementTestCase extends ModelTestCase
{
	/**
	 * We want to test Model result sets
	 */
	public function testQueryPreparedStatementModelObjectSet()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$formData = $this->formData->getJackFormData();
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData) 
		{
			$classMember = new $className(null, $db);
			/*
			 * Put a single Jill in the database, we can test her later.
			 */
			$jillProperties = $formData;
			$jillProperties["firstName"] = "Jill";
			$classMember->set($jillProperties);
			$classMember->create();
			/*
			 * Build up a prepared statement for the specific database and table with Jack data
			 */
			$tableName = $classMember->getTableName();
			
			// Build the prepared statement with some properties and options
			$options = array(
					PreparedStatement::OPTION_CLASS_NAME => $className,
					PreparedStatement::OPTION_CLASS_ARGS => array(
							null,
							$db
					),
					PreparedStatement::OPTION_CACHE => true
			);
			$properties = array(
					"firstName" => "Jack" 
			);
			$builder = new PreparedStatementBuilder($db);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> ORDER BY <ElastNameE>" ;
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties, $options);
			/*
			 * Test that we can get consecutive result set calls out using
			 * exactly the same query.
			 */
			for ($j = 0; $j > 2; $j++)
			{
				TestLogger::startTimer("$className objectSet$j");
				TestLogger::startTimer("$className objectSet$j fetch");
				$members = $queryStatement->objectSet();
				TestLogger::stopTimer("$className objectSet$j fetch");
				$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
				TestLogger::startTimer("$className objectSet$j iterate");
				foreach ($members as $i => $member)
				{
					$this->assertTrue($member->isPersisted());
					/*
					 * Resolve the lastName because it was stored based on a
					 * loop count by populateBulkDB().
					 */
					$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
					$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
					$dbData = $member->get();
					/*
					 * Make sure all the fields are there.
					 */
					$diff = array_diff_assoc($formData, $dbData);
					$this->assertTrue(empty($diff));
				}
				TestLogger::stopTimer("$className objectSet$j iterate");
				TestLogger::stopTimer("$className objectSet$j");
				$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			}
			/*
			 * Test that we can get a result set out using execute().
			 */
			TestLogger::startTimer("$className execute");
			$pdoStatement = $queryStatement->execute();
			$members = $pdoStatement->fetchAll(); // Defaults to FETCH_BOTH
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className execute");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator using exactly the same query.
			 */
			TestLogger::startTimer("$className modelObjectIterator");
			foreach ($queryStatement->iterator() as $i => $member)
			{
				$this->assertTrue($member->isPersisted());
				
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = $member->get();
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className modelObjectIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator with a different type 
			 * using exactly the same query.
			 */
			TestLogger::startTimer("$className assocIterator");
			foreach ($queryStatement->iterator(array(), Statement::FETCH_TYPE_ASSOC) as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className assocIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an array iterator using exactly the same query.
			 */
			TestLogger::startTimer("$className arraytIterator");
			foreach ($queryStatement->iterator(array(), Statement::FETCH_TYPE_ARRAY) as $i => $member)
			{
				$this->assertTrue(is_array($member));
				$this->assertTrue(in_array("Jack", $member));
			}
			TestLogger::stopTimer("$className arraytIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator of the same type for Jill.
			 */
			TestLogger::startTimer("$className arraytIterator");
			$differentProperties = array("firstName" => "Jill");
			foreach ($queryStatement->iterator($differentProperties , Statement::FETCH_TYPE_ASSOC) as $i => $member)
			{
				$diff = array_diff_assoc($jillProperties, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className arraytIterator");
			$this->assertEquals(1, $i+1);
			/*
			 * Test that we can get a different result set out using Jill.
			 *
			 * Get Jill back with execute.
			 */
			TestLogger::startTimer("$className excecute Jill");
			$pdoStatement = $queryStatement->execute($differentProperties);
			$jills = $pdoStatement->fetchAll(); // Defaults to FETCH_BOTH
			$this->assertEquals(1, count($jills)); // Only one Jill.
			$jill = reset($jills);
			$diff = array_diff_assoc($jillProperties, $jill);
			$this->assertTrue(empty($diff));
			TestLogger::stopTimer("$className excecute Jill");
			/*
			 * Get Jill back with objectSet().
			 */
			TestLogger::startTimer("$className objectSet Jill");
			$jills = $queryStatement->objectSet();
			$this->assertEquals(1, count($jills)); 
			$jill = reset($jills);
			$this->assertTrue($jill->isPersisted());
			$diff = array_diff_assoc($jillProperties, $jill->get());
			$this->assertTrue(empty($diff));
			TestLogger::stopTimer("$className objectSet Jill");
			/*
			 * Get Jill back with assocSet().
			 */
			TestLogger::startTimer("$className assocSet Jill");
			$jills = $queryStatement->assocSet();
			$this->assertEquals(1, count($jills));
			$jill = reset($jills);
			$diff = array_diff_assoc($jillProperties, $jill);
			$this->assertTrue(empty($diff));
			TestLogger::stopTimer("$className assocSet Jill");
		});
	}

	public function testQueryPreparedStatementStdClassObjectSet()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$formData = $this->formData->getJackFormData();
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData) 
		{
			$builder = new PreparedStatementBuilder($db);
			/*
			 * We want stdClass object result sets
			 */
			$options = array(
					PreparedStatement::OPTION_CLASS_NAME => "\\stdClass" 
			);
			$classMember = new $className(null, $db);
			$tableName = $classMember->getTableName();
			
			TestLogger::startTimer("$className objectSet1");
			// Build the prepared statement with some properties
			$properties = array(
					"firstName" => "Jack" 
			);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> ORDER BY <ElastNameE>" ;
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties, $options);
			TestLogger::startTimer("$className objectSet1 fetch");
			$members = $queryStatement->objectSet();
			TestLogger::stopTimer("$className objectSet1 fetch");
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			TestLogger::startTimer("$className objectSet1 iterate");
			foreach ($members as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className objectSet1 iterate");
			TestLogger::stopTimer("$className objectSet1");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get multiple result set calls out using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className objectSet2");
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className objectSet2");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an assocSet after an objectSet using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className arraySet");
			$members = $queryStatement->assocSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className arraySet");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className stdClassIterator");
			foreach ($queryStatement->iterator() as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, (array) $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className stdClassIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
		});
	}

	public function testQueryPreparedStatementAssocSet()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$formData = $this->formData->getJackFormData();
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData) 
		{
			$builder = new PreparedStatementBuilder($db);
			/*
			 * We want an array result set so don't define options
			 * with className
			 */
			$classMember = new $className(null, $db);
			$tableName = $classMember->getTableName();
			
			TestLogger::startTimer("$className assocSet1");
			// Build the prepared statement with some properties
			$properties = array(
					"firstName" => "Jack" 
			);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> ORDER BY <ElastNameE>" ;
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties);
			TestLogger::startTimer("$className assocSet1 fetch");
			$members = $queryStatement->assocSet();
			TestLogger::stopTimer("$className assocSet1 fetch");
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			TestLogger::startTimer("$className assocSet1 iterate");
			foreach ($members as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className assocSet1 iterate");
			TestLogger::stopTimer("$className assocSet1");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get multiple result set calls out using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className assocSet2");
			$members = $queryStatement->assocSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className assocSet2");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get a stdClass objectSet after an assocSet using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className objectSet");
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className objectSet");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className assocIterator");
			foreach ($queryStatement->iterator() as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className assocIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
		});
	}
	
	public function testQueryPreparedStatementArraySet()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$this->populateBulkDB($db);
		});
	
		$formData = $this->formData->getJackFormData();
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData)
		{
			$builder = new PreparedStatementBuilder($db);
			/*
			 * We want an array result set so don't define options
			 * with className
			 */
			$classMember = new $className(null, $db);
			$tableName = $classMember->getTableName();
				
			TestLogger::startTimer("$className arraySet1");
			// Build the prepared statement with some properties
			$properties = array(
					"firstName" => "Jack"
			);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> ORDER BY <ElastNameE>" ;
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties);
			TestLogger::startTimer("$className arraySet1 fetch");
			$members = $queryStatement->arraySet();
			TestLogger::stopTimer("$className arraySet1 fetch");
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			TestLogger::startTimer("$className arraySet1 iterate");
			foreach ($members as $i => $member)
			{
				$this->assertFalse(array_key_exists("firstName", $member));
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$name = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$email = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertTrue(in_array("Jack", $member));
				$this->assertTrue(in_array($name, $member));
				$this->assertTrue(in_array($email, $member));
			}
			TestLogger::stopTimer("$className arraySet1 iterate");
			TestLogger::stopTimer("$className arraySet1");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get multiple result set calls out using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className arraySet2");
			$members = $queryStatement->arraySet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$this->assertFalse(array_key_exists("firstName", $member));
				$name = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$email = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertTrue(in_array("Jack", $member));
				$this->assertTrue(in_array($name, $member));
				$this->assertTrue(in_array($email, $member));
			}
			TestLogger::stopTimer("$className arraySet2");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get a stdClass objectSet after an arraySet using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className objectSet");
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$dbData = (array) $member;
				$diff = array_diff_assoc($formData, $dbData);
				$this->assertTrue(empty($diff));
			}
			TestLogger::stopTimer("$className objectSet");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get an iterator using exactly the same query.
			 * 
			 * We have to explicitly specify FETCH_TYPE_ARRAY here because the default is 
			 * FETCH_TYPE_ASSOC
			 */
			TestLogger::startTimer("$className arrayIterator");
			foreach ($queryStatement->iterator(array(), Statement::FETCH_TYPE_ARRAY) as $i => $member)
			{
				$this->assertFalse(array_key_exists("firstName", $member));
				$name = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$email = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertTrue(in_array("Jack", $member));
				$this->assertTrue(in_array($name, $member));
				$this->assertTrue(in_array($email, $member));
			}
			TestLogger::stopTimer("$className arrayIterator");
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
		});
	}
	
	public function testQueryPreparedStatementKey()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$formData = $this->formData->getJackFormData();
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) use($formData) 
		{
			$builder = new PreparedStatementBuilder($db);
			$classMember = new $className(null, $db);
			$tableName = $classMember->getTableName();
			
			// Build the prepared statement with some properties
			$properties = array(
					"firstName" => "Jack" 
			);
			$options = array(
					PreparedStatement::OPTION_PREPARE_KEY => "testQueryPreparedStatementKey"
			);
			$query = "SELECT * FROM <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> ORDER BY <ElastNameE>" ;
			$query = $builder->translate($query, $properties);
			$queryStatement = new QueryPreparedStatement($db, $query, $properties, $options);
			$members = $queryStatement->assocSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$formData["lastName"] = "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$formData["email"] = "email_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$diff = array_diff_assoc($formData, $member);
				$this->assertTrue(empty($diff));
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			/*
			 * Test that we can get a result set out using nothing but the key.
			 * 
			 * This relies on the fact that the key is used to retrieve the query produced by the
			 * previous QueryPreparedStatement.
			 */
			$queryStatement = new QueryPreparedStatement($db, null, $properties, $options);
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
		});
	}
	
	public function testQueryPreparedStatementFuncSet()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			$this->populateBulkDB($db);
		});
		
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className) 
		{
			/*
			 * We want a result set processed through our own function so define options.
			 * 
			 * Test a static method.
			 */
			$options = array(
					PreparedStatement::OPTION_FUNCTION => array(
						"bizySoft\\tests\\MemberFunc",
						"processStatic" 
					) 
			);
			$classMember = new $className(null, $db);
			/*
			 * Build a query for each table, lastName is unique and sequential so order by it.
			 * Be careful about specific database formatting so use a tagged query.
			 */
			$tableName = $classMember->getTableName();
			$builder = new PreparedStatementBuilder($db);
			$properties = array("firstName" => "Jack");
			$query = "SELECT <EfirstNameE>, <ElastNameE> from <Q{$tableName}Q> WHERE <EfirstNameE> = <PfirstNameP> order by <ElastNameE>";
			$query = $builder->translate($query, $properties);
			// Build the prepared statement with some properties
			$queryStatement = new QueryPreparedStatement($db, $query, $properties, $options);
			TestLogger::startTimer("$className staticSet");
			$members = $queryStatement->funcSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$expected = "static_Jack_name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertEquals($expected, $member);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className staticSet");
			/*
			 * Test an instance method
			 */
			$memberFunc = new MemberFunc();
			$options = array(
							PreparedStatement::OPTION_FUNCTION => array(
							$memberFunc,
							"processInstance" 
						) 
			);
			$queryStatement->setOptions($options);
			TestLogger::startTimer("$className instanceSet");
			$members = $queryStatement->funcSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$expected = "instance_Jack_name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertEquals($expected, $member);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className instanceSet");
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
			TestLogger::startTimer("$className lambdaSet");
			$queryStatement->setOptions($options);
			$members = $queryStatement->funcSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$expected = "lambda_Jack_name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertEquals($expected, $member);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className lambdaSet");
			/*
			 * Test that we can get multiple result set calls out using
			 * exactly the same query after we have used a function.
			 */
			TestLogger::startTimer("$className assocSet");
			$members = $queryStatement->assocSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$expected = array(
						"firstName" => "Jack",
						"lastName" => "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i) 
				);
				$this->assertEquals($expected, $member);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className assocSet");
			/*
			 * Test that we can get a stdClass objectSet after an arraySet using
			 * exactly the same query.
			 */
			TestLogger::startTimer("$className objectSet");
			$members = $queryStatement->objectSet();
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$expected = array(
						"firstName" => "Jack",
						"lastName" => "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i) 
				);
				$dbData = (array) $member;

				$this->assertEquals($expected, $dbData);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className objectSet");
			/*
			 * Test that we can get an iterator using exactly the same query. Note that calling iterator() will
			 * execute the query. This will use the options which are currently set to the previous lambda function.
			 */
			TestLogger::startTimer("$className lambdaIterator");
			foreach ($queryStatement->iterator() as $i => $member)
			{
				/*
				 * Resolve the lastName because it was stored based on a
				 * loop count by populateBulkDB().
				 */
				$expected = "lambda_Jack_name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i);
				$this->assertEquals($expected,  $member);
			}
			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);
			TestLogger::stopTimer("$className lambdaIterator");
			/*
			 * Test that we can reset the options by using null
			 */
			$options = array(
					PreparedStatement::OPTION_FUNCTION => null
			);
			/*
			 * The default fetch type is FETCH_TYPE_ASSOC so this will be used because OPTION_FUNCTION has been reset.
			 */
			TestLogger::startTimer("$className reset fetch");
			$queryStatement->setOptions($options);
			$members = $queryStatement->funcSet();
			TestLogger::stopTimer("$className reset fetch");
			$this->assertEquals(ModelTestCase::ITERATIONS, count($members));
			foreach ($members as $i => $member)
			{
				$expected = array(
						"firstName" => "Jack",
						"lastName" => "name_" . sprintf(ModelTestCase::SUFFIX_FORMAT, $i)
				);
				$this->assertEquals($expected, $member);
			}

			$this->assertEquals(ModelTestCase::ITERATIONS, $i+1);

		});
	}

	/**
	 * Some code coverage for Exception handling.
	 */
	public function testBadFindStatement()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) 
		{
			try
			{
				$queryStatement = new QueryPreparedStatement($db, "select rubbish from trash");
				
				$queryStatement->execute();
				$this->fail("Failed to recognise bad statement");
			}
			catch ( ModelException $e )
			{
				// Passed another test, bump the assertion count.
				$this->assertTrue(true);
			}
		});
	}
}
?>