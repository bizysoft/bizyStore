<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Public constants that are used as option keys.
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
interface ModelOptions
{
	/**
	 * Index by database key specifier for find operations.
	 * 
	 * @var string
	 */
	const OPTION_INDEX_KEY = "__option_indexKey";
	
	/**
	 * Use this to append a tagged query portion to a Model find().
	 * 
	 * @var string
	 */
	const OPTION_APPEND_CLAUSE = "__append_clause";
}
?>