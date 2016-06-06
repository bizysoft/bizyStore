<?php
namespace bizySoft\common;

/**
 * Handles options that are specified in a multi-level associative array.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ArrayOptionHandler implements OptionHandlerI
{
	/**
	 * An Option instance representing a key/value pair.
	 *
	 * @var Option
	 */
	private $option = null;

	/**
	 * Construct with the associative array of options we want to search through.
	 *
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$this->option = new Option("", $options);
	}

	/**
	 * Get the option by it's name.
	 * 
	 * This method will return the FIRST occurence of the option key as an Option. This may have
	 * an effect on the array level that you build the ArrayOptionHandler from.
	 *
	 * Calling this method with no parameters or null will return all the options.
	 *
	 * @see \bizySoft\bizyStore\model\statements\OptionHandler::getOption()
	 * @return array An associative array containing ($name = > $value) or null if not found.
	 */
	public function getOption($name = null)
	{
		$this->option->key = $name;
		$result = $this->option;
		
		if ($name !== null)
		{
			$result = $this->traverseOption($this->option);
		}
		return $result;
	}

	/**
	 * Allow seting of the option.
	 *
	 * Use this if you require further searches within a returned option.
	 *
	 * @param array $option
	 */
	public function setOption($option)
	{
		$this->option = $option;
	}

	/**
	 * Option values can be arrays so recurse through to find the name we are looking for.
	 *
	 * Mostly, the option can be found in the root of the $option passed in, depending on your
	 * knowlege of the $option structure and whether you are looking in the right level.
	 *
	 * @param Option $option
	 * @return Option The Option found or null if not found.
	 */
	private function traverseOption(Option $option)
	{
		/*
		 * Set the result for a non-existant property
		 */
		$result = null;
		
		$property = $option->key;
		$properties = $option->value;
		
		/*
		 * Check if we found it before we go any further.
		 *
		 * This is a simple associative array lookup so is fast.
		 */
		if (isset($properties[$property]))
		{
			$result = new Option($property, $properties[$property]);
		}
		else
		{
			/*
			 * Otherwise we have to recurse through the options which is slower.
			 */
			foreach ($properties as $name => $value)
			{
				if (is_array($value))
				{
					/*
					 * Recurse the array.
					 */
					$result = $this->traverseOption(new Option($property, $value));

					if ($result !== null)
					{
						/*
						 * Found it
						 */
						break;
					}
				}
			}
		}
		
		return $result;
	}
}
?>