<?php
namespace bizySoft\bizyStore\services\core;

use \PDO;
use bizySoft\common\ValidationErrors;

/**
 * Abstract class defining common behaviour for connecting to databases.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class Connector implements ConnectorI
{	
	/**
	 * These are the most common config validations for databases.
	 *
	 * The &lt;user&gt; and &lt;password&gt; fields are mandatory. Most databases default the &lt;host&gt; to 'localhost'.
	 *
	 * The other manadatory fields &lt;id&gt;, &lt;interface&gt; and &lt;name&gt; are already validated.
	 *
	 * See the your vendors connection documentation for further information.
	 *
	 * @param array $dbConfig
	 */
	public function validate(array $dbConfig)
	{
		$mandatoryFields = array();
	
		$mandatoryFields[self::DB_USER_TAG] = isset($dbConfig[self::DB_USER_TAG]) ? $dbConfig[self::DB_USER_TAG] : null;
		$mandatoryFields[self::DB_PASSWORD_TAG] = isset($dbConfig[self::DB_PASSWORD_TAG]) ? $dbConfig[self::DB_PASSWORD_TAG] : null;
	
 		/*
		 * Validate each field
		 */
		foreach ($mandatoryFields as $field => $fieldValue)
		{
			if (!$fieldValue)
			{
				ValidationErrors::addError("Mandatory field <$field> is missing.");
			}
		}
	}
}
?>