<?php
namespace bizySoft\bizyStore\generator;

/**
 * Base class to support *Schema class file generation.
 * 
 * *Schema class files represent a table but can have information that relates to more than one database
 * if they share a table name (and potentially different schema for the table). 
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class SchemaProperties
{
	const SEQUENCED = "sequenced";
	const NON_SEQUENCED = "non_sequenced";
	
	protected $properties = array();
	
	/**
	 * Add the column schema keyed on the dbId passed in.
	 * 
	 * @param string $dbId
	 * @param array $columnSchema
	 */
	public function add($dbId, array $columnSchema)
	{
		if (!isset($this->properties[$dbId]))
		{
			$this->properties[$dbId] = array();
		}
		
		$this->properties[$dbId][] = $columnSchema;
	}	
	
	/*
	 * Gets the properties stored.
	 */
	public function getProperties()
	{
		return $this->properties;
	}
	
	/**
	 * Generate PHP code for these properties.
	 * 
	 * This massages the stored properties into the required form and returns the PHP code.
	 * Usually a generated array definition.
	 * 
	 * @return string
	 */
	abstract public function codify();
	
	/**
	 * Gets the key candidates for this set of schema properties.
	 * 
	 * The default implementation returns an empty array. Should be over-ridden for specific behaviour.
	 * 
	 * @return array
	 */
	public function keyCandidates()
	{
		return array();
	}
}
?>