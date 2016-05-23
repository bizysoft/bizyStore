<?php
namespace bizySoft\tests;

use \Exception;

/**
 * Difficult to test pessimistic locking in a single threaded environment, so we just do some code coverage.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ModelPessimisticTestCase extends ModelTestCase
{
	/**
	 * Test the findForUpdate Method
	 */
	public function testPessimistic()
	{
		$this->runTransactionOnAllDatabasesAndTables(function ($db, $outerTxn, $className)
		{
			$dbId = $db->getDBId();
			/*
			 * Create Jack
			 */
			$jackData = $this->formData->getJackFormData();
				
			$txn = null;
			try
			{
				$txn = $db->beginTransaction();
				$jack = new $className($jackData, $db);
				$jack->create();
				/*
				 * Find Jack for update
				 */
				$jacks = $jack->findForUpdate();
				$this->assertEquals(1, count($jacks));
				
				$txn->rollback();
			}
			catch (Exception $e)
			{
				if ($txn)
				{
					$txn->rollback();
				}
				$this->fail("Failed to apply findForUpdate lock");
			}
		});
	}
}
?>