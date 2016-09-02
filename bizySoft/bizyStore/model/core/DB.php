<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\model\statements\StatementBuilder;
use bizySoft\bizyStore\model\statements\FindForUpdatePreparedStatement;
use bizySoft\bizyStore\model\statements\PreparedPDOStatement;
use bizySoft\bizyStore\services\core\Config;

/**
 * Note that this class has no notion of what a database is. It's main use is to store general information from
 * bizySoftConfig and provide basic methods required for CRUD implementation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class DB implements DBI
{
	/**
	 * The id of this DB instance from bizySoftConfig.
	 *
	 * @var string
	 */
	private $dbId = null;
	
	/**
	 * A reference to the application's config.
	 *
	 * @var Config
	 */
	private $config;
	
	/**
	 * The name of the database from bizySoftConfig.
	 *
	 * @var string
	 */
	private $dbName = "";
	
	/**
	 * The name of the schema that this DB instance is associated with from bizySoftConfig.
	 *
	 * Many database systems have the notion of schema's which can be seen as a separate database
	 * within a database. For those that support it, a connection can be made to use a particular
	 * schema for all database operations. This is the name of the schema to use.
	 *
	 * @var string the name of the schema that this DB instance operates on.
	 */
	private $schemaName = "";
	
	/**
	 * Associative array of name => PreparedPDOStatement.
	 *
	 * This is where we cache the statements under a key so we only prepare once.
	 *
	 * The key is either derived or supplied by you.
	 *
	 * @var array
	 */
	private $cachedStatements = array();

	/**
	 *
	 * Sets the class variables that are required for management of all DB instances.
	 *
	 * @param array $dbConfig an associative array containing the database config information supplied in bizySoftConfig.
	 */
	public function __construct($dbId, Config $config)
	{
		$this->dbId = $dbId;
		$this->config = $config;
		$dbConfigs = $config->getProperty(self::DATABASE_TAG);
		$dbConfig = $dbConfigs[$dbId];
		$this->dbName = $dbConfig[self::DB_NAME_TAG]; // Mandatory
		$this->schemaName = isset($dbConfig[self::DB_SCHEMA_TAG]) ? $dbConfig[self::DB_SCHEMA_TAG] : "";
	}

	/**
	 * Gets the name of the database that this DB instance is associated with.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->dbName;
	}

	/**
	 * Gets the name of the schema that this DB instance is associated with.
	 *
	 * @return string
	 */
	public function getSchemaName()
	{
		// Make sure we return an empty string not null
		return $this->schemaName;
	}
	
	/**
	 * Sets the name of the schema that this DB instance is associated with.
	 *
	 * Some db's may need to set this to a default after base class construction.
	 * 
	 * @return string
	 */
	public function setSchemaName($name)
	{
		$this->schemaName = $name;
	}
	
	/**
	 * Gets the 'id' of this DB instance.
	 *
	 * @return string
	 */
	public function getDBId()
	{
		return $this->dbId;
	}

	/**
	 * Gets the application's config reference.
	 * 
	 * @return Config
	 */
	public function getConfig()
	{
		return $this->config;
	}
	
	/**
	 * Support for pessimistic locking if required.
	 * 
	 * Database specific, finds the Model instance(s) in the database and locks the rows found. 
	 *
	 * findForUpdate() may return more than one instance. This will block external processes from using 
	 * the rows/table/database depending on the vendors implementation.
	 *
	 * Care must be taken when using this method to surround it in fault tolerant transactional code, cleaning up 
	 * by calling endTransaction() to release the locks.
	 * 
	 * This method can be over-ridden if your database does not support 'select for update' functionality, in which case 
	 * buildSelectForUpdateStatement() should NOT be called as it is here in FindForUpdatePreparedStatement().
	 * 
	 * e.g for SQLite we just call Model::find() which has the same effect due to SQLite's locking policy.
	 *
	 * @param Model $modelObj
	 * @return array Always returns an associative array of Model objects.
	 * @throws ModelException when a failure occurs.
	 */
	public function findForUpdate(Model $modelObj)
	{
		$modelPreparedStatement = new FindForUpdatePreparedStatement($modelObj);
		return $modelPreparedStatement->objectSet();
	}

	/**
	 * Builds the 'select for update' statement for the database.
	 *
	 * This is database specific. Most databases support 'select ... for update' construct so this is the default. 
	 * Some databases use different techniques, so this method should be over-ridden where required.
	 * 
	 * For databases that don't support 'select for update' override findForUpdate() instead.
	 *
	 * @param string $tableName the table name to select on.
	 * @param array $properties the model properties used in the where clause.
	 * @param StatementBuilder $statementBuilder optional StatementBuilder to build with.
	 * @return string the select statement ready to prepare.
	 */
	public function buildSelectForUpdateStatement($tableName, array $properties, StatementBuilder $statementBuilder)
	{
		return $statementBuilder->buildModelSelectStatement($tableName, $properties) . " FOR UPDATE";
	}

	/**
	 * Produce a qualified name for the entity passed in.
	 *
	 * Includes all the semantics required to address the entity in a query. This is the default
	 * implementation. Override for specialised behaviour.
	 * 
	 * Entities in this case can include:
	 * 
	 * table names
	 * sequence names
	 * index names
	 * etc...
	 *
	 * @param string $entityName
	 * @return string the qualified $entityName formatted as required for database statements.
	 */
	public function qualifyEntity($entityName)
	{
		$schemaName = $this->getSchemaName();
		$schema = $schemaName ? $this->formatEntity($schemaName) . "." : "";
		$result = $schema . $this->formatEntity($entityName);
		
		return $result;
	}

	/**
	 * Default formatting for use of a database entity in a database statement.
	 * 
	 * A database entity can mean any of the following:
	 * 
	 * table mames
	 * column names
	 * schema names
	 * sequence names
	 * index names
	 * etc...
	 * 
	 * Some databases may require specialist processing to address the entities. 
	 *
	 * This is the default implementation and does nothing to the $entityName passed in.
	 * Override this for specific database behaviour.
	 *
	 * @param string $entityName
	 * @return string the $entityName formatted as required for database statements.
	 */
	public function formatEntity($entityName)
	{
		return $entityName;
	}

	/**
	 * Default formatting for use of a property value in a database statement.
	 *
	 * The default is to call escapeProperty(). This is typically used for non-prepared statements. Concrete classes 
	 * can override for specific database behaviour.
	 *
	 * eg. Most databases generally require single quotes for explicit values. This is not required when using named 
	 * parameters in prepared statements but may be required for general queries that use specific values.
	 *
	 * @param string $propertyValue
	 * @return string the property value formatted as required for database statements.
	 */
	public function formatProperty($propertyValue)
	{
		return $propertyValue == null ? "NULL" : $this->escapeProperty($propertyValue);
	}
	
	/**
	 * Get the cached prepared statement by name.
	 *
	 * @param string $name
	 * @return PreparedPDOStatement the PreparedPDOStatement or null if the name is not found.
	 */
	public function getCachedStatement($name)
	{
		return isset($this->cachedStatements[$name]) ? $this->cachedStatements[$name] : null;
	}
	
	/**
	 * Store the prepared statement under the name passed in.
	 *
	 * The name should be unique for this DB.
	 *
	 * @param string $name
	 * @param PreparedPDOStatement $statement
	 */
	public function setCachedStatement($name, PreparedPDOStatement $statement)
	{
		if ($name && $statement)
		{
			$this->cachedStatements[$name] = $statement;
		}
	}
}
?>