<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\statements\StatementBuilder;
use bizySoft\bizyStore\model\statements\PreparedStatementBuilder;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\bizyStore\model\statements\QueryStatement;
use bizySoft\bizyStore\app\unitTest\Member;
use bizySoft\bizyStore\app\unitTest\Membership;

/**
 * Test the translate method for all configured databases.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class TranslateTestCase extends ModelTestCase
{
	public function testTranslate()
	{
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)
		{
			$jillData = $this->formData->getJillFormData();
			$jackData = $this->formData->getJackFormData();
				
			$dateCreated = $db->getConstantDateTime();
			$jillData["dateCreated"] = $dateCreated;
			$jackData["dateCreated"] = $dateCreated;
			/*
			 * Create Jill.
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
			 * Create Jack.
			 */
			$jack = new Member($jackData, $db);
			$jack->create();
			/*
			 * Give him a membership.
			 */
			$jackMembership = new Membership(array(
					"memberId" => $jack->getValue("id"),
					"adminId" => $jill->getValue("id"),
					"length" => 1,
					"dateCreated" => $dateCreated), $db);
			$jackMembership->create();
			/*
			 * Here's our select which will be database agnostic.
			 */
			$sql = "SELECT ms.* FROM <QmembershipQ> ms JOIN <QmemberQ> AS m ON m.<EidE> = ms.<EmemberIdE> where m.<EfirstNameE> = <PfirstNameP>";
			$properties = array("firstName" => "Jack");
			/*
			 * Set up the Statement
			 */
			$statementBuilder = new StatementBuilder($db);
			$query = $statementBuilder->translate($sql, $properties);
			/*
			 * See if the Statement works
			 */
			$statement = new QueryStatement($db, $query);
			$memberships = $statement->assocSet();
			$this->assertEquals(1, count($memberships));
			$membership = reset($memberships);
			$this->assertEquals($jackMembership->get(), $membership);
			/*
			 * See if the PreparedStatement works
			 */
			$statementBuilder = new PreparedStatementBuilder($db);
			$query = $statementBuilder->translate($sql, $properties);				
			$statement = new QueryPreparedStatement($db, $query, $properties);
			$memberships = $statement->assocSet();
			$this->assertEquals(1, count($memberships));
			$membership = reset($memberships);
			$this->assertEquals($jackMembership->get(), $membership);
			/*
			 * Test for Jill.
			 */
			$properties = array("firstName" => "Jill");
			$memberships = $statement->assocSet($properties);
			$this->assertEquals(1, count($memberships));
			$membership = reset($memberships);
			$this->assertEquals($jillMembership->get(), $membership);
				
		});
	}
}
?>