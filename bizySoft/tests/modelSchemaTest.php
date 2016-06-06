<?php  
namespace bizySoft\tests;

use bizySoft\tests\services\TestLogger;

use bizySoft\bizyStore\model\unitTest\Member;
use bizySoft\bizyStore\model\unitTest\MultiPrimaryKeyMember;
use bizySoft\bizyStore\model\unitTest\OverlappedUniqueKeyMember;
use bizySoft\bizyStore\model\unitTest\UniqueKeyMember;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreOptions;

/**
 *
 * Run some basic Model tests on the public interface dependent on the schema.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelSchemaTestCase extends ModelTestCase
{
	public static function classProvider()
	{
		/*
		 * These are the classes we test specifically in this test case. They are used for dynamic instantiation, so must be fully 
		 * qualified class names.
		 *
		 * They should exist in all the test databases configured with the bizySoft/tests/testData scripts.
		 * In any case, they exist in the SQLite database configured with the distribution
		*/
		$modelNamespace = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE);
		
		return array(
				array("$modelNamespace\\Member"),
				array("$modelNamespace\\MultiPrimaryKeyMember"),
				array("$modelNamespace\\OverlappedUniqueKeyMember"),
				array("$modelNamespace\\UniqueKeyMember")
		);
	}
	
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testSchemaProperties($className)
  {
  	$this->runTransactionOnAllDatabases(function ($db, $outerTxn) use ($className)
  	{
	  	$jackHill = array("dob" => "1995-07-23", "firstName" => "Jack", "tempId" => "someId", "lastName" => "Hill", "tempEmail" => "someEmail");
	  	
	  	$model = new $className(null, $db);
	  	$schemaProperties = $model->getSchemaProperties();
	  	$this->assertEquals(array(), $schemaProperties);
	  	 
	  	$model = new $className($jackHill, $db);
	  	/*
	  	 * jackHill has some temporary variables stored with the schema.
	  	 * getSchemaProperties() will return only the valid schema properties.
	  	 */
	  	$schemaProperties = $model->getSchemaProperties();
	  	$this->assertEquals(array("dob" => "1995-07-23", "firstName" => "Jack", "lastName" => "Hill"), $schemaProperties);
  	});
  }
  
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testNonSequencedProperties($className)
  {
  	$this->runTransactionOnAllDatabases(function ($db, $outerTxn) use ($className)
  	{
	  	$jackHill = array("id" => "1234", "firstName" => "Jack", "tempId" => "someId", "lastName" => "Hill", "tempEmail" => "someEmail");
	  	
	  	/*
	  	 * Test empty model.
	  	 */
	  	$model = new $className(null, $db);
	  	$nonSequencedProperties = $model->getNonSequencedProperties();
	  	$this->assertEquals(array(), $nonSequencedProperties);
	  
	  	$model = new $className($jackHill, $db);
	  	/*
	  	 * jackHill has an id and some temporary variables stored with the schema, however some of our test classes
	  	 * have an id property that is non-sequenced (SQLite) and some don't have an id field at all so we take that 
	  	 * into consideration.
	  	 * 
	  	 * getNonSequencedProperties() will return only the valid non-sequenced schema properties.
	  	 */
	  	$dbId = $db->getDBId();
		$nonSequencedProperties = $model->getNonSequencedProperties();
		$columnSchema = $model->getColumnSchema();
		$sequenceSchema = $model->getSequenceSchema();
		if ($columnSchema->is($dbId, "id"))
	  	{
	  		if ($sequenceSchema->is($dbId, "id"))
	  		{
		  		$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill"), $nonSequencedProperties);
	  		}
	  		else
	  		{
	  			$this->assertEquals(array("id" => "1234", "firstName" => "Jack", "lastName" => "Hill"), $nonSequencedProperties);
	  		}
	  	}
	  	else
	  	{
	  		$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill"), $nonSequencedProperties);
	  	}
  	});
  }
  
  /**
   * Test getKeyProperties() on the Models that have different key fields.
   */
  public function testKeyProperties()
  {
  	$member = new Member();
  	$keyCandidateSchema = $member->getKeyCandidateSchema();
  	 
  	$notAKey = array("firstName" => "Jack");
  	$member->set($notAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	$this->assertFalse($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$primaryKey = array("id" => "1");
  	$member->set($primaryKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals($primaryKey, $keys);
  	$this->assertTrue($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$member = new MultiPrimaryKeyMember();
  	$keyCandidateSchema = $member->getKeyCandidateSchema();
  	 
  	$notAKey = array("email" => "jh@thehills.com");
  	$member->set($notAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	
  	$stillNotAKey = array("firstName" => "Jack");
  	$member->set($stillNotAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	$this->assertFalse($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$nowItsAKey = array("id" => "1");
  	$member->set($nowItsAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array("email" => "jh@thehills.com", "id" => "1"), $keys);
  	$this->assertTrue($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	/*
  	 * Two unique keys to test here
  	 */
  	$member = new OverlappedUniqueKeyMember();
  	$keyCandidateSchema = $member->getKeyCandidateSchema();
  	
  	$notAKey = array("firstName" => "Jack");
  	$member->set($notAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	 
  	$notAKeyYet = array("lastName" => "Hill");
  	$member->set($notAKeyYet);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	$this->assertFalse($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$nowItsAKey = array("dob" => "1995-07-16");
  	$member->set($nowItsAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill", "dob" => "1995-07-16"), $keys);
  	$this->assertTrue($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$member->reset();
  	
  	$notAKey = array("email" => "jh@thehills.com");
  	$member->set($notAKey);
  	$keys = $member->getKeyProperties();
  	
  	$this->assertEquals(array(), $keys);
  	
  	$notAKeyYet = array("phoneNo" => "123456789");
  	$member->set($notAKeyYet);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	$this->assertFalse($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	
  	$nowItsAKey = array("dob" => "1995-07-16");
  	$member->set($nowItsAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array("email" => "jh@thehills.com", "phoneNo" => "123456789", "dob" => "1995-07-16"), $keys);
  	$this->assertTrue($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	
  	$member = new UniqueKeyMember();
  	$keyCandidateSchema = $member->getKeyCandidateSchema();
  	 
  	$notAKey = array("firstName" => "Jack");
  	$member->set($notAKey);
  	$keys = $member->getKeyProperties();
  	
  	$this->assertEquals(array(), $keys);
  	
  	$notAKeyYet = array("lastName" => "Hill");
  	$member->set($notAKeyYet);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array(), $keys);
  	$this->assertFalse($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  	 
  	$nowItsAKey = array("dob" => "1995-07-16");
  	$member->set($nowItsAKey);
  	$keys = $member->getKeyProperties();
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill", "dob" => "1995-07-16"), $keys);
  	$this->assertTrue($keyCandidateSchema->keyExists($keyCandidateSchema->get($member->getDBId()), $keys));
  }
  
  /**
   * @param string $className
   * 
   * @dataProvider classProvider
   */
  public function testPrimaryKey($className)
  {
  	$this->runTransactionOnAllDatabases(function ($db, $outerTxn) use ($className)
  	{
	  	$member = new $className(null, $db);
	  	
	  	$dbId = $member->getDBId();
	  	$primaryKeySchema = $member->getPrimaryKeySchema();
	  	$primaryKeyDef = $primaryKeySchema->get($dbId);
		$keyCandidateSchema = $member->getKeyCandidateSchema();
		$keyCandidateDef = $keyCandidateSchema->get($dbId);
	  	/*
	  	 * Only do this part of the test for those classes that have primary keys
	  	 */
	  	if ($primaryKeyDef)
	  	{
	  		// use foreach here although there will be only one.
		  	foreach($primaryKeyDef as $indexName => $primaryKeyFields)
		  	{
		  		/*
		  		 * More than one column can be defined for a primary key.
		  		 */
		  		foreach($primaryKeyFields as $columnName => $sequenced)
		  		{
		  			$this->assertTrue($primaryKeySchema->is($dbId, $columnName));
		  		}
		  		/*
		  		 * Test that its a key candidate
		  		 */
		  		$isCandidate = false;
		  		foreach($keyCandidateDef as $name => $primaryKeyColumns)
		  		{
		  			if ($primaryKeyFields == $primaryKeyColumns)
		  			{
		  				$isCandidate = true;
		  				break;
		  			}
		  		}
		  		$this->assertTrue($isCandidate);
		  	}
	  	}
		else 
	  	{
	  		/*
	  		 * Test that none of the columns are primary keys.
	  		 * 
	  		 * Code coverage for classes with no primary key.
	  		 */
	  		$isPrimaryKey = false;
	  		$columnSchema = $member->getColumnSchema();
	  		foreach($columnSchema->get($dbId) as $columnName => $columnProperties)
	  		{
	  			if ($primaryKeySchema->is($dbId, $columnName))
	  			{
	  				$isPrimaryKey = true;
	  			}
	  		}
	  		$this->assertFalse($isPrimaryKey);
	  	}
  	});
  }

  /**
   * @param string $className
   * 
   * @dataProvider classProvider
   */
  public function testUniqueKeys($className)
  {
  	$this->runTransactionOnAllDatabases(function ($db, $outerTxn) use ($className)
  	{
  		$dbId = $db->getDBId();
	  	$member = new $className(null, $db);
	  	 
	  	$uniqueKeySchema = $member->getUniqueKeySchema();
	  	$uniqueKeyDefs = $uniqueKeySchema->get($dbId);
	  	$keyCandidateSchema = $member->getKeyCandidateSchema();
	  	
	  	foreach($uniqueKeyDefs as $indexName => $uniqueKeyFields)
	  	{
		  	/*
		  	 * Test that its a key candidate
		  	 */
		  	$isCandidate = false;
		  	foreach($keyCandidateSchema->get($dbId) as $name => $uniqueKeyColumns)
		  	{
		  		if ($uniqueKeyFields == $uniqueKeyColumns)
		  		{
		  			$isCandidate = true;
		  			break;
		  		}
		  	}
		  	$this->assertTrue($isCandidate);
	  	}
  	});
  }
}
?>