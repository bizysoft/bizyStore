<?php
namespace bizySoft\common;

/**
 * Base class for all TagValidators.
 * 
 * The standard form of any TagGrinder data is a key and a value. In this case the key is the tag name and the value is
 * an associative array of name/value pairs of the child fields for the tag.
 * 
 * Validations take place on the name/value pairs in the value. The original key and value are returned as
 * an associative array($key => $value)
 * 
 * TagValidator's are TagGrinder's, this class defines a generic validate() method that grind() can call to 
 * validate the fields in a tag. More specific validations should be done in the derived class if required.
 * 
 * Mandatory and optional validations are supported and can be passed into the constructor or set with the 
 * appropriate setter method.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class TagValidator extends TagGrinder
{
	/**
	 * These are the mandatory child fields for a tag.
	 * 
	 * These must be present and have a value.
	 * 
	 * @var array
	 */
	private $mandatory = array();
	
	/**
	 * These are the optional child fields for a tag.
	 * 
	 * If they are present then they must have a value.
	 * 
	 * @var array
	 */
	private $optional = array();

	/**
	 * Can take array's of mandatory and optional fields.
	 * 
	 * Alternatively, use setMandatory() and setOptional().
	 * 
	 * Use setKeyValue($key, $value) to initialise with the data that will be validated.
	 * 
	 * @param array $mandatory mandatory fields for the tag
	 * @param array $optional optional fields for the tag
	 */
	public function __construct(array $mandatory = array(), array $optional = array())
	{
		$this->mandatory = $mandatory;
		$this->optional = $optional;
		parent::__construct();
	}
	
	/**
	 * Set the mandatory fields to validate.
	 * 
	 * @param array $mandatory an array of field names that are mandatory for the tag name
	 */
	protected function setMandatory(array $mandatory)
	{
		$this->mandatory = $mandatory;
	}
	
	/**
	 * Set the optional fields to validate.
	 *
	 * @param array $optional an array of field names that are optional for the tag name
	 */
	protected function setOptional(array $optional)
	{
		$this->optional = $optional;
	}
	
	/**
	 * Default implementation. Validate the key => value set.
	 *
	 * @return array we just return the original key/value set as an associative array.
	 */
	public function grind()
	{
		$this->validate();
	
		return array($this->key => $this->value);
	}
	
	/**
	 * Validate the mandatory and optional fields stored in the value. 
	 * 
	 * @see \bizySoft\common\Grinder::grind()
	 */
	public function validate()
	{
		$key = $this->key;
		$value = $this->value;
		
		$badMandatoryFields = array();

		/*
		 * Check mandatory fields
		 */
		foreach($this->mandatory as $mandatoryField)
		{
			if (array_key_exists($mandatoryField, $value))
			{
				if (empty($value[$mandatoryField]))
				{
					$badMandatoryFields[] = "<$mandatoryField>";
				}
			}
			else
			{
				$badMandatoryFields[] = "<$mandatoryField>";
			}
		}
		/*
		 * Check optional fields
		 */
		$badOptionalFields = array();
		foreach($this->optional as $optionalField)
		{
			if (array_key_exists($optionalField, $value))
			{
				if (empty($value[$optionalField]))
				{
					$badOptionalFields[] = "<$optionalField>";
				}
			}
		}
		/*
		 * Cache error messages if required.
		 */
		if(!empty($badMandatoryFields))
		{
			$fields = implode(",", $badMandatoryFields);
			$message = count($badMandatoryFields) > 1 ? "Mandatory fields $fields are missing." : "Mandatory field $fields is missing.";
			ValidationErrors::addError("<$key> : $message");
		}
		if(!empty($badOptionalFields))
		{
			$fields = implode(",", $badOptionalFields);
			$message = count($badOptionalFields) > 1 ? "Optional fields $fields have no value." : "Optional field $fields has no value.";
			ValidationErrors::addError("<$key> : $message");
		}
	}
}
?>