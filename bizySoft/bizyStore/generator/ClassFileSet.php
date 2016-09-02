<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\Config;

/**
 * Abstract base class. Holds a set of class files.
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
abstract class ClassFileSet extends ClassFile
{
	/**
	 * An array of ClassFiles.
	 *
	 * @var array
	 */
	public $classFiles = array();
	
	/**
	 * Set class variables from the bizySoftConfig file.
	 */
	public function __construct(Config $config)
	{
		parent::__construct($config);
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
		return "";
	}
}

?>