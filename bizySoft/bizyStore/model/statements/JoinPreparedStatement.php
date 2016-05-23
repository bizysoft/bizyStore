<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\core\Model;

/**
 * Provide support for Model Join operations.
 * 
 * You can use this class to join tables with others via a resolve path, table columns specified don't necessarily have to be 
 * declared as foreign keys.
 * 
 * The resolve path should be consistent with the database design. As usual, subsequent executions of the same JoinPreparedStatement 
 * require the same property keys.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class JoinPreparedStatement extends PreparedStatement
{
	/**
	 * The Join we need to produce.
	 */
	private $join = null;
	
	private $resolvePath = null;
	/**
	 * Construct with the resolve path. 
	 * 
	 * Takes the same basic parameters as QueryPreparedStatement, the query being a resolvePath.

	 * As usual, subsequent executions of the same JoinPreparedStatement require the same property keys.
	 * 
	 * @param DB $db database to work on.
	 * @param string $resolvePath definition of the database relationship path to resolve.
	 * @param array $properties properties for the where clause based on the first JoinSpec.
	 * @param array $options can be the normal prepare options, additionally it can contain
	 * self::OPTION_SWIZZLE a boolean value to turn on/off. The default is true.
	 * self::OPTION_JOIN_TYPES specifying the join types between each JoinSpec.
	 * @see Join or JoinSpec for more info.
	 */
	public function __construct(DB $db,  $resolvePath, array $properties = array(), array $options = array())
	{
		$this->properties = $properties;
		$this->resolvePath = $resolvePath;
		parent::__construct($db, $options);
	}
	
	/**
	 * Initialise by creating a Join object
	 */
	protected function initialise()
	{
		$this->join= new Join($this->db, $this->resolvePath, $this->getOptions());
		$this->join->setPrefix($this->statementBuilder->getPropertyPrefix());
	}
	
	/**
	 * Build the statement so it can be prepared.
	 * 
	 * @return string the raw text query to be prepared.
	 */
	public function buildStatement()
	{
		$properties = $this->properties;
		$model = $this->join->getBaseModel();
		$whereProperties = $model->getSchemaProperties($properties);
		$options = $this->getOptions();
		$statement = $this->join->taggedQuery($whereProperties);
		$append =  isset($options[Model::OPTION_APPEND_CLAUSE]) ? $options[Model::OPTION_APPEND_CLAUSE] : null;
		
		if ($append)
		{
			$alias = $this->join->getBaseAlias($model->getTableName());
			$aliased = $this->statementBuilder->translateAlias($append, $alias);
			$this->statementBuilder->hasWhere(!empty($whereProperties));
			$statement = $this->statementBuilder->append($statement, $aliased);
		}
		/*
		 * The query is tagged so we translate.
		 */
		$query = $this->statementBuilder->translate($statement, $properties);
		/*
		 * Synchronise the properties with the statement.
		 */
		$this->properties = $this->statementBuilder->translateProperties($properties);
		
		return $query;
	}
	
	/**
	 * Specifically for returning the result set as an array of Model's specified by the
	 * resolvePath string. This ignores the OPTION_CLASS_NAME and OPTION_CLASS_ARGS options.
	 *
	 * @param $properties array of properties that the prepared statement requires to be executed with.
	 * @return array the array of Models specified by the first JoinSpec in the resolvePath. The Models are populated with 
	 * their relationships to other Models.
	 * @throws ModelException
	 */
	public function objectSet(array $properties = array())
	{
		$pdoStatement = $this->execute($properties);
		
		return $this->join->resolve($pdoStatement);
	}
}
?>