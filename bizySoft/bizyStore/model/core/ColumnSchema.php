<?php
namespace bizySoft\bizyStore\model\core;

/**
 * ColumnSchema holds the name definitions and required meta-data for all columns in a database table 
 * based on the database id.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ColumnSchema extends Schema
{
	/**
	 * ColumnSchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $columnData
	 */
	public function __construct($columnData)
	{
		parent::__construct($columnData);
	}
		
	/**
	 * Is the property part of the column schema. In other words is it a column name.
	 * 
	 * @return bool
	 * 
	 * @param string $dbId
	 * @param string $property
	 */
	public function is($dbId, $property)
	{
		return isset($this->schema[$dbId]) ? isset($this->schema[$dbId][$property]) : false;
	}
}
?>
