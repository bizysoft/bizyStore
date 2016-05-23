<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\strategies\PreparedStatementExecuteStrategy;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\DBManager;

/**
 * Abstract class for prepared statements.
 *
 * Support for more general queries you write yourself that are not necessarily based on Model objects, but need to 
 * have the security provided by prepared statements.
 * 
 * PreparedStatement's provide a number of advantages over Statements. Statements cannot generally be cached because they 
 * work on fixed values so are useful only for a specific query.
 * 
 * Prepared statements in general, provide a replacement mechanism which means that, although the query may be fixed it 
 * still allows you to supply different parameters that can change the query execution. Because the query is fixed, 
 * they open the possiblity of being cached for later use. Eliminating the need to prepare more than once.
 *
 * bizyStore has the ability to cache it's PreparedStatement's in a DB instance so they are available from anywhere 
 * in the code via a unique key.
 * 
 * PreparedStatement's directly support named parameters. Place holders (ie. ?) are not directly supported by the 
 * PreparedStatement classes. It is much more robust to use named parameters.
 *
 * For place holder functionality, you can prepare/execute your own statements on the database reference and use any 
 * PDOStatement methods as you would normally.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class PreparedStatement extends Statement
{
	/**
	 * The key to store the PreparedStatement under.
	 */
	const OPTION_PREPARE_KEY = "prepareKey";
	
	/**
	 * Option key to cache the statement.
	 * 
	 * @var string
	 */
	const OPTION_CACHE = BizyStoreOptions::OPTION_CACHE;
	
	/**
	 * The associative array of properties to replace the colon prefixed named keys in the statement.
	 *
	 * @var array
	 */
	protected $properties = array();
	
	/**
	 * The associative array of options that this statement is to be prepared with.
	 * 
	 * @var array.
	 */
	private $prepareOptions = array();
	
	/**
	 * The associative array of properties that this statement can be executed with.
	 * 
	 * This variable's keys are used for validation on execution to make sure that the statement's $properties are
	 * compatible with those that are prepared. The values are irrelevant in the validation.
	 * 
	 * @see PreparedPDOStatement
	 *
	 * @var array
	 */
	private $executableProperties = array();
	
	/**
	 * The key which can be either specified by OPTION_PREPARE_KEY in the options passed in or built by derived classes.
	 * 
	 * @var string
	 */
	private $key = null;
	
	/**
	 * Does the statement require finalisation?
	 * 
	 * Only true if buildStatement() is not called via prepare().
	 * 
	 * @var boolean
	 */
	private $requiresFinalisation = true;
	
	/**
	 * Set up the options and prepare the statement.
	 * 
	 * The important options for a PreparedStatement are:
	 * 
	 * PDO_PREPARE_OPTIONS_TAG - Same as the tag in bizySoftConfig. Allows config override. Any PDO_PREPARE_OPTIONS_TAG's 
	 * specified in bizySoftConfig are processed by prepare().
	 * OPTION_CACHE - specifcally set the PreparedStatement cache.
	 * OPTION_PREPARE_KEY - name to store the prepared statement under if used multiple times.
	 *
	 * @param DB $db the database reference
	 * @param array $options the prepare options
	 */
	public function __construct($db, $options = array())
	{
		/*
		 * Pass through the options to the parent.
		 */
		parent::__construct($db, $options);
		/*
		 * Set the options for the PreparedStatement in the correct order.
		 * 
		 * We set the prepare options from config only once per instance as prepare() is called from here...
		 */
		$this->setPrepareOptions($options);
		$this->setUserOptions($options);
		/*
		 * Do whatever we need to do before setting the statement in stone.
		 */
		$this->initialise();
		/*
		 * Prepare and set the statement
		 */
		$this->statement = $this->prepare();
		/*
		 * Do whatever we need to do after setting the statement.
		 */
		if ($this->requiresFinalisation)
		{
			$this->finalise();
		}
	}

	/**
	 * Get the builder for this statement.
	 * 
	 * @return PreparedStatementBuilder
	 */
	protected function getBuilder()
	{
		return new PreparedStatementBuilder($this->db);
	}
	
	/**
	 * Do specific initialisation in implementation classes.
	 */
	protected abstract function initialise();
	
	/**
	 * Finalise the statement by translating the properties.
	 * 
	 * Note that this will be called only if buildStatement() is not required.
	 * (i.e. when retrieving the statement via a key)
	 * 
	 * Override for specialist behaviour.
	 */
	protected function finalise()
	{
		$this->properties = $this->statementBuilder->translateProperties($this->properties);
	}	
	
	/**
	 * Build the statement and return it.
	 *
	 * The statement is the raw text sql statement including colon prefixed named parameter keys that relate 
	 * to the properties. Deferred to the concrete class for implementation. 
	 * 
	 * buildStatement() should synchronise this->properties with the statement for prepare().
	 *
	 * @return string the statement query.
	 */
	protected abstract function buildStatement();
	
	/**
	 * Override setOptions() for options that can be used in the statement excecution.
	 * 
	 * Execution options such as OPTION_FUNCTION/OPTION_CLASS_NAME/OPTION_CACHE etc. can be set externally through 
	 * setOptions() to allow different behaviour on a cached statement if the need arises.
	 * 
	 * Take care when using this method because it destroys the previous options in the instance and may
	 * have an effect on the statement if it is already prepared.
	 *
	 * @param array $options
	 * @return array The previous options that were specified.
	 */
	public function setOptions(array $options = array())
	{
		$oldOptions = array_merge(parent::setOptions($options), $this->prepareOptions);
		$this->setUserOptions($options);
		
		return $oldOptions;
	}
	
	/**
	 * Override to include options from this class.
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return array_merge(parent::getOptions(), $this->prepareOptions);
	}
	
	/**
	 * Sets the options the user has specified that are relevant to the execution of the statement.
	 * 
	 * @param array $options
	 */
	private function setUserOptions($options)
	{
		if (isset($options[BizyStoreOptions::OPTION_CACHE]))
		{
			$this->prepareOptions[BizyStoreOptions::OPTION_CACHE] = $options[BizyStoreOptions::OPTION_CACHE];
		}
		if (isset($options[self::OPTION_PREPARE_KEY]))
		{
			$this->prepareOptions[self::OPTION_PREPARE_KEY] = $options[self::OPTION_PREPARE_KEY];
			/*
			 * Set the key if it has been specified. It must be unique for a $db instance.
			*/
			$this->key = $options[self::OPTION_PREPARE_KEY];;
			/*
			/*
			 * Turn the cache on explicitly.
			 */
			$this->prepareOptions[BizyStoreOptions::OPTION_CACHE] = true;
		}
	}
	
	/**
	 * Honor prepare options from bizySoftConfig/user for this level.
	 *
	 * PDO prepare options can be a PDOStatement prepare option such as PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
	 * or any other option that is suitable for your database driver at the prepare stage.
	 * 
	 * It is only valid to set these options before prepare() is called.
	 *
	 * @param array $options
	 */
	private function setPrepareOptions($options)
	{
		$dbConfig = DBManager::getDBConfig($this->getDB()->getDBId());
			
		/*
		 * These are general options applied to all prepared statements for this DB.
		 */
		$configPDOPrepareOptions = isset($dbConfig[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] : array();
		if(!empty($configPDOPrepareOptions))
		{
			$this->prepareOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] = $configPDOPrepareOptions;
		}
		/*
		 * Override config prepare options with user options if any.
		 * If you are sending the PDO_PREPARE_OPTIONS_TAG explicitly, they must be in the same format as bizySoftConfig
		 * holds them ie. containing resolved PDO attributes and constants.
		 * eg.
		 * PDO_PREPARE_OPTIONS_TAG => array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, etc...)
		 */
		$userPDOPrepareOptions = isset($options[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG]) ? $options[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] : array();
		if ($userPDOPrepareOptions)
		{
			if ($prepareOptions = $this->prepareOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG])
			{
				$this->prepareOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] = array_replace($prepareOptions, $userPDOPrepareOptions);
			}
		}
	}
	
	/**
	 * Get the properties that this statement will be executed with.
	 * 
	 * Note that only derived classes are allowed to set the properties.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}
	
	/**
	 * Set the properties for execution if required.
	 *
	 * @param array $properties
	 */
	public function setProperties(array $properties = array())
	{
		if (!empty($properties)) // Use the new properties
		{
			$this->properties = $this->statementBuilder->translateProperties($properties);
		}
	}
	
	/**
	 * Get the properties that the original query has been prepared with.
	 *
	 * @return array
	 */
	public function getExecutableProperties()
	{
		return $this->executableProperties;
	}
	
	/**
	 * Get the PDO prepare options.
	 *
	 * @return array
	 */
	public function getPDOPrepareOptions()
	{
		return isset($this->prepareOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG]) ? 
			$this->prepareOptions[BizyStoreOptions::PDO_PREPARE_OPTIONS_TAG] : array();
	}
	
	/**
	 * Prepares a statement and possibly caches it in the DB instance for later use, depending on OPTION_CACHE/OPTION_PREPARE_KEY
	 * supplied to setOptions().
	 *
	 * Preparing is a fairly expensive operation. In addition to being a database hit that does not bring back any data, it 
	 * asks the database server to construct a query plan for the statement. This can actually hamper performance if 
	 * you prepare a statement each time it's used.
	 * 
	 * Caching the prepared statement in the DB instance allows access through a unique key. You can construct a 
	 * PreparedStatement instance anywhere in your code and it will use the key (if specified) to find the previously 
	 * prepared statement.
	 * 
	 * Performance improvements via the cache may be realised when hitting a database heavily with the same statement. Further 
	 * improvements can be made if you hold a reference to the PreparedStatement instance yourself which is sometimes not possible.
	 * 
	 * In the case of our cache, supplying your own unique key through OPTION_PREPARE_KEY is the most direct/fastest method. 
	 * The key MUST be unique for a DB instance. The cache is automatically turned on for the PreparedStatement with 
	 * OPTION_PREPARE_KEY specified.
	 * 
	 * Turning the cache on using OPTION_CACHE without an OPTION_PREPARE_KEY will build a key for you. 
	 * 
	 * Constructing a PreparedStatement without turning the cache on will prepare the statement per instance.
	 *
	 * @throws ModelException if the statement cannot be prepared.
	 */
	public function prepare()
	{
		/*
		 * The cache is always on if we have a key set.
		 */
		$key = $this->key;
		$cache = $key || (isset($this->prepareOptions[BizyStoreOptions::OPTION_CACHE]) && 
							$this->prepareOptions[BizyStoreOptions::OPTION_CACHE] == true);
		
		$preparedPDOStatement = null;
		$statement = null;
		if ($cache)
		{
			/*
			 * Try and get the prepared statement with the supplied key or generate one.
			 * 
			 * The most reliable generated key is the raw text statement itself, buildStatement() will always produce the same
			 * result for a given set of properties otherwise it is NOT the same statement.
			 */
			$statement = $key ? null : $this->getBuildStatement();
			$key = $key ? $key : $statement;
			$preparedPDOStatement = $this->db->getCachedStatement($key);
			if ($preparedPDOStatement)
			{
				$this->executableProperties = $preparedPDOStatement->executableProperties;
				$preparedStatement = $preparedPDOStatement->pdoStatement;
				$this->query = $preparedStatement->queryString;
			}
		}
		if (!$preparedPDOStatement)
		{
			/*
			 * The statement is not already cached or the cache is turned off, so we build the statement, 
			 * but only if it's not built already as the key ...
			 */
			$statement = $statement ? $statement : $this->getBuildStatement();
			/*
			 * prepare here...
			 */
			$this->query = $statement;
			$preparedStatement = $this->db->prepare($statement, $this->getPDOPrepareOptions());
			$this->executableProperties = $this->properties;
			/*
			 * and set the cache if required.
			 */
			if ($cache)
			{
				$preparedPDOStatement = new PreparedPDOStatement();
				$preparedPDOStatement->executableProperties = $this->executableProperties;
				$preparedPDOStatement->pdoStatement = $preparedStatement;
				$this->db->setCachedStatement($key, $preparedPDOStatement);
			}
		}
		return $preparedStatement;
	}

	/**
	 * Builds the statement and indicates that it does not need finalisation.
	 */
	private function getBuildStatement()
	{
		$this->requiresFinalisation = false;
		
		return $this->buildStatement();
	}
	
	/**
	 * Execute the prepared statement and return the PDOStatement so either the data can be fetched
	 * or the statement can be accessed for other information.
	 *
	 * @param $properties array of properties that the prepared statement requires to be executed.
	 * @return PDOStatement for access to statement execution info.
	 * @throws ModelException if statement cannot be executed.
	 */
	public function execute(array $properties = array())
	{
		$this->setProperties($properties);
		
		$strategy = new PreparedStatementExecuteStrategy($this);
		return $strategy->execute();
	}

	/**
	 * PreparedStatement's can only be executed, so we just call execute().
	 *
	 * @param $properties array of properties that the prepared statement requires to be executed with
	 * @return PDOStatement
	 * @throws ModelException if statement cannot be executed.
	 */
	public function query(array $properties = array())
	{
		return $this->execute($properties);
	}
}

?>