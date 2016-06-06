<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\TagValidator;

/**
 * Validate the &lt;bizyStore&gt; tag.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class BizyStoreTagValidator extends TagValidator
{
	/**
	 * Constructor takes no parameters.
	 * 
	 * Use setKeyValue($key, $value) to initialise.
	 */
	public function __construct()
	{
		/**
		 * DATABASE_TAG is the only mandatory field.
		 */
		$mandatory = array(BizyStoreOptions::DATABASE_TAG);
		parent::__construct($mandatory);
	}
}
?>