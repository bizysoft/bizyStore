<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\model\statements\UpdatePreparedStatement;

/**
 * Use an optimistic strategy to update row(s) in a database table.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class OptimisticUpdate extends OptimisticWrite
{
	/**
	 * These are the new properties to be persisted based on the Model.
	 * 
	 * @var array
	 */
	protected $newProperties;
	
	/**
	 * Sets the class member variables.
	 * 
	 * You can either set the new Model properties into an already persisted Model or use the $newProperties array to 
	 * set the new properties. This supports a combination of both for persisted and non-persisted Models. 
	 * 
	 * Setting the original Model with key properties to use as the where clause and setting only the $newProperties 
	 * for the update will be the fastest method.
	 * 
	 * Usually you would issue an update on a persisted Model. Non-persisted Models can be used, say if you only have 
	 * a contrived Model with key properties to search on or you want to update more than one row described by the Model.
	 * 
	 * @param Model $model
	 * @param array $newProperties
	 */
	public function __construct(Model $model, array $newProperties = array(), $options = array())
	{
		/*
		 * Check the dirty properties for Model's that are persisted already.
		 */
		if ($model->isPersisted())
		{
			$dirty = $model->getDirty();
			if ($dirty)
			{
				/*
				 * UpdatePreparedStatement expects a Model with old properties and an array of new properties.
				 * We have a problem in that the Model has been changed as well as possibly supplying
				 * a set of new properties. So we need to reconstruct the old Model so we can find it 
				 * in the database again.
				 */
				$modelProperties = $model->get();
				$oldModelProperties = $dirty + $modelProperties;
				$model->set($oldModelProperties);
				/*
				 * Now setup the properties we need to update. We want the $newProperties in a union with the 
				 * dirtied (new) Model properties.
				 * 
				 * Note that the $newProperties takes precedence, so will overwrite dirtied Model properties if they are the same.
				 */
				$newProperties += array_intersect_key($modelProperties, $dirty);
			}
		}
		parent::__construct($model, $options);
		
		$this->newProperties = $newProperties;
	}
	
	/**
	 * Sets the lock property to a new value. 
	 * 
	 * Note that this method is only called for a OPTION_LOCK_MODE of LOCK_MODE_LOCAL.
	 * 
	 * @param string $lockProperty the property name to set.
	 * @param string $oldLockValue
	 */
	protected function setLockProperty($lockProperty, $oldLockValue)
	{
		$this->newProperties[$lockProperty] = $oldLockValue + 1;
	}
	
	/**
	 * Executes the database update statement.
	 * 
	 * @param Model $model
	 * @return PDOStatement the executed PDOStatement for access to executed statement information.
	 */
	protected function executeStatement()
	{
		$modelPreparedStatement = new UpdatePreparedStatement($this->oldModel, $this->newProperties, $this->options);
		return $modelPreparedStatement->execute();
	}
	
	/**
	 * Do any clean up to finalise the write to the database.
	 * 
	 * Set the new properties into the original Model if it was a unique update.
	 */
	protected function cleanUp()
	{
		$this->oldModel->set($this->newProperties);
		$this->oldModel->resetDirty();
	}
}

?>