<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\model\statements\DeletePreparedStatement;

/**
 * Use an optimistic strategy to delete row(s) in a database table.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class OptimisticDelete extends OptimisticWrite
{
	/**
	 * Sets the class member variables.
	 * 
	 * @param Model $model
	 * @param array $newProperties
	 */
	public function __construct(Model $model, $options)
	{
		parent::__construct($model, $options);
	}
	
	/**
	 * This is a NO-OP for a delete. 
	 * 
	 * @param string $lockProperty the property name to set.
	 * @param string $oldLockValue
	 */
	protected function setLockProperty($lockProperty, $oldLockValue)
	{}
	
	/**
	 * Executes the database delete statement.
	 * 
	 * @param Model $model
	 * @return PDOStatement the executed PDOStatement for access to executed statement information.
	 */
	protected function executeStatement()
	{
		$modelPreparedStatement = new DeletePreparedStatement($this->oldModel, $this->options);
		return $modelPreparedStatement->execute();
	}
	
	/**
	 * This is a NO-OP for a delete. 
	 */
	protected function cleanUp()
	{}
}

?>