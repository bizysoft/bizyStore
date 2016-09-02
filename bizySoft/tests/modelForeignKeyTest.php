<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\app\unitTest\Member;
use bizySoft\bizyStore\app\unitTest\Membership;
use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Test Models with foreign key declarations.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelForeignKeyTestCase extends ModelTestCase
{
	/**
	 * Foreign keys appear on the child side of a table to table relationship.
	 * 
	 * Tables with foreign key declarations can have many row instances which refer to a single unique parent row in 
	 * another table. Therefore they define a many-to-one relationship which can include one-to-one relationships.
	 */
	public function testForeignKeySchema()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jackData = $this->formData->getJackFormData();
			$jillData = $this->formData->getJillFormData();			
			/*
			 * Member Model objects can have a Membership(s)
			 *
			 * Here we propose that Jill is an existing Member who allocates memberships.
			 */
			$jill = new Member($jillData, $db);
			$jill->create();
			/*
			 * Jack is a new member.
			 */
			$jack = new Member($jackData, $db);
			$jack->create();
			/*
			 * Use jack's primary key to specify the foreign key and give him a 1 year membership.
			 * This membership was created by Jill.
			 */
			$jackMembership = new Membership(array("memberId" => $jack->getValue("id"), 
				"adminId" => $jill->getValue("id"), "length" => 1), $db);
			$jackMembership->create();
			/*
			 * Get any foreign keys from $jackMembership's columns.
			 */
			$fkSchema = $jackMembership->getForeignKeySchema();
			$dbId = $db->getDBId();
			$config = $db->getConfig();
			$modelNamespace = $config->getModelNamespace();
			$hasAdmin = false;
			$hasMember = false;
			/*
			 * There are two foreign keys for a Membership, one on "memberId" and the other on "adminId". Both refer to
			 * the Member's "id" column.
			 */
			foreach ($fkSchema->get($dbId) as $indexName => $foreignKey)
			{
				$fkTable = null;
				$fkProperties = array();
				/*
				 * Get each column that the foreign key consists of and build up the foreign properties.
				 */
				foreach($foreignKey as $localColumn => $fkInfo)
				{
					/*
					 * There is only ever one entry in the $fkInfo for each column. We use foreach here
					 * only to resolve the $fkTable/$fkColumn. 
					 * 
					 * The $fkTable will always be the same within a foreign key.
					 */
					$this->assertTrue($localColumn == "adminId" || $localColumn == "memberId");
					foreach($fkInfo as $fkTable => $fkColumn)
					{
						/*
						 * Set the foreign key properties from jack's membership.
						 */
						$fkProperties[$fkColumn] = $jackMembership->getValue($localColumn);
					}
				}
				$className = "$modelNamespace\\" . ucfirst($fkTable);
				$fkModel = new $className($fkProperties, $db);
				/*
				 * See if we can find jack.
				 */
				$members = $fkModel->find();
				$this->assertEquals(1, count($members));
				$member = reset($members); // get first element
				$this->assertTrue($member instanceof Member);
				/*
				 * Now test each foreign key we have populated
				 */
				switch ($localColumn)
				{
					case "adminId":
						$hasAdmin = true;
						$this->assertEquals($jill->getValue("id"), $member->getValue("id"));
					break;
					case "memberId":
						$hasMember = true;
						$this->assertEquals($jack->getValue("id"), $member->getValue("id"));
					break;
				}
			}
			/*
			 * Make sure we have actually completed the test.
			 */
			$this->assertTrue($hasAdmin && $hasMember);
		});
	}

	/**
	 * Foreign key referees appear on the parent side of a table to table relationship.
	 * 
	 * bizyStore's ModelGenerator will compute them from the foreign key declarations existing in your database.
	 *
	 * One parent row instance can have many row instances from other table(s) refer to it through foreign key
	 * declarations on the other table(s).
	 *
	 * Foreign key referees define a basic one-to-many relationship which includes one-to-one relationships.
	 */
	public function testForeignKeyRefereeSchema()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jillData = $this->formData->getJillFormData();
			$jackData = $this->formData->getJackFormData();
			
			/*
			 * As above, Member Model objects can have a Membership(s)
			 * Here we propose that Jill is an existing user who allocates memberships.
			 */
			$jill = new Member($jillData, $db);
			$jill->create();
			/*
			 * Give her a membership.
			 */
			$jillMembership = new Membership(array(
				"memberId" => $jill->getValue("id"), 
				"adminId" => $jill->getValue("id"), 
				"length" => 1), $db);
			$jillMembership->create();
			
			/*
			 * Jack is a new member.
			 */
			$jack = new Member($jackData, $db);
			$jack->create();
			/*
			 * Use jack's primary key "id" to specify the foreign key and give him a 1 year membership.
			 * This membership was created by Jill so the "adminId" is her "id".
			 */
			$jackMembership = new Membership(array(
				"memberId" => $jack->getValue("id"), 
				"adminId" => $jill->getValue("id"), 
				"length" => 1), $db);
			$jackMembership->create();
			/*
			 * Give him another membership over time.
			 */
			$jackMembership = new Membership(array(
				"memberId" => $jack->getValue("id"), 
				"adminId" => $jill->getValue("id"), 
				"length" => 1), $db);
			$jackMembership->create();
			
			$config = $db->getConfig();
			$modelNamespace = $config->getModelNamespace();
			/*
			 * Get any foreign key references from $jack's columns and use them to find the related Model objects.
			 * In this Member instance just the "id" column has references.
			 */
			$refereeSchema = $jack->getForeignKeyRefereeSchema();
			$hasAdmin = false;
			$hasMember = false;
			$dbId = $db->getDBId();
			foreach ($refereeSchema->get($dbId) as $indexName => $foreignKey)
			{
				$fkTable = null;
				$fkProperties = array();
				/*
				 * Get each column that the foreign key consists of and build up the foreign properties.
				 * In this case there is only one, the "id" column.
				 */
				foreach ($foreignKey as $localColumn => $fkInfo)
				{
					$this->assertEquals("id", $localColumn);
					/*
					 * There is only ever one entry in the $fkInfo for each column. We use foreach here
					 * only to resolve the $fkTable/$fkColumn.
					 * The $fkTable will always be the same within a foreign key.
					 */
					foreach ($fkInfo as $fkTable => $fkColumn)
					{
						/*
						 * Set the foreign key properties from jack.
						 */
						$model = $fkColumn == "memberId" ? $jack : $jill;
						$fkProperties[$fkColumn] = $model->getValue($localColumn);
					}
				}
				$className = "$modelNamespace\\" . ucfirst($fkTable);
				$fkObject = new $className($fkProperties, $db);
				$memberships = $fkObject->find();
				switch ($fkColumn)
				{
					case "memberId" :
						/*
						 * Check jack's memberships.
						 */
						$this->assertEquals(2, count($memberships)); // Two Membership's for Jack
						$hasMembership = true;
						foreach ($memberships as $membership)
						{
							$this->assertTrue($membership instanceof Membership);
							/*
							 * "adminId" is relevant from jack's membership.
							 */
							$this->assertEquals($jill->getValue("id"), $membership->getValue("adminId"));
							$this->assertEquals(1, $membership->getValue("length"));
						}
					break;
					case "adminId" :
						/*
						 * Check that jill has admin'ed 3 memberships.
						 */
						$this->assertEquals(3, count($memberships));
						foreach ($memberships as $membership)
						{
							$this->assertTrue($membership instanceof Membership);
							$this->assertEquals($jill->getValue("id"), $membership->getValue("adminId"));
							$this->assertEquals(1, $membership->getValue("length"));
						}
					break;
				}
			}
		});
	}
}
?>