<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\TagValidator;

/**
 * Validate the &lt;database&gt; tag.
 * 
 * Database's from the bizySoftConfig file have some general mandatory and optional fields which can be validated here.
 * 
 * Specialsed validations are carried out in the Connector once the &lt;interface&gt; is resolved.
 * 
 * WRT duplicate DB_ID_TAG. If the DB_ID_TAG is repeated, then the previous database tag it will be overwritten. No validations 
 * take place for this scenario.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class DatabaseTagValidator extends TagValidator
{	
	/**
	 * The mandatory fields for each database configured.
	 *
	 * Each database tag must have these fields and they must contain a value
	 * 
	 * @var array
	 */
	private static $mandatoryDB = array(
					BizyStoreOptions::DB_ID_TAG,
					BizyStoreOptions::DB_INTERFACE_TAG,
					BizyStoreOptions::DB_NAME_TAG
	);
	
	/**
	 * The optional fields for each database configured. If specified, they must contain a value.
	 */
	private static $optionalDB = array(
					BizyStoreOptions::DB_CHARSET_TAG,
					BizyStoreOptions::DB_PORT_TAG,
					BizyStoreOptions::DB_SOCKET_TAG,
					BizyStoreOptions::DB_TABLES_TAG,
					BizyStoreOptions::DB_RELATIONSHIPS_TAG
	);
	
	/**
	 * Default constructor.
	 * 
	 * Use setKeyValue($key, $value) to initialise with the data that will be validated.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Validate the mandatory and optional fields in the database config.
	 * 
	 * @return array the original key/value in an associative array
	 * @see \bizySoft\common\Grinder::grind()
	 */
	public function grind()
	{
		$key = $this->key;
		$database = $this->value;
		
		if ($key && $database)
		{
			$this->setMandatory(self::$mandatoryDB);
			$this->setOptional(self::$optionalDB);
			$this->validate();
		}
		/*
		 * Return the original key/value.
		 */
		return array($key => $database);
	}
}
?>