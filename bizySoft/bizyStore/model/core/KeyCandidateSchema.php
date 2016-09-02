<?php
namespace bizySoft\bizyStore\model\core;

/**
 * Keys candidates are the possible properties that can form a key within a Model.
 * 
 * KeyCandidateSchema holds these properties to allow key searches to be derived for a table based on dbId.
 *
 * Key candidates have an order of preference. This is the order that they are iterated over to resolve keys.
 * Zero or more preferences may be present for a table.
 * 
 * The preferences and their order are:
 * 
 * +Primary Key with a sequenced column
 * +Primary Key non-sequenced
 * +Unique Keys with a sequenced column
 * +Sequenced column
 * +Unique Key non-sequenced
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class KeyCandidateSchema extends Schema
{
	/**
	 * KeyCandidateSchema instances are constructed under controlled conditions from the generated Model classes.
	 * 
	 * @param array $keyCandidateData
	 */
	public function __construct($keyCandidateData)
	{
		parent::__construct($keyCandidateData);
	}
	
	/**
	 * Gets the properties out of $properties that can fill a key based on the key candidate fields.
	 * 
	 * The first full key match is returned. Key properties can have null values.
	 * 
	 * @param string $dbId the database id from bizySoftConfig.
	 * @param array $properties tan associative array of (propertyName => value, etc... ) to fill a key from.
	 * @return array an associative array of (propertyName => value, etc... ) that fills a key candidate or an empty array if no key 
	 * candidates can be filled.
	 */
	public function getKeyProperties($dbId, array $properties)
	{
		foreach($this->get($dbId) as $indexName => $keyCandidate)
		{
			$result = array_intersect_key($properties, $keyCandidate);
			if(!array_diff_key($keyCandidate, $result))
			{
				/*
				 * We've found the first candidate with all the key properties present so return it.
				 */
				return $result;
			}
		}
		return array();
	}
	
	/**
	 * Gets the values of the properties in $properties that are able to form a unique key.
	 * If no key is found use all the $properties.
	 * 
	 * @param string $dbId the database id from bizySoftConfig.
	 * @param array $properties an associative array of (propertyName => value, etc... ) 
	 * @return array an associative array of (propertyName => value, etc... ) that fills a key candidate or the
	 * original properties if no key candidates can be filled.
	 */
	public function getKeyValues($dbId, $properties)
	{
		$keyProperties = $this->getKeyProperties($dbId, $properties);
	
		return $keyProperties ? $keyProperties : $properties;
	}
	
	/**
	 * String representation of getKeyValues().
	 * 
	 * @param string $dbId the database id from bizySoftConfig.
	 * @param array $properties an associative array of (propertyName => value, etc... ) 
	 * @return string
	 */
	public function getKeyValuesAsString($dbId, $properties)
	{
		$keyProperties = $this->getKeyValues($dbId, $properties);
		return implode(".", array_values($keyProperties));
	}
	
}
?>
