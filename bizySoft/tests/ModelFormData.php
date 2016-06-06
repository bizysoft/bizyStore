<?php
namespace bizySoft\tests;

/**
 * Form data modelling a web request etc.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 * @codeCoverageIgnore
 */
class ModelFormData
{

	public function getJackFormData()
	{
		$formData = array();
		
		$formData["firstName"] = "Jack";
		$formData["lastName"] = "Hill";
		$formData["address"] = "255 Mountain St";
		$formData["suburb"] = "Hilldene";
		$formData["state"] = "Vic";
		$formData["postCode"] = "3333";
		$formData["gender"] = "Male";
		$formData["dob"] = "1973-05-01";
		$formData["email"] = "jack@thehills.com";
		$formData["phoneNo"] = "0333333333";
		
		return $formData;
	}
	
	public function getJillFormData()
	{
		$formData = array();
		
		$formData["firstName"] = "Jill";
		$formData["lastName"] = "Gill";
		$formData["address"] = "10 Gill St";
		$formData["suburb"] = "Gilldene";
		$formData["state"] = "NSW";
		$formData["postCode"] = "2222";
		$formData["gender"] = "Female";
		$formData["dob"] = "1985-11-10";
		$formData["email"] = "jill@thegills.com";
		$formData["phoneNo"] = "0222222222";
		
		return $formData;
	}
	
	public function getJoeFormData()
	{
		$formData = array();
		
		$formData["firstName"] = "Joe";
		$formData["lastName"] = "Blow";
		$formData["address"] = "11 Blow St";
		$formData["suburb"] = "Gilldene";
		$formData["state"] = "SA";
		$formData["postCode"] = "5555";
		$formData["gender"] = "Male";
		$formData["dob"] = "1945-12-11";
		$formData["email"] = "joe@theblows.com";
		$formData["phoneNo"] = "0555555555";
		
		return $formData;
	}
	
	public function getJaneFormData()
	{
		$formData = array();
	
		$formData["firstName"] = "Jane";
		$formData["lastName"] = "Doe";
		$formData["address"] = "12 Doe St";
		$formData["suburb"] = "Doedene";
		$formData["state"] = "QLD";
		$formData["postCode"] = "4444";
		$formData["gender"] = "Female";
		$formData["dob"] = "1965-01-17";
		$formData["email"] = "jane@thedoes.com";
		$formData["phoneNo"] = "0444444444";
	
		return $formData;
	}
	
	public function checkMemberDetails($member, $formData)
	{
		$memberProperties = $member->get($formData);
		
		$diff = array_diff_assoc($memberProperties, $formData);
		return $diff;
	}
}

?>