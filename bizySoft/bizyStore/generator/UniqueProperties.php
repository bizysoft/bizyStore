<?php
namespace bizySoft\bizyStore\generator;

/**
 * Store unique key information about a table based on the database id.
 * 
 * More than one database can have key entries for a table if the table name is shared. Unique keys can span more than one 
 * column in a table.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class UniqueProperties extends SchemaProperties
{
	/**
	 * Transform the key information based on the key's name and the column it relates to.
	 * 
	 * We also store a sequence indicator which may prove useful.
	 * 
	 * @return array()
	 * 
	 */
	private function transform()
	{
		$uniqueProperties = array();
		
		/*
		 * Massage into a more useable form for code generation
		 */
		foreach($this->properties as $dbId => $columnSchema)
		{
			foreach($columnSchema as $columnProperties)
			{
				$indexName = $columnProperties[self::KEY_NAME];
				$columnName = $columnProperties[self::COLUMN_NAME];
				$sequenced = $columnProperties[self::SEQUENCED] == "true";
				
				if(!isset($uniqueProperties[$dbId]))
				{
						$uniqueProperties[$dbId] = array();
				}
				if(!isset($uniqueProperties[$dbId][$indexName]))
				{
						$uniqueProperties[$dbId][$indexName] = array();
				}
				
				$uniqueProperties[$dbId][$indexName][$columnName] = $sequenced;
			}
		}
		
		return $uniqueProperties;
	}
	
	/**
	 * Transform and generate code.
	 * 
	 * @return string
	 */
	public function codify()
	{
		$uniqueProperties = $this->transform();
		
		return $this->stringify($uniqueProperties);
	}
	
	/**
	 * Generate the code to support unique key behaviour for a database table
	 * keyed on the dbId.
	 * 
	 * @return string
	 */
	public function stringify(array $uniqueProperties)
	{
		/*
		 * Generate the unique key info into the file contents string.
		 */
		$schemaClassFileContents = "";
		$dbArraySeparator = "\n";
		foreach ($uniqueProperties as $dbId => $tableKeyProperties)
		{
			$schemaClassFileContents .= $dbArraySeparator . "\t\t\t\"$dbId\" => array(";
			$arraySeparator = "\n";
			foreach($tableKeyProperties as $indexName => $columnKeyProperties)
			{
				$schemaClassFileContents .= $arraySeparator . "\t\t\t\t\"$indexName\" => array(";
				$comma = "";
				foreach ($columnKeyProperties as $columnName => $sequenced)
				{
					$sequenced = $sequenced ? "true" : "false";
					$schemaClassFileContents .= $comma . "\"$columnName\" => $sequenced";
				  $comma = ", ";
				}
				$schemaClassFileContents .= ")";
				$arraySeparator = ",\n";
			}
			$schemaClassFileContents .= "\n\t\t\t)";
			$dbArraySeparator = ",\n";
		}
		
		return $schemaClassFileContents;
	}
	
	/**
	 * Gets the unique keys in order of preference.
	 * 
	 * Sequenced keys are preferred over non-sequenced. A sequenced key is a key that has one or more sequenced columns.
	 * 
	 * @return array
	 */
	public function keyCandidates()
	{
		$sequencedKeys = array();
		$nonSequencedKeys = array();
		
		$uniqueProperties = $this->transform();
		
		foreach ($uniqueProperties as $dbId => $tableKeyProperties)
		{
			$sequencedKeys[$dbId] = array();
			$nonSequencedKeys[$dbId] = array();
			foreach($tableKeyProperties as $keyName => $columnKeyProperties)
			{
				$isKeySequenced = false;
				$tableKeyName = "";
				foreach ($columnKeyProperties as $columnName => $sequenced)
				{
					$tableKeyName .= "." . $columnName;
					if ($sequenced)
					{
						$isKeySequenced = true;
					}
				}
				
				if ($isKeySequenced)
				{
					/*
					 * $tableKeyName is made up of the column names and allows us to eliminate duplicates between
					 * primary/unique/sequenced keys for a $dbId.
					 */
					$tableKeyName = self::SEQUENCED . $tableKeyName;
					$sequencedKeys[$dbId][$tableKeyName] = $columnKeyProperties;
				}
				else
				{
					$tableKeyName = self::NON_SEQUENCED .  $tableKeyName;
					$nonSequencedKeys[$dbId][$tableKeyName] = $columnKeyProperties;
				}
			}
		}
		return array(self::SEQUENCED => $sequencedKeys, self::NON_SEQUENCED => $nonSequencedKeys);
	}
}
?>