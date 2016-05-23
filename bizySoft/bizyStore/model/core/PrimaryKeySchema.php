<?php
namespace bizySoft\bizyStore\model\core;

/**
 * PrimaryKeySchema holds the primary key information for a table based on the database id.
 *
 * get() returns an associative array of indexName => array(columnName => value,..) that specify the primary key. The 
 * value is irrelvant in this context but is a sequence indicator (true/false).
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class PrimaryKeySchema extends Schema
{
	/**
	 * PrimaryKeySchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $columnData
	 */
	public function __construct($primaryKeyData)
	{
		parent::__construct($primaryKeyData);
	}
}
?>
