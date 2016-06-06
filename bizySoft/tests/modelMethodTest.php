<?php  
namespace bizySoft\tests;

use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreOptions;

/**
 *
 * Run some basic Model tests on the public interface not dependent on an actual database connection.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelMethodTestCase extends ModelTestCase
{
	public static function classProvider()
	{
		/*
		 * These are the classes we test specifically in this test case that have shared column names. 
		 * They are used for dynamic instantiation, so must be fully qualified class names.
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
	
	public static function classDBProvider()
	{
		/*
		 * These we test specifically in this test case for database schema related methods so all classes can be used. 
		 *
		 * They should exist in all the test databases configured with the bizySoft/tests/testData scripts.
		 * In any case, they exist in the SQLite database configured with the distribution.
		 */
		$modelNamespace = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE);
		
		return array(
				array("$modelNamespace\\Member"),
				array("$modelNamespace\\Membership"),
				array("$modelNamespace\\MemberView"),
				array("$modelNamespace\\MembershipView"),
				array("$modelNamespace\\MultiPrimaryKeyMember"),
				array("$modelNamespace\\OverlappedUniqueKeyMember"),
				array("$modelNamespace\\UniqueKeyMember"),
				array("$modelNamespace\\UniqueKeyMembership"),
				array("$modelNamespace\\VersionedMember")
		);
	}
	
	/**
	 * Model::equals().
	 * 
	 * @param string $className
	 *
	 * @dataProvider classProvider
	 */
  public function testEquals($className)
  {
  	$jackHill = array("firstName" => "Jack", "lastName" => "Hill");
  	$hill = array("lastName" => "Hill");
  	$jackHillTemp = array("firstName" => "Jack", "lastName" => "Hill", "temp" => "temp");
  	 
  	$firstModel = new $className($jackHill);
  	$secondModel = new $className($hill);
  	$thirdModel = new $className($jackHillTemp);
  	 
  	// Should not matter about the order of comparison
  	$this->assertFalse($firstModel->equals($secondModel));
  	$this->assertFalse($secondModel->equals($firstModel));
  	$this->assertFalse($secondModel->equals($thirdModel));
  	$this->assertFalse($thirdModel->equals($secondModel));
  	$this->assertTrue($firstModel->equals($thirdModel));
  	$this->assertTrue($thirdModel->equals($firstModel));
  	 
  	$hillJack = array("lastName" => "Hill", "firstName" => "Jack");
  	$secondModel = new $className($hillJack);
  	// Should not matter about the order of declaration
  	$this->assertTrue($firstModel->equals($secondModel));
  	$this->assertTrue($secondModel->equals($firstModel));
  	$this->assertTrue($secondModel->equals($thirdModel));
  	$this->assertTrue($thirdModel->equals($secondModel));
  }
  
	/**
	 * @param string $className
	 *
	 * @dataProvider classProvider
	 */
  public function testGet($className)
  {
  	$jackHill = array("firstName" => "Jack", "lastName" => "Hill", "temp" => null);
  	
  	$model = new $className();
  	$this->assertEquals(array(), $model->get());
  	
  	$model = new $className($jackHill);
  	$this->assertEquals(array("firstName" => "Jack"), $model->get(array("firstName" => "doesn't matter what this is")));
  	$this->assertEquals(array("lastName" => "Hill"), $model->get(array("lastName" => null)));
  	$this->assertEquals(array("temp" => null), $model->get(array("temp" => null)));
  	$this->assertEquals(array(), $model->get(array("someName" => null)));
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill"), $model->get(array("firstName" => null, "lastName" => null)));
  	$this->assertEquals($jackHill, $model->get());
  }
  
	/**
	 * @param string $className
	 *
	 * @dataProvider classProvider
	 */
  public function testGetValue($className)
  {
  	$jackHill = array("firstName" => "Jack", "lastName" => "Hill");
  	
  	$model = new $className();
  	$this->assertEquals(null, $model->getValue("someName"));
  	
  	$model = new $className($jackHill);
  	$this->assertEquals(null, $model->getValue("someName"));
  	$this->assertEquals("Jack", $model->getValue("firstName"));
  	$this->assertEquals("Hill", $model->getValue("lastName"));
  }
  
	/**
	 * @param string $className
	 *
	 * @dataProvider classProvider
	 */
  public function testSet($className)
  {
  	$jackHill = array("firstName" => "Jack", "lastName" => "Hill");
  	$dill = array("lastName" => "Dill");
  	 
  	$model = new $className();
  	$replaced = $model->set($jackHill);
  	
  	$this->assertEquals(array(), $replaced);
  	$this->assertEquals($jackHill, $model->get());
  	 
  	$replaced = $model->set($dill);
  
  	$this->assertEquals(array("lastName" => "Hill"), $replaced);
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Dill"), $model->get());
  }
  
	/**
	 * @param string $className
	 *
	 * @dataProvider classProvider
	 */
  public function testSetValue($className)
  {  
  	$model = new $className();
  	$replaced = $model->setValue("firstName", "Jack");
  	$this->assertEquals(array(), $replaced);
  	$replaced = $model->setValue("lastName", "Hill");
  	$this->assertEquals(array(), $replaced);
  	 
  	$replaced = $model->setValue("firstName", "Jill");
  	$this->assertEquals(array("firstName" => "Jack"), $replaced);
  	$replaced = $model->setValue("lastName", "Dill");
  	$this->assertEquals(array("lastName" => "Hill"), $replaced);
   	$this->assertEquals(array("firstName" => "Jill", "lastName" => "Dill"), $model->get());
  }
  
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testReset($className)
  {
  	$jackDill = array("firstName" => "Jack", "lastName" => "Dill", "email" => "jack@theHills.com");
  	$jillHill = array("firstName" => "Jill", "lastName" => "Hill");
  	 
  	$model = new $className($jackDill);
  	$this->assertEquals($jackDill, $model->get());
  	 
  	$replaced = $model->reset($jillHill);
  
  	$this->assertEquals($jackDill, $replaced);
  	$this->assertEquals($jillHill, $model->get());
  	
  	$replaced = $model->reset();
  	$this->assertEquals($jillHill, $replaced);
  	$this->assertEquals(array(), $model->get());
  }
  
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testStrip($className)
  {
  	$jackHill = array("id" => null, "firstName" => "Jack", "dob" => null, "lastName" => "Hill", "email" => null);
  	
  	$model = new $className($jackHill);
  	$this->assertEquals($jackHill, $model->get());
  	
  	$stripped = $model->strip();
  	
  	$this->assertEquals(array("id" => null, "dob" => null, "email" => null), $stripped);
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill"), $model->get());
  }
    
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testCopy($className)
  {
    $formData = $this->formData->getJackFormData();
    $formData["dateCreated"] = "2022-02-02";
    $formData["id"] = "1234";
    // Fill fields of member and setup for the default db
    $member = new $className($formData);
    $memberCopy = $member->copy();
    // Test through equals
    $this->assertTrue($member->equals($memberCopy));
    // Check through test method
    $diff = $this->formData->checkMemberDetails($member, $formData);
    $this->assertTrue(empty($diff));
  }
  
  /**
   * @param string $className
   *
   * @dataProvider classProvider
   */
  public function testDiff($className)
  {
  	$formData = $this->formData->getJackFormData();
  	$formData["dateCreated"] = "2022-02-02";
  	/*
  	 * All test classes have a firstName/lastName
  	 */
  	$formData["firstName"] = "Jack";
  	$formData["lastName"] = "Hill";
  	// Fill fields of member and setup for the default db
  	$member = new $className($formData);
  	$memberCopy = new $className($member->get());
  	// Test through equals
  	$this->assertTrue($member->equals($memberCopy));
  	// Test through diff
  	$this->assertEquals(array(), $member->diff($memberCopy));
  	$member->set(array("firstName" => "Jill", "lastName" => "Dill"));
  	$this->assertEquals(array("firstName" => "Jack", "lastName" => "Hill"), $member->diff($memberCopy));
  }
  
  /**
   * @param string $className
   * 
   * @dataProvider classDBProvider
   */
  public function testDefaultAndCompatibleDBIds($className)
  {
  	/*
  	 * Here we create a Model object with the default db...
  	 */
  	$model = new $className();
  	/*
  	 * then check the Model against the default...
  	 */
  	$defaultDBId = $model->getDefaultDBId();
  	$currentDBId = $model->getDBId();
  	$this->assertEquals($defaultDBId, $currentDBId);
  	/*
  	 * and the Model against it's compatible.
  	 */
  	$compatibleDBIds = $model->getCompatibleDBIds();
  	$this->assertTrue(isset($compatibleDBIds[$currentDBId]));
  	/*
  	 * Do the same through isCompatible().
  	 */
  	$this->assertTrue($model->isCompatible($currentDBId));
  }

}
?>