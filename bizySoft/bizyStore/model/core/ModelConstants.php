<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Public constants that are used as option keys for Model operations.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface ModelConstants
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
	
	/**
	 * The property that specifies the optimistic locking property in the Model's schema.
	 *
	 * Set this in the options to indicate which property to use for the versioned lock value. To use this property,
	 * the Model's table schema must have a column that stores the versioned value.
	 *
	 * @var string
	 */
	const OPTION_LOCK_PROPERTY = "lockProperty";
	
	/**
	 * The property for the optimistic locking mode.
	 *
	 * Set this in the options to indicate which mode to use for optimistic locking.
	 *
	 * Can be LOCK_MODE_LOCAL or LOCK_MODE_DATABASE
	 *
	 * @var string
	 */
	const OPTION_LOCK_MODE = "lockMode";
	
	/**
	 * OPTION_LOCK_MODE options.
	 *
	 * @var string
	 */
	const LOCK_MODE_LOCAL = "local";
	const LOCK_MODE_DATABASE = "database";
}
?>