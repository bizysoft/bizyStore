<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Concrete class defining methods that are used for generating the Model class files via the ModelGenerator. 
 * 
 * Generated classes represent a database table and provide a mechanism for CRUD functionality. This class forms part 
 * of the code generation framework and is only referenced by the ModelGenerator.
 *
 * Produces class files that are PSR-4 compliant wrt the bizyStore installation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class BizyStoreModelFile extends ClassFile
{
	/**
	 * Construct with class variables.
	 *
	 * @param string $className
	 * @param string $dbId
	 */
	public function __construct($className, $dbId)
	{
		$this->className = $className;
		$this->dbId = $dbId;
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
		$nameSpace = BizyStoreConfig::getAppName();
		$classFileContentsHeader = "<?php\n";
		$classFileContentsHeader .= "\nnamespace bizySoft\\bizyStore\\model" . ($nameSpace ? "\\$nameSpace" : "") . ";";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\model\\core\\ModelSchema;";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\model\\core\\ModelException;";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\model\\core\\DB;";
		$classFileContentsHeader .= "\nuse bizySoft\\bizyStore\\services\\core\\DBManager;\n\n";
		$classFileContentsHeader .= self::$licenseContents . "\n";
		
		return $classFileContentsHeader;
	}
	
	/**
	 * Generate the class definition for the particular Model.
	 *
	 * Includes the properties and implementation methods necessary for bizyStore to provide database services.
	 *
	 * @param ReferencedProperties $referencedProperties
	 * @return string the class definition.
	 */
	public function generateDefinition(ReferencedProperties $referencedProperties = null)
	{
		$className = $this->className;
		$classFileContents = "class " . $className . " extends ModelSchema\n{\n";
		
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
			"\t\t\$db = \$db ? \$db : DBManager::getDB(\"" . $compatibleIds[0] . "\");\n" . 
			"\t\t\$dbId = \$db->getDBId();\n" . 
			"\t\tif (!isset(self::\$commonSchema->compatibleDBIds[\$dbId]))\n" . 
			"\t\t{\n" . 
			"\t\t\tthrow new ModelException(__METHOD__.\" Model class is not compatible with database \$dbId\");\n" . 
			"\t\t}\n" . 
			"\t\tparent::__construct(\$properties ? \$properties : array(), \$db, self::\$commonSchema);\n" . 
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