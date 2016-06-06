<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\unitTest\Member;
use bizySoft\bizyStore\model\unitTest\Membership;
use bizySoft\tests\services\TestLogger;

/**
 * Test the realise method on Membership Models with foreign key declarations that point to a primary key on the Member.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelRealiseTestCase extends ModelTestCase
{
	/**
	 * realise() is like the find() method on a Model object.
	 * 
	 * The difference is that realise() will navigate ALL the foreign key relationships from the specified Model exactly as 
	 * defined in the database. It stores the required Model objects within the Model(s) found via the Model properties.
	 * 
	 * The specified Model properties define the where clause that starts the realisation, so the Model does not realise 
	 * itself but all Models matching the given properties, just like find().
	 * 
	 * In the Member case, the Model's table (member) is a parent to the declared foreign key relationship (membership) and 
	 * does not have database or bizySoftConfig foreign key declarations itself. bizyStore has computed them in it's schema, 
	 * so knows which tables and columns form the relationship(s) out to the number of hops.
	 */
	public function testRealiseMemberHops0()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Just find Jack
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			/*
			 * Test 0 hops.
			 */
			$idProperty = $jack->get(array("id" => null));
			/*
			 * Realise a new jack, keep the old one to compare
			 */
			$realiseJack = new Member($idProperty, $db);
			TestLogger::startTimer("realise");
			$jackArray = $realiseJack->realise(); // Realise just the parent level
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jackArray));
			$jackRealised = reset($jackArray);			
			$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
			/*
			 * Even though there is a membership for Jack, a realise() with 0 hops will not be enough to retrieve it.
			 */
			$properties = $jackRealised->get();
			$this->assertEquals(false, isset($properties["membership.memberId"]));
		});
	}
	
	public function testRealiseMemberHops1()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Just find Jack
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			
			$jackMembership = new Membership(array("memberId" => $jack->getValue("id")), $db);
			$jackMembership = $jackMembership->findUnique();
			$idProperty = $jack->get(array("id" => null));
			/*
			 * Realise a new jack, keep the old one to compare
			 */
			$realiseJack = new Member($idProperty, $db);
			TestLogger::startTimer("realise");
			$jackArray = $realiseJack->realise(1); // Realise 1 hop from the parent
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jackArray));
			$jackRealised = reset($jackArray);				
			$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
			$properties = $jackRealised->get();
			/*
			 * The relationship is always specified with the table name and columns of the foreign key declaration
			 * concatenated with ".". In this case it will be "membership.memberId".
			 */
			$this->assertEquals(true, isset($properties["membership.memberId"]));
			$memberships = $properties["membership.memberId"];
			$membership = reset($memberships); // First one
			$this->assertTrue($membership instanceof Membership);
			$this->assertEquals($jackMembership->getSchemaProperties(), $membership->getSchemaProperties());
		});
	}
	
	public function testRealiseMemberHops2()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			
			$jackMembership = new Membership(array("memberId" => $jack->getValue("id")), $db);
			$jackMembership = $jackMembership->findUnique();
			$idProperty = $jack->get(array("id" => null));
			/*
			 * Realise a new jack, keep the old one to compare
			 */
			$realiseJack = new Member($idProperty, $db);
			TestLogger::startTimer("realise");
			$jackArray = $realiseJack->realise(2); // Realise 2 hops from the parent
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jackArray));
			$jackRealised = reset($jackArray);
	
			$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
			$properties = $jackRealised->get();
			/*
			 * The relationship is always specified with the table name and columns of the foreign key declaration
			 * concatenated with ".". In this case it will be "membership.memberId".
			 */
			$this->assertEquals(true, isset($properties["membership.memberId"]));
			/*
			 * Member does not hold the foreign key (it's a referee), so the relationship is one to many.
			 */
			$memberships = $properties["membership.memberId"];
			$membership = reset($memberships); // First one
			$this->assertTrue($membership instanceof Membership);
			$this->assertEquals($jackMembership->getSchemaProperties(), $membership->getSchemaProperties());

			$properties = $membership->get();
			$this->assertEquals(true, isset($properties["membership.adminId"]));
			/*
			 * Many to one from child
			 */
			$admin = $properties["membership.adminId"];
			$this->assertTrue($admin instanceof Member);
			$this->assertEquals($jill->getSchemaProperties(), $admin->getSchemaProperties());
				
		});
	}

	/**
	 * In the Membership case, the table is the child side of the relationship and is declared to have foreign keys, 
	 * bizyStore knows which tables and columns form the relationship(s).
	 */
	public function testRealiseMembershipHops1()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			
			$jackMembership = new Membership(array("memberId" => $jack->getValue("id")), $db);
			$jackMembership = $jackMembership->findUnique();
				
			/*
			 * Test the realise method from the membership
			 */
			$idProperty = $jackMembership->get(array("id" => null));
			/*
			 * Realise a new jack membership, keep the old one to compare
			 */
			$realiseJackMembership = new Membership($idProperty, $db);
			TestLogger::startTimer("realise");
			$jackMembershipArray = $realiseJackMembership->realise(1); // Realise one hop from the membership
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jackMembershipArray));
			$jackMembershipRealised = reset($jackMembershipArray); // First one
			$this->assertEquals($jackMembership->getSchemaProperties(), $jackMembershipRealised->getSchemaProperties());
			$properties = $jackMembershipRealised->get();
			$this->assertEquals(true, isset($properties["membership.memberId"]));
			/*
			 * Membership holds the foreign key so it's a many-to-one.
			 * 
			 * Get the member
			 */
			$member = $properties["membership.memberId"];
			$this->assertTrue($member instanceof Member);
			$this->assertEquals($jack->getSchemaProperties(), $member->getSchemaProperties());
			/*
			 * Get the administrator
			 */
			$this->assertEquals(true, isset($properties["membership.adminId"]));

			$admin = $properties["membership.adminId"];
			$this->assertTrue($admin instanceof Member);
			$this->assertEquals($jill->getSchemaProperties(), $admin->getSchemaProperties());
		});
	}

	public function testRealiseAdminMembership()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			
			$jillMembership = new Membership(array("memberId" => $jill->getValue("id")), $db);
			$jillMembership = $jillMembership->findUnique();
				
			/*
			 * Give Jack another membership over time.
			 */
			$dateCreated = $db->getConstantDateTime();
			$jackMembership = new Membership(array(
					"memberId" => $jack->getValue("id"),
					"adminId" => $jill->getValue("id"),
					"length" => 1,
					"dateCreated" => $dateCreated), $db);
			$jackMembership->create();
			
			$idProperty = $jill->get(array("id" => null));
			/*
			 * Realise a new jill, keep the old one to compare
			 */
			$realiseJill = new Member($idProperty, $db);
			TestLogger::startTimer("realise");
			$jillArray = $realiseJill->realise(1); // Realise 1 hop from the parent
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jillArray));
			$jillRealised = reset($jillArray);
	
			$this->assertEquals($jill->getSchemaProperties(), $jillRealised->getSchemaProperties());
			$properties = $jillRealised->get();
			/*
			 * Jill's membership
			 */
			$this->assertEquals(true, isset($properties["membership.memberId"]));
			/*
			 * One to many from parent
			 */
			$memberships = $properties["membership.memberId"];
			$membership = reset($memberships); // First one
			$this->assertTrue($membership instanceof Membership);
			$this->assertEquals($jillMembership->getSchemaProperties(), $membership->getSchemaProperties());
			/*
			 * Memberships admin'ed by Jill. 
			 * 
			 * This may be a feature you will either want or not. It is a consequence of the recursive nature 
			 * of the relationship between members and administrators (who are also members), it makes administrator's
			 * a many-to-many with members through membership. You can control this behaviour with the <recursive> tag 
			 * in bizySoftConfig. In the case of Jill, calling realise() will not only bring back her own memberships 
			 * but also all the memberships she has admin'ed.
			 */
			$this->assertEquals(true, isset($properties["membership.adminId"]));
			/*
			 * One to many from parent
			 */
			$memberships = $properties["membership.adminId"];
			$this->assertEquals(3, count($memberships)); // In this case jill has admin'ed 3 memberships, including herself.
			$hasJack = 0;
			$hasJill = 0;
			foreach ($memberships as $key => $membership)
			{
				$this->assertTrue($membership instanceof Membership);
				if ($membership->getValue("memberId") == $jack->getValue("id"))
				{
					$hasJack++;
				}
				else
				{
					$hasJill++;
					// only one jill so we can compare
					$this->assertEquals($jillMembership->getSchemaProperties(), $membership->getSchemaProperties());
				}
			}
			$this->assertEquals(1, $hasJill);
			$this->assertEquals(2, $hasJack); 
		});
	}
	
	/**
	 * You can index the Model array on the database key as well as the normal integer index.
	 */
	public function testRealiseKeyIndexes()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			/*
			 * Creates Jack and Jill and a membership for both
			 */
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			
			$jillMembership = new Membership(array("memberId" => $jill->getValue("id")), $db);
			$jillMembership = $jillMembership->findUnique();
			/*
			 * Give Jack another membership over time.
			 */
			$dateCreated = $db->getConstantDateTime();
			$jackMembership = new Membership(array(
					"memberId" => $jack->getValue("id"),
					"adminId" => $jill->getValue("id"),
					"length" => 1,
					"dateCreated" => $dateCreated), $db);
			$jackMembership->create();				
			/*
			 * Test with key indexes
			 */
			$idProperty = $jill->get(array("id" => null));
			/*
			 * Realise a new jill, keep the old one to compare
			 */
			$realiseJill = new Member($idProperty, $db);
			TestLogger::log("Index by key");
			$indexOption = array(Model::OPTION_INDEX_KEY => true);
			TestLogger::startTimer("realise");
			$jillArray = $realiseJill->realise(1, $indexOption); // Realise 1 hop from the parent
			TestLogger::stopTimer("realise");
			$this->assertEquals(1, count($jillArray));
			foreach($jillArray as $key => $jillRealised)
			{
				$this->assertEquals($key, $jillRealised->getValue("id")); // Check that the array key is the jill's id.
			}	
			$this->assertEquals($jill->getSchemaProperties(), $jillRealised->getSchemaProperties());
			$properties = $jillRealised->get();
			/*
			 * Jill's membership
			 */
			$this->assertEquals(true, isset($properties["membership.memberId"]));
			/*
			 * One to many from parent
			 */
			$memberships = $properties["membership.memberId"];
			
			foreach($memberships as $key => $membership)
			{
				$this->assertTrue($membership instanceof Membership);
				$this->assertEquals($key, $membership->getValue("id"));  // Check that the array key is the membership.id
				$this->assertEquals($jillMembership->getSchemaProperties(), $membership->getSchemaProperties());
			}
			/*
			 * Memberships admin'ed by Jill. 
			 */
			$this->assertEquals(true, isset($properties["membership.adminId"]));
			/*
			 * One to many from parent
			 */
			$memberships = $properties["membership.adminId"];
			$this->assertEquals(3, count($memberships)); // In this case jill has admin'ed 3 memberships, including herself.
			foreach ($memberships as $key => $membership)
			{
				$this->assertTrue($membership instanceof Membership);
				$this->assertEquals($key, $membership->getValue("id")); // Check that the array key is the membership.id
			}
		});
	}
	
	/**
	 * Database data provider for this test case.
	 * 
	 * @param DB $db
	 */
	private function createTestData($db)
	{
		$jillData = $this->formData->getJillFormData();
		$jackData = $this->formData->getJackFormData();
			
		$dateCreated = $db->getConstantDateTime();
		$jillData["dateCreated"] = $dateCreated;
		$jackData["dateCreated"] = $dateCreated;
		/*
		 * Jill is an existing member who allocates memberships.
		 */
		$jill = new Member($jillData, $db);
		$jill->create();
		/*
		 * Give her a membership.
		 */
		$jillMembership = new Membership(array(
				"memberId" => $jill->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
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
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$jackMembership->create();
	}
}
?>