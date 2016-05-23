<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\unitTest\Member;
use bizySoft\bizyStore\model\unitTest\Membership;
use bizySoft\bizyStore\model\unitTest\MemberView;
use bizySoft\bizyStore\model\unitTest\MembershipView;
use bizySoft\bizyStore\model\unitTest\MembershipViewSchema;
use bizySoft\tests\services\TestLogger;

/**
 * Test Models that are a database view.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelViewTestCase extends ModelTestCase
{
	/**
	 * The test database schema has a view which is a copy of the member table.
	 */
	public function testMemberView()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jackData = $this->formData->getJackFormData();
			$jillData = $this->formData->getJillFormData();
				
			/*
			 * Create Jack and Jill
			 */
			$jill = new Member($jillData, $db);
			$jill->create();
			$jack = new Member($jackData, $db);
			$jack->create();
			/*
			 * We haven't set up a dateCreated, this is a defaulted column so get 
			 * Jack back out of the database with his "id" so we can compare with the view.
			 */
			$jacksId = array("id" => $jack->getValue("id"));
			$jack->reset($jacksId);
			$jack = $jack->findUnique();
			$this->assertTrue($jack != false);
			/*
			 * Get jacks view with his "id". And compare with his Member entry from
			 * the database, they should be exactly the same.
			 */
			$jacksView = new MemberView($jacksId, $db);
			$jacksView = $jacksView->findUnique();
			$this->assertTrue($jacksView != false);
			$this->assertEquals($jack->get(), $jacksView->get());
			/*
			 * Do the same for Jill.
			 */
			$jillsId = array("id" => $jill->getValue("id"));
			$jill->reset($jillsId);
			$jill = $jill->findUnique();
			$this->assertTrue($jill != false);
			
			$jillsView = new MemberView($jillsId, $db);
			$jillsView = $jillsView->findUnique();
			$this->assertTrue($jillsView != false);
			$this->assertEquals($jill->get(), $jillsView->get());
		});
	}
	
	/**
	 * The test database schema has a view which is a copy of the member table and a view which 
	 * is a copy of the membership table.
	 * 
	 * The bizySoftConfig file also specifies foreign keys on the views which allows us to test
	 * view relationships. These operate in exactly the same way as normal table relationships.
	 */
	public function testMemberViewRelationships()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jackData = $this->formData->getJackFormData();
			$jillData = $this->formData->getJillFormData();
	
			$dateCreated = $db->getConstantDateTime();
			$jillData["dateCreated"] = $dateCreated;
			$jackData["dateCreated"] = $dateCreated;
			/*
			 * Create Jack and Jill
			 */
			$jack = new Member($jackData, $db);
			$jack->create();
			$jill = new Member($jillData, $db);
			$jill->create();
			
			$jackMembership = new Membership(array(
					"memberId" => $jack->getValue("id"),
					"adminId" => $jill->getValue("id"),
					"length" => 1,
					"dateCreated" => $dateCreated), $db);
			$jackMembership->create();
			/*
			 * We don't know if the database passed in supports the MembershipView Model so we
			 * can create the Schema object to check. Only the SQLIte database with the 
			 * distribution has this view.
			 * 
			 * You can't do this by just constructing a Model because it will fail when the database
			 * does not have the MembershipViewSchema. 
			 * 
			 * We know that the default database has one, so we are safe to assume that the 
			 * MembershipViewSchema has been generated, we just have to check if the database is compatible.
			 */
			$membershipViewSchema = new MembershipViewSchema();
			$tableSchema = $membershipViewSchema->tableSchema;
			$dbId = $db->getDBId();
			if ($tableSchema->get($dbId))
			{
				/*
				 * We've got a database that supports the MembershipView view.
				 */
				$jacksId = array("id" => $jack->getValue("id"));
				/*
				 * Realise a new jack for the view, keep the old one to compare
				 */
				$realiseJack = new MemberView($jacksId, $db);
				TestLogger::startTimer("realise MemberView");
				$jackArray = $realiseJack->realise(1); // Realise 1 hop from the parent
				TestLogger::stopTimer("realise MemberView");
				$this->assertEquals(1, count($jackArray));
				$jackRealised = reset($jackArray);
				$this->assertEquals($jack->getSchemaProperties(), $jackRealised->getSchemaProperties());
				$properties = $jackRealised->get();
				/*
				 * The relationship is always specified with the table name and columns of the foreign key declaration
				 * concatenated with ".". In this case it will be "membershipView.memberId".
				 */
				$this->assertEquals(true, isset($properties["membershipView.memberId"]));
				$membershipViews = $properties["membershipView.memberId"];
				$membershipView = reset($membershipViews); // First one
				$this->assertTrue($membershipView instanceof MembershipView);
				$this->assertEquals($jackMembership->getSchemaProperties(), $membershipView->getSchemaProperties());
			}
		});
	}
}
?>