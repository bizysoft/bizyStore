<?php
namespace bizySoft\common;

/**
 * Validate the tags under the root node of &lt;bizySoft&gt;.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class BizySoftTagValidator extends TagValidator
{
	/**
	 * Default constructor.
	 * 
	 * Use setKeyValue($key, $value) to initialise with the data that will be validated.
	 */
	public function __construct()
	{
		/**
		 * APP_NAME_TAG is the only mandatory field.
		 */
		$mandatory = array(AppOptions::APP_NAME_TAG);
		parent::__construct($mandatory);
	}
}
?>