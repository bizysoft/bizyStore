<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\Model;

/**
 * Support the Delete CRUD operation on Model objects in the database.
 * 
 * Concrete class for deleting Model objects in a database table.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class DeletePreparedStatement extends CRUDPreparedStatement
{
	/**
	 * Construct a prepared statement using the Model object.
	 *
	 * @param Model $modelObj        	
	 * @param array $options
	 *        	prepare options.
	 */
	public function __construct($modelObj, $options = array())
	{
		parent::__construct($modelObj, $options);
	}

	/**
	 * Build a delete statement for the Model and return it.
	 *
	 * The statement is the raw text statement including colon prefixed named parameters keys.
	 *
	 * @return string the statement
	 */
	public function buildStatement()
	{
		$statement = $this->statementBuilder->buildModelDeleteStatement($this->modelObj->getTableName(), $this->properties);
		$options = $this->getOptions();
		$statement = isset($options[Model::OPTION_APPEND_CLAUSE]) ? 
				$this->statementBuilder->append($statement, $options[Model::OPTION_APPEND_CLAUSE]) : $statement;
		
		$properties = $this->modelObj->get();
		$statement = $this->statementBuilder->translate($statement, $properties);
		$this->properties = $this->statementBuilder->translateProperties($properties);
		
		return $statement;
	}
}
?>