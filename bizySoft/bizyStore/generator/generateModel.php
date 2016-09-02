<?php

use bizySoft\bizyStore\generator\ModelGenerator;
use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Model and Schema class files are automatically generated into the bizySoft/bizyStore/app/&lt;appName&gt; directory 
 * by bizyStore in normal operation, where &lt;appName&gt; is from bizySoftConfig.
 * 
 * This is a command line utility program to achieve the same result should you require it.
 *
 * Make sure you have your PHP include_path set to resolve the bizySoft directory.
 * 
 * It looks for either bizySoft/config/generator.xml or bizySoft/config/bizySoftConfig.xml in that order which should 
 * include all the config that your application requires.
 *
 * Run this from the place that contains the bizySoft directory like so:
 *
 * php bizySoft/bizyStore/generator/generateModel.php
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */

/*
 * Set some server vars that won't be set through CLI and run the ModelGenerator.
 *
 * This will use generator.xml if it exists or the default bizySoftConfig.xml file.
 */
$_SERVER['SERVER_NAME'] = "generator";
/*
 *  Bootstrap auto-loader by including this file.
 */
include str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");
/*
 * Generate the Model and Schema files
 */
try
{
	$config = BizyStoreConfig::getInstance();
	
	$generator = new ModelGenerator($config);
	
	$generator->generate();
	
	echo "Class generation complete.\n";
}
catch (\Exception $e)
{
	echo $e->getMessage() . "\n";
}

?>