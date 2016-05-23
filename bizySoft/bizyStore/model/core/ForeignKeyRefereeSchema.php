<?php
namespace bizySoft\bizyStore\model\core;

/**
 * ForeignKeyRefereeSchema holds the foreign key information from other tables that refer to this table 
 * based on database id.
 *
 * Provides implementations of abstract methods allowing realisation of foreign keys on Model objects.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ForeignKeyRefereeSchema extends RelationshipSchema
{
	/**
	 * ForeignKeyRefereeSchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $refereeData
	 */
	public function __construct($refereeData)
	{
		parent::__construct($refereeData);
	}
	
	/**
	 * Set the relationship into the Model's properties.
	 *
	 * $model is associated with one or more Model's through each foreign key referring to one of it's properties.
	 * More than one foreign key can refer to a particular property.
	 * Each foreign key referring to a property is on the one end of a one to many relationship, commonly called the parent.
	 * 
	 * @param Model $model
	 * @param array $property the Models to set keyed on the relationship name .
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
			/*
			 * realise() each Model found 
			 */
			foreach($modelsFound as $modelFound)
			{
				/*
				 * 'realise' the next level and decrement the hops until hops is 0 or no more Models to realise.
				 */
				$this->realiseChild($modelFound, $hops-1, $relName, $options);
			}
		}
		/*
		 * Set the relationship.
		 */
		$model->set(array($relName => $modelsFound));
	}
	
	/**
	 * Gets the appropriate column name for the relationship.
	 * 
	 * @return string.
	 */
	protected function getRelColumnName($localColumn, $foreignColumn)
	{
		return $foreignColumn;
	}
	
	/**
	 * Gets the appropriate table name for the relationship.
	 * 
	 * @return string.
	 */
	protected function getRelTableName($localTable, $foreignTable)
	{
		return $foreignTable;
	}
}
?>
