<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\unitTest\UniqueKeyMember;
use bizySoft\bizyStore\model\unitTest\UniqueKeyMembership;
use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\tests\services\TestLogger;

/**
 * Test the realise method on UniqueKeyMembership with foreign key declarations that use multiple columns to point to
 * a UniqueKeyMember. 
 * 
 * UniqueKeyMembership is purposely configured with no foreign Keys in the database provided with the distribution. 
 * We have declared the foreign key relations in the bizySoftConfig file for this table which will give you the same behaviour
 * as foreign key declarations in the database.
 * 
 * This can be useful if your database does not support foreign keys or there are no foreign key declarations in your database
 * and you cannot alter the schema.
 *
 * You can then realise() Models with foreign key relationships just as you would normally.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelRealiseUniqueKeyTestCase extends ModelTestCase
{
	/**
	 * In the UniqueKeyMembership case, the table is the child side of the relationship and is declared to have foreign keys
	 * in the bizySoftConfig file.
	 * 
	 * bizyStore knows which tables and columns form the relationship(s) through the generated schema files.
	 */
	public function testRealiseUniqueKeyMembership()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createUniqueKeyTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new UniqueKeyMember(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
				
			$jackMembership = new UniqueKeyMembership(array("memberFirstName" => $jack->getValue("firstName")), $db);
			$jackMembership = $jackMembership->findUnique();
	
			/*
			 * Test the realise method from the membership
			 */
			$nameProperty = $jackMembership->get(array("memberFirstName" => null));
			/*
			 * Realise a new jack membership, keep the old one to compare
			 */
			$realiseJackMembership = new UniqueKeyMembership($nameProperty, $db);
			TestLogger::startTimer("realise 1");
			$jackMembershipArray = $realiseJackMembership->realise(1); // Realise one hop from the UniqueKeyMembership
			TestLogger::stopTimer("realise 1");
			$this->assertEquals(1, count($jackMembershipArray));
			$jackMembershipRealised = reset($jackMembershipArray); // First one
			$this->assertEquals($jackMembership->getSchemaProperties(), $jackMembershipRealised->getSchemaProperties());
			$properties = $jackMembershipRealised->get();
			/*
			 * The relationship is always specified with the table name and columns of the foreign key declaration
			 * concatenated with ".". In this case it will be "uniqueKeyMembership.memberFirstName.memberLastName.memberDob".
			 * This is true from either end of the relationship.
			 */
			$this->assertEquals(true, isset($properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"]));
			/*
			 * UniqueKeyMembership holds the foreign key so it's a many-to-one.
			 *
			 * Get the UniqueKeyMember
			 */
			$member = $properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"];
			$this->assertTrue($member instanceof UniqueKeyMember);
			$this->assertEquals($jack->getSchemaProperties(), $member->getSchemaProperties());
			
			/*
			 * Do the same for Jill
			 */
			$jill = new UniqueKeyMember(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
				
			$jillMembership = new UniqueKeyMembership(array("memberFirstName" => $jill->getValue("firstName")), $db);
			$jillMembership = $jillMembership->findUnique();
	
			/*
			 * Test the realise method from the membership
			 */
			$nameProperty = $jillMembership->get(array("memberFirstName" => null));
			/*
			 * Realise a new jack membership, keep the old one to compare
			 */
			$realiseJillMembership = new UniqueKeyMembership($nameProperty, $db);
			TestLogger::startTimer("realise 1");
			$jillMembershipArray = $realiseJillMembership->realise(1); // Realise one hop from the UniqueKeyMembership
			TestLogger::stopTimer("realise 1");
			$this->assertEquals(1, count($jillMembershipArray));
			$jillMembershipRealised = reset($jillMembershipArray); // First one
			$this->assertEquals($jillMembership->getSchemaProperties(), $jillMembershipRealised->getSchemaProperties());
			$properties = $jillMembershipRealised->get();
			$this->assertEquals(true, isset($properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"]));
			/*
			 * UniqueKeyMembership holds the foreign key so it's a many-to-one.
			 *
			 * Get the UniqueKeyMember
			 */
			$member = $properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"];
			$this->assertTrue($member instanceof UniqueKeyMember);
			$this->assertEquals($jill->getSchemaProperties(), $member->getSchemaProperties());
		});
	}
	
	/**
	 * In the UniqueKeyMember case, the table is the parent side of the relationship and is declared with no foreign keys.
	 * 
	 * bizyStore still knows which tables and columns form the relationship(s), that information is contained in 
	 * the generated Schema files.
	 */
	public function testRealiseUniqueKeyMember()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a UniqueKeyMembership for both
			 */
			$this->createUniqueKeyTestData($db);
			/*
			 * Just find Jack
			 */
			$jack = new UniqueKeyMember(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			/*
			 * Give jack another UniqueKeyMembership
			 */
			$dateCreated = $db->getConstantDateTime();
			$jackMembership = new UniqueKeyMembership(array(
					"memberFirstName" => $jack->getValue("firstName"),
					"memberLastName" => $jack->getValue("lastName"),
					"memberDob" => $jack->getValue("dob"),
					"length" => 1,
					"dateCreated" => $dateCreated), $db);
			$jackMembership->create();
			
			$nameProperty = $jack->get(array("firstName" => null));
			/*
			 * Realise a new jack, keep the old one to compare
			 */
			$realiseJack = new UniqueKeyMember($nameProperty, $db);
			TestLogger::startTimer("realise 1");
			$jackArray = $realiseJack->realise(1); // Realise 1 hop from the parent
			TestLogger::stopTimer("realise 1");
			$this->assertEquals(1, count($jackArray));
			$jackRealised = reset($jackArray);
			$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
			$properties = $jackRealised->get();
			/*
			 * The relationship is always specified with the table name and columns of the foreign key declaration
			 * concatenated with ".". In this case it will be "uniqueKeyMembership.memberFirstName.memberLastName.memberDob".
			 */
			$this->assertEquals(true, isset($properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"]));
			$memberships = $properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"];
			$this->assertEquals(2, count($memberships)); // Two UniqueKeyMemberships for Jack
			foreach ($memberships as $membership)
			{
				$this->assertTrue($membership instanceof UniqueKeyMembership);
				$this->assertEquals($jackMembership->getSchemaProperties(), $membership->getSchemaProperties());
			}
			
			/*
			 * Now lets test the realise() method with a ridiculous depth just to make sure the recursion stops when there is
			 * no more data. Here we specify a depth of 10 when the depth of data is actually 1.
			 */
			$realiseJack = new UniqueKeyMember($nameProperty, $db);
			TestLogger::startTimer("realise 10");
			/*
			 * Realise 10 hop's from the parent and turn key indexing on.
			 */
			TestLogger::log("Index by key");
			$jackArray = $realiseJack->realise(10, array(Model::OPTION_INDEX_KEY => true));
			TestLogger::stopTimer("realise 10");
			$this->assertEquals(1, count($jackArray));
			$jackRealised = reset($jackArray);
			$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
			/*
			 * Check for a key index on UniqueKeyMember.
			 */
			$jacksKey = implode(".", $jack->get($jack->getKeyProperties()));
			$jacksRealisedKey = key($jackArray);
			$this->assertEquals($jacksKey, $jacksRealisedKey);
			$properties = $jackRealised->get();
			/*
			 * The relationship is always specified with the table name and columns of the foreign key declaration
			 * concatenated with ".". In this case it will be "uniqueKeyMembership.memberFirstName.memberLastName.memberDob".
			 * 
			 * Key indexing should not work for UniqueKeyMembership because there are no unique keys in that class. 
			 * Foreign keys are not unique.
			 */
			$this->assertEquals(true, isset($properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"]));
			$memberships = $properties["uniqueKeyMembership.memberFirstName.memberLastName.memberDob"];
			$this->assertEquals(2, count($memberships)); // Two UniqueKeyMemberships for Jack
			$i = 0;
			foreach ($memberships as $index => $membership)
			{
				/*
				 * Check we can't do key indexing on this class. This tests that we did a zero based index instead.
				 */
				$this->assertEquals($i++, $index);
				$this->assertTrue($membership instanceof UniqueKeyMembership);
				$this->assertEquals($jackMembership->getSchemaProperties(), $membership->getSchemaProperties());
			}
		});
	}
	
	/**
	 * Database data provider for uniqueKey test case.
	 * 
	 * @param DB $db
	 */
	private function createUniqueKeyTestData($db)
	{
		$jillData = $this->formData->getJillFormData();
		$jackData = $this->formData->getJackFormData();
			
		$dbId = $db->getDBId();
		$dbConfig = DBManager::getDBConfig($dbId);
	
		$dateCreated = $db->getConstantDateTime();
		$jillData["dateCreated"] = $dateCreated;
		$jackData["dateCreated"] = $dateCreated;
		/*
		 * Jill is a new uniqueKeyMember.
		 */
		$jill = new UniqueKeyMember($jillData, $db);
		$jill->create();
		/*
		 * Use jill's unique key to specify the foreign key and give her a 1 year uniqueKeyMembership.
		 */
		$jillMembership = new UniqueKeyMembership(array(
				"memberFirstName" => $jill->getValue("firstName"),
				"memberLastName" => $jill->getValue("lastName"),
				"memberDob" => $jill->getValue("dob"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$jillMembership->create();
		/*
		 * Jack is a new uniqueKeyMember.
		 */
		$jack = new UniqueKeyMember($jackData, $db);
		$jack->create();
		/*
		 * Use jack's unique key to specify the foreign key and give him a 1 year uniqueKeyMembership.
		 */
		$jackMembership = new UniqueKeyMembership(array(
				"memberFirstName" => $jack->getValue("firstName"),
				"memberLastName" => $jack->getValue("lastName"),
				"memberDob" => $jack->getValue("dob"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$jackMembership->create();
	}
}
?>