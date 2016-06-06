<?php
namespace bizySoft\tests;

use bizySoft\bizyStore\model\unitTest\MultiSequencedMember;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\DBManager;
use bizySoft\common\ArrayOptionHandler;

/**
 *
 * Test a Model with more than one sequenced column.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class SequencedModelTestCase extends ModelTestCase
{
	public function testSequenceModel()
	{
		$multiSequencedInterfaces = array("PgSQL" => "PgSQL");
		
		$this->runTransactionOnAllDatabases(function ($db, $outerTxn) use ($multiSequencedInterfaces)
		{
			$formData = $this->formData->getJackFormData();
			
			$dbId = $db->getDBId();
			$dbConfig = DBManager::getDBConfig($dbId);
			$optionHandler = new ArrayOptionHandler($dbConfig);
			$interfaceOption = $optionHandler->getOption(BizyStoreOptions::DB_INTERFACE_TAG);
			$interfaceValue = $interfaceOption->value;
			/*
			 * test the interfaces that have multiSequencedMember tables to test
			 */
			if (isset($multiSequencedInterfaces[$interfaceValue]))
			{
				/*
				 * Quick test for code coverage on some schema related methods.
				 * 
				 * First set up a MultiSequencedMember for the db.
				 */
				$multiSequencedModel = new MultiSequencedMember(null, $db);
				
				$notAKey = array("firstName" => "Jack");
				$multiSequencedModel->set($notAKey);
				$keys = $multiSequencedModel->getKeyProperties();
				$this->assertEquals(array(), $keys);
				
				$aUniqueKey = array("seq" => "1");
				$multiSequencedModel->set($aUniqueKey);
				$keys = $multiSequencedModel->getKeyProperties();
				$this->assertEquals($aUniqueKey, $keys);
				
				$primaryKey = array("id" => "2");
				$multiSequencedModel->set($primaryKey);
				$keys = $multiSequencedModel->getKeyProperties();
				// Prefer the primary over the unique key.
				$this->assertEquals($primaryKey, $keys);
				/*
				 * 'id' is the primary key for this Model.
				 */
				$primaryKeySchema = $multiSequencedModel->getPrimaryKeySchema();
				$sequenceSchema = $multiSequencedModel->getSequenceSchema();
				$columnSchema = $multiSequencedModel->getColumnSchema();
				$this->assertTrue($primaryKeySchema->is($dbId, "id"));
				$this->assertTrue($sequenceSchema->is($dbId, "id"));
				$this->assertTrue($columnSchema->is($dbId, "id"));
				$this->assertFalse($columnSchema->is($dbId, "notInSchema"));
				/*
				 * seq is sequenced but not part of the primary key
				 */
				$this->assertFalse($primaryKeySchema->is($dbId, "seq"));
				$this->assertTrue($sequenceSchema->is($dbId, "seq"));
				$this->assertTrue($columnSchema->is($dbId, "seq"));
				/*
				 * No unique keys specified for this Model
				 */
				$uniqueKeySchema = $multiSequencedModel->getUniqueKeySchema();
				$this->assertEmpty($uniqueKeySchema->get($dbId));
				/*
				 * Code coverage for this Model
				 */
				$thisDbId = $multiSequencedModel->getDBId();
				$this->assertEquals($dbId, $thisDbId);
				$this->assertTrue(in_array($thisDbId, $multiSequencedModel->getCompatibleDBIds()));
				$this->assertNotEmpty($multiSequencedModel->getDefaultDBId());
				/*
				 * Test we get back multiple sequences after a create() using the form data.
				 */
				$multiSequencedModel = new MultiSequencedMember($formData, $db);
				$multiSequencedModel->create();
				
				foreach ($sequenceSchema->get($dbId) as $columName => $sequenceName)
				{
					$seq = $multiSequencedModel->getValue($columName);
					$this->assertFalse(empty($seq));
					/*
					 * Test that getCurrentSequence() gets the same value as the Model has.
					 */
					if ($sequenceName)
					{
						$value = $db->getCurrentSequence($sequenceName);
						$this->assertEquals($seq, $value);
					}
				}
			}
		});
	}
}
?>