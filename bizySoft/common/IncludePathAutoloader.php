<?php
/**
 * PSR-4 auto-loader. Searches the include_path for the file specified by $className. 
 * 
 * Requires classes to be defined in their own ".php" file. The $className must resolve to the class file on the file system 
 * when augmented by the include_path.
 *
 * The $className parameter is passed in by the SPL from either the current namespace, use statement or explicit specification.
 *
 * Only called when the SPL needs the definition of a class, therefore it will be called once and only once for each undefined 
 * class that the SPL needs on demand.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you.</span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class IncludePathAutoloader
{
	/**
	 * Load the PSR-4 based className.
	 *
	 * @param string $className the fully qualified name of the class required to be loaded.
	 * @return boolean a return value of boolean false from load() or appLoad() indicates to the spl auto-loader 
	 * queue that the class could not be loaded.
	 */
	public final function load($className)
	{
		$loaded = false;
		/*
		 * $className is the path of the class in PHP namespace speak, that is a path using "\" as a 
		 * path separator. 
		 * 
		 * We need to replace that with the directory separator of the underlying 
		 * operating system. On 'nix systems this changes the separator to "/"
		 */
		$fileName = str_replace("\\", DIRECTORY_SEPARATOR, $className);
		/*
		 * Check if the file exists. We don't want to force an include on a file that does not exist. 
		 * In this case we call the specific appLoad() which will return a boolean. 
		 */
		$classFile = stream_resolve_include_path($fileName . ".php");
		if ($classFile)
		{
			include $classFile;
			$loaded = true;
		}
		else
		{
			$loaded =  $this->appLoad($className);
		}
				
		return $loaded;
	}
	
	/**
	 * Some app specifc processing may be required.
	 *
	 * This method can be over-ridden and should attempt to load a missing class with any app specific
	 * considerations. See bizySoft\bizyStore\services\core\bootstrap.php
	 *
	 * Defaulted here to return false.
	 *
	 * @param string the name spaced name of the class to load.
	 * @return boolean indicator if the class was loaded.
	 */
	protected function appLoad($className)
	{
		return false;
	}
	
	
}

?>