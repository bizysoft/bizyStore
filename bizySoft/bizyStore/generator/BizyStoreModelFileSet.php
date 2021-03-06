<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\services\core\Config;

/**
 * Acts as a container for BizyStoreModelFile's.
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
class BizyStoreModelFileSet extends ClassFileSet
{
	public function __construct(Config $config)
	{
		parent::__construct($config);
	}
	
	/**
	 * Generate the class definition for the particular Model.
	 *
	 * @return string the class definition.
	 * @codeCoverageIgnore
	 */
	public function generateHeader()
	{
		$classFileContentsHeader = "<?php\n" . self::$licenseContents . "\n\n";
		
		return "";
	}
	
	/**
	 * This is never called.
	 *
	 * @return string
	 * @codeCoverageIgnore
	 */
	public function getFileName()
	{
		return "";
	}
}

?>