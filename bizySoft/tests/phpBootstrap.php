<?php
use bizySoft\tests\services\TestLogger;
/*
 * Get the bootstrap file containing the auto-loader from the include path.
 */
$bootstrapFile = str_replace("/", DIRECTORY_SEPARATOR, "bizySoft/bizyStore/services/core/bootstrap.php");
// Bootstrap bizyStore by including this file
if ($bootstrapFile)
{
	include $bootstrapFile;
	TestLogger::configure();
}
else 
{
	echo "Can't find bizyStore bootstrap file. Check the include_path.";
	die();
}
?>