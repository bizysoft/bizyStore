<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\Model;

/**
 * Support the Find operation on Model objects in the database.
 *
 * Uses the specified properties to find a set of Model instances.
 *
 * Unique instances can be found by populating the Model with key properties only, either manually or by 
 * using Model::getKeyProperties().
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class FindPreparedStatement extends CRUDPreparedStatement
{
	/**
	 * Construct a prepared statement using the Model object.
	 *
	 * @param Model $modelObj        	
	 * @param array $options prepare options.
	 */
	public function __construct($modelObj, $options = array())
	{
		parent::__construct($modelObj, $options);
	}

	/**
	 * Build a find statement for the Model and return it.
	 * 
	 * Models can have user specified tagged clauses appended via Model::OPTION_APPEND_CLAUSE. Be aware that if the Model properties
	 * contain the same schema entity name as an appended entity, then the appended query portion may have no effect.
	 * 
	 * e.g.
	 * 
	 *    $modelproperties = array("firstName" => "Jack", "jackProp" => "Jack", "jillProp" => "Jill");
	 *    $options = array(Model::OPTION_APPEND_CLAUSE => "<EfirstNameE> IN (<PjackPropP>, <PjillPropP>)");
	 * 
	 * The above $properties will produce a where clause of firstName = 'Jack' negating the IN clause. The solution would be 
	 * to remove "firstName" from the $modelProperties, allowing the IN clause to work properly.
	 * 
	 * @return string the raw text statement including colon prefixed named parameter keys.
	 */
	public function buildStatement()
	{
		$statement = $this->statementBuilder->buildModelSelectStatement($this->modelObj->getTableName(), $this->whereClauseProperties);

		$options = $this->getOptions();
		$statement = isset($options[Model::OPTION_APPEND_CLAUSE]) ? 
				$this->statementBuilder->append($statement, $options[Model::OPTION_APPEND_CLAUSE]) : $statement;
		
		$properties = $this->modelObj->get();
		$statement = $this->statementBuilder->translate($statement, $properties);
		/*
		 * We can synchronise properties with the statement because we have called translate().
		 */
		$this->properties = $this->statementBuilder->translateProperties($properties);
		
		return $statement;
	}
}
?>