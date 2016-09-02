<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Interface to declare Statement methods that concrete classes must be define in an implementation.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface StatementI extends StatementConstants
{
	/**
	 * Free up the statement resources.
	 */
	public function close();
	
	/**
	 * Gets the error code produced by the underlying statement.
	 * 
	 * @return string
	 */
	public function errorCode();
	
	/**
	 * Gets the error information from the underlying statement.
	 * 
	 * @return array
	 */
	public function errorInfo();
	
	/**
	 * Execute the Statement and return the value of the execution.
	 * 
	 * Note that for Statements this method will call exec() on the underlying PDO reference and returns the number of rows affected.
	 * For PreparedStatements this method call's execute() on the PDOStatement reference and returns a PDOStatement instance.
	 *
	 * @param array $properties Optional, signature used when overridden by PreparedStatement.
	 * @return mixed the number of rows affected or a PDOStatement instance.
	 * @throws ModelException
	 */
	public function execute(array $properties = array());
	
	/**
	 * Execute the Statement query and return a PDOStatement so the data can be fetched.
	 *
	 * @param array $properties Optional, signature used when overridden by PreparedStatement.
	 * @return PDOStatement to fetch the data from.
	 * @throws ModelException
	 */
	public function query(array $properties = array());
	
	/**
	 * Gets the database reference
	 * 
	 * @return DB
	 */
	public function getDB();
	
	/**
	 * Gets the PDOStatement 
	 * 
	 * @return PDOStatement
	 */
	public function getStatement();
	
	/**
	 * Get the class name that the statement is configured with
	 *
	 * @return string
	 */
	public function getClassName();
	
	/**
	 * Get the constructor arguments associated with the class name
	 * for the statement.
	 *
	 * @return array
	 */
	public function getClassArgs();
	
	/**
	 * Get the function to manipulate fetch data the statement is configured with.
	 *
	 * @return callable
	 */
	public function getFunction();
	
	/**
	 * Allow read access to the options.
	 *
	 * @return array
	 */
	public function getOptions();
	
	/**
	 * Allows convenient foreach functionality through an Iterator.
	 * 
	 * Gets an appropriate Iterator instance from the $type passed in or the statement itself. 
	 * Calling iterator() will execute the statement.
	 *
	 * @param array $properties specify new properties for this Statement to execute with.
	 * @param string $type override the result set type for this Statement.
	 * @return StatementIterator
	 */
	public function iterator($properties = array(), $type = null);
	
	/**
	 * Get the next statement row as an associative array.
	 *
	 * The statement must have been executed before this method can be called.
	 *
	 * @return array associative array of column names/values from a statement fetch.
	 */
	public function assocRow();
	
	/**
	 * Get the next statement row as an object instance specified by the OPTION_CLASS_NAME option
	 * or stdClass if not specified.
	 *
	 * The statement must have been executed before this method can be called.
	 *
	 * @return mixed an object instance of OPTION_CLASS_NAME or "stdClass" with properties constructed from the fetch.
	 */
	public function objectRow();
	
	/**
	 * Get the results of the next statement row processed by the function specified in the OPTION_FUNCTION option.
	 *
	 * The statement must have been executed before this method can be called.
	 *
	 * @return mixed returns the value of the function call or false if not successful.
	 */
	public function funcRow();
	
	/**
	 * Get a scalar value from the database.
	 *
	 * Use when your statement returns a single value.
	 *
	 * @return string
	 * @throws ModelException
	 */
	public function scalar(array $properties = array());
	
	/**
	 * Specifically for returning the result set as an array of associative arrays.
	 *
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array the array of associative arrays that represent the rows from the database.
	 * @throws ModelException
	 */
	public function assocSet(array $properties = array());
	
	/**
	 * Specifically for returning the result set as an array of zero-based integer indexed arrays.
	 *
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array the array of zero-based integer indexed arrays that represent the rows from the database.
	 * @throws ModelException
	 */
	public function arraySet(array $properties = array());
	
	/**
	 * Specifically for returning the result set as and array of objects specified by the 
	 * OPTION_CLASS_NAME option.
	 *
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array the array of objects. Defaults to "stdClass" if no OPTION_CLASS_NAME given
	 * @throws ModelException
	 */
	public function objectSet(array $properties = array());
	
	/**
	 * Specifically for returning the result set that has been processed by the
	 * function specified in the OPTION_FUNCTION option.
	 *
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array the array of results of calling a function with each row.
	 * @throws ModelException
	 */
	public function funcSet(array $properties = array());
}

?>