<?php
namespace bizySoft\bizyStore\model\plugins;

use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\core\PDODB;
use bizySoft\bizyStore\model\core\SchemaI;
use bizySoft\bizyStore\model\statements\QueryStatement;

/**
 * Concrete PDODB class for SQLite.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class SQLite_PDODB extends PDODB
{
	const INTEGER_PRIMARY_KEY_INDEX = "integer_primary_key";

	/**
	 * Pass the database parameters in an associative array and use them to construct the connection.
	 *
	 * @param array $dbConfig an associative array containing the
	 *        database config information supplied in bizySoftConfig.
	 */
	public function __construct($dbConfig)
	{
		parent::__construct($dbConfig);
	}

	/**
	 * SQLite's isolation level is always SERIALIZABLE in PHP, so this is a NO-OP for SQLite. 
	 */
	protected function setIsolationLevelMap()
	{}
	
	/**
	 * SQLite's isolation level is always SERIALIZABLE in PHP, so this is a NO-OP for SQLite. 
	 */
	public function setIsolationLevel($isolationLevel)
	{}
	
	/**
	 * Gets the table names from the database.
	 *
	 * Used in generation of Model and Schema files if no table name's reside in bizySoftConfig.
	 *
	 * @return array a zero based array of table names.
	 */
	public function getDBTableNames()
	{
		$query = "SELECT tbl_name FROM sqlite_master
        WHERE (type = 'table' OR type = 'view')
        AND tbl_name NOT LIKE 'sqlite_%'";
					
		$statement = new QueryStatement($this, $query);
	
		return $statement->assocSet();
	}
	
	/**
	 * Get the schema info for a table as an array describing the column and key attributes.
	 * 
	 * Used for generation of Model and Schema classes.
	 *
	 * SQLite does not provide standard access to schema entities through information_schema views. We must use methods
	 * specific to SQLite . Needless to say, there is a lot of work to do here to mimick the results of an 
	 * information_schema query and massage the data into our standard form.
	 *
	 * @return array a zero based array of associative arrays of the above.
	 * @param string $tableName
	 * @see \bizySoft\bizyStore\model\core\DBI::getSchema()
	 */
	public function getSchema($tableName)
	{
		$databaseSchema = array();
		
		/*
		 * Get some table info
		 */
		$query = "SELECT * FROM sqlite_master
		WHERE (type = 'table' OR type = 'view')
		AND tbl_name = '" . $tableName . "'";
		
		$query = new QueryStatement($this, $query);
		/*
		 * Query produces: type|name|tbl_name|rootpage|sql
		*/
		$tableInfo = $query->assocSet();
		
		$tableSQL = $tableInfo[0]["sql"];
		
		$primaryKeys = array();
		$query = new QueryStatement($this, "pragma table_info(" . $tableName . ")");
		/*
		 * Query produces: cid|name|type|notnull|dflt_value|pk
		 */
		$tableSchema = $query->assocSet();
		
		/*
		 * Pre-process the key information
		 */
		$keys = $this->getKeys($tableName, $tableSchema, $tableSQL);
			
		foreach ($tableSchema as $columnInfo)
		{
			/*
			 * Get the column meta-data.
			 * 
			 * Default the fields that may not require a definite value
			 */
			$columnSchema = array();
			$columnSchema[SchemaI::SEQUENCED] = "false";
			$columnSchema[SchemaI::MAX_LENGTH] = null;
			$columnSchema[SchemaI::SEQUENCE_NAME] = null; // no 'sequences' for SQLite
			$columnSchema[SchemaI::KEY_TYPE] = null; // We fill the key fields later.
			$columnSchema[SchemaI::KEY_NAME] = null;
			$columnSchema[SchemaI::KEY_INDEX] = null;
			/*
			 * Get the column data that will always be present.
			 */
			$columnName = $columnInfo["name"];
			$columnSchema[SchemaI::TABLE_NAME] = $tableName;
			$columnSchema[SchemaI::COLUMN_NAME] = $columnName;
			$columnSchema[SchemaI::ORDINAL_POSITION] = $columnInfo["cid"] + 1;
			$columnSchema[SchemaI::IS_NULLABLE] = $columnInfo["notnull"] == 1 ? "false" : "true";
			$columnSchema[SchemaI::COLUMN_DEFAULT] = $columnInfo["dflt_value"];
			
			$openingBracePos = strpos($columnInfo["type"], '(');
			if ($openingBracePos !== false)
			{
				$openingBracePos++;
				// There must be a closing brace.
				$closingBracePos = strpos($columnInfo["type"], ')');
				$length = $closingBracePos - $openingBracePos;
				$columnSchema[SchemaI::MAX_LENGTH] = substr($columnInfo["type"], $openingBracePos, $length);
				$columnSchema[SchemaI::DATA_TYPE] = substr($columnInfo["type"], 0, $openingBracePos - 1);
			}
			else
			{
				$columnSchema[SchemaI::DATA_TYPE] = $columnInfo["type"];
			}
			/*
			 * Merge key information by filling in and possibly creating a new record based on the present one for
			 * overlapping key columns.
			 */
			if (isset($keys[$columnName]) && !empty($keys[$columnName]))
			{
				foreach ($keys[$columnName] as $index => $keyInfo)
				{
					$columnSchemaWithKeyInfo = $columnSchema;
					foreach ($keyInfo as $keyName => $keyValue)
					{
						$columnSchemaWithKeyInfo[$keyName] = $keyValue;
					}
					$databaseSchema[] = $columnSchemaWithKeyInfo;
				}
			}
			else
			{
				$databaseSchema[] = $columnSchema;
			}
		}
		
		return $databaseSchema;
	}

	/**
	 * Gets the key information for a table.
	 *
	 * Produces an array of key information for the table that is keyed on the columnName. It can then be used to fill 
	 * in the properties of column definitions which would normally be available from an information_schema query.
	 *
	 * @param string $tableName
	 * @return array an array of associative arrays keyed on columnName.
	 */
	private function getKeys($tableName, $tableSchema, $tableSQL)
	{
		$result = array();
		$primaryKeys = array();
		
		foreach ($tableSchema as $columnSchema)
		{
			$columnName = $columnSchema["name"];
			$result[$columnName] = array();
			/*
			 * Process the primary key information first
			 */
			$isPK = $columnSchema["pk"] != 0;
			if ($isPK)
			{
				/*
				 * Default the sequenced indicator.
				 */
				$primaryKeys[$columnName] = "false";
				/*
				 * The only way to define an sequenced column in SQLite is to use 'INTEGER PRIMARY KEY'
				 * in the column declaration. Additionally an 'INTEGER PRIMARY KEY' disallows any other primary key declaration.
				 *
				 * It follows that, if we find the string INTEGER PRIMARY KEY in the declaration then we are guaranteed that
				 * the column we are processing here is sequenced.
				 *
				 * First replace all the white-space runs with a single space.
				 */
				$haystack = preg_replace("!\s+!", " ", $tableSQL);
				$haystack = strtoupper($haystack);
				/*
				 * Now search for ' INTEGER PRIMARY KEY'.
				 */
				$sequenced = (strpos($haystack, " INTEGER PRIMARY KEY") !== false);
				
				if ($sequenced)
				{
					$primaryKeys[$columnName] = "true";
				}
			}
		}
		/*
		 * Primary keys can be stored as unique indexes so we have a way of getting the index name for the
		 * primary key.
		 *
		 * There will NOT be a unique index entry for INTEGER PRIMARY KEY columns because they are an alias for the ROWID
		 * which is handled internally by SQLite.
		 */
		$uniqueKeys = $this->getUniqueKeys($tableName);
		/*
		 * Merge the primary and unique key info
		 */
		$isIndexedPrimaryKey = false;
		foreach ($uniqueKeys as $indexName => $columnDetails)
		{
			$copyPrimaryKeys = $primaryKeys;
			$copyColumnDetails = $columnDetails;
			ksort($copyColumnDetails);
			ksort($copyPrimaryKeys);
			if (array_keys($copyColumnDetails) == array_keys($copyPrimaryKeys))
			{
				$isIndexedPrimaryKey = true;
				/*
				 * Maximum of one primary key declaration, which may have many columns.
				 */
				foreach ($primaryKeys as $columnName => $sequenced)
				{
					$keyIndex = $columnDetails[$columnName];
					$result[$columnName][] = array(
							SchemaI::KEY_TYPE => SchemaI::PRIMARY_KEY,
							SchemaI::KEY_INDEX => $keyIndex,
							SchemaI::KEY_NAME => $indexName,
							SchemaI::SEQUENCED => $sequenced 
					);
				}
			}
			else
			{
				/*
				 * Could be more than one unique key declaration with many columns each.
				 */
				foreach ($columnDetails as $columnName => $keyIndex)
				{
					$result[$columnName][] = array(
							SchemaI::KEY_TYPE => SchemaI::UNIQUE,
							SchemaI::KEY_INDEX => $keyIndex,
							SchemaI::KEY_NAME => $indexName 
					);
				}
			}
		}
		/*
		 * Integer primary key
		 */
		if (!$isIndexedPrimaryKey && !empty($primaryKeys))
		{
			foreach ($primaryKeys as $columnName => $sequenced)
			{
				$result[$columnName][] = array(
						SchemaI::KEY_TYPE => SchemaI::PRIMARY_KEY,
						SchemaI::KEY_INDEX => 0,
						SchemaI::KEY_NAME => self::INTEGER_PRIMARY_KEY_INDEX,
						SchemaI::SEQUENCED => $sequenced 
				);
			}
		}
		/*
		 * Merge the foreign key info
		 */
		$foreignKeys = $this->getForeignKeys($tableName);
		foreach ($foreignKeys as $indexName => $foreignKeyInfo)
		{
			foreach($foreignKeyInfo as $keyIndex => $columnInfo)
			{
				foreach($columnInfo as $columnName => $referenceDetails)
				{
					list($tableName, $tableColumn) = each($referenceDetails);
					$result[$columnName][] = array(
						SchemaI::KEY_TYPE => SchemaI::FOREIGN_KEY,
						SchemaI::KEY_INDEX => $keyIndex,
						SchemaI::KEY_NAME => $indexName,
						SchemaI::REFERENCED_TABLE => $tableName,
						SchemaI::REFERENCED_COLUMN => $tableColumn
					);
				}
			}
		}
		
		return $result;
	}

	/**
	 * Gets the unique indexes for a table.
	 *
	 * @param string $tableName
	 */
	private function getUniqueKeys($tableName)
	{
		$uniqueKeys = array();
		
		$query = new QueryStatement($this, "pragma index_list(" . $tableName . ")");
		/*
		 * Query produces: seq|name|unique
		 */
		$indexList = $query->assocSet();
		foreach ($indexList as $index)
		{
			/*
			 * Process unique key info only, we are not concerned with other indexes.
			 */
			$unique = $index["unique"] == 1;
			if ($unique)
			{
				$indexName = $index["name"];
				$uniqueKeys[$indexName] = array();
				$query = new QueryStatement($this, "pragma index_info(" . $indexName . ")");
				/*
				 * Query produces: seqno|cid|name
				 */
				$indexInfo = $query->assocSet();
				
				foreach ($indexInfo as $columnIndexInfo)
				{
					/*
					 * Store the unique key info for later.
					 */
					$columnName = $columnIndexInfo["name"];
					$uniqueKeys[$indexName][$columnName] = $columnIndexInfo["seqno"];
				}
			}
		}
		
		return $uniqueKeys;
	}	
	
	/**
	 * Gets the unique indexes for a table.
	 *
	 * @param string $tableName
	 */
	private function getForeignKeys($tableName)
	{
		$foreignKeys = array();
		
		$query = new QueryStatement($this, "pragma foreign_key_list(" . $tableName . ")");
		/*
		 * Query produces: id|seq|table|from|to|on_update|on_delete|match
		 */
		$foreignKeyList = $query->assocSet();
		foreach ($foreignKeyList as $foreignKey)
		{
			$columnName = $foreignKey["from"];
			$indexName = $foreignKey["id"];
			$keyIndex = $foreignKey["seq"];
			
			if (!isset($foreignKeys[$indexName]))
			{
				$foreignKeys[$indexName] = array();
			}
			/*
			 * Store the foreign key info for later.
			 */
			$foreignKeys[$indexName][$keyIndex] = array($columnName => array($foreignKey["table"] => $foreignKey["to"]));
		}
		
		return $foreignKeys;
	}

	/**
	 * SQLite does not support the FOR UPDATE syntax so we just call find(). 
	 * 
	 * This produces a SHARED lock on the database which will block any writes from other connections giving the same end result.
	 *
	 * @param Model $modelObj
	 * @return array
	 */
	public function findForUpdate(Model $modelObj)
	{
		return $modelObj->find();
	}
}
?>