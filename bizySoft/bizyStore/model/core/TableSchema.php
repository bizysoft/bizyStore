<?php
namespace bizySoft\bizyStore\model\core;

/**
 * TableSchema holds the table names for a Model based on the database id.
 *
 * Model class names are always the database table name with the first letter upper-cased. The TableSchema stores the 
 * names of the table as they are declared in the particular database.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class TableSchema extends Schema
{
	/**
	 * TableSchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $columnData
	 */
	public function __construct($tableData)
	{
		parent::__construct($tableData);
	}
		
	/**
	 * Is the tableName valid for this dbId.
	 * 
	 * @return bool
	 * 
	 * @param string $dbId
	 * @param string $tableName
	 */
	public function is($dbId, $tableName)
	{
		return isset($this->schema[$dbId]) ? $this->schema[$dbId] === $tableName : false;
	}
}
?>
