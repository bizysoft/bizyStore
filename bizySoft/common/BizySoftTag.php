<?php
namespace bizySoft\common;

/**
 * Transform/validate the &lt;bizySoft&gt; tag.
 * 
 * Since this is the root tag from the XML file it has no structural use other than being a container.
 * It does however have a use for validating the mandatory &lt;appName&gt; field within but does not become part of 
 * the config stored in memory.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class BizySoftTag extends Tag
{
	/**
	 * Construct with the name of the tag
	 * 
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct($name);
		$this->validator = new BizySoftTagValidator();
	}
}
?>