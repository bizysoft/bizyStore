<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\core\ModelException;

/**
 * Concrete class for updating Model objects in the database.
 *
 * Used by DB/Model update methods.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class UpdatePreparedStatement extends CRUDPreparedStatement
{

	/**
	 * The array holding the properties to be updated
	 *
	 * @var array
	 */
	private $newProperties = array();

	/**
	 * Property keys for the SET clause
	 *
	 * @var array
	 */
	private $setClauseKeys = array();
	
	/**
	 * Property values for the SET clause
	 *
	 * @var array
	 */
	private $setClauseProperties = array();

	/**
	 * Construct a prepared statement using the Model passed in.
	 *
	 * @param Model $modelObj the model instance to update.
	 * @param array $newProperties the new properties to update the Model with.
	 * @param array $options prepare options.
	 */
	public function __construct($modelObj, array $newProperties, $options = array())
	{
		$this->newProperties = $newProperties;
		parent::__construct($modelObj, $options);
	}

	/**
	 * Set the prepared statement for an update according to the Model instance and properties passed in.
	 */
	protected function initialise()
	{
		$this->whereClauseProperties = $this->getWhereClauseProperties();
		
		/*
		 * Set the update properties.
		 * It's possible to be using the same property keys with different values between SET and WHERE clause.
		 * eg UPDATE table SET firstName = 'Jack' WHERE firstName = 'Jill'
		 * 
		 * The usual prepared statement would be:
		 * UPDATE table SET firstName = :firstName WHERE firstName = :firstName
		 * which is a no-op.
		 * 
		 * Here we augment the new property name's with an underscore so SET clause keys don't clash with WHERE clause
		 * keys. The result is:
		 * UPDATE table SET firstName = :_firstName WHERE firstName = :firstName
		 */
		$nonSequencedProperties = $this->modelObj->getNonSequencedProperties($this->newProperties);
		foreach ($nonSequencedProperties as $name => $value)
		{
			$key = "_" . $name;
			$this->setClauseKeys[$name] = $key;
			$this->setClauseProperties[$key] = $value;
		}
		/*
		 * We sort here to eliminate duplicates when caching.
		 */
		ksort($this->setClauseKeys);
	}
	
	/**
	 * Build the update statement and return it.
	 *
	 * @return string the raw text statement including colon prefixed named parameters.
	 */
	public function buildStatement()
	{
		$statement = $this->statementBuilder->buildModelUpdateStatement($this->modelObj->getTableName(), 
		                                                                $this->setClauseKeys, $this->whereClauseProperties);
		if (!$statement)
		{
			throw new ModelException("No properties defined.");
		}
		$statement = isset($options[Model::OPTION_APPEND_CLAUSE]) ?
				$this->statementBuilder->append($statement, $options[Model::OPTION_APPEND_CLAUSE]) : $statement;
		
		$properties = $this->setClauseProperties + $this->modelObj->get();
		$statement = $this->statementBuilder->translate($statement, $properties);
		$this->properties = $this->statementBuilder->translateProperties($properties);
		
		return $statement;
	}
}
?>