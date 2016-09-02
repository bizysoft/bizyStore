<?php
namespace bizySoft\bizyStore\generator;

use \Exception;
use bizySoft\bizyStore\services\core\BizyStoreConstants;

/**
 * Called by the autoLoader to 'complile' a class based on the contents of the bizySoftConfig xml file.
 * 
 * <span style="color:orange">bizySoftConfig files can contain SENSITIVE INFORMATION and should be SECURED so that the web server 
 * never serves them as content. The recommended way of doing this is to install the 'bizySoft' directory outside the 
 * DOCUMENT_ROOT of your web server.</span>
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. Details at</span> <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 * @codeCoverageIgnore
 */
final class BizyStoreConfigGenerator extends Generator implements BizyStoreConstants
{
	/**
	 * Build a config class file from the appProperties which contains everything required
	 * for generation, including the file name.
	 */
	public function generate(array $appProperties = array())
	{
		/*
		 * All these properties are at the root of the appProperties as
		 * per BizyStoreConfigLoader.
		 */
		$configDir = $appProperties[self::BIZYSTORE_CONFIG_DIR];
		$class = $appProperties[self::BIZYSTORE_CONFIG_CLASS];
		$fileName = $configDir . DIRECTORY_SEPARATOR . $class . ".php";
		$namespace = $appProperties[self::BIZYSTORE_CONFIG_NAMESPACE];
		$search = "<CONFIG_REPLACE>";
		
		$config = 
				"<?php\n" .
				"namespace $namespace;\n\n" .
				"/*\n" .
				" * Generated code. Don't edit.\n" .
				" */\n\n" . 
				"final class $class\n" .
				"{\n" .
				"\tprivate \$config;\n\n" .
				"\tpublic function __construct()\n" .
				"\t{\n" .
				"\t\t\$this->config = array($search);\n" .
				"\t}\n\n" .
	
				"\tpublic function getConfig()\n" .
				"\t{\n" .
				"\t\treturn \$this->config;\n" .
				"\t}\n" .
				"}\n" .
				"?>";
		
		$this->createDirectory($configDir);
		
		$replace = $this->stringify($appProperties, 3);
		
		$classContents = str_replace($search, $replace, $config);
		
		file_put_contents($fileName, $classContents);
	}

	/**
	 * Produces the PHP code representation of an array with integer, null and boolean support.
	 * 
	 * @param array $properties
	 * @param int $tabDepth
	 */
	private function stringify(array $properties, $tabDepth)
	{
		$stringified = "";
		$comma = "";
		foreach($properties as $propName => $propValue)
		{
				if (is_array($propValue))
				{
					$stringified .= $comma . $this->indentDefinition($propName, " => array(", $tabDepth+1);
					$stringified .= $this->stringify($propValue, $tabDepth+1);
					$stringified .= $this->indentDefinition("", ")", $tabDepth+1);
				}
				else
				{
					$stringified .= $comma . $this->indent($propName, $propValue, $tabDepth+1);
				}
				$comma = ",";
		}
	
		return $stringified;
	}
	
	private function indentDefinition($name, $definition, $tabDepth)
	{
		$key = $name ? (is_numeric($name) ? $name : "'$name'") : $name;
		$indented = "\n" . str_repeat("\t", $tabDepth) . $key . $definition;
		return $indented;		
	}
	
	private function indent($name, $value, $tabDepth)
	{
		/*
		 * $name can be either numeric or string
		 */
		$key = is_numeric($name) ? $name : "'$name'";
		/*
		 * $value needs to be processed.
		 */
		$thisValue = $value;
		if(is_null($value))
		{
			$thisValue = "null";
		}
		else
		{
			if(is_numeric($value))
			{
				$thisValue =  $value;
			}
			else
			{
				$thisValue = is_bool($value) ? ($value ? "true" : "false") : "'$value'";
			}
		}
		$indented = "\n" . str_repeat("\t", $tabDepth) . "$key => $thisValue";
		return $indented;
	}
}
?>