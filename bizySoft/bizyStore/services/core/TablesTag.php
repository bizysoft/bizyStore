<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\OptionsTag;

/**
 * Transform the 'tables' tag to a useable form.
 * 
 * Takes the self::OPTION_SEPARATOR separated values and transforms them into a zero based array.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class TablesTag extends OptionsTag
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
		
		$this->tags = $this->transform($value);
	}
}
?>