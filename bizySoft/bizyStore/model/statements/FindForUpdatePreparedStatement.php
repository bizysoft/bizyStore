<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\Model;

/**
 * Concrete class for issuing a pessimistic lock on Model's that you need to modify.
 *
 * This locks the Model row(s) found so that the modification can be completed without external interference.
 *
 * Calling code should unlock the Model row(s) after update with endTransaction() or calling commit() or 
 * rollBack() from a DBTransaction object.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class FindForUpdatePreparedStatement extends FindPreparedStatement
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
	 * Build a select for update statement for the Model and return it.
	 *
	 * Selecting for update is database specific so defer to the database.
	 *
	 * @return string the statement
	 */
	public function buildStatement()
	{
		$properties = $this->properties;
		$statement = $this->db->buildSelectForUpdateStatement($this->modelObj->getTableName(), $properties, $this->statementBuilder);
		
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