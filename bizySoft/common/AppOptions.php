<?php
namespace bizySoft\common;
/**
 * Specifies the general tag/property names that would possibly be used for an App.
 * 
 * They are used as common config keys that form the AppConfig.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 *         
 */
interface AppOptions
{
	/**
	 * The root XML tag for bizySoft AppConfig.
	 *
	 * @var string
	 */
	const BIZYSOFT_TAG = "bizySoft";
	
	/**
	 * The xml tag for the name of your application in AppConfig.
	 *
	 * @var string
	 */
	const APP_NAME_TAG = "appName";
	/**
	 * This is a field name for the AppConfig file name that has been loaded.
	 * 
	 * It is a derived field based on the include_path and does not appear in any config file
	 * 
	 * @var string
	 */
	const CONFIG_FILE_NAME = "configFileName";
	
	/**
	 * You can reference an external config file within the bizySoftConfig file by using this tag
	 * to point to the other config file.
	 * 
	 * This is useful if you must put the bizySoft installation into the DOCUMENT_ROOT to eliminate 
	 * problems associated with setting the include_path in shared environments. You can reference the 
	 * file that has all your database config outside the DOCUMENT_ROOT to keep it safe.
	 * 
	 * @var string
	 */
	const REFERENCED_CONFIG_FILE = "referencedConfigFile";
	
	/**
	 * This is a field name for the App's installation directory.
	 * 
	 * It is a derived field based on the include_path and does not appear in any config file.
	 * 
	 * @var string
	 */
	const INSTALL_DIR = "installDir";
	
	/**
	 * Tag name for specifying app options.
	 *
	 * @var string
	 */
	const OPTIONS_TAG = "options";
	
	/**
	 * Tag names for valid options
	 * 
	 * @var string
	 */
	const OPTION_INCLUDE_PATH = "includePath";
	const OPTION_LOG_FILE = "logFile";
}
?>