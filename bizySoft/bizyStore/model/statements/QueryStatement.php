<?php
namespace bizySoft\bizyStore\model\statements;


/**
 * Support for more general queries you write yourself that are not neccessarily based on Model objects, or prepared statements.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class QueryStatement extends Statement
{
	/**
	 * Construct using the db and query passed in.
	 *
	 * @param DB $db the database reference associated with this statement.
	 * @param string $query the query to be executed.
	 * @param array $options the options for the query.
	 */
	public function __construct($db, $query, $options = array())
	{
		parent::__construct($db, $options);
		
		$this->query = $query;
	}
}
?>