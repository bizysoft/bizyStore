<?php
namespace bizySoft\common;

/**
 * Simple Singleton class that acts as a central place to accumulate any validation errors from config when the application starts.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class ValidationErrors extends Singleton
{
	/**
	 * Keep a cache of all the problems found in the validations.
	 * 
	 * Validations can be for a complete config file.
	 * 
	 * @var string
	 */
	private $badFields = array();
	
	/**
	 * Set up the singleton instance
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Get the errors that the validation run has accumulated.
	 * 
	 * @return array
	 */
	public static function getErrors()
	{
		$validationErrors = self::getInstance();
		return $validationErrors->badFields;
	}
	
	/**
	 * Get the errors that the validation run has accumulated as a string.
	 * 
	 * Each error is separated by a new line.
	 *
	 * @return string
	 */
	public static function getErrorsAsString()
	{
		$result = "";
		
		foreach(self::getErrors() as $errorString)
		{
			$result .= $errorString . "\n";
		}
		
		return $result;
	}
	
	/**
	 * Is there any errors accumulated.
	 * 
	 * @return boolean
	 */
	public static function hasErrors()
	{
		$validationErrors = self::getInstance();
		
		return !empty($validationErrors->badFields);
	}
	
	/**
	 * Clears all previous errors.
	 */
	public static function clearErrors()
	{
		$validationErrors = self::getInstance();
		$validationErrors->badFields = array();
	}
		
	/**
	 * Appends the specified field error.
	 * 
	 * @param string $fieldError
	 */
	public static function addError($fieldError)
	{
		$validationErrors = self::getInstance();
		$validationErrors->badFields[] = $fieldError;
	}
	
	/**
	 * Initialise the Singleton ValidationErrors object
	 */
	public static function configure()
	{
		if (!self::getInstance())
		{
			new ValidationErrors();
		}
	}
}

ValidationErrors::configure();

?>