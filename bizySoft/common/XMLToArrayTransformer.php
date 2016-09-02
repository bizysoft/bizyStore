<?php
namespace bizySoft\common;

use \DOMDocument;
use \DOMNode;
use \Exception;

/**
 * Transform a well formed XML string to a PHP array.
 *
 * By default the array key will be the tag name. Multiple tags of the same name at the
 * same level are overwritten.
 *
 * This not generally what we want so $tagClasses are used, allowing us to validate/manipulate the tag info
 * including the ability to key the tag however we like, eliminating overwrites if required.
 *
 * Does not support XML attributes.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class XMLToArrayTransformer implements GrinderI
{
	/**
	 * @var string the XML to transform.
	 */
	private $xml;
	
	/**
	 * @var array an associative array of tagName => tagClass. The tagClass is used to process a specific tag.
	 */
	private $tagClasses;

	/**
	 * Set the instance variables to allow validation/transformation.
	 *
	 * @param string $xml the well formed XML to process.
	 * @param array $tagClasses an associative array of (tagName => $tagClass, ....).
	 */
	public function __construct($xml, array $tagClasses = array())
	{
		$this->xml = $xml;
		$this->tagClasses = $tagClasses;
	}

	public function setXML($xml)
	{
		$this->xml = $xml;
	}
	/**
	 * Transforms the XML into an array.
	 *
	 * @return array
	 * @throws Exception if XML/Tag validations fail.
	 */
	public function grind()
	{
		$xml = $this->xml;
		$dom = new DOMDocument();
		/*
		 * Don't put any whitespace pollution in the DOM. This is important for straight forward parsing later on.
		 */
		$dom->preserveWhiteSpace = false;
		/*
		 * Supress error/warning output, but collect for later use. 
		 */
		libxml_clear_errors();
		$old_libxml_use_internal_errors = libxml_use_internal_errors(true);
		$dom->loadXML($xml);
		/*
		 * Check for DTD
		 * <!DOCTYPE name SYSTEM systemId>
		 */
		$hasDTD = isset($dom->doctype) && $dom->doctype->name && $dom->doctype->systemId;		
		/*
		 * Validate against a DTD if any is specified in the XML.
		 *
		 * The bizySoft DTD is provided with the distribution in the 'config' directory.
		 * You can point your config file to this DTD eg.
		 * 
		 * <!DOCTYPE bizySoft SYSTEM "file:///var/www/bizySoft/config/bizySoft.dtd">
		 *
		 * Its best to have a DTD so that your overall config file structure can be validated generally.
		 * Specific validations are handled by TagGrinder's which are specified by the $tagClasses constructed with 
		 * this object. 
		 * 
		 * DTD validation is not a requirement, you can have a config file without a DOCTYPE that specifies a DTD. 
		 * If a DTD exists then we attempt to validate the document against it. Any errors are accumulated in 
		 * the ValidationErrors class.
		 * 
		 * Clear the ValidationErrors before we start.
		 */
		ValidationErrors::clearErrors();
		if ($hasDTD && !$dom->validate())
		{			
			$xmlErrors = libxml_get_errors();
			foreach($xmlErrors as $xmlError)
			{
				ValidationErrors::addError("Line $xmlError->line: " . trim($xmlError->message));
			}
		}
		libxml_clear_errors();
		libxml_use_internal_errors($old_libxml_use_internal_errors);
		/*
		 * Continue processing the file even if we have no DTD or the structural DOM validations fail. This can give more 
		 * detailed info.
		 * 
		 * Here we start off with the documentElement. Any childNodes from here on will be related to 
		 * the document itself.
		 * 
		 * Specific validations and transformations are done as we go via the tagClasses and the return value is the 
		 * processed xml in array form.
		 */
		$rootElement = $dom->documentElement;
		$bizySoftTag = $this->traverseXML($rootElement);
		if (ValidationErrors::hasErrors())
		{
			throw new Exception(ValidationErrors::getErrorsAsString());
		}
		/*
		 * Return only the 'contents' of the bizySoft tag.
		 */
		return $bizySoftTag->getValue();
	}

	/**
	 * Recursive method to traverse the XML, storing it's array equivalent as we go.
	 *
	 * @param DOMNode $parentNode The DOMNode at a certain level.
	 * @return Tag representing the XML processed.
	 */
	private function traverseXML(DOMNode $parentNode)
	{
		$nodeName = $parentNode->nodeName;
		/* 
		 * Set up the Tag we will use to process
		 */
		$thisClass = isset($this->tagClasses[$nodeName]) ? $this->tagClasses[$nodeName] : 'bizySoft\common\ParentTag';
		$thisTag = new $thisClass($nodeName);
		/*
		 * Process the Tag with it's children.
		 */
		foreach ($parentNode->childNodes as $child)
		{
			/*
			 * Only process real child nodes.
			 */
			if ($child->nodeType == XML_ELEMENT_NODE)
			{
				if ($this->hasChild($child))
				{
					/*
					 * Recurse child nodes and return the Tag.
					 * The childTag represents ALL tags under the current child tag being processed 
					 * so add them to $thisTag.
					 */
					$childTag = $this->traverseXML($child);
					$thisTag->add($childTag);
				}
				else
				{
					/*
					 * Single child node. These are either ChildTag's or OptionTag's that have a single XML #PCDATA value
					 * which is passed into the constructor.
					 */
					$childName = trim($child->nodeName);
					$childValue = trim($child->nodeValue);
					$childClass = isset($this->tagClasses[$childName]) ? $this->tagClasses[$childName] : 'bizySoft\common\ChildTag';
					
					$childTag = new $childClass($childName, $childValue);
					$thisTag->add($childTag);
				}
			}
		}
		$thisTag->validate();
		
		return $thisTag;
	}

	/**
	 * Determine if the parent node has node children.
	 * 
	 * @param DOMNode $parent
	 * @return boolean true if the parent node has children of type XML_ELEMENT_NODE, false otherwise.
	 */
	private function hasChild($parent)
	{
		if ($parent->hasChildNodes())
		{
			foreach ($parent->childNodes as $child)
			{
				if ($child->nodeType == XML_ELEMENT_NODE)
					return true;
			}
		}
		return false;
	}
}
?>