<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * Interface to declare Statement constants.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
interface StatementConstants
{
	/***********************************************************************
	 * Statement options.
	 */
	const OPTION_FUNCTION = "function";
	const OPTION_CLASS_ARGS = "classArgs";
	const OPTION_CLASS_NAME = "className";

	/***********************************************************************
	 * Valid fetch types supported.
	 * 
	 * These are used for iterating over result sets
	 */
	const FETCH_TYPE_ARRAY = "array";
	const FETCH_TYPE_ASSOC = "assoc";
	const FETCH_TYPE_OBJECT = "object";
	const FETCH_TYPE_FUNCTION = "function";
}

?>