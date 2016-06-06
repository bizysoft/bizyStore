<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Abstract base class specifying behaviour used for generating a set of Model and Schema class files.
 *
 * This class forms part of the code generation framework and is only referenced by the ModelGenerator.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
abstract class ClassFileSet extends ClassFile
{
	/**
	 * An array of ClassFiles.
	 *
	 * @var array
	 */
	public $classFiles = array();
	
	/**
	 * The name of the application from the bizySoftConfig file.
	 * 
	 * @var string
	 */
	public $appName;
	
	/**
	 * Set class variables from the bizySoftConfig file.
	 */
	public function __construct()
	{
		$appName = BizyStoreConfig::getAppName();
		if ($appName)
		{
			$appName = ucfirst($appName);
		}
		
		$this->appName = $appName;
	}
	
	/**
	 * Main method to generate the class definitions for a set of ClassFiles.
	 *
	 * @param ReferencedProperties $referencedProperties
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function generateDefinition(ReferencedProperties $referencedProperties = null)
	{
		$result = "";
		foreach ($this->classFiles as $classFile)
		{
			$result .= $classFile->generateDefinition($referencedProperties);
		}
		return $result;
	}
}

?>