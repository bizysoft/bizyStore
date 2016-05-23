<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Concrete class defining methods that are used for generating a set of Schema class files that bizyStore requires.
 *
 * This class forms part of the code generation framework and is only referenced by the ModelGenerator.
 *
 * Generated files from this class are not PSR-4 compliant, all classes are put in the same file. Used as a container for
 * BizyStoreModelSchemaFile instances. Class file generation is not used on this class in normal operation. 
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class BizyStoreModelSchemaFileSet extends ClassFileSet
{
	/**
	 * Generate the class definition for the particular Model.
	 *
	 * @return string the class definition.
	 * @codeCoverageIgnore
	 */
	public function generateHeader()
	{
		$schemaClassFileContentsHeader = "<?php\n" . "namepsace = bizySoft\\bizyStore\\model;\n" . self::$licenseContents . "\n\n";
		
		return $schemaClassFileContentsHeader;
	}
	
	/**
	 * Gets the name of the file we are generating.
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getFileName()
	{
		$bizyStoreInstallDir = BizyStoreConfig::getProperty(BizyStoreOptions::INSTALL_DIR);
		
		return $bizyStoreInstallDir . DIRECTORY_SEPARATOR. "model" . DIRECTORY_SEPARATOR. $this->filePrefix . $this->appName . "Schema.php";
	}
}

?>