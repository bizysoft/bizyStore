<?php
namespace bizySoft\common;

/**
 * Transform an option type tag to a useable form.
 *
 * Option entries are separated by a self::OPTION_SEPARATOR.
 * 
 * Option key/values themselves (if any) can be separated by self::KEY_VALUE_SEPARATOR. LHS is the key, RHS is the value.
 * Derived classes should process the key/values as required.
 * 
 * This class can also be used be used to process tags that just have entries separated by self::OPTION_SEPARATOR.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class OptionsTag extends Tag
{
	const OPTION_SEPARATOR = ";";
	
	const KEY_VALUE_SEPARATOR = "=>";

	/**
	 * Construct with the name of the tag
	 * 
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct($name);
	}
	
	/**
	 * Transform XML #PCDATA options separated by OPTION_SEPARATOR into a zero based array ignoring empty
	 * or whitespace options.
	 *
	 * @param string $bulkOptions
	 */
	public function transform($bulkOptions)
	{
		$options = explode(self::OPTION_SEPARATOR, $bulkOptions);
	
		/*
		 * trim whitespace for each option
		 */
		$result = array();
		foreach ($options as $key => $option)
		{
			$value = trim($option);
			if ($value)
			{
				$result[] = $value;
			}
		}
		return $result;
	}
}
?>