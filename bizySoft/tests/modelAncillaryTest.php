<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\services\core\BizyStoreLogger;
use bizySoft\tests\services\TestLogger;

/**
 * Code coverage for class methods that may not have been called otherwise.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ModelAncillaryTestCase extends ModelTestCase
{
	/**
	 * Test schema query for some code coverage at least.
	 */
	public function testGetSchema()
	{
		$config = self::getTestcaseConfig();
		
		$ids = array_keys($config->getDBConfig());
		foreach ($ids as $id)
		{
			$db = $config->getDB($id);
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
		$config = self::getTestcaseConfig();
		$configClass = $config->getProperty(self::BIZYSTORE_CONFIG_CLASS, true);
		$this->assertEquals("UnitTestConfig", $configClass);
		
		$fileName = $config->getProperty(self::CONFIG_FILE_NAME, true);
		/*
		 * Two possibilites here, either the standard bizySoftConfig.xml or the specific unitTest file.
		 * The standard one is used when the specific unitTest file does not exist.
		 */
		$this->assertTrue(strpos($fileName, "unitTest.xml") !== false || strpos($fileName, "bizySoftConfig.xml") !== false);
		/*
		 * Code coverage for Logger classes
		 */
		$previous = $this->logger->logging(false);
		$this->assertTrue($previous);
		$previous = $this->logger->logging($previous);
		$this->assertFalse($previous);
	}
	
	
}
?>