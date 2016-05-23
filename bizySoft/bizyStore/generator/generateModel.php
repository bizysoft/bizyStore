<?php

use bizySoft\bizyStore\generator\ModelGenerator;

/**
 * Model and Schema class files are automatically generated into the bizySoft/bizyStore/model/&lt;appName&gt; directory 
 * by bizyStore in normal operation, where &lt;appName&gt; is from the bizySoftConfig file.
 * 
 * This is a command line utility program to achieve the same result should you require it.
 *
 * Make sure you have your PHP include_path set to resolve the bizySoft directory.
 * 
 * By default it looks for bizySoft/config/bizySoftConfig.xml file which should include all the config that your 
 * application requires including the &lt;appName&gt;.
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
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */

/*
 * Set some server vars that won't be set through CLI and run the ModelGenerator.
 *
 * This will use bizySoftConfig.xml by default. You can change this to reference another
 * domain specific file if you require, see BizyStoreConfig API doco.
 */
$_SERVER['SERVER_NAME'] = "";
/*
 *  Bootstrap auto-loader by including this file.
 */
include str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");
/*
 * Generate the Model and Schema files
 */
try
{
	$generator = new ModelGenerator();
	
	$generator->generate();
	
	echo "Class generation complete.\n";
}
catch (\Exception $e)
{
	echo $e->getMessage() . "\n";
}

?>