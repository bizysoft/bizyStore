<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\tests\services\TestLogger;

/**
 * Test DBManager initialisation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class DBInitTestCase extends ModelTestCase
{
	/**
	 * Test the initialisaton of the databases from
	 * the config file.
	 */
	public function testDBInit()
	{
		// Make sure we are starting from scratch
		DBManager::reset();
		$config = DBManager::getDBConfig();
		
		$configKeys = array_keys($config);
		$dbIds = DBManager::getDBIds();
		// And the result should be the same as above
		$diff = array_diff($configKeys, $dbIds);
		$this->assertTrue(empty($diff));
		/*
		 * This will initialise the databases from config
		 * and provide some code coverage for the connect() as well
		 */ 
		foreach($dbIds as $dbId)
		{
			DBManager::getDB($dbId);
		}
		
		/*
		 * Its difficult to test anything other than the mandatory config items.
		 * We can't guarantee that bizySoftConfig will be our standard test file.
		 */
		foreach ($dbIds as $dbId)
		{
			$this->assertTrue(array_key_exists(BizyStoreOptions::DB_ID_TAG, $config[$dbId]));
			$this->assertTrue(array_key_exists(BizyStoreOptions::DB_NAME_TAG, $config[$dbId]));
			$this->assertTrue(array_key_exists(BizyStoreOptions::DB_INTERFACE_TAG, $config[$dbId]));
		}
	}
	
}
?>