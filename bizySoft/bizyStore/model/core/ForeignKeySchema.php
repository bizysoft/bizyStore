<?php
namespace bizySoft\bizyStore\model\core;

/**
 * ForeignKeySchema holds the information on foreign key columns based on database id.
 * 
 * Foreign keys are declared via your database schema when you create a table with a foreign key constraint.
 * Foreign keys can also be declared via the bizySoftConfig file for databases that either don't support or
 * have no foreign key declarations specified in a static database schema.
 *
 * Provides implementations of abstract methods allowing realisation of foreign keys on Model objects.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ForeignKeySchema extends RelationshipSchema
{
	/**
	 * ForeignKeySchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $foreignKeyData
	 */
	public function __construct($foreignKeyData)
	{
		parent::__construct($foreignKeyData);
	}

	
	/**
	 * Set the relationship into the Model's properties.
	 * 
	 * $model is associated with exactly one Model through each foreign key property that it declares.
	 * Each foreign key property is on the many end of a one to many relationship, commonly called the child.
	 * 
	 * @param Model $model
	 * @param array $property the Model to set keyed on the relationship name.
	 * @param int $hops
	 * @param array $options
	 */
	protected function setRelationship(Model $model, array $property, $hops, $options = array())
	{
		/*
		 * Only ever one property here. We use foreach to retreive the property name and the Model array.
		 */
		foreach ($property as $relName => $modelsFound)
		{
			if (count($modelsFound) > 0)
			{
				$modelFound = reset($modelsFound);
				/*
				 * 'realise' the next level and decrement the hops until hops is 0 or no more Models to realise.
				 */
				$this->realiseChild($modelFound, $hops-1, $relName, $options);
				/*
				 * Set the relationship in the Model
				 */
				$model->set(array($relName => $modelFound));
			}
		}
	}
	
	/**
	 * Gets the appropriate column name for the relationship.
	 *
	 * @return string
	 */
	protected function getRelColumnName($localColumn, $foreignColumn)
	{
		return $localColumn;
	}
	
	/**
	 * Gets the appropriate table name for the relationship.
	 *
	 * @return string
	 */
	protected function getRelTableName($localTable, $foreignTable)
	{
		return $localTable;
	}
}
?>
