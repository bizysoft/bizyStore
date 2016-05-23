<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\model\core\SchemaI;

/**
 * This class is used for storing foreign key references, keyed on a database id.
 * 
 * For a particular database/table, the referenced property is the parent end of a foreign key declaration on 
 * another table.
 * 
 * ReferencedProperties holds information for every ForeignProperties in the schema for all databases.
 * The add() method takes a schema row from ForeignProperties which is produced by the ModelGenerator.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ReferencedProperties extends SchemaProperties
{
	/**
	 * Generate the code to support referenced foreign key columns for a single-table instance keyed on the dbId.
	 * 
	 * Only used for single-table instances.
	 */
	public function codify()
	{
		$referencedProperties = $this->transform();
		
		return $this->stringify($referencedProperties);
	}
	
	/**
	 * Stringify the referenced foreign key information as code.
	 * 
	 * @param array $referencedProperties
	 */
	public function stringify($referencedProperties)
	{
		/*
		 * Generate the foreign key info into the file contents string.
		 */
		$schemaClassFileContents = "";
		$dbArraySeparator = "\n";
		foreach ($referencedProperties as $dbId => $foreignKeyProperties)
		{
			$schemaClassFileContents .= $dbArraySeparator . "\t\t\t\"$dbId\" => array(";
			$arraySeparator = "\n";
			foreach($foreignKeyProperties as $indexName => $columnKeyProperties)
			{
				$schemaClassFileContents .= $arraySeparator . "\t\t\t\t\"$indexName\" => array(";
				$comma = "";
				foreach ($columnKeyProperties as $columnName => $referenced)
				{
					list($referencedTable, $referencedColumn) = each($referenced);
					$schemaClassFileContents .= $comma . "\n\t\t\t\t\t\"$columnName\" => array(\"$referencedTable\" => \"$referencedColumn\")";
				  $comma = ", ";
				}
				$schemaClassFileContents .= "\n\t\t\t\t)";
				$arraySeparator = ",\n";
			}
			$schemaClassFileContents .= "\n\t\t\t)";
			$dbArraySeparator = ",\n";
		}
		
		return $schemaClassFileContents;
	}
	/**
	 * Transform the schema column data into the required form for code generation.
	 */
	public function transform()
	{
		$referencedProperties = array();

		foreach($this->properties as $dbId => $columnSchema)
		{
			foreach($columnSchema as $columnProperties)
			{
				$referringTableName = $columnProperties[SchemaI::TABLE_NAME];
				$referringColumnName = $columnProperties[SchemaI::COLUMN_NAME];
				$columnName = $columnProperties[SchemaI::REFERENCED_COLUMN];
				/*
				 * Some databases use a true index name others just a numeric index.
				 */
				$indexName = $columnProperties[SchemaI::KEY_NAME];
				
				if(!isset($referencedProperties[$dbId]))
				{
					$referencedProperties[$dbId] = array();
				}
		
				if(!isset($referencedProperties[$dbId][$indexName]))
				{
					$referencedProperties[$dbId][$indexName] = array();
				}
		
				$referencedProperties[$dbId][$indexName][$columnName] = array($referringTableName => $referringColumnName);
			}
		}
		
		return $referencedProperties;
	}
	
	/**
	 * Gets the referenced properties based on the $dbId and $requiredTableName.
	 * 
	 * This method will get single-table information from the multi-table instance so as to assist in producing 
	 * a single-table instance for code generation.
	 * 
	 * @param string $dbId
	 * @param string $requiredTableName
	 */
	public function getReferencedProperties($dbId, $requiredTableName)
	{
		$referencedProperties = array();
		
		$columnSchema = isset($this->properties[$dbId]) ? $this->properties[$dbId] : array();
		foreach($columnSchema as $columnProperties)
		{
			$tableName = $columnProperties[SchemaI::REFERENCED_TABLE];
			if ($tableName === $requiredTableName)
			{
				if(!isset($referencedProperties[$dbId]))
				{
					$referencedProperties[$dbId] = array();
				}
				
				$referencedProperties[$dbId][] = $columnProperties;
			}
		}
		
		return $referencedProperties;
	}
}
?>