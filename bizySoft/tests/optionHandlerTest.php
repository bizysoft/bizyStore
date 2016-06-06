<?php
namespace bizySoft\tests;

use \PDO;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\common\ArrayOptionHandler;

/**
 * PHPUnit test case class. Run some ArrayOptionHandler tests used for our config.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class OptionHandlerTestCase extends ModelTestCase
{
	/*
	 * A contrived but typical config file as produced by XMLToArrayTransformer.
	 */
	private $config = array(
			BizyStoreOptions::APP_NAME_TAG => "yourAppName",
			BizyStoreOptions::BIZYSTORE_TAG => array(
					BizyStoreOptions::DATABASE_TAG => array(
							"A" => array(
									BizyStoreOptions::DB_HOST_TAG => "dbAHost",
									BizyStoreOptions::DB_NAME_TAG => "dbAName",
									BizyStoreOptions::DB_PORT_TAG => "dbAPort",
									BizyStoreOptions::DB_USER_TAG => "dbAUser",
									BizyStoreOptions::DB_PASSWORD_TAG => "dbAPassword",
									BizyStoreOptions::DB_CHARSET_TAG => "dbACharset",
									BizyStoreOptions::DB_INTERFACE_TAG => "MySQL",
									BizyStoreOptions::DB_ID_TAG => "A",
									BizyStoreOptions::PDO_OPTIONS_TAG => array(
											PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false 
									),
									BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG => array(
											PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL 
									),
									BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG => array(
											BizyStoreOptions::OPTION_CACHE => true 
									) 
							),
							"B" => array(
									BizyStoreOptions::DB_NAME_TAG => "dbBName",
									BizyStoreOptions::DB_INTERFACE_TAG => "SQLite",
									BizyStoreOptions::DB_ID_TAG => "B",
									BizyStoreOptions::PDO_OPTIONS_TAG => array(
											PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false 
									),
									BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG => array(
											BizyStoreOptions::OPTION_CACHE => false 
									) 
							) 
					),
					BizyStoreOptions::OPTIONS_TAG => array(
							BizyStoreOptions::OPTION_CLEAN_UP => "commit",
							BizyStoreOptions::OPTION_INCLUDE_PATH => "/some/include/path",
							BizyStoreOptions::OPTION_LOG_FILE => "/path/to/logFile" 
					) 
			) 
	);

	public function testOptionHandler()
	{
		$optionHandler = new ArrayOptionHandler($this->config);
		
		/*
		 * Get all the options
		 */
		$config = $optionHandler->getOption();
		/*
		 * Check that the options are stored correctly
		 */
		$this->assertEquals($this->config, $config->value);

		/*
		 * There are two OPTION_CACHE entries check that we get the first one
		 * which has a value of true.
		 */
		$option = $optionHandler->getOption(BizyStoreOptions::OPTION_CACHE);
		$this->assertTrue($option !== null);
		$this->assertTrue($option->value === true);
		/*
		 * Check that we can still get the other OPTION_CACHE entry (false) by digging down
		 * into the options,
		 */
		$databases = $optionHandler->getOption(BizyStoreOptions::DATABASE_TAG);
		$this->assertTrue($databases !== null);
		$optionHandler->setOption($databases);
		$dbB = $optionHandler->getOption("B");
		$this->assertTrue($dbB !== null);
		$optionHandler->setOption($dbB);
		$modelPrepareOptions = $optionHandler->getOption(BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG);
		$this->assertTrue($modelPrepareOptions !== null);
		$optionHandler->setOption($modelPrepareOptions);
		$option = $optionHandler->getOption(BizyStoreOptions::OPTION_CACHE);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value, false);
		/*
		 * check that we can get all the databases from the higest level with just the key for each.
		 */
		$optionHandler->setOption($config);
		$option = $optionHandler->getOption("A");
		$this->assertTrue($option !== null);
		$option = $optionHandler->getOption("B");
		$this->assertTrue($option !== null);
		
		/*
		 * Check all the properties from database "A"
		 */
		$dbA = $optionHandler->getOption("A");
		$this->assertTrue($dbA !== null);
		$optionHandler->setOption($dbA);
		$option = $optionHandler->getOption(BizyStoreOptions::DB_HOST_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAHost");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_NAME_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAName");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_PORT_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAPort");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_USER_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAUser");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_PASSWORD_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAPassword");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_CHARSET_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbACharset");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_INTERFACE_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "MySQL");
		$option = $optionHandler->getOption(BizyStoreOptions::DB_ID_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "A");
		$option = $optionHandler->getOption(BizyStoreOptions::PDO_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false ));
		$option = $optionHandler->getOption(BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(
											PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
									));
		$option = $optionHandler->getOption(BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(BizyStoreOptions::OPTION_CACHE => true ));
		/*
		 * Check that we can get the options that occur at a lower level with just the name.
		 */
		$optionHandler->setOption($config);
		$option = $optionHandler->getOption(BizyStoreOptions::OPTION_CLEAN_UP);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value, "commit");
		$option = $optionHandler->getOption(BizyStoreOptions::OPTION_LOG_FILE);
		$this->assertEquals($option->value, "/path/to/logFile");
		$option = $optionHandler->getOption(BizyStoreOptions::OPTION_INCLUDE_PATH);
		$this->assertEquals($option->value, "/some/include/path");
	}
}
?>