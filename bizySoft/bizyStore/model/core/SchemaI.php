<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Methods that absolutley must be implemented for bizyStore base-class Model algorithms to work properly.
 *
 * Generally, this interface is implemented in the ModelSchema class, a Model instance of which has access to the Schema 
 * through these methods.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
interface SchemaI
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
	const KEY_TYPE = "keyType";
	const SEQUENCE_NAME = "sequenceName";
	const KEY_INDEX = "keyIndex";
	const KEY_NAME = "keyName";
	const REFERENCED_TABLE = "referencedTable";
	const REFERENCED_COLUMN = "referencedColumn";
	
	/**
	 * Get the ColumnSchema for the Model.
	 *
	 * ColumnSchema holds the column names and their associated meta-data. They will be a standard set of values for the 
	 * core functionality and may include some of the above key constants for each column.
	 *
	 * @return ColumnSchema The column schema for the Model.
	 * @see ModelI::getModelSchema()
	 */
	public function getColumnSchema();
	
	/**
	 * Get the SequenceSchema for the Model.
	 *
	 * Sequences in this context mean any column that is generated automatically by the database and possibly retrievable 
	 * by DB::getInsertId().
	 * 
	 * They are not necessarily primary key columns in all cases. There can be zero or more sequences defined for a table.
	 *
	 * @return SequenceSchema 
	 */
	public function getSequenceSchema();
		
	/**
	 * Get the table name for the Model.
	 * 
	 * A Schema class can have different table names. This is a consequence of tableName -> className mapping. 
	 * Class names are the table names with the first character in upper case. We need to preserve the case of table names
	 * for those databases that are case sensitive.
	 *
	 * @return string 
	 */
	public function getTableName();

	/**
	 * Get the PrimaryKeySchema for the Model.
	 * 
	 * There can be zero or one primary key declaration per table. The declaration may contain more than one column.
	 *
	 * @return PrimaryKeySchema 
	 */
	public function getPrimaryKeySchema();
	
	/**
	 * Gets the UniqueKeySchema for the Model.
	 * 
	 * Unique keys may contain more than one column. There can be zero or more unique keys defined for a table and they 
	 * may intersect each other.
	 *
	 * @return UniqueKeySchema 
	 */
	public function getUniqueKeySchema();
	
	/**
	 * Gets the ForeignKeySchema for the Model.
	 * 
	 * Foreign keys are columns that are references or part of a reference to a unique row in another table.
	 * Foreign keys may contain more than one column. There can be zero or more foreign keys defined for a table. 
	 *
	 * @return ForeignKeySchema
	 */
	public function getForeignKeySchema();
	
	/**
	 * Gets the ForeignKeyRefereeSchema for the Model.
	 * 
	 * ForeignKeyRefereeSchema holds the columns in a table that are referred to by foreign key declaration(s) in other tables.
	 * They are the other end of the foreign key relationship declaration.
	 * 
	 * There can be zero or more foreign key references to a table. Multiple foreign keys can reference the same column in the table.
	 * A particular foreign key can reference more than one column in the table.
	 *
	 * @return ForeignKeyRefereeSchema 
	 */
	public function getForeignKeyRefereeSchema();
	
	/**
	 * Gets the KeyCandidateSchema for the Model.
	 * 
	 * Keys candidates are the possible properties that can form a key in the following order of preference:
	 * 
	 * Primary Key sequenced
	 * Primary Key non-sequenced
	 * Unique Key sequenced
	 * Sequenced
	 * Unique Key non-sequenced
	 * 
	 * These scenarios may or may not be part of the schema.
	 *
	 * @return KeyCandidateSchema 
	 */
	public function getKeyCandidateSchema();
	
}
?>