<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\app\unitTest\Member;
use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\examples\services\MemberService;
use bizySoft\tests\services\TestLogger;

/**
 * PHPUnit test case class to test the example service code.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class MemberServiceTestCase extends ModelTestCase
{
	/**
	 * Use the MemberService to do multiple atomic creates,
	 * an atomic update, then an atomic delete.
	 */
	public function testMemberServices()
	{
		$db = null;
		$config = self::getTestcaseConfig();
		try
		{
			$db = $config->getDB();
			$createDate = $db->getConstantDateTime();
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			
			$member1Created = new Member($formData);
			
			// Test the creation of a member from the form data.
			MemberService::create($member1Created);
			
			// Check the id of the member is populated
			$this->assertTrue($member1Created->getValue("id") > 0);
			
			// Find the created member from the db
			$member1Id = new Member(array(
					"id" => $member1Created->getValue("id") 
			));
			$member1Found = $member1Id->findUnique();
			$this->assertTrue($member1Found instanceof Model);
			// Check against the form data
			
			$diff = $this->formData->checkMemberDetails($member1Found, $formData);
			$this->assertTrue(empty($diff));
			// Check against the explicitly created member.
			$this->assertTrue($member1Found->equals($member1Created));
			
			// Change the values and update the member
			// The primary key is still valid so the update will use this value
			$formData = $this->formData->getJillFormData();
			$formData["dateCreated"] = $createDate;
			MemberService::update($member1Found, $formData);
			// Test the update worked via the primary key
			$updatedMember = MemberService::findById($member1Found->getValue("id"));
			// Get the expected results
			$expectedUpdate = new Member();
			$expectedUpdate = Model::modelCopy($member1Found, $expectedUpdate);
			$expectedUpdate->set($formData);
			$diff = $this->formData->checkMemberDetails($updatedMember, $formData);
			$this->assertTrue(empty($diff));
			$this->assertTrue($expectedUpdate->equals($updatedMember));
			
			// Test the creation of a second member from form data.
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $createDate;
			$member2Created = new Member($formData);
			
			MemberService::create($member2Created);
			
			// Check everything is OK
			$member2Found = MemberService::findById($member2Created->getValue("id"));
			$diff = $this->formData->checkMemberDetails($member2Found, $formData);
			$this->assertTrue(empty($diff));
			// Delete the first member created
			$member = new Member(array(
					"id" => $member1Created->getValue("id") 
			));
			MemberService::delete($member);
			
			// Now check that the member has been deleted
			$deletedMember = MemberService::findById($member->getValue("id"));
			$this->assertEquals($deletedMember, false);
			
			// Check we still have the second member
			$allMembers = MemberService::findAll();
			$this->assertEquals(1, count($allMembers));
			$onlyMemberLeft = reset($allMembers);
			$this->assertEquals($onlyMemberLeft->getValue("id"), $member2Found->getValue("id"));
			
			// Test the name finder
			$firstName = "Jack";
			$lastName = "Hill";
			$jackHills = MemberService::findByName($firstName, $lastName);
			$jackHill = reset($jackHills);
			// Check that members from both finders are equal.
			$this->assertTrue($onlyMemberLeft->equals($jackHill));
		}
		catch ( ModelException $e )
		{
			$message = __METHOD__ . "Test Exception = " . $e->getMessage();
			$this->logger->log($message);
			if ($db)
			{
				// commit here in case we want to look at the test database
				$db->endTransaction(PDODB::COMMIT);
			}
			$this->fail($message);
		}
	}
}
?>