<?php
namespace bizySoft\bizyStore\model\statements;

use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\core\ModelException;
/**
 * Provides generic methods for building database queries.
 *
 * Individual methods may prove useful for building more complex queries.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
class StatementBuilder
{
	/*
	 * Constant array keys for translate().
	 */
	const QUALIFY = "qualify"; // Used for schema related entities i.e. table names, sequences etc.
	const ENTITY = "entity"; // Used for table related entities i.e. column names.
	const PROPERTY = "property"; // Used wherever a property needs to be replaced by another representation.
	
	/**
	 * The database reference
	 * 
	 * @var DB
	 */
	protected $db;
	
	/**
	 * Translators include QUALIFY, ENTITY and PROPERTY.
	 * 
	 * @var array
	 */
	private $translators = array();
	
	/**
	 * Indicator if the statement has a where clause.
	 * 
	 * @var boolean
	 */
	private $hasWhere = false;
	
	/**
	 * Actual properties that have been processed by translate().
	 * 
	 * @var array
	 */
	private $translatedProperties = array();
	
	/**
	 * Entity prefixes for translate().
	 * 
	 * @var array
	 */
	private static $prefixes = array(self::QUALIFY => "<Q", self::ENTITY => "<E", self::PROPERTY => "<P");
	
	/**
	 * Entity suffixes for translate().
	 * 
	 * @var array
	 */
	private static $suffixes = array(self::QUALIFY => "Q>", self::ENTITY => "E>", self::PROPERTY => "P>");
	
	/**
	 * Control functions for append().
	 * 
	 * 'order by' and 'limit' can appear at the start of an append portion.
	 *
	 * @var array
	 */
	private static $controlFunctions = array("order by", "limit");
	
	/**
	 * Set up the db and translators.
	 *
	 * @param DB $db
	 */
	public function __construct(DB $db)
	{
		$this->db = $db;
		
		$this->translators[self::QUALIFY] = function($entity, array $properties =  null) {return $this->db->qualifyEntity($entity);};
		$this->translators[self::ENTITY] = function($entity, array $properties =  null) {return $this->db->formatEntity($entity);};
		/*
		 * Property translation is dependent on the StatementBuilder implementation.
		 */
		$this->translators[self::PROPERTY] = $this->getPropertyTranslator();
	}
	
	/**
	 * Set the where clause indicator.
	 * 
	 * @param boolean $yesNo
	 * @return boolean
	 */
	public function hasWhere($yesNo)
	{
		$this->hasWhere = $yesNo;
	}
	/**
	 * Get the property prefix for this builder.
	 * 
	 * @return string
	 */
	public function getPropertyPrefix()
	{
		return "";
	}
	
	/**
	 * Gets the translated properties as built by translate().
	 * 
	 * @return array an associative array keyed on the original property name and the translated property value.
	 */
	public function getTranslatedProperties()
	{
		return $this->translatedProperties;
	}
	
	/**
	 * Extracts the property name/value's and uses them to produce a SET clause for the statement.
	 *
	 * Used for UPDATE statements.
	 *
	 * @param array $properties associative array of property name/value to be set.
	 *
	 * @return string the SET clause for the statement.
	 */
	public function buildSetClause($properties)
	{
		$setClause = "";
		$comma = "";
	
		foreach($properties as $key => $value)
		{
			$setClause .= "$comma<E{$key}E> = <P{$value}P>";
			$comma = ",";
		}
	
		return $setClause;
	}
	
	/**
	 * Default behaviour, extracts the property names/values and uses them to produce a VALUES clause.
	 *
	 * Used for single INSERT statements.
	 *
	 * @param array $properties the associative array of properties the clause will be built from
	 * @return string the values clause suitable for being used in an insert statement or an empty array.
	 */
	public function buildValuesClause($properties)
	{
		/*
		 * There is only one element returned from buildRowConstructor(), we
		 * use foreach here in case it returns an empty array,
		 */
		foreach ($this->buildRowConstructor($properties) as $columnNames => $columnValues)
		{
			return "($columnNames) VALUES ($columnValues)";
		}
		
		return array();
	}

	/**
	 * Extracts the property names/values and uses them to produce a WHERE clause.
	 *
	 * Used for SELECT, UPDATE and DELETE statements.
	 * 
	 * Note that where clauses can have null properties which don't appear in the executable properties for the
	 * statement.
	 *
	 * @param array $properties associative array of properties.
	 * @return array the where clause.
	 */
	public function buildWhereClause($properties)
	{
		$whereClause = "";
		$whereProperties = array();
		$and = "";
		
		$this->hasWhere = $properties ? true : false;
		
		foreach($properties as $key => $value)
		{
			if ($value === null)
			{
				$whereClause .= "$and<E{$key}E> IS NULL";
			}
			else
			{
				$whereClause .= "$and<E{$key}E> = <P{$key}P>";
			}
			$and = " AND ";
		}
		
		return $whereClause;
	}
	
	/**
	 * Extracts the property names/values to produce a row constructor.
	 * 
	 * Note that this can be used as a basis for either single or multiple insert specifications. See InsertBulkStatement.
	 * 
	 * @param array $properties the associative array of Model properties
	 * @return array A single element associative array, the key is the row constructor specification, the value is the row 
	 * constructor values.
	 */
	public function buildRowConstructor($properties)
	{
		/*
		 * Go through the $properties array and build the row constructor
		 * while keeping column names and column values in sync.
		 * 
		 * It's important to keep speed considerations in mind here. There are many ways you can do whats required,
		 * we've found this is the simplest and fastest technique.
		 */
		$columnNames = "";
		$columnValues = "";
		$comma = "";
		foreach ($properties as $key => $value)
		{
			$columnNames .= "$comma<E{$key}E>";
			$columnValues .= "$comma<P{$key}P>";
			$comma = ",";
		}
		
		return $columnNames ? array($columnNames => $columnValues) : array();
	}
	
	/**
	 * Gets the properties that may or may not be synchronised to the statement via translate().
	 * 
	 * Properties may need to be key prefixed if specified in bizySoftConfig.
	 * Using non-prefixed properties is the fastest method.
	 * 
	 * @param array $properties
	 * @return array
	 */
	public function translateProperties(array $properties)
	{
		$result = array();
		
		if($properties)
		{
			$prefix = $this->getPropertyPrefix();
			if ($this->translatedProperties)
			{
				/*
				 * We can synchronise with the statement. This can happen for tagged queries that
				 * are translate()'ed with the same StatementBuilder beforehand.
				 */
				if ($prefix)
				{
					foreach ($this->translatedProperties as $column => $translated)
					{
						if (array_key_exists($column, $properties))
						{
							$result[$translated] = $properties[$column];
						}
					}
				}
				else
				{
					$result = array_intersect_key($properties, $this->translatedProperties);
				}
			}
			else 
			{
				/*
				 * We are NOT necessarily synchronised with the statement.
				 */
				if ($prefix)
				{
					foreach ($properties as $column => $value)
					{
						$result[$prefix . $column] = $value;
					}
				}
				else 
				{
					$result = $properties;
				}
			}
		}
		return $result;
	}
	
	/**
	 * Takes a raw text tagged statement and replaces tagged fields with the database formatted versions.
	 * 
	 * bizyStore supports multiple databases. This can help you produce database agnostic queries.
	 * 
	 * You can tag entities within queries as follows:
	 * 
	 * + <Q...Q> format and QUALIFY the entity with the formatted schema name if supported. This is usually a table name.
	 * + <E...E> a database ENTITY that needs to be formatted. This is usually a column name.
	 * + <P...P> a column name (or PROPERTY) that needs to be formatted. StatementBuilder will replace this with the
	 *   property value. PreparedStatementBuilder will use the property to produce a colon prefixed named parameter.
	 * 
	 * Tagged entities can be any database related identifier. Tagged entities can be repeated. 
	 * 
	 * eg. select * from <QmemberQ> where <EfirstnameE> = <PfirstnameP>
	 * 
	 * select ms.* from <QmembershipQ> ms, <QmemberQ> m where ms.<EmemberIdE> = m.id and m.<EfirstNameE> = <PfirstNameP>
	 * 
	 * The the tagged $sql passed in should have an SQL compliant structure if you require the statement to address multiple 
	 * databases.
	 * 
	 * @param string $taggedSQL the tagged query
	 * @param array $properties
	 * @return string the statement formatted for prepare/execution depending on the implementation.
	 */
	public final function translate($taggedSQL, array $properties = array())
	{
		$parsed = array();

		foreach (self::$prefixes as $key => $prefix)
		{
			$suffix = self::$suffixes[$key];
			/*
			 * Get all the matches for a string between the tags specified.
			 * 
			 * Full regex matches (eg. <QmembershipQ>) are returned into $parsed[$key][0].
			 * The corresponding sub-pattern matches between the tags (eg. membership) are returned into $parsed[$key][1]. 
			 * Sub-pattern matches are specified by parentheses in the regex string below.
			 */
			$regex = "/$prefix([a-zA-Z0-9_]*)$suffix/";
			$matched = array();
			preg_match_all($regex, $taggedSQL, $matched);
			if (isset($matched[0]) && $matched[0])
			{
				$parsed[$key] = $matched;
			}
		}
		$translated = $taggedSQL;
		$this->translatedProperties = array();
		foreach ($parsed as $key => $parsedAs)
		{
			$originals = $parsedAs[0];
			$replacements = $parsedAs[1];
			$replaceAs = array();
			foreach($originals as $i => $original)
			{
				/*
				 * No duplicates. We don't want to str_replace() something that
				 * has already been replaced. The replacement is always the same for an original.
				 */
				if (!isset($replaceAs[$original]))
				{
					$replacement = $replacements[$i];
					$replaceAs[$original] = $replacement;
					$translatedEntity = $this->translators[$key]($replacement, $properties);
					if ($key === self::PROPERTY)
					{
						$this->translatedProperties[$replacement] = $translatedEntity;
					}
					$translated = str_replace($original, $translatedEntity, $translated);
				}
			}
		}
		return $translated;
	}
	
	/**
	 * Takes a raw text tagged statement and augments tagged entity fields with an alias.
	 *
	 * @param string $sql the tagged sql
	 * @param string $alias the alias to augment with.
	 * @return string the tagged statement with aliases inserted.
	 */
	public final function translateAlias($sql, $alias)
	{
		$parsed = array();
	
		/*
		 * Get all the entity matches for a string between the tags specified.
		 *
		 * Full regex matches (eg. <EfirstNameE>) are returned into $parsed[0].
		 * The corresponding sub-pattern matches between the tags (eg. firstName) are returned into $parsed[1].
		 * Sub-pattern matches are specified by parentheses in the regex string below.
		 */
		$regex = "/<E([a-zA-Z0-9_]*)E>/";
		preg_match_all($regex, $sql, $parsed);
		$translated = $sql;
		if ($parsed)
		{
			$originals = $parsed[0];
			$replaceAs = array();
			foreach($originals as $i => $original)
			{
				/*
				 * No duplicates. We don't want to str_replace() something that
				 * has already been replaced. The replacement is always the same for an original.
				 */
				if (!isset($replaceAs[$original]))
				{
					$replacement = $alias . "." . $original;
					$replaceAs[$original] = $replacement;
					$translated = str_replace($original, $replacement, $translated);
				}
			}
		}
		return $translated;
	}
	/**
	 * Append a tagged statement with another tagged statement portion.
	 *
	 * Used for Model statements that may require specialised where clauses e.g IN clause or ORDER BY with
	 * LIMIT/OFFSET to get the desired result set.
	 *
	 * This method assumes that the $append statement does not begin with "and" or "AND".
	 *
	 * Grouping is not supported for Model CRUD operations because it is a summing/reporting function.
	 *
	 * @param string $statement
	 * @param string $appendPortion
	 */
	public function append($statement, $appendPortion)
	{
		$result = $statement;
		
		$portion = trim($appendPortion);
		/*
		 * Reduce white space to one
		 */
		$portion = preg_replace("!\s+!", " ", $portion);
	
		$isControlFunction = false;
		foreach(self::$controlFunctions as $controlFunction)
		{
			$startOfPortion = substr($portion, 0, strlen($controlFunction));
			if (strtolower($startOfPortion) === $controlFunction)
			{
				$isControlFunction = true;
				break;
			}
		}
	
		if ($isControlFunction)
		{
			/*
			 * We don't care if we already have a where clause
			 * or not, just append with a space.
			 */
			$result .= " $portion";
		}
		else
		{
			/*
			 * Append with an "and" if the statement has a WHERE clause.
			 */
			$result .= $this->hasWhere ? " AND $portion" : " WHERE $portion";
		}
		return $result;
	}
	
	/**
	 * Gets the function to translate a property into it's database query form.
	 * 
	 * @return callable this function translates the property from the available properties to its database formatted value.
	 * @throws ModelException if the property does not exist in $properties.
	 */
	public function getPropertyTranslator()
	{
		$propertyTranslator = function($property, array $properties) 
		{
			if(!array_key_exists($property, $properties))
			{
				/*
				 * translate() expects that each property it is looking for exists in 
				 * the $properties array no matter if they are null.
				 */
				throw new ModelException("$property does not exist in properties.");
			}
			return $this->db->formatProperty($properties[$property]);
		};
		
		return $propertyTranslator;
	}
}
?>