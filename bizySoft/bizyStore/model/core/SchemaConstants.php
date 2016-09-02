<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Constants for Schema generation and retrieval.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface SchemaConstants
{
	const PRIMARY_KEY = "PRIMARY KEY";
	const UNIQUE = "UNIQUE";
	const FOREIGN_KEY = "FOREIGN KEY";
	
	/*
	 * Key constants for each column.
	 */
	const TABLE_NAME = "tableName";
	const COLUMN_NAME = "columnName";
	const ORDINAL_POSITION = "ordinalPosition";
	const COLUMN_DEFAULT = "columnDefault";
	const IS_NULLABLE = "isNullable";
	const DATA_TYPE = "dataType";
	const MAX_LENGTH = "maxLength";
	const SEQUENCED = "sequenced";
	const NON_SEQUENCED = "non_sequenced";
	const KEY_TYPE = "keyType";
	const SEQUENCE_NAME = "sequenceName";
	const KEY_INDEX = "keyIndex";
	const KEY_NAME = "keyName";
	const REFERENCED_TABLE = "referencedTable";
	const REFERENCED_COLUMN = "referencedColumn";
}
?>