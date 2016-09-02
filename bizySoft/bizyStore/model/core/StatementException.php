<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\model\statements\Statement;

/**
 * Convenience wrapper on ModelException to provide bizyStore related information that would not normally be available.
 * 
 * Just pass in the statement reference when constructing.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class StatementException extends ModelException
{
	/**
	 * Create message and pass through to parent.
	 *
	 * @param Statement $statement
	 * @param string $codeContext
	 * @param Exception $e
	 */
	public function __construct(Statement $statement, $codeContext = null, $e = null)
	{
		$db = $statement->getDB();
		$errorInfo = $statement->errorInfo();
		$sqlState = $errorInfo[0];
		$errorCode = $errorInfo[1];
		$errorMessage = "$codeContext:" . (($e) ? $e->getMessage() : $errorInfo[2]);
		$dbId = $db->getDBId();
		$dbName = $db->getName();
		$dbReflector = new \ReflectionClass($db);
		$dbClass = $dbReflector->getShortName();
		
		$message = "DATABASE[$dbId, $dbName, $dbClass]" . ":" . $errorCode . ($e ? "" : ":SQLSTATE[" . $sqlState . "]") . ":" . $errorMessage;
		
		parent::__construct($message, $errorCode, $e);
	}
}
?>