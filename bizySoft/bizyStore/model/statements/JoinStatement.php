<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\core\Model;

/**
 * Support for more general queries you write yourself that are not neccessarily based on Model objects, or prepared statements.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class JoinStatement extends Statement
{
	/**
	 * The Join we need to produce.
	 */
	private $join = null;
	
	/**
	 * Construct using the db and query passed in.
	 *
	 * @param DB $db the database reference associated with this statement.
	 * @param string $resolvePath the path to be resolved.
	 * @param array $properties the properties for the query.
	 * @param array $options the options for the query.
	 */
	public function __construct(DB $db, $resolvePath, $properties = array(), $options = array())
	{
		parent::__construct($db, $options);
		
		$this->join= new Join($db, $resolvePath, $options);
		$this->join->setPrefix($this->statementBuilder->getPropertyPrefix());
		$model = $this->join->getBaseModel();
		$whereProperties = $model->getSchemaProperties($properties);
		
		$statement = $this->join->taggedQuery($whereProperties);
		$append =  isset($options[Model::OPTION_APPEND_CLAUSE]) ? $options[Model::OPTION_APPEND_CLAUSE] : null;
		if ($append)
		{
			$alias = $this->join->getBaseAlias($model->getTableName());
			$aliased = $this->statementBuilder->translateAlias($append, $alias);
			$statement = $this->statementBuilder->append($statement, $aliased);
		}
		
		$query = $this->statementBuilder->translate($statement, $properties);
		$this->query = $query;
	}
		
	/**
	 * Specifically for returning the result set as an array of objects specified by the
	 * resolvePath string. This ignores the OPTION_CLASS_NAME and OPTION_CLASS_ARGS options.
	 *
	 * @param $properties optional, signature used for PreparedStatement.
	 * @return array the array of Models specified by the first JoinSpec in the resolvePath. The Models are populated 
	 * with their relationships to other Models.
	 * @throws ModelException
	 */
	public function objectSet(array $properties = array())
	{
		$pdoStatement = $this->query();
		return $this->join->resolve($pdoStatement);
	}
}
?>