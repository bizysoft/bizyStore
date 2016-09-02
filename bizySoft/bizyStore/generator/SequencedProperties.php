<?php
namespace bizySoft\bizyStore\generator;

/**
 * Store sequence information about a table based on a database id. 
 * 
 * 'sequenced' columns are a general term here, they are identity fields that the database automatically 
 * allocates when you insert a table row. Usually they occur with a primary key declaration but not 
 * necessarily. See ModelI::getSchemaSequences().
 * 
 * Most databases don't support more than one sequenced column per table (MySQL, SQLite). Other databases do and generate
 * sequences from a sequence object which will have a name (PostgreSQL). We store the sequenceName with the sequenced column for 
 * this case.
 *
 * More than one database can have sequenced entries for a table if the table name is shared.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class SequencedProperties extends SchemaProperties
{
	/**
	 * Transform the sequenced data into the required form for code generation.
	 * 
	 * @return array
	 */
	private function transform()
	{
		$sequencedProperties = array();
		/*
		 * Massage into a more useable form for code generation
		 */
		foreach($this->properties as $dbId => $columnSchema)
		{
			foreach($columnSchema as $columnProperties)
			{
				$columnName = $columnProperties[self::COLUMN_NAME];
				$sequenceName = $columnProperties[self::SEQUENCE_NAME];
		
				if(!isset($sequencedProperties[$dbId]))
				{
					$sequencedProperties[$dbId] = array();
				}
		
				$sequencedProperties[$dbId][$columnName] =  $sequenceName;
			}
		}
		
		return $sequencedProperties;
	}
	
	/**
	 * Add the required sequenceName keyed on columName. The sequence name can be null.
	 * 
	 * @return string
	 */
	public function codify()
	{
		$sequencedProperties = $this->transform();
		
		return $this->stringify($sequencedProperties);
	}
	
	/**
	 * Generate the code to support sequenced columns for a database table keyed on the dbId.
	 * 
	 * @return string
	 */
	public function stringify(array $sequencedProperties)
	{
		/*
		 * Generate the sequence info into the file contents string.
		 */
		$schemaClassFileContents = "";
		$dbArraySeparator = "\n\t\t\t";
		foreach ($sequencedProperties as $dbId => $sequence)
		{
			$comma = "";
			$schemaClassFileContents .= $dbArraySeparator . "\"$dbId\" => array(";
			foreach ($sequence as $columnName => $sequenceName)
			{
				$sequenceName = $sequenceName ? "\"$sequenceName\"" : "null";
				$schemaClassFileContents .= $comma . "\"$columnName\" => $sequenceName";
				$comma = ", ";
			}
			$schemaClassFileContents .= ")";
			$dbArraySeparator = ",\n\t\t\t";
		}
		
		return $schemaClassFileContents;
	}
	
	/**
	 * Gets the sequenced key candidates for a database table keyed on the dbId.
	 * 
	 * @return array
	 */
	public function keyCandidates()
	{
		$keyCandidates = array();
		
		$sequencedProperties = $this->transform();
		
		foreach ($sequencedProperties as $dbId => $sequence)
		{
			$keyCandidates[$dbId] = array();
			foreach ($sequence as $columnName => $sequenceName)
			{
				/*
				 * This key allows us to eliminate duplicates between primary/unique/sequenced keys for a $dbId.
				 */
				$keyCandidates[$dbId][self::SEQUENCED . ".$columnName"] = array();
				/*
				 * The sequence indicator is always true here.
				 */
				$keyCandidates[$dbId][self::SEQUENCED . ".$columnName"][$columnName] = true;
			}
		}
		return array(self::SEQUENCED => $keyCandidates);
	}
}
?>