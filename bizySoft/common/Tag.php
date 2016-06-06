<?php
namespace bizySoft\common;

/**
 * Base class for validation and transformation of XML tags into arrays.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
abstract class Tag
{
	/**
	 * The tag name.
	 * 
	 * @var string
	 */
	protected $name = null;

	/**
	 * Tags can either have a single value or more than one value and can be indexed by a unique key.
	 * 
	 * @var array
	 */
	protected $tags = null;
	
	/**
	 * The TagValidator associated with this Tag.
	 * 
	 * @var TagValidator
	 */
	protected $validator = null;
	
	/**
	 * Construct with the name.
	 * 
	 * @param string $name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	/**
	 * Add the child Tag details into this Tag's array.
	 * 
	 * Generally, a Tag will contain all it's children's Tag details as an array under the child Tag's name.
	 * 
	 * Additionally, if isUnique() returns true for the $tag passed in then it is stored under $tag's name augmented 
	 * via a key to prevent data being overwritten. The key can be either automatic (integer) or generated via $tag->getKey();
	 * 
	 * @param Tag $tag 
	 */
	public function add(Tag $tag)
	{
		$tagName = $tag->name;
		
		if ($tag->isUnique())
		{
			/*
			 * This indicates that the Tag value is not to be overwritten, but placed with its own key under the tag name.
			 * 
			 * Make sure the array is set up
			 */
			if (!isset($this->tags))
			{
				$this->tags = array();
			}
			if (!isset($this->tags[$tagName]))
			{
				$this->tags[$tagName] = array();
			}
			/*
			 * Try and get a unique key
			 */
			$tagKey = $tag->getKey();
			
			if($tagName !== $tagKey)
			{
				/*
				 * This indicates that the tag has generated it's own key.
				 * 
				 * Check key, then add if unique.
				 */
				if (isset($this->tags[$tagName][$tagKey]))
				{
					ValidationErrors::addError("Key '$tagKey' for <$tagName> is not unique.");
				}
				else
				{
					$this->tags[$tagName][$tagKey] = $tag->getValue();
				}
			}
			else
			{
				/*
				 * Add the tag under an integer key
				 */
				$this->tags[$tagName][] = $tag->getValue();
			}
		}
		else
		{
			/*
			 * The default is a single value for the tag under the tag name.
			 * This overwrites any previous data.
			 */
			$this->tags[$tagName] = $tag->getValue();
		}
	}
	
	/**
	 * Validates the tag.
	 */
	public function validate()
	{
		$validator = $this->validator;
		if ($validator)
		{
			$validator->setKeyValue($this->name, $this->tags);
			$validator->grind();
		}
	}
	
	/**
	 * Gets the value(s) for this Tag.
	 * 
	 * Override for specialist behaviour.
	 * 
	 * @return array
	 */
	public function getValue()
	{
		return $this->tags;
	}
	
	/**
	 * Gets the key that the parent indexes the tag with.
	 * 
	 * This is the default implementation which returns the name of the tag. 
	 * 
	 * Key's returned by getKey() should be unique, but can take on the name of the Tag to allow integer 
	 * indexing if isUnique() returns true.
	 * 
	 * Override for specialist behaviour.
	 * 
	 * @return string
	 */
	public function getKey()
	{
		return $this->name;
	}
	
	/**
	 * Unique indicator to determine if the tag should be added as a unique instance via a key. 
	 *  
	 * @return boolean the default is to return false. Override where required.
	 */
	public function isUnique()
	{
		return false;
	}
}
?>