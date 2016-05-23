<?php
namespace bizySoft\bizyStore\model\statements;

use \PDO;
use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\strategies\ArraySetFetchStrategy;
use bizySoft\bizyStore\model\strategies\AssocSetFetchStrategy;
use bizySoft\bizyStore\model\strategies\FuncSetFetchStrategy;
use bizySoft\bizyStore\model\strategies\ObjectSetFetchStrategy;
use bizySoft\bizyStore\model\strategies\ScalarFetchStrategy;

/**
 * Support for more general queries you write yourself that are not necessarily based on Model objects. 
 * 
 * Provides statement execute, query, fetch and iterator methods with support for associative arrays, object arrays, 
 * scalar values and the PDO::FETCH_FUNC construct.
 *
 * If you are building your own queries based on user data, then consider using
 * QueryPreparedStatement for better security.
 *
 * Note: You CAN use the Statement class for more complex queries returning Model objects using joins etc. if you desire. 
 * In this case, the query should bring back data from a single table related to the OPTION_CLASS_NAME option.
 *
 * For the Model object case, OPTION_CLASS_NAME should always pass the fully qualified class name.
 * OPTION_CLASS_ARGS in this case should be array(null, $db) to keep in line with the Model constructor.
 * Use of the Model Object find methods is recommended if you don't require joins.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @see bizySoft\bizyStore\model\statements\StatementI
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class Statement implements StatementI
{
	/**
	 * The database this statement is associated with.
	 *
	 * @var DB
	 */
	protected $db;
	
	/**
	 * The class name that this statement is expected to return rows as.
	 *
	 * Optional, and should be null if an array or a scalar value is expected.
	 *
	 * @var string
	 */
	protected $className = null;
	
	/**
	 * Optional constructor arguments to set className objects
	 *
	 * @var array
	 */
	protected $classArgs = array();
	
	/**
	 * The builder for this Statement.
	 * 
	 * @var StatementBuilder
	 */
	protected $statementBuilder = null;
	
	/**
	 * Optional function to call for each row of results.
	 * 
	 * $statementFunction is a callable function name OR array of (class/object = > method name).
	 *
	 * @var callable
	 */
	protected $statementFunction = null;
	
	/**
	 * The raw query string.
	 *
	 * @var string representing the query.
	 */
	protected $query = null;
	
	/**
	 * The options for the statement.
	 * 
	 * Only allow setting via setOptions().
	 * 
	 * @var array
	 */
	private $options = array();
	
	/**
	 * The statement representing the executed query.
	 *
	 * @var PDOStatement
	 */
	protected $statement;
	
	/**
	 * Set the class variables.
	 *
	 * @param DB $db the database reference
	 * @param array $options
	 */
	public function __construct(DB $db, array $options = array())
	{
		$this->db = $db;
		$this->setOptions($options);
		$this->statementBuilder = $this->getBuilder();
	}
	
	/**
	 * Get the builder for this statement.
	 * 
	 * @return StatementBuilder
	 */
	protected function getBuilder()
	{
		return new StatementBuilder($this->db);
	}
	
	/**
	 * Get the class name that this statement is configured with
	 *
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}
	
	/**
	 * Get the constructor arguments associated with the class name
	 * for this statement.
	 * 
	 * @return array
	 */
	public function getClassArgs()
	{
		return $this->classArgs;
	}
	
	/**
	 * Get the function to manipulate fetch data this statement is configured with.
	 *
	 * @return callable
	 */
	public function getFunction()
	{
		return $this->statementFunction;
	}
	
	/**
	 * Allow read access to the options.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}
	
	/**
	 * Allow access to the statement for specialist processing.
	 *
	 * @return PDOStatement
	 */
	public function getStatement()
	{
		return $this->statement;
	}
	
	/**
	 * Allow access to the StatementBuilder.
	 * 
	 * @return StatementBuilder
	 */
	public function getStatementBuilder()
	{
		return $this->statementBuilder;
	}
	
	/**
	 * Allow access to the db.
	 *
	 * @return DB
	 */
	public function getDB()
	{
		return $this->db;
	}
	
	/**
	 * Allow access to the raw query.
	 *
	 * @return String
	 */
	public function getQuery()
	{
		return $this->query;
	}
	
	/**
	 * Execute the query and return the value of the execution.
	 * 
	 * You should bare in mind that this method is for non-prepared statements that don't produce a result set, 
	 * this includes statements that can change your data.
	 * 
	 * Consider using PreparedStatement for it's extra security features.
	 *
	 * @param array $properties Optional, signature used when overridden by PreparedStatement.
	 * @return int the number of rows affected.
	 * @throws ModelException
	 */
	public function execute(array $properties = array())
	{
		return $this->db->execute($this->query);
	}
	
	/**
	 * Execute the query and set/return a statement so the data can be fetched.
	 *
	 * You should bare in mind that this method is for non-prepared statements that produce a result set.
	 * 
	 * Consider using PreparedStatement for it's extra security features.
	 *
	 * @param array $properties Optional, signature used when overridden by PreparedStatement.
	 * @return PDOStatement to fetch the data from.
	 * @throws ModelException
	 */
	public function query(array $properties = array())
	{
		// Reinitialise the statement with the query
		$this->statement = $this->db->query($this->query);
		
		return $this->statement;
	}
	
	/**
	 * Free up the statement resources.
	 */
	public final function close()
	{
		if ($this->statement)
		{
			$this->statement->closeCursor();
		}
	}
	
	/**
	 * Gets the error code produced by the underlying statement.
	 *
	 * @return string
	 */
	public function errorCode()
	{
		return $this->statement ? $this->statement->errorCode() : "0";
	}
	
	/**
	 * Gets the error information from the underlying statement.
	 *
	 * @return array
	 */
	public function errorInfo()
	{
		return $this->statement ? $this->statement->errorInfo() : array(null, "0", null);
	}
	
	/**
	 * @see StatementI::assocRow()
	 *
	 * @return array associative array of column names/values from a statement fetch
	 */
	public final function arrayRow()
	{
		return $this->statement->fetch(PDO::FETCH_NUM);
	}	
	
	/**
	 * @see StatementI::assocRow()
	 *
	 * @return array associative array of column names/values from a statement fetch
	 * @throws ModelException
	 */
	public final function assocRow()
	{
		return $this->statement->fetch(PDO::FETCH_ASSOC);
	}
	
	/**
	 * @see StatementI::objectRow()
	 *
	 * @return mixed an object instance of OPTION_CLASS_NAME or "stdClass" with properties constructed from the fetch.
	 * @throws ModelException
	 */
	public function objectRow()
	{
		$class = $this->className;
		$constructorArgs = $class ? $this->classArgs : null;
		/*
		 * Default to stdClass if non specified.
		*/
		$class = $class ? $class : "\\stdClass";
			
		return $this->statement->fetchObject($class, $constructorArgs);
	}
	
	/**
	 * @see StatementI::funcRow()
	 *
	 * @return mixed returns the value of the function call or false if not successful.
	 * @throws ModelException
	 */
	public final function funcRow()
	{
		$result = false; // Indicates the end of the result set.
			
		if ($this->statementFunction)
		{
			$row = $this->statement->fetch(PDO::FETCH_ASSOC);
			if ($row !== false)
			{
				// $statementFunction should either throw an exception or return false on error
				$result = call_user_func($this->statementFunction, $row);
			}
		}
		else 
		{
			$result = $this->assocRow();
		}
		return $result;
	}
	
	/**
	 * Get a single scalar value from the database.
	 * 
	 * @see StatementI::scalar()
	 * @return string
	 * @throws ModelException
	 */
	public function scalar(array $properties = array())
	{
		$strategy = new ScalarFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Specifically for returning the result set as an array of associative arrays.
	 * 
	 * @see StatementI::assocSet()
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array The array of associative arrays that represent the rows from a database table.
	 * @throws ModelException
	 */
	public function assocSet(array $properties = array())
	{
		$strategy = new AssocSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Specifically for returning the result set as an array of zero-based integer indexed arrays.
	 * 
	 * @see StatementI::arraySet()
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array The array of zero-based integer indexed arrays that represent the rows from a database table.
	 * @throws ModelException
	 */
	public function arraySet(array $properties = array())
	{
		$strategy = new ArraySetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Specifically for returning the result set as an array of objects specified by the 
	 * OPTION_CLASS_NAME option.
	 * 
	 * @see StatementI::objectSet()
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array The array of objects that represent rows in a database table. Defaults to stdClass if no 
	 * OPTION_CLASS_NAME given.
	 * @throws ModelException
	 */
	public function objectSet(array $properties = array())
	{
		$strategy = new ObjectSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Specifically for returning an array of results processed by the function specified in the OPTION_FUNCTION option.
	 * 
	 * If there is no statementFunction specified then returns an arraySet.
	 *
	 * @see StatementI::funcSet()
	 * @param array $properties Optional, signature used for PreparedStatement.
	 * @return array The array of results of calling a function with each row. Defaults to an arraySet if no 
	 * OPTION_FUNCTION given.
	 * @throws ModelException
	 */
	public function funcSet(array $properties = array())
	{
		$strategy = $this->statementFunction ? new FuncSetFetchStrategy($this) : new AssocSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Gets an iterator on the result set from the executed statement.
	 *
	 * Gives the user a way to access database rows returned individually through a fetch on the executed statement. 
	 * May save some PHP memory while processing a result set.
	 * 
	 * @param array $properties
	 * @param string $type
	 * @return StatementIterator
	 * @throws ModelException If the statement cannot be executed.
	 */
	public function iterator($properties = array(), $type = null)
	{
		$result = null;

		$fetchType = $type ? $type : $this->getFetchType();
		switch ($fetchType)
		{
			case self::FETCH_TYPE_FUNCTION:
				/*
				 * Default if a callable function is not specified.
				 */
				if ($this->statementFunction)
				{
					$result = new StatementFunctionIterator($this, $properties);
				}
				else 
				{
					$result = new StatementAssocIterator($this, $properties);
				}
				break;
			case self::FETCH_TYPE_OBJECT:
				/*
				 * You can always iterate via an object, defaulted to 'stdClass'
				 */
				$result = new StatementObjectIterator($this, $properties);
				break;
			case self::FETCH_TYPE_ARRAY:
				/*
				 * You can always iterate via an integer indexed array. 
				 * You must explicity specify this as a type, the default is always FETCH_TYPE_ASSOC.
				 */
				$result = new StatementArrayIterator($this, $properties);
				break;
			default:
				/*
				 * The default fetch type is an associative array, because it's fast and the most 
				 * convenient to extract column values.
				 */
				$result = new StatementAssocIterator($this, $properties);
		}
		return $result;
	}
	
	
	/**
	 * Gets the fetch type from the class variables set.
	 *
	 * @param array $options
	 * @return string
	 */
	protected function getFetchType()
	{
		/*
		 * FETCH_TYPE_FUNCTION takes precedence, here we use the class members to determine a type.
		 * Defaults to FETCH_TYPE_ASSOC if no class members specified.
		 */
		$fetchType = $this->statementFunction ? self::FETCH_TYPE_FUNCTION :
		                                        ($this->className ? self::FETCH_TYPE_OBJECT : self::FETCH_TYPE_ASSOC);
	
		return $fetchType;
	}
	
	/**
	 * Override/set the class variables required from the options specified.
	 *
	 * @param $options array of options which may include OPTION_CLASS_NAME, OPTION_CLASS_ARGS or OPTION_FUNCTION details.
	 * @return array the old options.
	 */
	protected function setOptions(array $options = array())
	{
		$oldOptions = $this->options;
		/*
		 * Specifically set the class variables based on the options. Options can be null to enable a reset.
		 */
		$this->className = array_key_exists(self::OPTION_CLASS_NAME, $options) ? $options[self::OPTION_CLASS_NAME] : $this->className;
		$this->classArgs = array_key_exists(self::OPTION_CLASS_ARGS, $options) ? $options[self::OPTION_CLASS_ARGS] : $this->classArgs;
		/*
		 * statementFunction is a callable function name OR array of (class/object => method name).
		 */
		$this->statementFunction = array_key_exists(self::OPTION_FUNCTION, $options) ? $options[self::OPTION_FUNCTION] : $this->statementFunction;
			
		$this->options = array_merge($this->options, $options);
	
		return $oldOptions;
	}
}
?>