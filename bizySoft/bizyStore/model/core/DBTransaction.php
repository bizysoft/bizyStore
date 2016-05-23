<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Allows support for nested transactions and those variables which are transaction specific.
 *
 * Used internally within the DB class and can be exposed for application control via DB::beginTransaction();
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class DBTransaction
{
	const UPDATE_POLICY_UNIQUE = 0;
	const UPDATE_POLICY_MULTIPLE = 1;
	const UPDATE_POLICY_DEFAULT = self::UPDATE_POLICY_UNIQUE;
		
	/**
	 * Reference to our database.
	 *
	 * @var PDODB
	 */
	private $db;
	
	/**
	 * The update policy for this transaction.
	 *
	 * Can be either UPDATE_POLICY_UNIQUE or UPDATE_POLICY_MULTIPLE. This behaviour is transaction specific and is 
	 * used to define whether an update or delete can be performed on multiple Model objects.
	 *
	 * @var int
	 */
	private $updatePolicy = self::UPDATE_POLICY_DEFAULT;
	
	/**
	 * Keep a count.
	 *
	 * @var int
	 */
	private $count = 0;
	
	/**
	 * Sets the database reference.
	 *
	 * @param $db DB a reference to the associated database.
	 */
	public function __construct(DB $db)
	{
		$this->db = $db;
	}
	
	/**
	 * Get the current update policy for this transaction.
	 *
	 * @return number can be either DBTransaction::UPDATE_POLICY_UNIQUE or DBTransaction::UPDATE_POLICY_MULTIPLE
	 */
	public function getUpdatePolicy()
	{
		return $this->updatePolicy;
	}
	
	/**
	 * Set the update policy for this transaction.
	 *
	 * The update policy set will take effect until the end of the transaction or until it is changed.
	 * 
	 * The $policy can be either DBTransaction::UPDATE_POLICY_UNIQUE or DBTransaction::UPDATE_POLICY_MULTIPLE
	 * otherwise the update policy is not changed.
	 * 
	 * @param int $policy 
	 * @return int the value of the last update policy.
	 */
	public function setUpdatePolicy($policy)
	{
		$result = $this->updatePolicy;
		switch ($policy)
		{
			case self::UPDATE_POLICY_UNIQUE :
			case self::UPDATE_POLICY_MULTIPLE :
				$this->updatePolicy = $policy;
				break;
		}
		return $result;
	}
	
	/**
	 * Determine if we can update more than one Model object when using update() or delete() methods.
	 *
	 * @return boolean indicating if only unique Model objects can be updated.
	 *        
	 */
	public function isUniqueUpdatePolicy()
	{
		return $this->updatePolicy == self::UPDATE_POLICY_UNIQUE;
	}
	
	/**
	 * Counts can be useful to keep track of the number of rows affected by database activity within the transaction. 
	 * You can use the count however you wish.
	 * 
	 * This method bumps the count by the increment specified.
	 *
	 * The increment can be derived from anything that you want to count, the usual increment would be the 
	 * row count returned via a Statement from a Model create(), update() or delete() method, or the result 
	 * of a DB::execute().
	 *
	 * @param number $increment bump the count by this much.
	 */
	public function count($increment = 1)
	{
		$this->count += $increment;
	}
	
	/**
	 * Gets the current count.
	 *
	 * @return number
	 */
	public function getCount()
	{
		return $this->count;
	}
	
	/**
	 * Flag the database to commit this transaction.
	 */
	public function commit()
	{
		$this->db->endTransaction(DB::COMMIT);
	}
	
	/**
	 * Flag the database to roll-back pending changes made in this transaction
	 */
	public function rollBack()
	{
		$this->db->endTransaction(DB::ROLLBACK);
	}
}
?>