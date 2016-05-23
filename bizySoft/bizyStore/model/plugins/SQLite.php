<?php
namespace bizySoft\bizyStore\model\plugins;

use \PDO;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\Connector;

/**
 * Concrete Connector class for SQLite.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class SQLite extends Connector
{
	public function __construct()
	{}
	
	/**
	 * Build SQLite connection string and then connect.
	 *
	 * No notion of a schema in SQLite so don't process them.
	 *
	 * @param array $dbConfig an associative array containing the database config information supplied in bizySoftConfig.
	 * @return PDO an instance of the PDO class.
	 */
	public function connect(array $dbConfig)
	{
		$dsn = "sqlite:";
		$dbName = $dbConfig[BizyStoreOptions::DB_NAME_TAG]; // name is mandatory.
	
		$dsn .= $dbName;
		// $dbOptions in $dbConfig is an array of name value pairs for the
		// attributes that can be set on a particular connection.
		$dbOptions = isset($dbConfig[BizyStoreOptions::PDO_OPTIONS_TAG]) ? $dbConfig[BizyStoreOptions::PDO_OPTIONS_TAG] : array();
	
		$db = new PDO($dsn, null, null, $dbOptions);
	
		return $db;
	}
	
	/**
	 * There are no specific validations for SQLite.
	 *
	 * The other manadatory fields &lt;id&gt;, &lt;interface&gt; and &lt;name&gt; are already validated.
	 *
	 * @param array $dbConfig
	 */
	public function validate(array $dbConfig)
	{}
}
?>