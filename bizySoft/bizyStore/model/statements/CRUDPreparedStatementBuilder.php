<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Provide some common functions for building prepared statements that support bizyStore CRUD operations on 
 * Model objects.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class CRUDPreparedStatementBuilder extends PreparedStatementBuilder
{
	/**
	 * Set up the db.
	 *
	 * @param PDODB $db
	 */
	public function __construct($db)
	{
		parent::__construct($db);
	}
	
	/**
	 * Build a Model SELECT statement specific to a database table.
	 *
	 * @param string $tableName the database tableName to select from.
	 * @param array $properties the associative array of properties the statement will be built from
	 *
	 * @return string
	 */
	public final function buildModelSelectStatement($tableName, $properties = array())
	{
		$whereClause = $this->buildWhereClause($properties);
	
		return "SELECT * FROM <Q{$tableName}Q>" . ($whereClause ? " WHERE " . $whereClause : "");
	}
	
	/**
	* Build a Model INSERT statement specific to a database table.
	*
	* @param string $tableName the database tableName to insert into.
	* @param array $properties the associative array of properties the statement will be built from
	*
	* @return string
	*/
	public final function buildModelInsertStatement($tableName, $properties)
	{
		$valuesClause = $this->buildValuesClause($properties);
		
		return "INSERT INTO <Q{$tableName}Q> " . $valuesClause;
	}

	/**
	* Build a Model UPDATE statement specific to a database table.
	*
	* @param string $tableName the database tableName to update.
	* @param array $setProperties the associative array of properties the SET clause will be built from
	* @param array $whereProperties the associative array of properties the WHERE clause will be built from
	*
	* @return string
	*/
	public final function buildModelUpdateStatement($tableName, $setProperties, $whereProperties = array())
	{
		$setClause = $this->buildSetClause($setProperties);
		$whereClause = $this->buildWhereClause($whereProperties);
	
		return $setClause ? "UPDATE <Q{$tableName}Q> SET " . $setClause . ($whereClause ? " WHERE " . $whereClause : "") : null;
	}
	
	/**
	* Build a Model DELETE statement specific to a database table.
	*
	* @param string $tableName the database tableName to delete from.
	* @param array $properties the associative array of properties the statement will be built from
	*
	* @return string
	*/
	public final function buildModelDeleteStatement($tableName, $properties = array())
	{
		$whereClause = $this->buildWhereClause($properties);

		return "DELETE FROM <Q{$tableName}Q>" . ($whereClause ? " WHERE " . $whereClause : "");
	}
}
?>