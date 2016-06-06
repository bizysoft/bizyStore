<?php
namespace bizySoft\bizyStore\model\plugins;

use \PDO;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\Connector;

/**
 * Concrete Connector class for PostgreSQL.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class PgSQL extends Connector
{
	public function __construct()
	{}
	
	/**
	 * Build PostgreSQL connection string and then connect.
	 *
	 * Mandatory fields from bizySoftConfig are:
	 *
	 * + &lt;name&gt; The database name
	 * + &lt;user&gt; The database user
	 * + &lt;password&gt; The database paswword for user.
	 *
	 * these should always appear in the config that is passed to connect() after validation.
	 *
	 * &lt;host&gt; is not mandatory, if left blank PostgreSQL will attempt to connect via the "local" socket configuration. 
	 * You don't have to specify the socket name.
	 * 
	 * Logins for PostgreSQL depend on the settings in pg_hba.conf eg.
	 * 
	 *  TYPE  DATABASE        USER            ADDRESS                 METHOD
	 *  "local" is for Unix domain socket connections only
	 * local   all             all                                     md5
	 *  IPv4 local connections:
	 * host    all             all             127.0.0.1/32            md5
	 * 
	 * This connector plugin is designed to work with the above settings which require a database user and password. 
	 * ie. a METHOD of (md5 or password).
	 * 
	 * Using a METHOD of "peer" will not generally work all the time, the PHP client may run as an operating system 
	 * user that does not have a mapping to a database user or does not have a login at all. You can create mappings in 
	 * "pg_ident.conf" if you wish.
	 * 
	 * @param array $dbConfig an associative array containing the database config information supplied in bizySoftConfig.
	 * @throws PDOException, Exception
	 * @return PDO an instance of the PDO class.
	 */
	public function connect(array $dbConfig)
	{
		$dsn = "pgsql:";
		/*
		 * Mandatory fields.
		 * These are validated by ConnectionManager and are guaranteed to exist.
		 */
		$dbName = $dbConfig[BizyStoreOptions::DB_NAME_TAG];
		$dbUser = $dbConfig[BizyStoreOptions::DB_USER_TAG];
		$dbPassword = $dbConfig[BizyStoreOptions::DB_PASSWORD_TAG];
		/*
		 * Optional fields.
		 * 
		 * Host is only required for TCP/IP connections, postgreSQL will use the socket configured for localhost if no <host>
		 * tag is defined in the bizySoftConfig file.
		 */
		$dbHost = isset($dbConfig[BizyStoreOptions::DB_HOST_TAG]) ? $dbConfig[BizyStoreOptions::DB_HOST_TAG] : null;
		$dbOptions = isset($dbConfig[BizyStoreOptions::PDO_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::PDO_OPTIONS_TAG] : null;
		$dbSchema = isset($dbConfig[BizyStoreOptions::DB_SCHEMA_TAG]) ? $dbConfig[BizyStoreOptions::DB_SCHEMA_TAG] : "";
		$dbPort = isset($dbConfig[BizyStoreOptions::DB_PORT_TAG]) ? $dbConfig[BizyStoreOptions::DB_PORT_TAG] : null;
		/*
		 * Now build the dsn.
		 */
		$dsn .= ($dbHost ? "host=$dbHost;" : "") . ($dbName ? "dbname=$dbName;" : "") . ($dbPort ? "port=$dbPort;" : "");
		/*
		 * Connect to the database
		 */
		$db = new PDO($dsn, $dbUser, $dbPassword, $dbOptions);
		/*
		 * Check existance/permissions for schema
		 */
		if ($dbSchema)
		{
			$schemas = $db->query("SELECT current_schemas(false)");
			$pdoResult = $schemas->fetch(PDO::FETCH_ASSOC);
			$schema = trim($pdoResult["current_schemas"], "{}");
			// Check for access rights to schema specified.
			$schemaForbidden = strpos($schema, $dbSchema) === false;
			if ($schemaForbidden)
			{
				throw new Exception("'$dbUser' does not have access rights to schema '$dbSchema'");
			}
		}
		/*
		 * Set up the charset for the connection.
		 *
		 * Defaults to 'UNICODE' if not specified, which PostgreSQL understands as being utf-8 encoding.
		 */
		$dbCharset = isset($dbConfig[BizyStoreOptions::DB_CHARSET_TAG]) ? $dbConfig[BizyStoreOptions::DB_CHARSET_TAG] : "UNICODE";
		$charsetQuery = "set names '$dbCharset'";
		$db->exec($charsetQuery);
		
		return $db;
	}
}

?>