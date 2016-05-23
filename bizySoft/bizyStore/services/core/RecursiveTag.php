<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\OptionsTag;

/**
 * Transform the 'recursive' tag to a useable form.
 * 
 * Takes the self::OPTION_SEPARATOR separated values and transforms them into an associative array with the
 * option itself as the key AND value for look-up purposes.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class RecursiveTag extends OptionsTag
{
	/**
	 * Construct with the name and value of the tag
	 * 
	 * @param string $name
	 * @param string $value
	 */
	public function __construct($name, $value)
	{
		parent::__construct($name);
		
		$options = $this->transform($value);
		
		/*
		 * Set with the specification as the key and value
		 */
		$recursives = array();
		foreach ($options as $option)
		{
			$recursives[$option] = $option;
		}
		$this->tags = $recursives;
	}
}
?>