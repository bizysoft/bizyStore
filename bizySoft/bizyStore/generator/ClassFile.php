<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\BizyStoreConstants;
use bizySoft\bizyStore\services\core\Config;

/**
 * Abstract base class specifying behaviour used for generating the Model and Schema class files.
 *
 * This class forms part of the code generation framework and is only referenced by the ModelGenerator.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class ClassFile implements BizyStoreConstants
{
	private static $config = null;
	
	public function __construct(Config $config = null)
	{
		if (!self::$config && $config)
		{
			self::$config = $config;
		}
	}
	
	/**
	 * Gets the application's config
	 * @return Config
	 */
	protected function getConfig()
	{
		return self::$config;
	}
	/**
	 * Licence info per file.
	 *
	 * @var string
	 */
	protected static $licenseContents = "/**
 * Generated code. Don't edit.
 */";
	
	/**
	 * Table schema including required column names for generation of Model classes.
	 *
	 * @var array
	 */
	public $schema = array();
	
	/**
	 * The table names from the database. The case of a table name can be different to the className so we 
	 * store them per dbId.
	 *
	 * @var array
	 */
	public $tableNames = array();
	
	/**
	 * The name of the class to be generated.
	 * Derived from the table name.
	 * 
	 * @var string
	 */
	public $className = "";
	
	/**
	 * The id of the database
	 * 
	 * @var string
	 */
	public $dbId = "";
	
	/**
	 * Generate the header of the file.
	 *
	 * This includes PHP tag's, licence information etc.
	 *
	 * @return string
	 */
	public abstract function generateHeader();
	
	/**
	 * Generate the class definition for the particular Model.
	 *
	 * @param ReferencedProperties $referencedProperties
	 * @return string the class definition.
	 */
	public abstract function generateDefinition(ReferencedProperties $referencedProperties = null);
	
	/**
	 * Gets the name of the file we are generating.
	 *
	 * @return string
	 */
	public abstract function getFileName();
	
	/**
	 * Main method to generate a class file.
	 *
	 * @param ReferencedProperties $referencedProperties
	 * @return string the file contents.
	 */
	public function generateFile(ReferencedProperties $referencedProperties = null)
	{
		$fileContents = $this->generateHeader();
		$fileContents .= $this->generateDefinition($referencedProperties);
		$fileContents .= $this->generateTail();
		
		return $fileContents;
	}
	
	/**
	 * Generates the tail of the file.
	 *
	 * This just includes the PHP end tag.
	 *
	 * @return string
	 */
	public function generateTail()
	{
		return "?>\n";
	}
}

?>