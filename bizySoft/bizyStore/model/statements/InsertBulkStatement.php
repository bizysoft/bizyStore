<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\DB;

/**
 * Create an insert statement(s) for multiple Model objects in a database.
 * 
 * Uses row constructor syntax to insert multiple rows. 
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class InsertBulkStatement
{
	/**
	 * Database reference for the destination
	 * @var DB
	 */
	private $db = null;

	/**
	 * Qualified table name.
	 * 
	 * @var string
	 */
	private $tableName = null;
	
	/**
	 * Statement builder for the database.
	 * 
	 * @var StatementBuilder
	 */
	private $builder = null;
	
	/**
	 * Array of row constructors for the values clause, keyed on the row constructor spec.
	 * 
	 * @var array
	 */
	private $inserts = array();

	/**
	 * Array of comma separators for construction of the values clause, keyed on the row constructor spec.
	 * 
	 * @var array
	 */
	private $commas = array();
	
	/**
	 * Set class variables.
	 *
	 * @param DB $db The database reference.
	 * @param string $tableName the schema qualified table name.
	 */
	public function __construct(DB $db, $qualifiedTableName)
	{
		$this->db = $db;
		$this->tableName = $qualifiedTableName;
		$this->builder = new StatementBuilder($db);
	}
	
	/**
	 * Add the properties using the row constructor spec as a key.
	 * 
	 * @param array $properties
	 */
	public function add(array $properties)
	{
		/*
		 * Creating a row constructor can be an intensive exercise because it provides a key for all add()'s and may negate any 
		 * performance enhancements that are obtained on the database side in the case of Model's with many properties.
		 * 
		 * We use foreach here in case the builder returns an empty array.
		 */
		foreach ($this->builder->buildRowConstructor($properties) as $constructorKey => $constructorValues)
		{
			$key = $this->builder->translate($constructorKey, $properties);
			$values = $this->builder->translate($constructorValues, $properties);
			if (!isset($this->inserts[$key]))
			{
				$this->inserts[$key] = "";
				$this->commas[$key] = "";
			}
			/*
			 * Append the values to the insert query
			 */

			$this->inserts[$key] .=  $this->commas[$key]. "(". $values . ")";
			$this->commas[$key] = ",";
		}
	}

	/**
	 * Construct an insert statement and execute against the database.
	 */
	public function execute()
	{
		$result = 0;
		foreach ($this->inserts as $rowConstructor => $rowConstructorValues)
		{
			$statement = new QueryStatement(
					$this->db,
					"INSERT INTO " . $this->tableName . "($rowConstructor) VALUES $rowConstructorValues"
			);
			/*
			 * Keep count of the number of rows inserted.
			 */
			$result += $statement->execute();
		}
		return $result;
	}
}
?>