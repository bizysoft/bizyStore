<?php
namespace bizySoft\bizyStore\model\plugins;

use \PDO;
use bizySoft\bizyStore\services\core\Connector;

/**
 * Concrete Connector class for MySql.
 *
 * If you are using MySQL on windows machines and your database will use mixed case database entities, then you
 * need the MYSQL config item.
 *
 * + lower_case_table_names = 2
 *
 * to be set in my.ini file under the [mysqld] section.
 *
 * This is not a requirement for bizyStore to operate, but IS a requirement when you expect MySQL to preserve case of
 * your table/column names when creating them via a CREATE TABLE statement. bizyStore does not create tables and columns 
 * in normal operation, it uses your existing database entity definitions.
 *
 * charsets/encoding: At this time, the full Unicode character set is supported if:
 *
 * + your tables are declared with DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci.
 * + String based columns that are subject to internationalisation are declared with CHARACTER SET utf8mb4.
 * + The database connection encoding is initialised with utf8mb4.
 *
 * The database's charset does not seem to matter, the connection encoding and table/column declarations are the
 * overriding factors. The default connection encoding for bizyStore is 'utf8mb4' unless you specify another &lt;charset&gt;
 * for the connection in the bizySoftConfig file.

 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class MySQL extends Connector
{
	public function __construct()
	{}
	
	/**
	 * Build MySQL connection string and then connect.
	 *
	 * Mandatory fields from bizySoftConfig are:
	 *
	 * + &lt;name&gt;      The database name
	 * + &lt;user&gt;      The database user
	 * + &lt;password&gt;  The database password for user.
	 *
	 * these will always appear in the config that is passed to connect().
	 *
	 * Note that the &lt;host&gt; variable from bizySoftConfig is not mandatory, if none is specified then MySQL will use
	 * localhost and connect via the MySQL default socket configuration. Also, the &lt;socket&gt; variable from bizySoftConfig can 
	 * be used to connect to a specific domain socket on 'nix systems or a named pipe on Windows systems. A specific &lt;socket&gt; 
	 * config entry is only allowed on localhost so you don't require &lt;host&gt; in this case.
	 *
	 * There is no notion of a schema in MySQL so we don't process them.
	 * 
	 * @param array $dbConfig <p>an associative array containing the
	 *        database config information supplied in bizySoftConfig.</p>
	 * @throws \PDOException
	 * @return \PDO an instance of the PDO class.
	 */
	public function connect(array $dbConfig)
	{
		$dsn = "mysql:";
		/*
		 * Mandatory fields.
		 * These are validated when read from the config file and are guaranteed to exist.
		 */
		$dbName = $dbConfig[self::DB_NAME_TAG];
		$dbUser = $dbConfig[self::DB_USER_TAG];
		$dbPassword = $dbConfig[self::DB_PASSWORD_TAG];
		/*
		 * Optional fields.
		 */
		$dbHost = isset($dbConfig[self::DB_HOST_TAG]) ? $dbConfig[self::DB_HOST_TAG] : "";
		$dbPort = isset($dbConfig[self::DB_PORT_TAG]) ? $dbConfig[self::DB_PORT_TAG] : "";
		$dbOptions = isset($dbConfig[self::PDO_OPTIONS_TAG]) ? $dbConfig[self::PDO_OPTIONS_TAG] : null;
		$dbSocket = isset($dbConfig[self::DB_SOCKET_TAG]) ? $dbConfig[self::DB_SOCKET_TAG] : null;
	
		if ($dbSocket)
		{
			/*
			 * 'socket' takes precedence, always on localhost.
			 */
			$dsn .= "unix_socket=$dbSocket;dbName=$dbName;";
		}
		else
		{
			$dsn .= ($dbHost ? "host=$dbHost;" : "") . "dbname=$dbName;" . ($dbPort ? "dbport=$dbPort;" : "");
		}
		$dbUser = isset($dbConfig[self::DB_USER_TAG]) ? $dbConfig[self::DB_USER_TAG] : "";
		$dbPassword = isset($dbConfig[self::DB_PASSWORD_TAG]) ? $dbConfig[self::DB_PASSWORD_TAG] : "";
		/*
		 * Connect to the database
		 */
		$db = new PDO($dsn, $dbUser, $dbPassword, $dbOptions);
		/*
		 * Set up the charset
		 */
		$dbCharset = isset($dbConfig[self::DB_CHARSET_TAG]) ? $dbConfig[self::DB_CHARSET_TAG] : "utf8mb4";
		$charsetQuery = "set names '$dbCharset'";
		$db->exec($charsetQuery);
	
		return $db;
	}
}

?>