<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreLogger;
use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\tests\services\TestLogger;

/**
 * Code coverage for class methods that may not have been called otherwise.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelAncillaryTestCase extends ModelTestCase
{
	/**
	 * Test schema query for some code coverage at least.
	 */
	public function testGetSchema()
	{
		$ids = DBManager::getDBIds();
		foreach ($ids as $id)
		{
			$db = DBManager::getDB($id);
			$tables = $db->getTableNames();
			
			foreach($tables as $tableName)
			{
				$schema = $db->getSchema($tableName);
				
				// Test that we have something
				$this->assertTrue(count($schema) > 1);
			}
		}
	}
	
	public function testAncillary()
	{
		$appName = BizyStoreConfig::getAppName();
		$this->assertEquals("unitTest", $appName);
		
		$fileName = BizyStoreConfig::getFileName();
		/*
		 * Two possibilites here either the standard bizySoftConfig.xml or the specific unitTest file.
		 * The standard one is used when the specific unitTest file does not exist.
		 */
		$this->assertTrue(strpos($fileName, "unitTest.xml") !== false || strpos($fileName, "bizySoftConfig.xml") !== false);
		/*
		 * Code coverage for Logger classes
		 */
		BizyStoreLogger::log("Code coverage for BizyStoreLogger");
		$previous = TestLogger::logging(false);
		$this->assertTrue($previous);
		$previous = TestLogger::logging($previous);
		$this->assertFalse($previous);
	}
	
	
}
?>