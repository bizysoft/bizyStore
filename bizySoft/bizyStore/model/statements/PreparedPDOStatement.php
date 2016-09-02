<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * A simple public wrapper on PDOStatement for storing the properties that a statement was prepared with.
 * 
 * This allows us to correctly compare the properties that have been prepared, against the properties that we are 
 * using at run time. See PreparedStatement::prepare().
 * 
 * Although most bizyStore Model methods are safe guarded in this respect, for the times when you are building your 
 * own queries, properties can get out of sync with the query easily if you provide incorrect keys or the incorrect
 * number of properties.
 * 
 * Takes care of some deficiencies in database drivers that don't map the number of named parameters properly, giving
 * erroneous results when there is a deficient number of parameters for the statement. All databases that we have 
 * implemented interfaces for exhibit this tendency to a varying degree, which can produce some serious confusion AND 
 * undesirable behaviour differing with the PDO::ATTR_EMULATE_PREPARES setting. In any case, the behaviour is nowhere 
 * near consistent across drivers.
 * 
 * This class allows us to produce more relevant error messages than it seems PDO can provide.
 * 
 */
class PreparedPDOStatement
{
	public $executableProperties;
	
	public $pdoStatement;
}
?>