<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\AppOptions;

/**
 * Specific XML tags and config array keys used by bizyStore.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 * 
 */
interface BizyStoreOptions extends AppOptions
{
	/*
	 * XML/array keys for config items
	 */
	const BIZYSTORE_TAG = "bizyStore";
	const DATABASE_TAG = "database";
	const DB_CHARSET_TAG = "charset";
	const DB_HOST_TAG = "host";
	const DB_ID_TAG = "id";
	const DB_INTERFACE_TAG = "interface";
	const DB_NAME_TAG = "name";
	const DB_PASSWORD_TAG = "password";
	const DB_PORT_TAG = "port";
	const DB_RELATIONSHIPS_TAG = "relationships";
	const DB_SCHEMA_TAG = "schema";
	const DB_SOCKET_TAG = "socket";
	const DB_TABLES_TAG = "tables";
	const DB_USER_TAG = "user";
	const MODEL_PREPARE_OPTIONS_TAG = "modelPrepareOptions";
	const PDO_OPTIONS_TAG = "pdoOptions";
	const PDO_PREPARE_OPTIONS_TAG = "pdoPrepareOptions";
	const REL_FOREIGN_KEYS_TAG = "foreignKeys";
	const REL_RECURSIVE_TAG = "recursive";
	
	/*
	 * Valid option names
	 */
	const OPTION_CLEAN_UP = "cleanUp";
	const OPTION_PREPARE_PREFIX = "preparePrefix";
	/*
	 * Valid clean up option values
	 */
	const OPTION_COMMIT = "commit";
	const OPTION_ROLLBACK = "rollback";
	/*
	 * Valid modelPrepareOption names
	 */
	const OPTION_CACHE = "cache";
	

	/*
	 * Derived BizyStoreConfig items that don't appear in the bizySoftConfig file
	 */
	const BIZYSTORE_MODEL_DIR = "bizyStoreModelDir";
	const BIZYSTORE_MODEL_NAMESPACE = "bizyStoreModelNamespace";
}
?>