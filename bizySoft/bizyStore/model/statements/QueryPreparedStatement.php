<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Support for more general queries you write yourself that are not necessarily based on Model objects, 
 * but need to have the security provided by prepared statements.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 * @see QueryStatement for usage
 */
class QueryPreparedStatement extends PreparedStatement
{
	/**
	 * Construct a prepared statement on a database using the query and properties passed in.
	 *
	 * The query must have named parameters specified that match each property key.
	 * 
	 * Note that you can construct with the result of a StatementBuilder::translate() for the query and your
	 * original properties if you require database agnostic queries.
	 * 
	 * @param PDODB $db the database reference associated with this prepared statement.
	 * @param string $query the sql compliant query to execute.
	 * @param array $properties associative array of name/values that match the named parameters.
	 * @param array $options <p>associative array of optional parameters
	 */
	public function __construct($db, $query, $properties = array(), $options = array())
	{
		/*
		 * Set the local class variables first because they are used
		 * by abstract methods in the base class constructor.
		 */
		$this->query = $query;
		$this->properties = $properties;
		
		parent::__construct($db, $options);
	}
	
	/**
	 * This is a no-op, there are no local class members to set or manipulate.
	 */
	protected function initialise()
	{
	}
	
	/**
	 * Build the statement and return it.
	 *
	 * In this case the statement is just the query passed into the constructor.
	 * Note that the properties CANNOT be synchronised to the statement as this is
	 * an un-tagged query.
	 *
	 * @return string the statement
	 */
	protected function buildStatement()
	{
		$this->properties = $this->statementBuilder->translateProperties($this->properties);
		
		return $this->getQuery();
	}
}
?>