<?php
namespace bizySoft\common;

/**
 * Class to represent an option or a property.
 * 
 * A value from a config file for example, may well be null or resolve to boolean false. If we just returned a value
 * then we would not be able to resolve the difference between a null value and a key which didn't exist.
 * 
 * This class is used to return a definitive value for a key. 
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class Option
{
	/**
	 * The option key.
	 */
	public $key = null;
	
	/**
	 * The option value.
	 *
	 * Note that this can be an object or an array not just a single value.
	 */
	public $value = null;

	/**
	 * Sets the class variables.
	 * 
	 * @param string $key
	 * @param mixed $value
	 */
	public function __construct($key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}
}
?>