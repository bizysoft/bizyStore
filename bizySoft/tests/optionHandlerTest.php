<?php
namespace bizySoft\tests;

use \PDO;
use bizySoft\common\ArrayOptionHandler;

/**
 * PHPUnit test case class. Run some ArrayOptionHandler tests used for our config.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class OptionHandlerTestCase extends ModelTestCase
{
	/*
	 * A contrived but typical config file as produced by XMLToArrayTransformer.
	 */
	private $config = array(
			self::BIZYSTORE_TAG => array(
					self::DATABASE_TAG => array(
							"A" => array(
									self::DB_HOST_TAG => "dbAHost",
									self::DB_NAME_TAG => "dbAName",
									self::DB_PORT_TAG => "dbAPort",
									self::DB_USER_TAG => "dbAUser",
									self::DB_PASSWORD_TAG => "dbAPassword",
									self::DB_CHARSET_TAG => "dbACharset",
									self::DB_INTERFACE_TAG => "MySQL",
									self::DB_ID_TAG => "A",
									self::PDO_OPTIONS_TAG => array(
											PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false 
									),
									self::PDO_PREPARE_OPTIONS_TAG => array(
											PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL 
									),
									self::MODEL_PREPARE_OPTIONS_TAG => array(
											self::OPTION_CACHE => true 
									) 
							),
							"B" => array(
									self::DB_NAME_TAG => "dbBName",
									self::DB_INTERFACE_TAG => "SQLite",
									self::DB_ID_TAG => "B",
									self::PDO_OPTIONS_TAG => array(
											PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false 
									),
									self::MODEL_PREPARE_OPTIONS_TAG => array(
											self::OPTION_CACHE => false 
									) 
							) 
					),
					self::OPTIONS_TAG => array(
							self::OPTION_CLEAN_UP => "commit",
							self::OPTION_INCLUDE_PATH => "/some/include/path",
							self::OPTION_LOG_FILE => "/path/to/logFile" 
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
		$option = $optionHandler->getOption(self::OPTION_CACHE);
		$this->assertTrue($option !== null);
		$this->assertTrue($option->value === true);
		/*
		 * Check that we can still get the other OPTION_CACHE entry (false) by digging down
		 * into the options,
		 */
		$databases = $optionHandler->getOption(self::DATABASE_TAG);
		$this->assertTrue($databases !== null);
		$optionHandler->setOption($databases);
		$dbB = $optionHandler->getOption("B");
		$this->assertTrue($dbB !== null);
		$optionHandler->setOption($dbB);
		$modelPrepareOptions = $optionHandler->getOption(self::MODEL_PREPARE_OPTIONS_TAG);
		$this->assertTrue($modelPrepareOptions !== null);
		$optionHandler->setOption($modelPrepareOptions);
		$option = $optionHandler->getOption(self::OPTION_CACHE);
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
		$option = $optionHandler->getOption(self::DB_HOST_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAHost");
		$option = $optionHandler->getOption(self::DB_NAME_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAName");
		$option = $optionHandler->getOption(self::DB_PORT_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAPort");
		$option = $optionHandler->getOption(self::DB_USER_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAUser");
		$option = $optionHandler->getOption(self::DB_PASSWORD_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbAPassword");
		$option = $optionHandler->getOption(self::DB_CHARSET_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "dbACharset");
		$option = $optionHandler->getOption(self::DB_INTERFACE_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "MySQL");
		$option = $optionHandler->getOption(self::DB_ID_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  "A");
		$option = $optionHandler->getOption(self::PDO_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
											PDO::ATTR_EMULATE_PREPARES => false ));
		$option = $optionHandler->getOption(self::PDO_PREPARE_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(
											PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
									));
		$option = $optionHandler->getOption(self::MODEL_PREPARE_OPTIONS_TAG);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value,  array(self::OPTION_CACHE => true ));
		/*
		 * Check that we can get the options that occur at a lower level with just the name.
		 */
		$optionHandler->setOption($config);
		$option = $optionHandler->getOption(self::OPTION_CLEAN_UP);
		$this->assertTrue($option !== null);
		$this->assertEquals($option->value, "commit");
		$option = $optionHandler->getOption(self::OPTION_LOG_FILE);
		$this->assertEquals($option->value, "/path/to/logFile");
		$option = $optionHandler->getOption(self::OPTION_INCLUDE_PATH);
		$this->assertEquals($option->value, "/some/include/path");
	}
}
?>