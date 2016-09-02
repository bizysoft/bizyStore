<?php
namespace bizySoft\bizyStore\services\core;

use bizySoft\common\Logger;

/**
 * Log to the bizyStore log file specified by:
 * 
 * <ul>
 * <li>bizySoftConfig &lt;logFile&gt;</li>
 * <li>The bizySoft/bizyStore/logs/&lt;appName&gt;.log if bizySoftConfig does not specify a logFile.</li>
 * <li>PHP error_log if bizySoft/bizyStore/logs/&lt;appName&gt;.log is not writeable.</li>
 * </ul>
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class BizyStoreLogger extends Logger implements BizyStoreConstants
{
	public function __construct($appName, $logFile)
	{
		parent::__construct($appName, $logFile);
	}
}

?>