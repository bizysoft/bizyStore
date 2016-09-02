<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\Config;

/**
 * Concrete class defining methods that are used for generating the Model class files via the ModelGenerator. 
 * 
 * Generated classes that represent a database table providing a mechanism for CRUD functionality. This class forms part 
 * of the code generation framework and is only referenced by the ModelGenerator.
 *
 * Produces class files that are PSR-4 compliant wrt the bizyStore installation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class BizyStoreModelFile extends ClassFile
{
	/**
	 * Construct with class variables.
	 *
	 * @param string $className
	 * @param string $dbId
	 * @param array $config The application's config 
	 */
	public function __construct($className, $dbId, Config $config = null)
	{
		$this->className = $className;
		$this->dbId = $dbId;
		parent::__construct($config);
	}
	
	/**
	 * Generate the header of the file.
	 *
	 * The header includes everything before the 'class' definition like PHP tag's,
	 * namespaces use statements and licence information etc.
	 *
	 * @return string the header contents.
	 */
	public function generateHeader()
	{
		$config = $this->getConfig();
		$nameSpace = $config->getModelNamespace();
		$classFileContentsHeader = "<?php\n";
		$classFileContentsHeader .= "\nnamespace $nameSpace;\n";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\model\\core\\ModelSchema;";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\model\\core\\DB;\n\n";
		$classFileContentsHeader .= self::$licenseContents . "\n";
		
		return $classFileContentsHeader;
	}
	
	/**
	 * Generate the class definition for the particular Model.
	 *
	 * Associates the Model with a particular Schema.
	 *
	 * @param ReferencedProperties $referencedProperties
	 * @return string the class definition.
	 */
	public function generateDefinition(ReferencedProperties $referencedProperties = null)
	{
		$className = $this->className;
		$classFileContents = "\nclass " . $className . " extends ModelSchema\n{\n";
		
		$compatibleIds = array_keys($this->schema);
		
		$classFileContents .= 
			"\tprivate static \$commonSchema = null;\n\n" . 
			"\tpublic function __construct(\$properties = null, DB \$db = null)\n" . 
			"\t{\n" . 
			"\t\tif (!self::\$commonSchema)\n" . 
			"\t\t{\n" . 
			"\t\t\t//Optimise execution time by synching schema only once per Model class\n" . 
			"\t\t\tself::\$commonSchema = new " . $this->className . "Schema();\n" . 
			"\t\t}\n\n" . 
			"\t\tparent::__construct(\$properties ? \$properties : array(), self::\$commonSchema, \$db);\n" . 
			"\t}\n" . 
			"}\n";
		
		return $classFileContents;
	}
	
	/**
	 * Gets the name of the file we are generating.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->className . ".php";
	}
}

?>