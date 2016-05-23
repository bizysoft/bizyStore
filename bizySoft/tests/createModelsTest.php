<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\services\CreateModels;
use bizySoft\tests\services\TestLogger;

/**
 * Test CreateModels which is a service class provided with the distribution.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class CreateModelsTestCase extends ModelTestCase
{
	public function testCreateModels()
	{
		$iterations = 10;		
		$bulkCreator = new CreateModels();
		$createCount = 0;
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)  use($iterations, $bulkCreator, &$createCount)
		{
			$formData = $this->formData->getJackFormData();
			$formData["dateCreated"] = $db->getConstantDateTime();
			/*
			 * Create $iterations of all our test classes in each database
			 */
			$classNames = self::getTestClasses();
			foreach ($classNames as $className)
			{
				TestLogger::log("Instantiating $iterations $className");
				TestLogger::startTimer("Add timer");
				for ($i = 0; $i < $iterations; $i++)
				{
					$model = new $className($formData, $db);
					/*
					 * Change the lastName/email to avoid key clashes on some test classes.
					 */
					$newProperties = array(
							"lastName" => "lastName_" . sprintf(self::SUFFIX_FORMAT, $i + $iterations), 
							"email" => "email_" . sprintf(self::SUFFIX_FORMAT, $i + $iterations)
					);
					$model->set($newProperties);
					$bulkCreator->add($model);
					$createCount++;
				}
				TestLogger::stopTimer("Add timer");
			}
		});
		$noCreated = $bulkCreator->excecute();
		TestLogger::log("Created $noCreated models");
		$this->assertEquals($createCount, $noCreated);
		/*
		 * Now do a real test to see if we have actually created everything in all databases
		 */
		$createCount = 0;
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn)  use($iterations, &$createCount)
		{
			$classNames = self::getTestClasses();
			foreach ($classNames as $className)
			{
				$model = new $className(null, $db);
				/*
				 * Find the Model's for this database
				 */
				$models = $model->find();
				$modelCount = count($models);
				$this->assertEquals($iterations, $modelCount);
				$createCount += $modelCount;
			}
		});
		$this->assertEquals($createCount, $noCreated);
	}
}
?>