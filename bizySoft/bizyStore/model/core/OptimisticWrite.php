<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Optimistic locking support.
 * 
 * Locking in this context means handling concurrency specifically for update and delete actions.
 * 
 * Locks are taken by the database as would be the normal case when executing statements for these actions, but 
 * optimistic techniques do not require any previous read locks on data that is to be updated/deleted. The data itself 
 * supplies a means to manage version conflicts between database writes.
 *
 * We don't know how long the data for the old Model has been around, it may even span other requests. Someone may 
 * have even changed the key fields if they are not sequenced, in which case we will definitely not find it again.
 * Also, we may not want to update multiple database records (according to the transaction update policy) if the Model
 * has no resolvable key fields.
 * 
 * If the $options are set with a property of self::OPTION_LOCK_PROPERTY, then we use this property to get the schema property 
 * in the Model as a version field for the lock check. 
 * 
 * ie. $options = array(OptimisticUpdate::OPTION_LOCK_PROPERTY => "name of your versioned property");
 * 
 * The OPTION_LOCK_PROPERTY is also used to indicate that the update should be on a unique row. In this scenario it is 
 * possible to use a key search for the update/delete so you only need to pass the key fields in the Model.
 * 
 * Not specifying a OPTION_LOCK_PROPERTY uses the properties of the old Model that are specified to perform version-less locking. 
 * Note that for this scenario, ALL the possible schema properties for the Model should be included for the version check, otherwise 
 * the modification will be NON optimistic, it's just the same as issuing an update without concern for concurrency. 
 * This is likely to result in 'lost updates' and possibly incorrect operation in concurrent systems. You can 
 * also update multiple rows according to the transaction update policy in this mode.
 *
 * If $options has a property of Model::OPTION_LOCK_MODE then the specified mode will be used for the lock/versioning mechanism. 
 * OPTION_LOCK_MODE can be either LOCK_MODE_LOCAL or LOCK_MODE_DATABASE. For LOCK_MODE_DATABASE, a database 
 * column default or trigger should set this value on row modification.
 *
 * LOCK_MODE_LOCAL is preferred over LOCK_MODE_DATABASE for updates because it is under application control and is the most 
 * reliable method. bizyStore defaults to LOCK_MODE_LOCAL if no OPTION_LOCK_MODE is specified.
 *
 * Optimistic locking involves:
 * 
 * 1) use the Model's value of the option OPTION_LOCK_PROPERTY in the update/delete where clause along with any properties 
 * required to find the correct row(s). If there is no option OPTION_LOCK_PROPERTY then use all the available Model properties.
 * 
 * 2) specifically for an update with the default option OPTION_LOCK_MODE => LOCK_MODE_LOCAL, change the value of the option 
 * OPTION_LOCK_PROPERTY within the Model in the set clause for the update.
 * 
 * 3) issue the update/delete statement based on the old Model properties which will lock the row(s) concerned.
 * 
 * 4) if the statement does not find any rows to update/delete then return false. This is an optimistic lock failure and 
 * indicates to the calling code that the Model data is stale. The calling code should itself determine what to do, usually 
 * re-read the model and attempt the update again.
 * 
 * 5) if the update changes more than one row, then if the option OPTION_LOCK_PROPERTY exists OR the transaction update policy 
 * is the default DBTransaction::UPDATE_POLICY_UNIQUE an exception is thrown and the calling code should rollback to release 
 * the lock(s). Otherwise the calling code must either commit or rollback the transaction to release the lock(s).
 * 
 * 6) if the update changes exactly one row, the calling code must commit or rollback the transaction to release the 
 * lock(s).
 * 
 * Recovery without user intervention from an optimistic lock failure can be done under certain circumstances, and usually 
 * consists of re-reading and re-processing the Model concerned, a number of times if required. 
 * 
 * + recovery is always possible if you use a sequenced key property to re-read the Model.
 * + recovery may be possible if you use non-sequenced unique key properties to re-read the Model, unless the key values have 
 * changed, in which case user intervention may be required.
 * + it's normally not possible to recover if a Model has no key properties at all. User intervention may be required.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class OptimisticWrite implements OptimisticOptions
{
	/**
	 * This is the Model object that requires a write action to be invoked on it.
	 * 
	 * @var Model
	 */
	protected $oldModel;
	
	/**
	 * Options can contain the self::OPTION_LOCK_PROPERTY, self::OPTION_LOCK_MODE
	 * @var array
	 */
	protected $options = array();
	
	/**
	 * The number of rows updated by the execution.
	 * 
	 * @var int
	 */
	protected $rowsUpdated = 0;
	
	/**
	 * Construct with the Model.
	 * 
	 * @param Model $model
	 */
	protected function __construct(Model $model, $options = array())
	{
		$this->oldModel = $model;
		$this->options = $options;
	}

	/**
	 * Optimistic base class algorithm to handle concurrency issues when writing to database tables. 
	 * 
	 * Supports both updates and deletes through derived classes. Should be called from fault 
	 * tolerant transactional code.
	 * 
	 * @return PDOStatement for access to the executed statement information.
	 * @throws ModelException if the write action fails.
	 */
	public function execute()
	{
		$modelToUpdate = $this->oldModel;
		$lockProperty = isset($this->options[self::OPTION_LOCK_PROPERTY]) ? $this->options[self::OPTION_LOCK_PROPERTY] : null;
		$oldLockValue = null;
		
		if ($lockProperty != null)
		{
			$mode = isset($this->options[self::OPTION_LOCK_MODE]) ? $this->options[self::OPTION_LOCK_MODE] : null;
			/*
			 * Default to LOCK_MODE_LOCAL if none specified.
			 */
			$lockMode = $mode ? $mode : self::LOCK_MODE_LOCAL;
			if ($lockMode == self::LOCK_MODE_LOCAL)
			{
				$oldLockValue = $modelToUpdate->getValue($lockProperty);
				if ($oldLockValue !== null)
				{
					/*
					 * Update the lock property based on the old value. This will persist it during an update.
					 * 
					 * LOCK_MODE_DATABASE does not require processing, a database column default or trigger should set this value
					 * on row modification.
					 */
					$this->setLockProperty($lockProperty, $oldLockValue);
				}
			}
		}
		/*
		 *  We've set up the versioning mechanism, now execute the statement. This will wait for any other 
		 *  locks to be released before locking the row(s)/table/database depending on the vendors implementation.
		 */
		$result = $this->executeStatement();
		
		$this->rowsUpdated = $result->rowCount();
		/*
		 * If no rows are found, then we return false as an indicator that modification was not attempted.
		 * This is not an exceptional condition and indicates to the calling code that the old Model
		 * data is stale and that it may need to re-process the Model before applying the modification again.
		 * 
		 * Note that bizyStore will always throw an Exception if executeStatement() fails, independent of the setting 
		 * for PDO::ATTR_ERRMODE on the database connection. So returning false here is not ambiguous, it means that 
		 * nothing was found.
		 */
		if ($this->rowsUpdated == 0)
		{
			return false;
		}
		else
		{
			if ($this->rowsUpdated > 1)
			{
				/*
				 * Check if we can modify more than one row.
				 */
				$db = $modelToUpdate->getDB();
				if ($db->getTransaction()->isUniqueUpdatePolicy() || $oldLockValue != null)
				{
					throw new ModelException(__METHOD__ . ": Writes to multiple Model's are not allowed in this transaction.");
				}
			}
			/*
			 * Finalise the processing
			 */
			$this->cleanUp();
		}
		return $result;
	}
	
	/**
	 * Sets the lock property if required.
	 * 
	 * Typically for an update, this arranges to persist a new version of the lock value (based on the old value) 
	 * during excecution. For a delete it would do nothing.
	 * 
	 * @param $lockProperty string the name of schema property to set based on the $oldLockValue.
	 * @param $oldLockValue string the old value of the $lockProperty.
	 */
	protected abstract function setLockProperty($lockProperty, $oldLockValue);
	
	/**
	 * Executes the Statement required for the write to the database.
	 * 
	 * @param Model $model
	 * @return PDOStatement the executed PDOStatement for access to executed statement information.
	 */
	protected abstract function executeStatement();
	
	/**
	 * Do any clean up to finalise the write to the database.
	 * 
	 * Typically for an update this would set the new properties into the old Model. For a delete it would do nothing.
	 */
	protected abstract function cleanUp();
}

?>