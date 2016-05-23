<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\unitTest\Member;

/**
 * Test Model order by, limit and offset.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelOrderByTestCase extends ModelTestCase
{
	public function testOrderBy()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jackData = $this->formData->getJackFormData();
			$jillData = $this->formData->getJillFormData();
			$joeData = $this->formData->getJoeFormData();
				
			/*
			 * Create Jack, Jill and Joe
			 */
			$jill = new Member($jillData, $db);
			$jill->create();
			$jack = new Member($jackData, $db);
			$jack->create();
			$joe = new Member($joeData, $db);
			$joe->create();
			/*
			 * Order by the firstName property in descending order
			 */
			$member = new Member();
			
			$members = $member->find(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <EfirstNameE> DESC"));
			$this->assertEquals(3, count($members));
			$this->assertEquals("Joe", $members[0]->getValue("firstName"));
			$this->assertEquals("Jill", $members[1]->getValue("firstName"));
			$this->assertEquals("Jack", $members[2]->getValue("firstName"));
			/*
			 * Order by the lastName descending and the date of birth (dob) decending
			 */
			$members = $member->find(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <ElastNameE> DESC, <EdobE> DESC"));
			$this->assertEquals(3, count($members));
			$this->assertEquals("Jack", $members[0]->getValue("firstName"));
			$this->assertEquals("Jill", $members[1]->getValue("firstName"));
			$this->assertEquals("Joe", $members[2]->getValue("firstName"));
			/*
			 * Order by the date of birth property (dob) in ascending order
			 */
			$members = $member->find(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <EdobE>"));
			$this->assertEquals(3, count($members));
			$this->assertEquals("Joe", $members[0]->getValue("firstName"));
			$this->assertEquals("Jack", $members[1]->getValue("firstName"));
			$this->assertEquals("Jill", $members[2]->getValue("firstName"));
		});
	}
	
	public function testLimitOffset()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jackData = $this->formData->getJackFormData();
			$jillData = $this->formData->getJillFormData();
			$joeData = $this->formData->getJoeFormData();
				
			/*
			 * Create Jack, Jill and Joe
			 */
			$jill = new Member($jillData, $db);
			$jill->create();
			$jack = new Member($jackData, $db);
			$jack->create();
			$joe = new Member($joeData, $db);
			$joe->create();
			/*
			 * Order by the date of birth (dob) and see if we can get them all with a large LIMIT clause.
			 */
			$member = new Member(array("limit" => 10));
			$members = $member->find(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <EdobE> LIMIT <PlimitP>"));
			$this->assertEquals(3, count($members));
			$this->assertEquals("Joe", $members[0]->getValue("firstName"));
			$this->assertEquals("Jack", $members[1]->getValue("firstName"));
			$this->assertEquals("Jill", $members[2]->getValue("firstName"));
			/*
			 * Check by getting a find statement.
			 */
			$statement = $member->getFindStatement(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <EdobE> LIMIT <PlimitP>"));
			$members = $statement->objectSet(array("limit" => 1));
			$this->assertEquals(1, count($members));
			$this->assertEquals("Joe", $members[0]->getValue("firstName"));
			/*
			 * Get a window of 2 Models starting at offset 1 
			 * from the start of the result set. This means the last two here.
			 * 
			 * This technique can be used for pagination. Just change limit and offset in the execute
			 * properties for each objectSet() and append the same query portion.
			 */
			$statement = $member->getFindStatement(array(Model::OPTION_APPEND_CLAUSE => "ORDER BY <EdobE> LIMIT <PlimitP> OFFSET <PoffsetP>"));
			$members = $statement->objectSet(array("limit" => 2, "offset" => 1));
			$this->assertEquals(2, count($members));
			$this->assertEquals("Jack", $members[0]->getValue("firstName"));
			$this->assertEquals("Jill", $members[1]->getValue("firstName"));
			/*
			 * Check another window.
			 */
			$members = $statement->objectSet(array("limit" => 1, "offset" => 2));
			$this->assertEquals(1, count($members));
			$this->assertEquals("Jill", $members[0]->getValue("firstName"));
		});
	}
}
?>