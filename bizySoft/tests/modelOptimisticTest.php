<?php
namespace bizySoft\tests;

use \Exception;
use bizySoft\bizyStore\model\core\ModelConstants;
use bizySoft\bizyStore\app\unitTest\VersionedMember;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case class for Optimistic locking
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelOptimisticTestCase extends ModelTestCase
{
	/**
	 * Closure harness to test Optimistic updates and deletes.
	 */
	private function versionedTest($closure)
	{
		$txn = null;
		$config = self::getTestcaseConfig();
		/*
		 * Do for all db's
		 */
		foreach ($config->getDBConfig() as $dbId => $dbConfig)
		{
			try
			{
				$db = $config->getDB($dbId);
				$createDate = $db->getConstantDateTime();
				$formData = $this->formData->getJackFormData();
				$formData["dateCreated"] = $createDate;
				/*
				 * VersionedMember Models have a "version" property which is defaulted to 0.
				 */
				$versionedMember = new VersionedMember($formData, $db);
				/*
				 * Create the first member from the form data.
				 * 
				 * Jack Hill.
				 */
				$txn = $db->beginTransaction();
				$versionedMember->create();
				$txn->commit();
				/*
				 * Get whats in the database.
				 */
				$idProperty = array("id" => $versionedMember->getValue("id"));
				$versionedMember = new VersionedMember($idProperty, $db);
				$orignalVersionedMember = $versionedMember->findUnique();
				$this->assertTrue($orignalVersionedMember !== false);
				/*
				 * Check we have Jack and his version.
				 */
				$this->assertEquals("Jack", $orignalVersionedMember->getValue("firstName"));
				$originalLockValue = $orignalVersionedMember->getValue("version");
				$this->assertEquals(0, $originalLockValue);
				/*
				 * We have a Model from the database, we can now update. 
				 * 
				 * Change the firstName to "Jill".
				 */
				$newProperties = array("firstName" => "Jill");
				/*
				 * Do the update in another transaction. The update should proceed as normal because the version
				 * has not changed.
				 * 
				 * Set up the lock property to point to our versioned schema property "version".
				 */
				$options = array(ModelConstants::OPTION_LOCK_PROPERTY => "version");
				
				$txn = $db->beginTransaction();
				$result = $orignalVersionedMember->update($newProperties, $options);
				$txn->commit();
				$this->assertTrue($result !== false);
				/*
				 * One row updated.
				 */
				$this->assertEquals(1, $result->rowCount());
				/*
				 * After the update, the version should have changed
				 * 
				 * See whats in the database, get the Model as before.
				 */
				$versionedMember = new VersionedMember($idProperty, $db);
				$newVersionedMember = $versionedMember->findUnique();
				$this->assertTrue($newVersionedMember !== false);
				/*
				 * Check Jill and her version
				 */
				$this->assertEquals("Jill", $newVersionedMember->getValue("firstName"));
				$this->assertEquals(1, $newVersionedMember->getValue("version"));
				/* 
				 * Make a Model so we can test an Optimistic lock failure.
				 */
				$newVersionedMember = new VersionedMember($idProperty, $db);
				/*
				 * Lets set up the Model with the old version property.
				 */
				$orignalVersionedMember->reset(array("id" => $idProperty["id"], 
						"version" => $originalLockValue));
				/*
				 * Now we can test the locking. The previous update emulates the behaviour of a concurrent 
				 * transaction undermining the original data.
				 */
				$txn = $db->beginTransaction();
				/*
				 * Handle updates and deletes on the original Model with the closure.
				 */
				$result = $closure($orignalVersionedMember, $newProperties, $options);
				/*
				 * The result should be false as the original "version" property will not match 
				 * indicating that the data in $orignalVersionedMember is stale.
				 */
				if ($result === false)
				{
					$txn->commit();
					/*
					 * Passed another test, bump assertion count.
					 */
					$this->assertTrue(true);
				}
				else
				{
					throw new Exception("Failed to detect concurrent update");
				}
			}
			catch (Exception $e)
			{
				$this->logger->log(__METHOD__ . ": We caught an outer Exception of type " . get_class($e));
				
				if ($txn)
				{
					$txn->rollback();
				}
				$this->fail("Got an unexpected Exception: " . $e->getMessage());
			}
		}
	}
	
	/**
	 * Test update with a versioned property.
	 *
	 * Version-less updates are the norm when there is no versioned Model property and are tested through-out other unit tests.
	 */
	public function testVersionedUpdate()
	{
		$this->versionedTest(function ($model, $properties, $options) 
		{
			return $model->update($properties, $options);
		});
	}
	
	/**
	 * Test delete with a versioned property.
	 *
	 * Version-less deletes are the norm when there is no versioned Model property and are tested through-out other unit tests.
	 */
	public function testVersionedDelete()
	{
		$this->versionedTest(function ($model, $properties, $options) 
		{
			return $model->delete();
		});
	}
}
?>