<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Public methods that absolutley must be implemented for bizyStore base-class Model algorithms to work properly.
 *
 * Generally, these are methods that are defined in ModelSchema or the generated implementation class which 
 * refers to the DB and Schema information.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
interface ModelI
{
	/**
	 * Gets the database reference for this Model
	 *
	 * @return DB
	 */
	public function getDB();
	
	/**
	 * Gets the database id for this Model that is specified in bizySoftConfig.
	 *
	 * @return string
	 */
	public function getDBId();
	
	/**
	 * Gets the database Id that this Model has as a default
	 *
	 * @return string
	 */
	public function getDefaultDBId();
	
	/**
	 * Gets the database Id's that this Model is compatible with
	 *
	 * @return string
	 */
	public function getCompatibleDBIds();
	
}
?>