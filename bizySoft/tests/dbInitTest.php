<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\tests\services\TestLogger;

/**
 * Test DBManager initialisation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class DBInitTestCase extends ModelTestCase
{
	/**
	 * Test the initialisaton of the databases from
	 * the config file.
	 */
	public function testDBInit()
	{
		/*
		 * Make sure we are starting from scratch.
		 */
		
		$config = self::getTestcaseConfig();
		
		$config->closeDBs();
		/*
		 * Get all the database config.
		 */
		$dbConfig = $config->getDBConfig();
		$dbIds = array_keys($dbConfig);
		/*
		 * This will initialise the databases from config
		 * and provide some code coverage for the connect() as well
		 */ 
		foreach($dbIds as $dbId)
		{
			$config->getDB($dbId);
		}
		
		/*
		 * Its difficult to test anything other than the mandatory config items.
		 * We can't guarantee that bizySoftConfig will be our standard test config.
		 */
		foreach ($dbIds as $dbId)
		{
			$this->assertTrue(array_key_exists(self::DB_ID_TAG, $dbConfig[$dbId]));
			$this->assertTrue(array_key_exists(self::DB_NAME_TAG, $dbConfig[$dbId]));
			$this->assertTrue(array_key_exists(self::DB_INTERFACE_TAG, $dbConfig[$dbId]));
		}
	}
	
}
?>