<?php
namespace bizySoft\bizyStore\model\core;

/**
 * RelationshipSchema provides a means of realising relationships from a Model.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class RelationshipSchema extends Schema
{
	/**
	 * RelationshipSchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $foreignKeyData
	 */
	public function __construct($foreignKeyData)
	{
		parent::__construct($foreignKeyData);
	}

	/**
	 * Sets the relationship into the Model's properties.
	 * 
	 * The property name for a particular relationship is the table name of the declared foreign key and the 
	 * columns of the declared foreign key all concatenated with a '.'. This is true when navigating from either 
	 * end of the relationship.
	 * 
	 * @param Model $model the Model being realised.
	 * @param array $property the Model(s) to set keyed on the relationship name .
	 * @param int $hops the number of hops from the Model to realise.
	 */
	protected abstract function setRelationship(Model $model, array $property, $hops);
	
	/**
	 * Gets the column name for this relationship.
	 *
	 * @param string $localColumn
	 * @param string $foreignColumn
	 * @return string
	 */
	protected abstract function getRelColumnName($localColumn, $foreignColumn);
	
	/**
	 * Gets the table name for this relationship.
	 *
	 * @param string $localTable
	 * @param string $foreignTable
	 * @return string
	 */
	protected abstract function getRelTableName($localTable, $foreignTable);
	
	/**
	 * Determine if this relationship is redundant.
	 * 
	 * The relationship being navigated from the originating end is not realised by the Model at the terminating 
	 * end (ie. is redundant). The originating Model contains the relationship and therefore the terminating Model(s)
	 * there is no need for the terminating Model(s) to contain the originating Model.
	 *
	 * @param string $originating name of the originating relationship
	 * @param string $terminating name of the terminating relationship
	 * @return boolean
	 */
	protected function isRedundant($originating, $terminating)
	{
		return ($originating != null) && ($originating == $terminating);
	}
	
	/**
	 * Realise the relationships on a Model.
	 * 
	 * @param Model $model the Model object whose relationships we want to realise.
	 * @param int $hops the number of relationship hops from the Model to realise.
	 * @param string $currentRel the current relationship being traversed which is always redundant at this end.
	 * @param array $options valid options are Model::OPTION_INDEX_KEY
	 */
	public function realise(Model $model, $hops, $currentRel = null, $options = array())
	{
		$schema = $this->get($model->getDBId());
		/*
		 * There may be many relationships to realise for a Model
		 */
		foreach ($schema as $indexName => $foreignKey)
		{
			$foreignProperties = array();
			$hasFullKey = true;
			/*
			 * The $foreignKey can consist of more than one local column.
			 * The $foreignTable will always be the same for a particular foreignKey.
			 * 
			 * Here we build up the foreign key with the data in each local column.
			 * 
			 * We have to check for a full key as there is no guarantee that this method was not called 
			 * directly by Model::realiseChild() on a Model that is not from the database.
			 * i.e. has incomplete foreign key properties.
			 */
			$name = "";
			$foreignTable = null;
			foreach ($foreignKey as $localColumn => $foreignInfo)
			{
				/*
				 * A $localColumn will only ever refer to a single $foreignTable/$foreignColumn combination 
				 * within a foreign key. So there will be one entry in each $foreignInfo, we use foreach just 
				 * to resolve the $foreignTable/$foreignColumn.
				 */
				foreach ($foreignInfo as $foreignTable => $foreignColumn)
				{
					/*
					 * Get the value of the column from the Model to use as part of the key.
					 */
					$foreignValue = $model->getValue($localColumn);
					/*
					 * Foreign key columns must have a non-null value.
					 */
					if ($foreignValue)
					{
						/*
						 * Build the properties to realise the relationship.
						 */
						$columnName = $this->getRelColumnName($localColumn, $foreignColumn);
						$foreignProperties[$foreignColumn] = $foreignValue;
						/*
						 * Build up the relationship name from the column names.
						 */
						$name .= $name ? ".$columnName" : $columnName;
					}
					else
					{
						/*
						 * Not enough properties to realise the relationship, so indicate that we cannot continue.
						 */
						$hasFullKey = false;
						break;
					}
				}
				if (!$hasFullKey)
				{
					/*
					 * Don't continue if we can't fill the key.
					 */
					break;
				}
			}
			
			if ($hasFullKey)
			{
				$localTable = $model->getTableName();
				/*
				 * The relationship name is always made up of the name of the table declaring the foreign key, and the columns that 
				 * form the key in declared order. All are concatenated with a "."
				 * 
				 * This is true for both ForeignKeySchema's and ForeignKeyRefereeSchema's and is used as the relationship's property name.
				 */
				$relName = $this->getRelTableName($localTable, $foreignTable) . ".$name";
				/*
				 * Here we exclude the redundant end of the relationship because the Model will hold this information.
				 */
				if (!$this->isRedundant($currentRel, $relName))
				{
					$db = $model->getDB();
					$config = $db->getConfig();
					$modelNamespace = $config->getModelNamespace();
					$foreignClass = "$modelNamespace\\" . ucfirst($foreignTable);
						
					$nextModel = new $foreignClass($foreignProperties, $db);
					$result = $nextModel->find($options);
					
					if ($result)
					{
						/*
						 * Set the Model with the new property that describes the relationship.
						 */
						$this->setRelationship($model, array($relName => $result), $hops, $options);
					}
				}
			}
		}
	}
	
	/**
	 * Populates the Model with its foreign key relationships.
	 *
	 * This method should only be called via the realise() method to ensure that the Model is actually from the database.
	 *
	 * This method is called within RelationshipSchema::realise() to recurse the child Model relationships.
	 *
	 * @param int $depth the number of relationship hops from this Model to realise.
	 * @param string $current the current relationship which is being traveresd and is always redundant at the other end.
	 * @param array $options valid options are OPTION_INDEX_KEY.
	 */
	public function realiseChild(Model $model, $hops, $current = null, array $options = array())
	{
		if ($hops > 0)
		{
			$foreighKeyRefereeSchema = $model->getForeignKeyRefereeSchema();
			$foreighKeyRefereeSchema->realise($model, $hops, $current, $options);
	
			$foreignKeySchema = $model->getForeignKeySchema();
			$foreignKeySchema->realise($model, $hops, $current, $options);
		}
	}
	
}
?>
