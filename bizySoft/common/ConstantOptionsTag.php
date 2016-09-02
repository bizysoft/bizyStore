<?php
namespace bizySoft\common;

/**
 * Process an option type tag with support for transforming PHP constants.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class ConstantOptionsTag extends OptionsTag
{
	/**
	 * Construct with the name of the tag
	 * @param string $name
	 */
	public function __construct($name, $value)
	{
		parent::__construct($name);
		
		/*
		 * Options always need to be transformed into an array
		 */
		$options = $this->transform($value);
		
		/*
		 * Get the options and transform to PHP constants if required.
		 */
		$result = array();
		foreach ($options as $option)
		{
			$keyValue = explode(self::KEY_VALUE_SEPARATOR , $option);
			$optionKey = trim($keyValue[0]);
			$optionValue = count($keyValue) > 1 ? trim($keyValue[1]) : "";
			$optionKey = $optionKey ? (defined($optionKey) ? constant($optionKey) : $optionKey) : $optionKey;
			$optionValue = $optionValue ? (defined($optionValue) ? constant($optionValue) : $optionValue) : $optionValue;
			$result[$optionKey] = $optionValue;
		}
		$this->tags = $result;
	}
}
?>