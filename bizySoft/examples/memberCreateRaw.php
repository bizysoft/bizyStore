<?php
use \Exception;
use \PDO;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\ConnectionManager;

/**
 * memberCreateRaw.php
 * 
 * To start this example, copy the html and php files in bizySoft/examples to your DOCUMENT_ROOT and go to 
 * 
 * http://yourserver/memberEntryRaw.html
 *
 * This is an entry/top level php file called by a form action. You should realise that this is just an example, so does not comply
 * with best practices of producing a web page via PHP.
 * 
 * It's the raw version of "memberCreate.php". Because we don't use any Models, it does not rely on Model, Schema, DB or Statement 
 * support. The amount of database code you have to write is 4X that of bizyStore code for the same result (see memberCreate.php). 
 * 
 * Reading the code functionally, exception handling and managing result sets is more difficult than "memberCreate.php". 
 * Only ConnectionManager has support for multiple database vendors, the rest of the code does not. Here we assume that 
 * you are using the standard SQLite database that comes with the distribution.
 *
 * It can use the bizySoftConfig file (bizySoft/config/bizyStoreExample.xml) as a basis which
 * is provided in the distribution. You will need to change the path to the database in this file to suit
 * your environment, then copy the file to the default bizySoftConfig file (bizySoft/config/bizySoftConfig.xml).
 * This references the SQLite database (also provided) which is the default unit test database.
 * 
 * ** Note ** that you need to have the bizySoft directory on your include_path. The best solution is to externally set 
 * the include_path with the path to the bizySoft directory. eg. in php.ini, include_path=/var/www
 * 
 * This eliminates problems with namespaces, use statements and include calls etc...
 * 
 * It's recommended practice for the bizySoft directory to be OUTSIDE the web server's DOCUMENT_ROOT. You must still give your 
 * web server write access to some bizySoft directories for logging etc, if required.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */

/*
 * Get the bootstrap file from the include path.
 *
 * This will be the only time you need to manually require/include a PSR-4 compliant file if it is on your include_path.
 *
 * str_replace() handles both 'nix and Windows file systems.
 */
include str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset=\"utf-8\">";
echo "</head>";
echo "<body>";

$milli = 1000;
$baseTime = microtime(true);
/*
 * Create a member row in the database from the form data passed to us by memberEntryRaw.html.
 *
 * Database connections can fail for any number of reasons so we encapsulate all the database work in a try/catch.
 * 
 * We need to set the db to null for reference in the catch block. It's always best to use 
 * this technique as the code could fail with an exception during the call to ConnectionManager.
 */
