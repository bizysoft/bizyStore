<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\ParentTag;

/**
 * Transform the &lt;database&gt; tag to a useable form.
 *
 * Databases from the bizySoftConfig file are keyed on their unique &lt;id&gt; field.
 * This class associates the key as being the &lt;id&gt; field.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class DatabaseTag extends ParentTag
{
	/**
	 * Construct with the name of the tag
	 * 
	 * @param string $name
	 */
	public function __construct($name)
	{
		parent::__construct($name);
		$this->validator = new DatabaseTagValidator();
	}
	
	/**
	 * Overrides base class method to allow multiple instances of the database tag to be stored.
	 * 
	 * The key is based on the &lt;id&gt; tag from bizySoftConfig which is a mandatory field so will be validated.
	 * 
	 * @return string the value of the &lt;id&gt; tag.
	 */
	public function getKey()
	{
		return isset($this->tags[BizyStoreOptions::DB_ID_TAG]) ? $this->tags[BizyStoreOptions::DB_ID_TAG] : $this->name;
	}
	
	/**
	 * Indicate that multiple instances of this Tag should not be overwritten.
	 */
	public function isUnique()
	{
		return true;
	}
}
?>