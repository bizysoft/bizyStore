<?php
use \Exception;
use bizySoft\bizyStore\model\core\Model;
use bizySoft\bizyStore\model\bizyStoreExample\Member;

/**
 * memberCreate.php
 *
 * To start this example, copy the html and php files in bizySoft/examples to your DOCUMENT_ROOT and go to 
 * 
 * http://yourserver/examples/memberEntry.html
 *
 * This is an entry/top level php file called by a form action. It shows an example of creating a 'Member'
 * in a database from a web form. It includes data storage/retrieval, transactions, exception handling, 
 * international charsets, timing and building a real html page. This code is compatible with all our supported 
 * database vendors.
 *
 * You can use the bizySoftConfig file (bizySoft/config/bizyStoreExample.xml) as a basis which
 * is provided in the distribution. You will possibly need to change the path to the database in this file to suit
 * your environment, then copy the file to the default bizySoftConfig file (bizySoft/config/bizySoftConfig.xml).
 * This references the SQLite database (also provided) which is the default unit test database.
 *
 * ** Note ** this is non-namepaced code and is therefore in the global namespace of \, but we can still use 'use' statements
 * to refer to the classes in this file. The only file 'include'ed is the bootstrap file which contains the auto-loader.
 *
 * ** Note ** that you need to have the bizySoft directory on your include_path. The best solution is to externally set 
 * the include_path with the path to the bizySoft directory. eg. in php.ini, include_path=/var/www
 * 
 * This eliminates problems with namespaces, use statements and include calls etc...
 * 
 * It's recommended practice for the bizySoft directory to be OUTSIDE the web server's DOCUMENT_ROOT. You must still give your 
 * web server write access to the bizySoft directories for Schema file generation or logging if required.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL or see the LICENSE file with this distribution.
 */

/*
 * Get the bootstrap file from the include path. This will be the only time you need to manually require/include a 
 * PSR-4 compliant file if it is on your include_path.
 * 
 * str_replace() handles both 'nix and Windows file systems.
 */
include str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "</head>";
echo "<body>";

$milli = 1000;
$baseTime = microtime(true);
/* 
 * Create a Member model object from the form data passed to us by memberEntry.html.
 * 
 * Database connections can fail for any number of reasons so we encapsulate all the database work in a try/catch block.
 * 
 * We are going to use a transaction within the try block so set it to null for reference in the catch block. It's
 * always best to do this as the code could fail with an exception before or during the call to beginTransaction().
 */
$txn = null;
try
{
	/*
	 * Model constructors can throw a ModelException if the config file becomes out of sync with the generated 
	 * classes, so we also put this in the try block.
	 * 
	 * You would normally need to validate the $_POST form data before storing in the db, this is just an 
	 * example so we don't.
	 * 
	 * The Model references the default database for a Member object because we haven't specified one expicitly. 
	 * The default database for a Model is the first database specified in bizySoftConfig that has the particular
	 * Model as a table.
	 * 
	 * Model class files will be generated in the bizySoft/bizyStore/model/bizyStoreExample directory. Model generation 
	 * is automatic via the auto-loader when the SPL needs the definition of "Member", as referenced in the 'use' statement 
	 * above.
	 */
	$member = new Member($_POST);
	$db = $member->getDB();
	/*
	 * Set a dateCreated property.
	 */
	$member->setValue("dateCreated", $db->getConstantDateTime());
	/*
	 * Database writes should always be surrounded in fault tolerant code with a transaction so the database 
	 * does not end up in an undefined state.
	 * 
	 * bizyStore always throws exceptions on failure despite the setting for PDO::ATTR_ERRMODE.
	 */
	$txn = $db->beginTransaction();
	/*
	 * Store all the Model properties that have been set.
	 * 
	 * create() automatically sets any sequenced properties that the database allocates back into the Model 
	 * so we can use them straight away.
	 */
	$member->create();
	/*
	 *  See if we can get the same properties out of the database using the 'id' primary key field.
	 *  Get the data into another Model object so we can compare with the original.
	 */
	$findMember = new Member(array("id" => $member->getValue("id")));
	$storedMember = $findMember->findUnique();
	if(!$storedMember)
	{
		throw new Exception("Could not find Member");
	}
	/*
	 * We leave the commit until here in case there is any problem retrieving.
	 *
	 * The commit() will take most of the time for this request.
	 */
	$txn->commit();
	/*
	 * You should be able to put any international characters into the name fields in memberEntry.html
	 * and have them retrieved correctly from the database. We use SQLite for this demo which has a default
	 * charset of UTF-8.
	 */
	$properties = $storedMember->get();
	echo "<br />Member " . $properties["firstName"] . " " . $properties["lastName"] . " created with id of " . $properties["id"];
	
	$entryDiff = Model::modelDiff($storedMember, $member);
	$dBDiff = Model::modelDiff($member, $storedMember);
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
	/*
	 * Check the transaction first, the code may have failed in or before beginTransaction().
	 */
	if ($txn)
	{
		$txn->rollBack();
	}
	
	echo "<br />Member " . $_POST["firstName"] . " " . $_POST["lastName"] .  "<br />" . $e->getMessage();
}
echo "<br />Request took " . number_format((microtime(true) - $baseTime) * $milli, 2) . "ms";
echo "</body>";
echo "</html>";
?>