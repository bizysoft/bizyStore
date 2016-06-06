<?php
namespace bizySoft\bizyStore\model\statements;

use \PDO;
use \PDOStatement;
use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\core\ModelOptions;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
/**
 * Provide support for Model Join operations.
 * 
 * You can use this class to join tables with others via a resolve path, table columns specified don't necessarily have to be 
 * declared as foreign keys.
 * 
 * The resolve path should be consistent with the database design.
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class Join
{
	const OPTION_SWIZZLE = "swizzle";
	const OPTION_JOIN_TYPES = "joinType";
	const INNER_JOIN = "INNER JOIN";
	const LEFT_OUTER_JOIN = "LEFT OUTER JOIN";
	const RIGHT_OUTER_JOIN = "RIGHT OUTER JOIN";
	
	/**
	 * 
	 * @var DB
	 */
	private $db = null;
	
	/**
	 * The JoinSpec's to control the joins.
	 * 
	 * @var array
	 */
	private $joinSpecs = array();
	
	/**
	 * It is an (N-1) array where N is the number of JoinSpecs, defaulted to self::INNER_JOIN.
	 * 
	 * If specified, can contain join options self::INNER_JOIN, self::LEFT_OUTER_JOIN, self::RIGHT_OUTER_JOIN.
	 * 
	 * @var array
	 */
	private $joinOptions = array();
	
	/**
	 * Do we swizzle or not?
	 * 
	 * @var boolean
	 */
	private $swizzle = true;
	
	/**
	 * Do we index by database key or not?
	 * 
	 * @var boolean
	 */
	private $indexByKey = false;
	
	/**
	 * The prepared statement prefix for properties.
	 * 
	 * @var string
	 */
	private $prefix = "";
	
	/**
	 * The Model at the base of the resolve path.
	 * 
	 * @var Model
	 */
	private $baseModel = null;
	
	/**
	 * Construct with the resolve path. 
	 * 
	 * Takes the same basic parameters as QueryPreparedStatement, the query being a resolvePath.
	 * 
	 * The resolve path consists of JoinSpecs separated by '=>'.
	 * 
	 * e.g.  "author(id) => authorBooks(authorId, bookId) => book(id)"
	 *
	 * The JoinSpecs together define the relationships from the source table (the first JoinSpec) to the information 
	 * in the dest table (the last JoinSpec). The resolvePath uses the database table and column names just as you 
	 * would declare a foreign key in bizySoftConfig.
	 * 
	 * Here's an example that uses multiple columns, these will become an "AND" in the join.
	 * 
	 * e.g. "uniqueKeyMember(firstName.lastName.dob) => uniqueKeyMembership(memberFirstName.memberLastName.memberDob)"
	 * 
	 * @param DB $db database to work on.
	 * @param string $resolvePath definition of the database relationship path to resolve.
	 * @param array $builder properties for the where clause based on the first JoinSpec.
	 * @param array $options can be the normal prepare options, additionally it can contain self::OPTION_SWIZZLE a boolean 
	 * value to turn on/off. The default is true. self::OPTION_JOIN_TYPES specifying the join types between each JoinSpec.
	 * @see JoinSpec for more info.
	 */
	public function __construct(DB $db, $resolvePath, array $options = array())
	{
		$this->db = $db;
		$this->swizzle = isset($options[self::OPTION_SWIZZLE]) ? $options[self::OPTION_SWIZZLE] : true;
		$this->indexByKey = isset($options[ModelOptions::OPTION_INDEX_KEY]) ? $options[ModelOptions::OPTION_INDEX_KEY] : false;
		/*
		 * Get the join types for the JoinSpecs (if any) this should be an (N-1) array where N is the number of JoinSpecs.
		 */
		$this->joinOptions = isset($options[self::OPTION_JOIN_TYPES]) ? $options[self::OPTION_JOIN_TYPES] : array();
		
		$joinSpecs = explode("=>", $resolvePath);
		$modelNameSpace = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_NAMESPACE);
		foreach ($joinSpecs as $i => $joinSpec)
		{
			/*
			 * Create a JoinSpec for each on the resolvePath.
			 */
			$thisJoinSpec = new JoinSpec(trim($joinSpec));
			$this->joinSpecs[] = $thisJoinSpec;
			/*
			 * Set the Model for the JoinSpec. 
			 * 
			 * This is used as a proptotype for cloning later.
			 */
			$modelName = "$modelNameSpace\\" . ucfirst($thisJoinSpec->table);
			$model = new $modelName(null, $db);
			$model->setPersisted(true);
			/*
			 * Set the base Model for the Join first time round.
			 */
			if (!$this->baseModel)
			{
				$this->baseModel = $model;
			}
			$thisJoinSpec->db = $db;
			$thisJoinSpec->model = $model;
			$thisJoinSpec->columnSchema = $model->getColumnSchema();
			$thisJoinSpec->keyCandidateSchema = $model->getKeyCandidateSchema();
			$thisJoinSpec->db = $db;
		}
	}
	
	/**
	 * Set the prefix for generation of property keys.
	 * 
	 * @param string $prefix
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	/**
	 * Get the base Model for this join. This is the first Model specified by the resolve path.
	 * 
	 * @return Model
	 */
	public function getBaseModel()
	{
		return $this->baseModel;
	}
	
	/**
	 * Swizzle indexes based on the array passed in to aid in ordering the data.
	 * 
	 * If we want to swizzle then leave the first index as is, the rest is in reverse order.
	 * 
	 * Swizzling is ON by default. It's a technique that allows the Model data to be returned in the appropriate order and only has any
	 * effect on relationships of more than one hop i.e. many-to-many and larger.
	 * 
	 * e.g. a many-to-many join from author to book through the junction table authorBook may look like this
	 * 
	 *          $resolvePath = "author(id) => authorBook(authorId, bookId) => book(id)";
	 *          
	 * By default swizzled Models would be brought back in this order.
	 * 
	 *          Author->Book->AuthorBook
	 *          
	 * which is what you would expect for many-to-many relationships. So each Author Model will contain a number of Book Models each having
	 * exactly one AuthorBook associated.
	 * 
	 * You can turn swizzling off by setting the Join::OPTION_SWIZZLE option to false, in which case the Models 
	 * are returned in declared order.
	 * 
	 *          Author->AuthorBook->Book
	 *          
	 * No matter how long the resolve path is, this method will perform a single database query. 
	 * Only the resolve path relationships are resolved.
	 *  
	 * @return array 
	 */
	private function swizzle(array $toSwizzle)
	{
		$swizzled = array();
		$original = array_keys($toSwizzle);
		
		if ($this->swizzle)
		{
			$reverse = array_reverse($original);
			$swizzled[0] = array_pop($reverse);
			$swizzled = array_merge($swizzled, $reverse);
		}
		else
		{
			$swizzled = $original;
		}

		return $swizzled;
	}
	
	/**
	 * Gets the alias for a table name.
	 *
	 * @param string $tableName
	 * @return string the alias for the tableName or the implicit alias for this join if tableName is not specified.
	 */
	public function getBaseAlias($tableName)
	{
		$result = $tableName ? $tableName : $this->baseModel->getTableName();
		
		return "alias__{$result}0";
	}
	
	/**
	 * Transform the JoinSpecs into a tagged query.
	 * 
	 * The query returned can be easily translated to any of our supported databases.
	 *
	 * @param array $properties
	 * @return string the tagged query
	 */
	public function taggedQuery(array $properties = array())
	{
		$taggedQuery = "";
		if ($this->joinSpecs)
		{
			/*
			 * Create the select clause
			 */
			$select = "SELECT ";
			$comma = "";
			$aliasPrefix = "alias__";
			foreach($this->joinSpecs as $i => $joinSpec)
			{
				$alias = "$aliasPrefix$joinSpec->table$i";
				$select .= "$comma $alias.*";
				$comma = ",";
			}
			/*
			 * Create the from clause
			 */
			$from = " FROM";
			/*
			 * Create the joins in the order that they were declared
			 */
			$join = "";
			$prevSpec = null;
			foreach ($this->joinSpecs as $i => $joinSpec)
			{
				$table = $joinSpec->table;
				$specAlias = "$aliasPrefix$table$i";
				if ($prevSpec)
				{
					/*
					 * Produce the join.
					 */
					$prevEntities = $prevSpec->assocColumns;
					$specEntities = $joinSpec->columns;
					$prevAlias = $aliasPrefix . $prevSpec->table . ($i-1);
					$joinType = isset($this->joinOptions[$i-1]) ? $this->joinOptions[$i-1] : self::INNER_JOIN;
					$join .= " $joinType <Q{$table}Q> AS $specAlias ON (";
					$and = "";
					foreach ($specEntities as $j => $specEntity)
					{
						$prevEntity = $prevEntities[$j];
						$join .= "$and$specAlias.<E{$specEntity}E> = $prevAlias.<E{$prevEntity}E>";
						$and = " AND ";
					}
					$join .= ")";
				}
				else
				{
					$join .= " <Q{$table}Q> $specAlias";
				}
				$prevSpec = $joinSpec;
			}
			/*
			 * Create the where clause.
			 */
			$joinSpec = reset($this->joinSpecs);
			$tableAlias = $this->getBaseAlias($joinSpec->table);
			$propertiesWhere = "";
			$and = "";
			foreach ($properties as $columnKey => $columnValue)
			{
				$propertiesWhere .= "$and$tableAlias.<E{$columnKey}E>";
				
				$prefixedColumnKey = "$this->prefix$columnKey";
				$propertiesWhere .= " = <P{$columnKey}P>";
				$and = " AND ";
			}
			$where = $propertiesWhere ? " WHERE $propertiesWhere" : "";
			
			$taggedQuery = "$select$from$join$where";
		}
		
		return $taggedQuery;
	}
	
	/**
	 * Convert zero-based results to named properties.
	 * 
	 * @param array $row a zero based array of column data from a database query.
	 * @return array an array of associative arrays of column data that have named properties as per the schema.
	 */
	private function split(array $row, $dbId)
	{
		$result = array();
		
		$start = 0;
		foreach ($this->joinSpecs as $joinSpec)
		{
			$columnSchema = array_keys($joinSpec->columnSchema->get($dbId));
			/*
			 * This is the conversion
			 */
			$length = count($columnSchema);
			$result[] = array_combine($columnSchema, array_slice($row, $start, $length));
			$start += $length;
		}
		return $result;
	}
	/**
	 * Resolves Models based on the rows fetched from the executed statement.
	 * 
	 * Note that Models returned from this method can be composed of null properties if an outer join is 
	 * specified.
	 * 
	 * @param array $properties properties to resolve with.
	 * @return array an array of Models specified by the resolvePath form the constructor.
	 */
	public function resolve(PDOStatement $data)
	{
		$result = array();
		/*
		 * Resolve all Models. 
		 * 
		 * Get ready to transform the data by creating the swizzled indexes.
		 */
		$swizzled = $this->swizzle($this->joinSpecs);
		$dbId = $this->db->getDBId();
		$shadowModels = array_fill(0, count($this->joinSpecs) - 1, array());
		while($row = $data->fetch(PDO::FETCH_NUM))
		{
			$prevJoinKey = "";
			$prevJoinSpec = null;
			$prevModel = null;
			/*
			 * Convert zero-based results to named properties
			 */			
			$rowProperties = $this->split($row, $dbId);
			foreach ($swizzled as $i => $j)
			{
				/*
				 * $i is the original index, $j is the swizzled index.
				 * 
				 * We process the results on the swizzled index.
				 */
				$joinSpec = $this->joinSpecs[$j];
				$properties = $rowProperties[$j];
				/*
				 * Normalise the data into the appropriate Models. What we want to do here is to store Models
				 * into the Model hierarchy taking care of redundancy as we go.
				 */	
				$modelKey = $joinSpec->keyCandidateSchema->getKeyValuesAsString($dbId, $properties);
				if(!$prevModel)
				{
					/*
					 * The first Model may have many duplicates so only construct a new Model when required
					 * and store in the array we intend to return.
					 */
					if (!isset($result[$modelKey]))
					{
						$model = clone $joinSpec->model;
						$model->set($properties);
						$result[$modelKey] = $model;
					}
					else 
					{
						 $model = $result[$modelKey];
					}
				}
				else
				{
					/*
					 * Subsequent Models may still have duplicates so we use a shadow key with the shadow Models to 
					 * eliminate redundancy where required.
					 */
					$shadowKey = $prevJoinKey ? "$prevJoinKey-$modelKey" : $modelKey;
					if (!isset($shadowModels[$j][$shadowKey]))
					{
						$model = clone $joinSpec->model;
						$model->set($properties);
						$shadowModels[$j][$shadowKey] = $model;
						/*
						 * Set into the previous Model which will be returned as part of the results.
						 */
						$relName = "$prevJoinSpec->table.$joinSpec->table";
						$prevModel->append($relName, $model, $this->indexByKey ? $modelKey : null);
					}
					else
					{
						$model = $shadowModels[$j][$shadowKey];
					}
					$modelKey = $shadowKey;
				}
				$prevJoinKey = $modelKey;
				$prevJoinSpec = $joinSpec;
				$prevModel = $model;
			}
		}
		/*
		 * Return the Models with all the other Model's set into them with their respective 
		 * relationship names.
		 */
		return $this->indexByKey ? $result : array_values($result);
	}
}
?>