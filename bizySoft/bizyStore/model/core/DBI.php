<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Interface to specify database access methods that absolutely must be implemented to support CRUD or 
 * database management activities.
 *
 * Most of these interface specifications have been defined in the PDODB abstract base class because they 
 * use standard SQL syntax or use the standard PDO methods, others are deferred to the specific implementation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
interface DBI
{
	const COMMIT = "commit";
	const ROLLBACK = "rollback";
		
	/**
	 * Get the timestamp string for "now".
	 *
	 * @return string in the form YYYY:MM:DD HH24:MI:SS
	 */
	public function getDateTime();
	
	/**
	 * Gets a connection for this database.
	 */
	public function connect();
	
	/**
	 * Closes the database connection
	 */
	public function close($mode);
	
	/**
	 * Gets the last id allocated by the database.
	 *
	 * Gets the value of the sequenced column generated by the last insert statement. Sequenced columns 
	 * are generally but not necessarily primary keys.
	 *
	 * Some databases use a name to retrieve the value from a database sequence entity.
	 *
	 * @param string $name optional, the name of the sequence to get.
	 * @return string the last id allocated by the database.
	 */
	public function getInsertId($name = null);
	
	/**
	 * Execute a read query on the database.
	 *
	 * Used to execute SQL that returns some form of result set.
	 *
	 * @param string $sql the sql query to run.
	 * @return PDOStatement allowing access to the result set via PDO fetch methods.
	 * @throws ModelException
	 */
	public function query($sql);
	
	/**
	 * Prepares a statement ready to be executed.
	 *
	 * bizyStore core code uses this method to prepare statements that use colon prefixed named parameter keys. 
	 * 
	 * Generally, you would not directly call this method unless you have special needs like binding parameters 
	 * to variables etc...
	 *
	 * @param string $statement the sql statement to prepare.
	 * @param array $options prepare options.
	 * @throws ModelException if the statement cannot be prepared or another failure occurs.
	 * @return PDOStatement with execution information.
	 */
	public function prepare($statement, array $options = array());
	
	/**
	 * Execute a write query on the database.
	 *
	 * Used to execute SQL against the database that does not return a result set. These can be DML or DDL 
	 * queries that can change your data.
	 *
	 * @param string $sql the sql to run.
	 * @throws ModelException if the sql could not be executed.
	 * @return int the number of rows affected if the sql was executed.
	 */
	public function execute($sql);
	
	/**
	 * Begin a transaction on the database.
	 * 
	 * @param string $isolationLevel
	 * @return DBTransaction allows explicit handling of the transaction and its attributes.
	 */
	public function beginTransaction($isolationLevel = null);
	
	/**
	 * Is there a transaction active on the database.
	 *
	 * @return true if there is a transaction in progress, false otherwise.
	 */
	public function hasTransaction();
	
	/**
	 * End a transaction on the database.
	 *
	 * @param const $endMode one of self::COMMIT, self::ROLLBACK.
	 */
	public function endTransaction($endMode);
	
	/**
	 * Run the code specified by $closure within a transaction boundary.
	 * 
	 * @param callable $closure
	 * @param string $isolationLevel the database isolation level of the transaction.
	 */
	public function transact($closure, $isolationLevel = null);
	
	/**
	 * Escape properties that may affect your query/statement.
	 *
	 * For use in non-prepared statements.
	 *
	 * @param string $propertyValue
	 * @return string the escaped string.
	 */
	public function escapeProperty($propertyValue);
	
	/**
	 * Get the schema info describing the database column and key meta-data for each database table.
	 *
	 * Used for generation of Model and Schema classes. See the implementation classes MySQL_PDODB,
	 * SQLite_PDODB etc...
	 *
	 * Properties returned by this method are array key/values for each database column in no particular order :
	 *
	 * tableName|columnName|ordinalPosition|columnDefault|isNullable|dataType|
	 * maxLength|sequenced|sequenceName|keyType|keyName|keyIndex|referencedTable|referencedColumn
	 *
	 * The minimum set of mandatory properties for bizyStore to configure the Schema files are:
	 * <ul>
	 * <li>tableName - the database table name.</li>
	 * <li>columnName - the database column name. May be different from the property name.</li>
	 * <li>sequenced - "true" if the column is a database generated value or sequence, "false" otherwise.</li>
	 * <li>sequenceName - the name of the sequence if this column is defined as being sequenced, null otherwise.</li>
	 * <li>keyType - One of "PRIMARY KEY", "UNIQUE", "FOREIGN KEY", NULL which describes the type of the key for this column.
	 * NULL means no key associated with this column. Keys can have more than one column and may overlap, extra row(s) will be 
	 * returned in this case.</li>
	 * <li>keyIndex - If the key should contain more than one column, then this is the position (zero based array index) 
	 * of the column in the key.</li>
	 * <li>keyName - The name of the key. Columns related to a key will have the same keyName.</li>
	 * <li>referencedTable - for foreign key support. The table the foreign key refers to.</li>
	 * <li>referencedColumn - for foreign key support. The column of the referencedTable that the foreign key refers to.</li>
	 * </ul>
	 * The following are output to the schema files as default column attributes...
	 * <ul>
	 * <li>dataType - the datatype of the column eg. int, varchar etc.</li>
	 * <li>maxLength - the maximum length of the column where applicable to the dataType or null.</li>
	 * </ul>
	 *
	 * The following are available from getSchema() for our supported vendors but are not output to the schema files 
	 * as default column attributes...
	 * 
	 * <ul>
	 * <li>ordinalPosition - the one-based position of the column in the table declaration.</li>
	 * <li>isNullable - "true" if the column is able to have a NULL value, "false" otherwise.</li>
	 * <li>columnDefault - the default value of the column eg NULL, CURRENT_TIMESTAMP etc.</li>
	 * </ul>
	 * 
	 * @throws ModelException If query is not able to be executed.
	 * @return array a zero based array of the above.
	 */
	public function getSchema($tableName);
	
	/**
	 * Gets the table names from the database if none are specified in the bizySoftConfig file.
	 */
	public function getDBTableNames();
	
	/**
	 * Database specific method to get a sequence value.
	 *
	 * @param string $sequenceName
	 */
	public function getCurrentSequence($sequenceName);
	
	/**
	 * Gets the error code produced by the underlying database connection.
	 * 
	 * @return string
	 */
	public function errorCode();
	
	/**
	 * Gets the error information from the underlying database connection.
	 * 
	 * @return array
	 */
	public function errorInfo();
	
}

?>