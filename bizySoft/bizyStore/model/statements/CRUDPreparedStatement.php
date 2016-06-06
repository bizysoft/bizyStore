<?php
namespace bizySoft\bizyStore\model\statements;

use \PDO;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\model\strategies\ModelSetFetchStrategy;
use bizySoft\bizyStore\model\strategies\ModelArraySetFetchStrategy;
use bizySoft\bizyStore\model\strategies\ModelAssocSetFetchStrategy;
use bizySoft\bizyStore\model\strategies\ModelFuncSetFetchStrategy;
use bizySoft\bizyStore\services\core\DBManager;

/**
 * Provide additional properties and methods for prepared statements, specifically on Model objects.
 * 
 * As a general note, once prepared, the statement is set in stone, so it's particularly important to make sure 
 * the Model properties are the ones that you intend to use in the statement. 
 * 
 * null properties generate an 'ISNULL propertyName ' in the where clause and won't have a named parameter associated 
 * with it. Be careful if your Model holds data from a database, which may return null properties that you don't intend to use.
 * 
 * buildStatement() synchronises the properties with the statement by stripping nulls with implementation 
 * specific considerations so that the statement can be safely executed after being prepared. Any subsequent executions 
 * of the statement will need to pass the matching non null properties.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
abstract class CRUDPreparedStatement extends PreparedStatement
{	
	/**
	 * The Model object we are concerned with.
	 *
	 * @var Model
	 */
	protected $modelObj = null;
	
	/**
	 * Save the where clause properties.
	 *
	 * @var array
	 */
	protected $whereClauseProperties = array();
		
	/**
	 * Append this SQL to the statement.
	 * 
	 * This is typically used to append such things as 'order by' and 'limit' clauses.
	 * 
	 * @var string
	 */
	protected $appendClause = null;

	/**
	 * This is a CRUD class so we accept a Model object as a basis in constructing the statement.
	 *
	 * Honor the "cache" setting from the MODEL_PREPARE_OPTIONS_TAG in bizySoftConfig. This is turned 
	 * into OPTION_CACHE in the options.
	 *
	 * @param Model $modelObj type checked here to be a Model object.
	 * @param array $options prepare options.
	 */
	public function __construct(Model $modelObj, $options = array())
	{
		$this->modelObj = $modelObj;
		
		/*
		 * Get the db options
		 */
		$db = $modelObj->getDB();
		$dbConfig = DBManager::getDBConfig($db->getDBId());
		
		/*
		 * These are general options applied to all CRUDPreparedStatements for the $db.
		 * 
		 * The idea here is to turn the options specific to CRUD into options that PreparedStatement uses.
		 */
		$configModelPrepareOptions = isset($dbConfig[BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::MODEL_PREPARE_OPTIONS_TAG] : array();
		
		if (isset($configModelPrepareOptions[BizyStoreOptions::OPTION_CACHE]))
		{
			/*
			 * Only override if the user doesn't explicity specify.
			 */
			if (!isset($options[BizyStoreOptions::OPTION_CACHE]))
			{
				$options[BizyStoreOptions::OPTION_CACHE] = $configModelPrepareOptions[BizyStoreOptions::OPTION_CACHE];
			}
		}
		$options[Statement::OPTION_CLASS_NAME] = get_class($modelObj);
		
		/*
		 * The parent takes care of preparing the statement.
		 */
		parent::__construct($db, $options);
	}

	/**
	 * Get the builder for this statement.
	 *
	 * @return CRUDPreparedStatementBuilder
	 */
	protected function getBuilder()
	{
		return new CRUDPreparedStatementBuilder($this->db);
	}
	
	/**
	 * Default implementation to do whatever is required to initialise the object so that a statement can be created.
	 *
	 * This will typically set the properties of the PreparedStatement as required by the concrete class object.
	 * The default is to set the where clause properties, which is used to build the query that needs to be prepared.
	 *
	 * Should be overidden by the implementation to provide specific settings.
	 *
	 * @param Model $modelObj
	 */
	protected function initialise()
	{
		/*
		 * Set the properties for the where clause. The statement is prepared based of these properties.
		 */
		$this->whereClauseProperties = $this->getWhereClauseProperties();
		$this->properties = $this->whereClauseProperties;
	}

	/**
	 * Get's the where clause properties from the Model's properties.
	 *
	 * This is used to build the where clause for the query.
	 *
	 * @param Model $modelObj
	 */
	protected function getWhereClauseProperties()
	{
		$result = $this->modelObj->getSchemaProperties();
		
		// Sort the properties for cache
		ksort($result);
		
		return $result;
	}

	/**
	 * Gets the Model associated with this statement.
	 * 
	 * @return Model
	 */
	public function getModel()
	{
		return $this->modelObj;
	}
	
	/**
	 * Override base class method for optimised Model object fetches.
	 *
	 * Get the next statement row as a Model object specified by the OPTION_CLASS_NAME option.
	 * The statement must have been executed before this method can be called.
	 *
	 * @return Model A Model object with properties constructed from the fetch.
	 */
	public function objectRow()
	{
		$result = false;
		$pdoStatement = $this->getStatement();
		$class = $this->className;
		/*
		 * Grab the row as an array this is the fastest method
		 */
		if ($row = $pdoStatement->fetch(PDO::FETCH_ASSOC))
		{
			/*
			 * Properly instantiate a model object from the array.
			 */
			$result = new $class($row, $this->db);
			$result->setPersisted(true);
		}
		return $result;
	}
	
	/**
	 * Override base class method for assoc set fetches using the schema properties of a Model object.
	 *
	 * Specifically for returning the result set as an associative array of associative arrays indexed on a 
	 * database table key or a zero based array.
	 * 
	 * assocSet() with normal zero based indexing is fastest fetch method.
	 *
	 * @param array $properties The array of properties that the statement will work with.
	 * @return array an array of associative array's of schema properties.
	 * @throws ModelException
	 */
	public function assocSet(array $properties = array())
	{
		$strategy = new ModelAssocSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Override base class method for array set fetches using the schema properties of a Model object.
	 *
	 * Specifically for returning the result set as an associative array of zero-based arrays indexed on a 
	 * database table key or a zero based array.
	 *
	 * arraySet() with normal zero based indexing is the fastest fetch method.
	 *
	 * @param array $properties The array of properties that the statement will work with.
	 * @return array an array of associative array's of schema properties.
	 * @throws ModelException
	 */
	public function arraySet(array $properties = array())
	{
		$strategy = new ModelArraySetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Override base class method for optimised Model object fetches.
	 *
	 * Specifically for returning the result set as an array of Model objects indexed on a database table key or a zero based array.
	 *
	 * @param array $properties The array of properties that the statement will work with.
	 * @return array The array of Model objects fetched.
	 * @throws ModelException
	 */
	public function objectSet(array $properties = array())
	{
		$strategy = new ModelSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
	
	/**
	 * Override base class method for func set fetches using the schema properties of a Model object.
	 *
	 * Specifically for returning the result set as an array indexed on a database table key or a zero based array.
	 * 
	 * @param array $properties The array of properties that the statement will work with.
	 * @return array The array of Model objects fetched.
	 * @throws ModelException
	 */
	public function funcSet(array $properties = array())
	{
		$strategy = new ModelFuncSetFetchStrategy($this);
		return $strategy->execute($properties);
	}
}

?>