$db = null;
try
{
	/*
	 * We use the ConnectionManager here because one of bizyStore's main aims is to make database connections easy. It 
	 * still uses the bizySoftConfig file, but you don't have to use all bizyStore's features if you don't need to, 
	 * so ConnectionManager returns a standard PDO object.
	 * 
	 * This example does not use Models so will not load all of bizyStore's core code. It gives you the flexibility to work 
	 * the way you want with the connection. The down side is you have to write a lot more code which will most probably end 
	 * up being database specific which can be fine if you only intend to use a single database vendor. You will run into 
	 * problems if you decide to change vendors, or use multiple databases from different vendors.
	 * 
	 * Usually a raw connection takes a decent amount of code to setup if you need to do it properly, even just for one database
	 * vendor. Because the database is still configured from the bizySoftConfig file, you won't see any raw connection details 
	 * which you would normally have to supply somehow.
	 * 
	 * Here it takes very little code to handle databases from all our supported vendors.
	 */
	$config = BizyStoreConfig::getInstance();
	$connectionManager = new ConnectionManager($config->getDBConfig());
	/*
	 * Database "A" is the database <id> from the bizyStoreConfig file.
	 */
	$db = $connectionManager->getConnection("A");
	/*
	 * Get the time from the database
	 */
	$dbDateTime = $db->query("SELECT CURRENT_TIMESTAMP");
	$dateCreated = $dbDateTime->fetch(PDO::FETCH_ASSOC);
	/*
	 * It's more difficult to construct a prepared statement from scratch and we can never be sure that all the _POST
	 * variables are actually in the database schema (in this case they are) or if we have a schema to qualify the 
	 * statement with (in this case not) etc...
	 * 
	 * Even getting the database time is dependent on the database implementation so none of this code 
	 * (except for ConnectionManager) is completely useable by another vendors database.
	 * 
	 * Also the prepared statements cannot be leveraged for re-use anywhere else in your code ie. another php file. 
	 *
	 * These are all issues that bizyStore transparently handles for you.
	 * 
	 * You would normally need to validate the $_POST form data before storing in the db, this example uses prepared statements 
	 * so data is already safe from SQL injection issues. 
	 */
	$memberValues = $_POST;
	$memberValues["dateCreated"] = $dateCreated["CURRENT_TIMESTAMP"];
	
	$prepareKeys = array_keys($memberValues);
	/*
	 * Build the statement to prepare.
	 */
	$insertStatement = "INSERT INTO member ";
	
	$comma = "";
	$columns = "";
	$values = "";
	foreach ($prepareKeys as $key)
	{
		$columns .= $comma . $key;
		$values .= $comma . ":$key";
		$comma = ",";
	}
	$insertStatement .= "($columns) VALUES ($values)";
	/*
	 * Usual practice is to try any database writes using transactions because we don't want to leave the database
	 * in an undefined state.
	 * 
	 * We are not using bizyStore methods here, so the code is more dependent on the setting for PDO::ATTR_ERRMODE supplied 
	 * in the config file. In this case we should manually check for a false execute() result as well, in case PDO::ATTR_ERRMODE
	 * is changed.
	 * 
	 * Prepare the statement and hold a reference to it.
	 */
	$preparedStatement = $db->prepare($insertStatement);
	if ($preparedStatement === false)
	{
		$errorInfo = $db->errorInfo();
		throw new Exception("Could not prepare insert " . print_r($errorInfo, true));
	}
	$db->beginTransaction();
	/*
	 * Store all the properties that have been set into the database.
	 */
	$result = $preparedStatement->execute($memberValues);
	if ($result === false)
	{
		$errorInfo = $preparedStatement->errorInfo();
		throw new Exception("Could not create a row " . print_r($errorInfo, true));
	}
	/*
	 * We have to manually get the id produced by the database and set it.
	 */
	$id = $db->lastInsertId();
	$memberValues["id"] = $id;
	/*
	 *  See if we can get the same properties out of the database using the 'id' primary key field.
	 *  Get the data into another array so we can compare with the original.
	 */
	$findPreparedStatement = $db->prepare("SELECT * from member WHERE id = :id");
	if ($findPreparedStatement === false)
	{
		$errorInfo = $db->errorInfo();
		throw new Exception("Could not prepare select " . print_r($errorInfo, true));
	}
	
	$result = $findPreparedStatement->execute(array("id" => $id));
	if ($result === false)
	{
		$errorInfo = $findPreparedStatement->errorInfo();
		throw new Exception("Could not execute select " . print_r($errorInfo, true));
	}
	/*
	 * We need PDO to return an associative array but only keyed on the column name not the
	 * default of both associative and zero based indexes.
	 */
	$memberRows = $findPreparedStatement->fetchAll(PDO::FETCH_ASSOC);
	if ($memberRows === false || (count($memberRows) < 1))
	{
		throw new Exception("Could not find Member");
	}
	$storedMember = $memberRows[0];
	/*
	 * We leave the commit until here in case there is any problem retrieving.
	 * 
	 * The commit will actually take most of the time for this request.
	 */
	$db->commit();
	/*
	 * You should be able to put any international characters into the name fields in memberEntryRaw.html
	 * and have them retrieved correctly from the database. We use SQLite for this demo which has a default 
	 * charset of UTF-8.
	 */
	echo "<br />Member " . $storedMember["firstName"] . " " . $storedMember["lastName"] . " created with id of " . $storedMember["id"];

	$entryDiff = array_diff_assoc($storedMember, $memberValues);
	$dBDiff = array_diff_assoc($memberValues, $storedMember);
	if ($entryDiff)
	{
		/*
		 * Any charset deficiencies in the retrieval of data will show up here.
		 */
		echo "<br />However the database row differ's from what was entered. <br />The differences are:";
		echo "<br />Entry:<br />" . print_r($entryDiff, true);
		echo "<br />Database:<br />" . print_r($dBDiff, true);
	}
}
catch ( Exception $e )
{
	if ($db && $db->inTransaction())
	{
		$db->rollBack();
	}
	
	echo "<br />Member " . $_POST["firstName"] . " " . $_POST["lastName"] .  "<br />" . $e->getMessage();
}
echo "<br />Request took " . number_format((microtime(true) - $baseTime) * $milli, 2) . "ms";
echo "</body>";
echo "</html>";
?>