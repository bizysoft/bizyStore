<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\ParentTag;

/**
 * Transform the 'relationship' tag to a useable form.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class RelationshipsTag extends ParentTag
{
	/**
	 * Construct with the name of the tag
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct($name);
	}
}
?>