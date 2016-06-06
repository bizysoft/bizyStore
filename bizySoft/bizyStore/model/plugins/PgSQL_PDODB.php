<?php
namespace bizySoft\bizyStore\model\plugins;

use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\model\statements\QueryPreparedStatement;
use bizySoft\bizyStore\services\core\BizyStoreOptions;

/**
 * Concrete PDODB class for PostgreSQL.
 *
 * Provides interface and specialised methods for bizyStore integration of PostgreSQL.
 * 
 * PostgreSQL requires specialised handling of database entity names to preserve case.
 * 
 * If you have a table named "Member" the PostgreSQL server will force lower case on this entity in queries so it 
 * becomes "member".
 * 
 * eg. 
 * <code>
 * select * from Member;
 * -- the server sees 
 * select * from member;
 * </code>
 * 
 * and the result is a failure because "member" does not exist. 
 * 
 * The same goes for column names and any other database entities of mixed or upper case. The solution is to double 
 * "quote" the entity name.
 * <code>
 * select * from "Member";
 * -- works
 * </code>
 * 
 * See formatEntity()/qualifyEntity();
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class PgSQL_PDODB extends PDODB
{
	/**
	 * Constant query for getCurrentSequence().
	 * 
	 * @var string
	 */
	const GET_CURRENT_SEQUENCE_QUERY = "select currval(:sequenceName)";
		
	/**
	 * Constant query for getDateTime().
	 * 
	 * @var string
	 */
	const PG_GET_DATE_TIME_QUERY = "select current_timestamp::timestamp(0)";
	
	/**
	 * Constant query for getSchema().
	 * 
	 * @var string
	 */
	const PG_GET_SCHEMA_QUERY = "
		SELECT
			isc.table_name AS \"tableName\",
			isc.column_name AS \"columnName\",
			isc.ordinal_position AS \"ordinalPosition\",
			isc.column_default AS \"columnDefault\",
			isc.is_nullable AS \"isNullable\",
			isc.data_type AS \"dataType\",
			isc.character_maximum_length AS \"maxLength\",
			CASE WHEN iss.sequence_name IS NULL THEN 'false' ELSE 'true' END AS sequenced,
			istc.constraint_type AS \"keyType\",
			iss.sequence_name AS \"sequenceName\",
			isku.ordinal_position-1 AS \"keyIndex\",
			isku.constraint_name AS \"keyName\",
			fk.parent_table AS \"referencedTable\",
			fk.parent_column AS \"referencedColumn\"
		FROM information_schema.columns isc
			LEFT JOIN information_schema.sequences AS iss ON
				(iss.sequence_schema = isc.table_schema
				AND isc.column_default LIKE '%'||iss.sequence_name||'%')
			LEFT JOIN information_schema.key_column_usage AS isku ON
				(isku.table_schema = isc.table_schema
				AND isku.table_name = isc.table_name
				AND isku.column_name = isc.column_name)
			LEFT JOIN information_schema.table_constraints AS istc ON
				(istc.table_schema = isku.table_schema
				AND istc.table_name = isku.table_name
				AND istc.constraint_name = isku.constraint_name)
			LEFT JOIN information_schema.referential_constraints AS isrc ON
				(isrc.constraint_schema = istc.table_schema
				AND isrc.constraint_name = istc.constraint_name)
			LEFT JOIN
			(
				SELECT 
					fk_constraint.conname AS constraint_name,
					table_parent.relname AS parent_table, 
					att_parent.attname AS parent_column,
					att_child.attname AS child_column
				FROM
					(SELECT 
						pgcon.conname,
						unnest(pgcon.conkey) AS parent, 
						unnest(pgcon.confkey) AS child, 
						pgcon.confrelid, 
						pgcon.conrelid
					FROM 
						pg_class pgcl
						JOIN pg_namespace pgns ON pgcl.relnamespace = pgns.oid
						JOIN pg_constraint pgcon ON pgcon.conrelid = pgcl.oid
					WHERE
						pgcl.relname = :tableName
						AND pgns.nspname = :schemaName
						AND pgcon.contype = 'f'
					) fk_constraint
				JOIN pg_class table_parent ON
					(table_parent.oid = fk_constraint.confrelid)
				JOIN pg_attribute att_parent ON
					(att_parent.attrelid = fk_constraint.confrelid 
					AND att_parent.attnum = fk_constraint.child)
				JOIN pg_attribute att_child ON
					(att_child.attrelid = fk_constraint.conrelid 
					AND att_child.attnum = fk_constraint.parent)
			) fk ON (fk.child_column = isc.column_name
					AND fk.constraint_name = isrc.constraint_name)
		WHERE
			isc.table_schema = :schemaName
			AND isc.table_name = :tableName
		ORDER BY \"ordinalPosition\"";
	
	/**
	 * Pass the database parameters in an associative array and use them to construct the interface.
	 *
	 * @param array $dbConfig <p>an associative array containing the
	 *        database config information supplied in bizySoftConfig.</p>
	 */
	public function __construct($dbConfig)
	{
		/*
		 * Default the schema
		 */
		if (!isset($dbConfig[BizyStoreOptions::DB_SCHEMA_TAG]))
		{
			$dbConfig[BizyStoreOptions::DB_SCHEMA_TAG] = "public";
		}
		
		parent::__construct($dbConfig);
	}

	/**
	 * Gets statement that is required to set the transaction isolation level on the database connection (session).
	 * 
	 * @param string $isolationLevel the vendor's isolation level as per the isolationMap.
	 * @return string the isolation level statement ready to be executed.
	 */
	public function getVendorIsolationLevelStatement($isolationLevel)
	{
		return "set session characteristics as transaction isolation level " . $isolationLevel;
	}
	
	/**
	 * PostgreSQL has a slightly different way of getting the current date/time.
	 * 
	 * @return string The database time in YYYY-MM-DD HH24:MI:SS format.
	 * @throws ModelException if there is a database error.
	 */
	public function getDateTime()
	{
		/*
		 * Specifying a key will turn the prepared statement cache on for this statement, subsequent calls may be faster 
		 * because prepare() is not required.
		 */
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_DATE_TIME_KEY);
		/*
		 * This gives a result in the form YYYY-MM-DD HH24:MI:SS
		 */
		$stmt = new QueryPreparedStatement($this, self::PG_GET_DATE_TIME_QUERY, array(), $options); 
		
		return $stmt->scalar();
	}

	/**
	 * Gets the table names from the database information_schema.
	 * 
	 * Used in generation of Model and Schema classes if no table name's reside in bizySoftConfig.
	 * 
	 * @return array a zero based array of table names.
	 */

	public function getDBTableNames()
	{
		$properties = array("schemaName" => $this->getSchemaName());
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_DB_TABLE_NAMES_KEY);
		
		$statement = new QueryPreparedStatement($this, self::DEFAULT_DB_TABLE_NAMES_QUERY, $properties, $options);
		
		return $statement->assocSet();
	}
	
	/**
	 * Get the schema info for a table as an array. 
	 *
	 * Used for generation of Model and Schema classes by ModelGenerator. The bizyStore schema describes the database 
	 * column meta-data for each database table. This can include primary, unique, sequence and foreign key information 
	 * as well as normal column schema meta-data.
	 * 
	 * Called once per table definition.
	 * 
	 * PostgreSQL has deficiencies in the information_schema relating to foreign keys that have multiple columns.
	 * Thanks to StackOverflow user: martin for the nice system catalog solution above, tweaked for use in a LEFT JOIN.
	 *
	 * @return array an array of associative arrays of column meta-data.
	 */
	public function getSchema($tableName)
	{
		$properties = array("tableName" => $tableName, "schemaName" => $this->getSchemaName());
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_SCHEMA_KEY);
		
		$statement = new QueryPreparedStatement($this, self::PG_GET_SCHEMA_QUERY, $properties, $options);
		
		return $statement->assocSet();
	}

	/**
	 * Here we qualify the sequence name with the schema.
	 * 
	 * @return string
	 */
	public function getInsertId($name = null)
	{
		/*
		 * Qualify with schema
		 */
		$sequenceName = $this->qualifyEntity($name);
		return parent::getInsertId($sequenceName);
	}
	
	/**
	 * Get the current value of the named sequence. 
	 * 
	 * This is referenced in Model::getSchemaSequences() but is possibly never called for PostgreSQL because 
	 * DB::getInsertId($sequenceName) will usually handle it. 
	 * 
	 * In any case, this method is useful if you want the current value of a PostgreSQL sequence not 
	 * necessarily associated with a Model object.
	 *
	 * @param string $sequenceName
	 * @return string
	 */
	public function getCurrentSequence($sequenceName)
	{
		/*
		 * Qualify with schema
		 */
		$name = $this->qualifyEntity($sequenceName);
		
		$properties = array(
				"sequenceName" => $name
		);
		/*
		 * Specifying a key will turn the prepared statement cache on for this statement, subsequent calls may be faster 
		 * because prepare() is not required.
		 */
		$options = array(QueryPreparedStatement::OPTION_PREPARE_KEY => self::GET_CURRENT_SEQUENCE_KEY);
		
		$statement = new QueryPreparedStatement($this, self::GET_CURRENT_SEQUENCE_QUERY, $properties, $options);
		
		return $statement->scalar();
	}

	/**
	 * Override formatting to handle mixed case for PostgreSQL.
	 *
	 * PostgreSQL forces lower case on query entities unless they are "double quoted".
	 * 
	 * This can play havoc with your query if you use upper or mixed case entity names in your database 
	 * so we "double quote" accordingly.
	 * 
	 * @param string $entityName
	 * @return string the $entityName formatted as required for PostgreSQL database statements.
	 */
	public function formatEntity($entityName)
	{
		return "\"$entityName\"";
	}

}
?>