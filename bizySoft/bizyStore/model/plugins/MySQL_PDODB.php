<?php
namespace bizySoft\bizyStore\model\plugins;

use \PDO;
use bizySoft\bizyStore\services\core\Config;
use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;

/**
 * Concrete PDODB class for MySql.
 *
 * Implements the remaining interface methods specifically for MySQL.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class MySQL_PDODB extends PDODB
{
	const GET_SCHEMA_QUERY = "SELECT
        isc.table_name AS tableName,
        isc.column_name AS columnName,
        isc.ordinal_position AS ordinalPosition,
        isc.column_default AS columnDefault,
        IF(isc.is_nullable = 'NO', 'false', 'true') AS isNullable,
        isc.data_type AS dataType,
        isc.character_maximum_length AS maxLength,
        IF(isc.extra = 'auto_increment', 'true', 'false') AS sequenced,
        istc.constraint_type AS keyType,
        NULL AS sequenceName,
        isku.ordinal_position-1 AS keyIndex,
        isku.constraint_name AS keyName,
		iskufk.referenced_table_name AS referencedTable,
		iskufk.referenced_column_name AS referencedColumn
		FROM information_schema.columns isc
		LEFT JOIN information_schema.key_column_usage AS isku ON
			(isku.table_schema = isc.table_schema
			AND isku.table_name = isc.table_name
			AND isku.column_name = isc.column_name)
		LEFT JOIN information_schema.table_constraints AS istc ON
			(istc.table_schema = isku.table_schema
			AND istc.table_name = isku.table_name
			AND istc.constraint_name = isku.constraint_name)
		LEFT JOIN information_schema.referential_constraints AS isrc ON
			(isrc.constraint_schema = isku.constraint_schema
			AND isrc.constraint_name = isku.constraint_name)
		LEFT JOIN information_schema.key_column_usage AS iskufk ON
			(iskufk.constraint_schema = isrc.constraint_schema 
			AND iskufk.constraint_name = isrc.constraint_name
			AND iskufk.column_name = isc.column_name)
        WHERE isc.table_name = :tableName 
        AND isc.table_schema = :schemaName
        ORDER BY ordinalPosition";
	
	/**
	 * Just pass the database parameters and config.
	 *
	 * @param PDO $db
	 * @param string $dbId
	 * @param Config $config
	 */
	public function __construct(PDO $db, $dbId, Config $config)
	{
		parent::__construct($db, $dbId, $config);
	}
		
	/**
	 * Gets the table names from the database information_schema.
	 *
	 * Used in generation of Model and ModelSchema files if no table name's reside in bizySoftConfig.
	 * 
	 * @return array a zero based array of table names.
	 */
	public function getDBTableNames()
	{
		$properties = array("schemaName" => $this->getName());
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_DB_TABLE_NAMES_KEY);
		
		$statement = new QueryPreparedStatement($this, self::DEFAULT_DB_TABLE_NAMES_QUERY, $properties, $options);
		
		return $statement->assocSet();
	}
	
	/**
	 * Get the schema info as an array describing the database column and key attributes for each database table.
	 * Used for generation of Model and Schema classes.
	 * 
	 * At the time of writing, MySQL does not support multiple schemas per database. The schema fields in the information_schema tables
	 * refer to the database name.
	 * 
	 * @see DBI::getSchema() for more information.
	 * @return array a zero based array of associative arrays of the above.
	 */
	public function getSchema($tableName)
	{
		$properties = array("tableName" => $tableName, "schemaName" => $this->getName());
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_SCHEMA_KEY);
				
		$statement = new QueryPreparedStatement($this, self::GET_SCHEMA_QUERY, $properties, $options);
		
		return $statement->assocSet();
	}
}
?>