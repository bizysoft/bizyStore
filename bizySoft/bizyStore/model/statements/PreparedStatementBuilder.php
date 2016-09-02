<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\services\core\BizyStoreConstants;

/**
 * Provide some common functions for building prepared statements.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class PreparedStatementBuilder extends StatementBuilder implements BizyStoreConstants
{
	private static $propertyPrefix = null;
	
	/**
	 * Set up the db.
	 *
	 * @param PDODB $db
	 */
	public function __construct($db)
	{
		if (self::$propertyPrefix === null)
		{
			/*
			 * Determine if we need to supply a property prefix globally.
			 */
			$config = $db->getConfig();
			
			$bizyStore = $config->getProperty(self::BIZYSTORE_TAG);
			$options = isset($bizyStore[self::OPTIONS_TAG]) ? $bizyStore[self::OPTIONS_TAG] : null;
			$prefix = $options ? (isset($options[self::OPTION_PREPARE_PREFIX]) ? $options[self::OPTION_PREPARE_PREFIX] : false) : false;
			
			self::$propertyPrefix  = $prefix ? ":" : "";
		}
		parent::__construct($db);
	}
	
	/**
	 * Get the property key prefix for this builder.
	 *
	 * At the moment PDO supports both colon and non-colon prefixed parameter/property keys for replacement values. 
	 * If this support ceases then a lot of other people's code will not work. We provide some options to get around 
	 * this, you can set the global <bizyStore> <options> 'preparePrefix' in bizySoftConfig to true to invoke internal 
	 * colon prefixing of parameter/property keys. The default is false.
	 * 
	 * From a PDO user point of view, our thoughts are that colon prefixes should be used only in the query as an 
	 * indication that the following name is expected as a replacement parameter/property key, and NOT to force the 
	 * replacement's key to have a colon prefix.
	 * 
	 * @return string
	 */
	public function getPropertyPrefix()
	{
		return self::$propertyPrefix;
	}
	
	/**
	 * Gets the function to translate a query property into it's database query form.
	 * 
	 * @return callable this function simply translates the property specified to a named parameter.
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