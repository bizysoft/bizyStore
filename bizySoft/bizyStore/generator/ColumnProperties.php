<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\model\core\SchemaI;

/**
 * Store column meta-data and generate code for a table's schema class file to support CRUD operations on databases.
 * 
 * Holds column meta-data directly associated with table column declarations. More than one database can have a Schema entry 
 * if the table name is shared. Shared table names can have different column declarations. 
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ColumnProperties extends SchemaProperties
{
	/**
	 * Only codify this column meta-data. You can comment/un-comment fields to toggle output to the 
	 * schema class files.
	 * 
	 * @var array
	 */
	private static $codifiedMetaData = array(
			SchemaI::DATA_TYPE
			,SchemaI::MAX_LENGTH
			//,SchemaI::ORDINAL_POSITION
			//,SchemaI::IS_NULLABLE
			//,SchemaI::COLUMN_DEFAULT
	);
	
	/**
	 * Generate the code for the column schema.
	 * 
	 * Key the meta-data on the column name and database id.
	 * 
	 * @see \bizySoft\bizyStore\generator\SchemaProperties::add()
	 */
	public function codify()
	{
		$allColumnProperties = array();
		
		/*
		 * Massage into a more useable form for code generation
		 */
		foreach($this->properties as $dbId => $columnSchema)
		{
			foreach($columnSchema as $columnProperties)
			{
				$columnName = $columnProperties[SchemaI::COLUMN_NAME];
		
				if(!isset($allColumnProperties[$dbId]))
				{
					$allColumnProperties[$dbId] = array();
				}
				if(!isset($allColumnProperties[$dbId][$columnName]))
				{
					$allColumnProperties[$dbId][$columnName] = array();
				}

				foreach (self::$codifiedMetaData as $codifiedMeta)
				{
					/*
					 * Only output specified meta-data for the columns we want to codify and only do it once.
					 * 
					 * eg. there is no reason why someone cannot include multiple unique key declarations in a single table 
					 * definition. If those unique keys overlap then this will cause the column definition to be repeated. 
					 * 
					 * Here we store the property meta-data once by overwriting the previous because it will be the same 
					 * for all repeats.
					 */
					$columnMeta = isset($columnProperties[$codifiedMeta]) ? $columnProperties[$codifiedMeta] : "null";
					// If the value is a PHP constant then drop the quotes (booleans, null etc...)
					$columnMeta = defined($columnMeta) ? $columnMeta : "\"$columnMeta\"";
					$allColumnProperties[$dbId][$columnName][$codifiedMeta] = $columnMeta;
				}
			}
		}
		
		return $this->stringify($allColumnProperties);
	}
	
	/**
	 * Stringify an array of column information.
	 * 
	 * @return string
	 */
	public function stringify($columnProperties)
	{
		/*
		 * Generate the column schema.
		 */
		$schemaClassFileContents = "";
		$dbArraySeparator = "\n";
		foreach ($columnProperties as $dbId => $columnInfo)
		{
			$schemaClassFileContents .= $dbArraySeparator . "\t\t\t\"$dbId\" => array(";
			$arraySeparator = "\n";
			foreach ($columnInfo as $columnName => $columnMeta)
			{
				$schemaClassFileContents .= $arraySeparator . "\t\t\t\t\"$columnName\" => array(";
				$comma = "";
				foreach ($columnMeta as $propertyKey => $propertyValue)
				{
					$schemaClassFileContents .= $comma . "\"$propertyKey\" => " . $propertyValue;
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
}
?>