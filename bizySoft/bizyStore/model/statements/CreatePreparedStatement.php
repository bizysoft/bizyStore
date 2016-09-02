<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\core\SchemaConstants;

/**
 * Supports the Create CRUD operation on Model objects.
 *
 * Concrete class for inserting Model objects into a database table.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class CreatePreparedStatement extends CRUDPreparedStatement implements SchemaConstants
{
	/**
	 * Construct a prepared statement using the Model object.
	 *
	 * @param Model $modelObj
	 * @param array $options prepare options.
	 */
	public function __construct($modelObj, $options = array())
	{
		parent::__construct($modelObj, $options);
	}

	/**
	 * Create does not have a where clause so we override the base class method.
	 * 
	 * @param Model $modelObj
	 */
	protected function initialise()
	{
		/*
		 * Get only the properties that are not allocated automatically by the database
		 */
		$properties = $this->modelObj->getNonSequencedProperties();
		/*
		 * Sort by property name for cache
		 */
		ksort($properties);
		$this->properties = $properties;
	}

	/**
	 * Build an insert statement for the Model and return it.
	 *
	 * The statement is the raw text statement including colon prefixed named parameters keys.
	 *
	 * @return string the create statement ready to prepare.
	 */
	protected function buildStatement()
	{
		/*
		 * Guaranatee there will be properties.
		 */
		$properties = empty($this->properties) ? $this->getNullProperties() : $this->properties;

		$statement = $this->statementBuilder->buildModelInsertStatement($this->modelObj->getTableName(), $properties);
		$statement = $this->statementBuilder->translate($statement, $properties);
		
		$this->properties = $this->statementBuilder->translateProperties($properties);
		
		return $statement;
	}
	
	/**
	 * Do all we can to fill nullable properties with nulls.
	 * 
	 * This is a database/table agnostic way to allow an empty Model to be inserted into the database.
	 * 
	 * Under certain circumstances this may return an array that will fail in the query.
	 * eg. null key properties or the Model totally consists of non-nullable properties 
	 * in both cases those properties should be provided in the Model.
	 * 
	 * @returns array
	 */
	private function getNullProperties()
	{
		$nullProperties = array();
		$schema = $this->modelObj->getColumnSchema();
		foreach ($schema->get($this->modelObj->getDBId()) as $key => $columnProps)
		{
			if (isset($columnProps[self::IS_NULLABLE]) && $columnProps[self::IS_NULLABLE])
			{
				$nullProperties[$key] = null;
			}
		}
		ksort($nullProperties);
		return $this->modelObj->getNonSequencedProperties($nullProperties);
	}
}
?>