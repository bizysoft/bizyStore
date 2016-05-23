<?php
namespace bizySoft\bizyStore\model\core;

/**
 * UniqueKeySchema holds the unique key information for a table based on database id.
 *
 * Unique keys are column(s) within a table that the database declares as being unique. There can be zero or more unique keys
 * declared for a table. Unique keys can be formed from more than one column.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class UniqueKeySchema extends Schema
{
	/**
	 * UniqueKeySchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $columnData
	 */
	public function __construct($primaryKeyData)
	{
		parent::__construct($primaryKeyData);
	}
}
?>
