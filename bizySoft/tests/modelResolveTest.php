<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\statements\Join;
use bizySoft\bizyStore\model\statements\JoinPreparedStatement;
use bizySoft\bizyStore\model\statements\JoinStatement;
use bizySoft\bizyStore\model\unitTest\Member;
use bizySoft\bizyStore\model\unitTest\Membership;
use bizySoft\tests\services\TestLogger;

/**
 * Test the resolve method on Membership Models with foreign key declarations that point to a primary key on the Member.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelResolveTestCase extends ModelTestCase
{
	/**
	 * resolve() is similar to the realise() method on a Model object.
	 * 
	 * The difference is that resolve() will navigate the joins specified and realise only
	 * those Models on the resolvePath.
	 */
	public function testOneToManyJoinPreparedStatementAppend()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
														
			TestLogger::startTimer("jackJill.join.membership");
			$options = array(Model::OPTION_APPEND_CLAUSE => "<EfirstNameE> IN (<PjackP>, <PjillP>)");
			$properties = array("jack" => "Jack", "jill" => "Jill");
			$join = new JoinPreparedStatement($db, "member(id) => membership(memberId)", $properties, $options);
			$models = $join->objectSet();
			TestLogger::stopTimer("jackJill.join.membership");
			$jackTested = false;
			$jillTested = false;
			foreach ($models as $model)
			{
				$properties = $model->get();
				/* 
				 * For swizzled data, JoinSpecs are reversed all except for the first. The relationship names follow the table 
				 * names of the swizzled JoinSpecs concatenated with ".". In the case of a one-to-many relationship like this, 
				 * the swizzled data is the same as un-swizzled, so the relationship name is 'member.membership' and the Models are 
				 * stored in an array under the relationship name.
				 */
				$this->assertTrue(isset($properties["member.membership"]));
				$memberships = $properties["member.membership"];
				if ($jill->getValue("id") == $model->getValue("id"))
				{
					$this->assertEquals(2, count($memberships));
					$this->assertEquals($jill->getSchemaProperties(), $model->getSchemaProperties());
					foreach($memberships as $membership)
					{
						$this->assertEquals($jill->getValue("id"), $membership->getValue("memberId"));
					}
					$jillTested = true;
				}
				if ($jack->getValue("id") == $model->getValue("id"))
				{
					$this->assertEquals(3, count($memberships));
					$this->assertEquals($jack->getSchemaProperties(), $model->getSchemaProperties());
					foreach($memberships as $membership)
					{
						$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
					}
					$jackTested = true;
				}
			}
			$this->assertTrue($jackTested && $jillTested);
		});
	}	
	
	public function testOneToManyJoinStatementAppend()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack and Jill
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
				
			$dateCreated = $db->getConstantDateTime();
				
			TestLogger::startTimer("jackJill.join.membership");
			$options = array(Model::OPTION_APPEND_CLAUSE => "<EfirstNameE> IN (<PjackP>, <PjillP>)");
			$properties = array("jack" => "Jack", "jill" => "Jill");
			$join = new JoinStatement($db, "member(id) => membership(memberId)", $properties, $options);
			$models = $join->objectSet();
			TestLogger::stopTimer("jackJill.join.membership");
			$jackTested = false;
			$jillTested = false;
			foreach ($models as $model)
			{
				$properties = $model->get();
				/*
				 * The relationship names follow the table names ,so the relationship name is 'member.membership'.
				*/
				$this->assertTrue(isset($properties["member.membership"]));
				$memberships = $properties["member.membership"];
				if ($jill->getValue("id") == $model->getValue("id"))
				{
					$this->assertEquals(2, count($memberships));
					$this->assertEquals($jill->getSchemaProperties(), $model->getSchemaProperties());
					foreach($memberships as $membership)
					{
						$this->assertEquals($jill->getValue("id"), $membership->getValue("memberId"));
					}
					$jillTested = true;
				}
				if ($jack->getValue("id") == $model->getValue("id"))
				{
					$this->assertEquals(3, count($memberships));
					$this->assertEquals($jack->getSchemaProperties(), $model->getSchemaProperties());
					foreach($memberships as $membership)
					{
						$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
					}
					$jackTested = true;
				}
			}
			$this->assertTrue($jackTested && $jillTested);
		});
	}
	
	public function testModelGetJoinStatement()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack
			*/
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
				
			TestLogger::startTimer("jack.join.membership");
			$jackProperties = array("firstName" => "Jack");
			$statement = $jack->getJoinStatement("member(id) => membership(memberId)", $jackProperties);
			$models = $statement->objectSet();
			TestLogger::stopTimer("jack.join.membership");
			$jackTested = false;
			foreach ($models as $model)
			{
				$properties = $model->get();
				$this->assertTrue(isset($properties["member.membership"]));
				$memberships = $properties["member.membership"];
				$this->assertEquals(3, count($memberships));
				$this->assertEquals($jack->getSchemaProperties(), $model->getSchemaProperties());
				foreach($memberships as $membership)
				{
					$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
				}
				$jackTested = true;
			}
			$this->assertTrue($jackTested);
		});
	}
	
	public function testModelResolveManyToMany()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack, Jill and Jane
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			$jane = new Member(array("firstName" => "Jane"), $db);
			$jane = $jane->findUnique();
			
			$dateCreated = $db->getConstantDateTime();

			/*
			 * Keep jack for comparison, make a new jack to resolve with just the id.
			 * This is the fastest technique as the where clause is limited to one property.
			 */
			$jacksIdProperty = $jack->get(array("id" => null));
			$resolveJack = new Member($jacksIdProperty, $db);
			TestLogger::startTimer("jack.resolve.many-to-many");
			$models = $resolveJack->resolve("member(id) => membership(memberId, adminId) => member(id)", array(Model::OPTION_INDEX_KEY => true));
			TestLogger::stopTimer("jack.resolve.many-to-many");
			$resolvedJack = reset($models);
			//TestLogger::log("jack = " . $this->sprintModel($resolvedJack));
			$this->assertEquals($jack->getSchemaProperties(), $resolvedJack->getSchemaProperties());
			
			$properties = $resolvedJack->get();
			/*
			 * For the default swizzled data, joinSpecs are reversed all except for the first. The relationship 
			 * names follow the table names of the swizzled joinSpecs concatenated with ".". In this case it will be "member.member".
			 */
			$this->assertEquals(true, isset($properties["member.member"]));
			/*
			 * Jack has three memberships two admined by Jill, one by Jane.
			 */
			$members = $properties["member.member"];
			/*
			 * Make sure Jill exists. The relationship from member is 'member.membership' and is a one-to-many so the Models are 
			 * stored in an array under the relationship name.
			 * 
			 * We have OPTION_INDEX_KEY turned on so we can just index into the 
			 * array with the database id.
			 */
			$jillId = $jill->getValue("id");
			$this->assertEquals(true, isset($members[$jillId]));
			$resolvedJill = $members[$jillId];
			$this->assertEquals($jill->getSchemaProperties(), $resolvedJill->getSchemaProperties());
			$jillProperties = $resolvedJill->get();
			$this->assertEquals(true, isset($jillProperties["member.membership"]));
			/*
			 * Make sure Jacks memberships exist under Jill.
			 */
			$memberships = $jillProperties["member.membership"];
			$this->assertEquals(2, count($memberships));
			foreach ($memberships as $membership)
			{
				$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
			}
			/*
			 * Make sure Jane exists.
			 */
			$janeId = $jane->getValue("id");
			$this->assertEquals(true, isset($members[$janeId]));
			$resolvedJane = $members[$janeId];
			$this->assertEquals($jane->getSchemaProperties(), $resolvedJane->getSchemaProperties());
			$janeProperties = $resolvedJane->get();
			$this->assertEquals(true, isset($janeProperties["member.membership"]));
			/*
			 * Make sure Jacks memberships exist under Jane.
			 */
			$memberships = $janeProperties["member.membership"];
			$this->assertEquals(1, count($memberships));
			foreach ($memberships as $membership)
			{
				$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
			}
		});
	}
		
	public function testModelResolveManyToManyNoSwizzle()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack, Jill and Jane
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			$jane = new Member(array("firstName" => "Jane"), $db);
			$jane = $jane->findUnique();
			
			$dateCreated = $db->getConstantDateTime();
											
			TestLogger::startTimer("jack.resolve.many-to-many.noswizzle");
			$models = $jack->resolve("member(id) => membership(memberId, adminId) => member(id)", 
					array(Join::OPTION_SWIZZLE => false));
			TestLogger::stopTimer("jack.resolve.many-to-many.noswizzle");
			/*
			 * We resolved from Jack, so only one result.
			 */
			$this->assertEquals(1, count($models));
			$resolvedJack = reset($models);
			//TestLogger::log("jack = " . $this->sprintModel($resolvedJack));
			$this->assertEquals($jack->getSchemaProperties(), $resolvedJack->getSchemaProperties());
			$properties = $resolvedJack->get();
			/*
			 * For un-swizzled data, the relationship names follow the table names between JoinSpecs as declared in the
			 * resolvePath concatenated with ".". In this case it will be "member.membership" Models are stored in an 
			 * array under the relationship name.
			 */
			$this->assertEquals(true, isset($properties["member.membership"]));
			/*
			 * Jack has three memberships two admined by Jill, one by Jane.
			 */
			$memberships = $properties["member.membership"];
			$noOfMemberships = 0;
			$noOfJills = 0;
			$noOfJanes = 0;
			foreach ($memberships as $membership)
			{
				$noOfMemberships++;
				$properties = $membership->get();
				$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
				/*
				 * The relationship name follows the joinSpecs, this time it's 'membership.member' 
				 */
				$this->assertTrue(isset($properties["membership.member"]));
				foreach($properties["membership.member"] as $member)
				{
					if($jill->getValue("id") == $member->getValue("id"))
					{
						$noOfJills++;
						$this->assertEquals($jill->getSchemaProperties(), $member->getSchemaProperties());
					}
					if($jane->getValue("id") == $member->getValue("id"))
					{
						$noOfJanes++;
						$this->assertEquals($jane->getSchemaProperties(), $member->getSchemaProperties());
					}
				}
			}
			$this->assertEquals(3, $noOfMemberships);
			$this->assertEquals(2, $noOfJills);
			$this->assertEquals($noOfMemberships, $noOfJills + $noOfJanes);
		});
	}
	
	/**
	 * This is a many-to-many that goes one step further, back out to the membership of the admin member.
	 * It is contrived, but shows that you can retrieve data from many levels easily with this technique, 
	 * either swizzled or not.
	 */
	public function testModelResolveManyToManyMore()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack, Jill and Jane
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			$jane = new Member(array("firstName" => "Jane"), $db);
			$jane = $jane->findUnique();
			
			$dateCreated = $db->getConstantDateTime();
											
			TestLogger::startTimer("jack.resolve.many-to-many.more");
			/*
			 * Many-to-many back out to the membership of the admin member. 
			 */
			$models = $jack->resolve("member(id) => membership(memberId, adminId) => member(id) => membership(memberId)", 
					array(Model::OPTION_INDEX_KEY => true));
			//TestLogger::stopTimer("jack.resolve.many-to-many.more");
			/*
			 * We resolved from Jack, so only one result.
			 */
			$this->assertEquals(1, count($models));
			$resolvedJack = reset($models);
			TestLogger::log("jack = " . $this->sprintModel($resolvedJack));
			$this->assertEquals($jack->getSchemaProperties(), $resolvedJack->getSchemaProperties());
			$properties = $resolvedJack->get();
			/*
			 * For swizzled data, JoinSpecs are reversed all except for the first in the returned Model(s). The relationship 
			 * names follow the swizzled JoinSpecs concatenated with ".".  In this case it will be "member.membership" for 
			 * the first relationship. The membership will be the admin member's membership NOT the member's membership.
			 */
			$this->assertEquals(true, isset($properties["member.membership"]));
			/*
			 * Jack has three memberships two admined by Jill, one by Jane.
			 * Jill has 2 memberships, Jane has one.
			 * 
			 * This is swizzled data, so first there will be 3 admin memberships related to Jack in the Model. 
			 * 
			 * There will be 2 admin memberships that each relate back to the same 2 Jack memberships through Jill. 
			 * This is repeated data that has been specified in the resolve path.
			 * 
			 * There is one admin membership that relates to a single Jack membership through Jane.
			 */
			$adminMemberships = $properties["member.membership"];
			$noOfAdminMemberships = 0;
			$noOfJills = 0;
			$noOfJanes = 0;
			$noOfJackMemberships = 0;
			$jillsId = $jill->getValue("id");
			$janesId = $jane->getValue("id");
			$jacksId = $jack->getValue("id");
			foreach ($adminMemberships as $adminMembership)
			{
				$noOfAdminMemberships++;
				$properties = $adminMembership->get();
				/*
				 * This time the relationship name is 'membership.member'.
				 * Only one here stored in an array.
				 */
				$this->assertTrue(isset($properties["membership.member"]));
				foreach ($properties["membership.member"] as $adminMember)
				{
					$properties = $adminMember->get();
					/*
					 * This time the relationship name is 'member.membership'.
					 */
					$this->assertTrue(isset($properties["member.membership"]));
					$memberships = $properties["member.membership"];
					
					if($jillsId == $adminMember->getValue("id"))
					{
						$noOfJills++;
						$this->assertEquals($jill->getSchemaProperties(), $adminMember->getSchemaProperties());
						foreach ($memberships as $membership)
						{
							$this->assertEquals($jacksId, $membership->getValue("memberId"));
							$noOfJackMemberships++;
						}
					}
					if($janesId == $adminMember->getValue("id"))
					{
						$noOfJanes++;
						$this->assertEquals($jane->getSchemaProperties(), $adminMember->getSchemaProperties());
						foreach ($memberships as $membership)
						{
							$this->assertEquals($jacksId, $membership->getValue("memberId"));
							$noOfJackMemberships++;
						}
					}
				}
			}
			$this->assertEquals(2, $noOfJills);
			$this->assertEquals(1, $noOfJanes);
			$this->assertEquals(3, $noOfAdminMemberships);
			/*
			 * 2 extra for Jack because Jane has admin'ed 2 memberships each pointing back to 2 Jack memberships
			 * via the resolve path.
			 */
			$this->assertEquals(5, $noOfJackMemberships);
		});
	}
	
	public function testJackResolveHimself()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$dbId = $db->getDBId();
			$this->createTestData($db);
			/*
			 * Find Jack, Jill and Jane
			 */
			$jack = new Member(array("firstName" => "Jack"), $db);
			$jack = $jack->findUnique();
			$jill = new Member(array("firstName" => "Jill"), $db);
			$jill = $jill->findUnique();
			$jane = new Member(array("firstName" => "Jane"), $db);
			$jane = $jane->findUnique();
				
			$dateCreated = $db->getConstantDateTime();
	
			/*
			 * Keep jack for comparison, make a new jack to resolve with just the id.
			 * This is the fastest technique as the where clause is limited to one property.
			 */
			$jacksIdProperty = $jack->get(array("id" => null));
			$resolveJack = new Member($jacksIdProperty, $db);
			TestLogger::startTimer("jack.resolve.himself");
			$models = $resolveJack->resolve("member(id) => membership(memberId, memberId) => member(id)", array(Model::OPTION_INDEX_KEY => true));
			TestLogger::stopTimer("jack.resolve.himself");
			$resolvedJack = reset($models);
			$this->assertEquals($jack->getSchemaProperties(), $resolvedJack->getSchemaProperties());
				
			$properties = $resolvedJack->get();
			/*
			 * For the default swizzled data, joinSpecs are reversed all except for the first. The relationship
			 * names follow the table names of the swizzled joinSpecs concatenated with ".". In this case it will be "member.member".
			 */
			$this->assertEquals(true, isset($properties["member.member"]));
			/*
			 * Jack has three memberships two admined by Jill, one by Jane.
			 */
			$members = $properties["member.member"];
			/*
			 *
			 * We have OPTION_INDEX_KEY turned on so we can just index into the
			 * array with the database id.
			 * 
			 * All the memberIds will relate back to Jack so there should only be one Jack
			 * with three memberships
			 */
			$jackId = $jack->getValue("id");
			$this->assertEquals(1, count($members));
			$this->assertEquals(true, isset($members[$jackId]));
			$resolvedJack = $members[$jackId];
			$this->assertEquals($jack->getSchemaProperties(), $resolvedJack->getSchemaProperties());
			$jackProperties = $resolvedJack->get();
			$this->assertTrue(isset($jackProperties["member.membership"]));
			/*
			 * Make sure Jacks memberships exists .
			 */
			$memberships = $jackProperties["member.membership"];
			$this->assertEquals(3, count($memberships));
			foreach ($memberships as $membership)
			{
				$this->assertEquals($jack->getValue("id"), $membership->getValue("memberId"));
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
		$joeData = $this->formData->getJoeFormData();
		$janeData = $this->formData->getJaneFormData();
		
		$dateCreated = $db->getConstantDateTime();
		$jillData["dateCreated"] = $dateCreated;
		$jackData["dateCreated"] = $dateCreated;
		$joeData["dateCreated"] = $dateCreated;
		$janeData["dateCreated"] = $dateCreated;
		/*
		 * Create the members.
		 */
		$jill = new Member($jillData, $db);
		$jill->create();
		$jack = new Member($jackData, $db);
		$jack->create();
		$joe = new Member($joeData, $db);
		$joe->create();
		$jane = new Member($janeData, $db);
		$jane->create();
		/*
		 * Set up the memberships admined by Jill.
		 */
		$jillMembership = new Membership(array(
				"memberId" => $jill->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$jillMembership->create();
		$joeMembership = new Membership(array(
				"memberId" => $joe->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$joeMembership->create();
		$janeMembership = new Membership(array(
				"memberId" => $jane->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$janeMembership->create();
		$jackMembership = new Membership(array(
				"memberId" => $jack->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 1,
				"dateCreated" => $dateCreated), $db);
		$jackMembership->create();
		/*
		 * Create some more memberships for jack one admined
		 * by Jill and one by Jane
		*/
		$jackMembership = new Membership(array(
				"memberId" => $jack->getValue("id"),
				"adminId" => $jill->getValue("id"),
				"length" => 2,
				"dateCreated" => $dateCreated), $db);
		$jackMembership->create();
		$jackMembership = new Membership(array(
				"memberId" => $jack->getValue("id"),
				"adminId" => $jane->getValue("id"),
				"length" => 3,
				"dateCreated" => $dateCreated), $db);
		$jackMembership->create();
		/*
		 * And another for Jill admined by Jane
		*/
		$jillMembership = new Membership(array(
				"memberId" => $jill->getValue("id"),
				"adminId" => $jane->getValue("id"),
				"length" => 2,
				"dateCreated" => $dateCreated), $db);
		$jillMembership->create();
	}
}
?>