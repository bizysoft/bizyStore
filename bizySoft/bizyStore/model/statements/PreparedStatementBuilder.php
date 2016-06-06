<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Provide some common functions for building prepared statements.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class PreparedStatementBuilder extends StatementBuilder
{
	/**
	 * Set up the db.
	 *
	 * @param PDODB $db
	 */
	public function __construct($db)
	{
		parent::__construct($db);
	}
	
	/**
	 * Get the property prefix for this builder.
	 *
	 * PDO actually forces colon prefixes internally on property key's that don't have them, making it look
	 * like the standard implementation which is particularly silly. At the moment PDO supports both colon 
	 * and non-colon prefixed parameters. If PDO support ceases either way then a lot of other people's code
	 * will not work. We provide some options to get around this, you can set the global option 'preparePrefix' in 
	 * bizySoftConfig to true to invoke colon prefixing.
	 * 
	 * From a PDO user point of view, our thoughts are that colon prefixes should be used only in the query as an 
	 * indication that the following name is expected as a parameter/property, and NOT to force the replacement
	 * parameters/properties to have a colon prefixed key.
	 * 
	 * @return string
	 */
	public function getPropertyPrefix()
	{
		$prefix = BizyStoreConfig::getProperty(BizyStoreOptions::OPTION_PREPARE_PREFIX);

		return $prefix ? ":" : "";
	}
	
	/**
	 * Gets the function to translate a property into it's database query form.
	 * 
	 * @return callable this function simply translates the property specified to a named parameter, handling
	 * null values as 'IS NULL'.
	 */
	public function getPropertyTranslator()
	{
		$propertyTranslator = function($property, $properties) {
			return ":$property";
		};
	
		return $propertyTranslator;
	}
}
?>