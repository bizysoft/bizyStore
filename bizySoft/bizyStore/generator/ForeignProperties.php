<?php
namespace bizySoft\bizyStore\generator;

/**
 * Store foreign key information about a table based on a database id. 
 * 
 * ForeignProperties holds information on the child end of a relationship between two tables and is where the 
 * foreign key is declared.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ForeignProperties extends SchemaProperties
{
	/**
	 * Generate the code to support foreign key columns for a database table keyed on the dbId.
	 * 
	 * @see \bizySoft\bizyStore\generator\SchemaProperties::codify()
	 */
	public function codify()
	{
		$foreignKeyProperties = array();
		/*
		 * Massage into a more useable form for code generation
		 */
		foreach($this->properties as $dbId => $columnSchema)
		{
			foreach($columnSchema as $columnProperties)
			{
				$columnName = $columnProperties[self::COLUMN_NAME];
				/*
				 * Some databases use a true index name others just a numeric index.
				 */
				$indexName = $columnProperties[self::KEY_NAME];
				$referencedTable = $columnProperties[self::REFERENCED_TABLE];
				$referencedColumn = $columnProperties[self::REFERENCED_COLUMN];
				
				if(!isset($foreignKeyProperties[$dbId]))
				{
					$foreignKeyProperties[$dbId] = array();
				}
				if(!isset($foreignKeyProperties[$dbId][$indexName]))
				{
					$foreignKeyProperties[$dbId][$indexName] = array();
				}
	
				$foreignKeyProperties[$dbId][$indexName][$columnName] = array($referencedTable => $referencedColumn);
			}
		}
		
		return $this->stringify($foreignKeyProperties);
	}
		
	/**
	 * Stringify an array of foreign key information.
	 * 
	 * @param array $foreignKeyProperties
	 * @return string
	 */
	public function stringify(array $foreignKeyProperties)
	{
		/*
		 * Generate the foreign key info into the file contents string.
		 */
		$schemaClassFileContents = "";
		$dbArraySeparator = "\n";
		foreach ($foreignKeyProperties as $dbId => $fkProperties)
		{
			$schemaClassFileContents .= $dbArraySeparator . "\t\t\t\"$dbId\" => array(";
			$arraySeparator = "\n";
			foreach($fkProperties as $indexName => $columnKeyProperties)
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
}
?>