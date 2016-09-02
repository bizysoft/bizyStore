<?php
namespace bizySoft\bizyStore\generator;

use \Exception;

/**
 * Base class with common methods for all Generators.
 * 
 * <span style="color:orange">bizySoftConfig files can contain SENSITIVE INFORMATION and should be SECURED so that the web server 
 * never serves them as content. The recommended way of doing this is to install the 'bizySoft' directory outside the 
 * DOCUMENT_ROOT of your web server.</span>
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. Details at</span> <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class Generator
{
	/**
	 * Generation usually happens from an array.
	 */
	protected abstract function generate(array $generateFrom = array());
	/**
	 * Checks if a directory exists and creates it if possible.
	 * 
	 * @param string $directory
	 */
	protected function createDirectory($directory)
	{
		if (!file_exists($directory))
		{
			if (!mkdir($directory))
			{
				/*
				 * We can't create the required model directory, so bail.
				 */
				throw new Exception("Unable to create $directory");
			}
		}
		if (!is_writable($directory))
		{
			/*
			 * We need to throw here because we have no place to write files to.
			 */
			throw new Exception("Unable to write files to $directory");
		}
	}
}
?>