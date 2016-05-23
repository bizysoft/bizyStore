<?php
/**
 * This is the raw bootstrap file for bizyStore.
 * 
 * You can use this bootstrap file if you just require raw PDO connections to your database(s) based on bizySoftConfig 
 * without any bizyStore Model or Schema support. 
 * 
 * ConnectionManager can be used directly to connect to databases via bizySoftConfig.
 * 
 * ie. $pdoConnection = ConnectionManager::getConnection($dbId); // Where $dbId is from bizySoftConfig
 * 
 * Registers the base class auto-loader and initialises the required components on a once off basis (BizyStoreConfig). 
 * The directory that contains the 'bizySoft' directory must be on your include_path so that the bizySoftConfig file 
 * can be found and class files can be loaded by the auto-loader.
 *
 * It's recommended practice for the 'bizySoft' directory to be OUTSIDE the DOCUMENT_ROOT of your web server to protect 
 * sensitive information in your bizySoftConfig file. You must still give your web server or cli program write access to 
 * the bizySoft directories or logging if required.
 * 
 * eg. for a local installation, if the standard DOCUMENT_ROOT for Apache on Ubuntu is '/var/www/html' then you may want 
 * to install the 'bizySoft' directory into '/var/www' and add '/var/www' to the PHP include_path so that the 'bizySoft' 
 * directory is resolvable. This will directory will vary for hosted environments and will usually be your hosted account's
 * home directory which is a level above your DOCUMENT_ROOT (public_html directory).
 * 
 * It is only needed to include this file in your entry level php files that are directly called by a web server 
 * or CLI program. You should not need to include any other file if your code uses 'namespace' and 'use' statements that 
 * comply to PSR-4.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
use bizySoft\bizyStore\services\core\BizyStoreConfig;
/*
 * include the auto-loader before registration.
 */
include str_replace("/", DIRECTORY_SEPARATOR, 'bizySoft/common/IncludePathAutoloader.php');
/*
 * Register the loader.
 */
spl_autoload_register( array( new IncludePathAutoloader(), 'load'));
/*
 * Configure bizyStore
 */
BizyStoreConfig::configure();

?>