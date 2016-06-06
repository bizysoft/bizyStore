<?php
namespace bizySoft\common;

/**
 * Abstract base class which defines the structure and initialisation of all TagGrinders.
 * 
 * Grinding can include tag validation or transformation.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
abstract class TagGrinder implements GrinderI
{
	/**
	 * The key is usually, but not necessarily the tag name.
	 * 
	 * @var string
	 */
	protected $key;
	/**
	 * The value of a tag which may have children, so can be either an array or a single value.
	 * 
	 * @var mixed
	 */
	protected $value;
	
	/**
	 * Default constructor takes no parameters.
	 *
	 * Use setKeyValue($key, $value) to set the key and value that grind() will work on.
	 */
	protected function __construct()
	{}
	
	/**
	 * All TagGrinders are set with a key value pair.
	 * 
	 * grind() will work on these and return a key/value array which may or may not be the same as the 
	 * parameters set.
	 * 
	 * @param string $key the key is usually, but not necessarily the tag name, it may or may not have a use as part of 
	 * grind()'s return value.
	 * 
	 * @param mixed $value The tag's value, can be an array or single value.
	 */
	public function setKeyValue($key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}
}
?>