<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\model\statements\CreatePreparedStatement;
use bizySoft\bizyStore\model\statements\FindPreparedStatement;
use bizySoft\bizyStore\model\statements\JoinPreparedStatement;
use bizySoft\bizyStore\model\statements\UpdatePreparedStatement;

/**
 * The Model class represents a database table and has access to database schema information for CRUD 
 * (Create, Read (Find), Update, Delete) functionality.
 * 
 * A Model object represents a row of data in a database table. The properties defined in a Model object 
 * represent columns in a table row. CRUD operations are based on Model objects. 
 * 
 * Generated Model classes hold the database connection and schema information. All database interactions are 
 * deferred to the generated class through interface methods.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license http://www.bizysoft.com.au/LICENSE.html GNU GPL. See the LICENSE file with this distribution.
 */
abstract class Model implements ModelOptions, ModelI, SchemaI
{
	/**
	 * The array of property names/values that model columns in a database table row.
	 * 
	 * May also hold properties that don't appear in the database schema.
	 *
	 * @var array associative array of column name/value.
	 */
	private $properties = array();
	/**
	 * For persisted Models, this caches the original schema properties that have been changed by set().
	 * 
	 * @var array
	 */
	private $dirtyProperties = array();
	
	/**
	 * Indicator for the Model's persistent state in the database.
	 * 
	 * @var boolean
	 */
	private $persisted = false;
	
	/**
	 * The properties passed in are set into the Model object. This is a protected constructor, which is only called 
	 * from a generated class. 
	 *
	 * Model objects can possibly be constructed through the __set magic method from a statement fetch() or fetchAll() through a
	 * Statement or PreparedStatement class using the CLASS_NAME option set to a Model class. In this case we don't want to set the 
	 * properties passed through because the constructor is called after the properties are set.
	 *
	 * @param array $properties the property name/values that model columns in a database table row.
	 * @throws Exception
	 */
	protected function __construct(array $properties)
	{
		if (!$this->properties)
		{
			$this->properties = $properties;
		}
		else
		{
			/*
			 * Built from a fetch/fetchAll
			 */
			$this->persisted = true;
		}
	}
	
	/**
	 * Append a property name/value to an array property with the specifed index.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @param string $index
	 */
	public function append($name, $value, $index = null)
	{
		if (!isset($this->properties[$name]))
		{
			$this->properties[$name] = array();
		}
		if ($index)
		{
			$this->properties[$name][$index] = $value;
		}
		else
		{
			$this->properties[$name][] = $value;
		}
	}
	
	/**
	 * Set a Model's properties from an associative array.
	 *
	 * Use as a bulk setter for properties in the Model. This method replaces any currently stored properties that 
	 * match the ones passed in. Other properties already in the Model object are preserved. Properties passed in that 
	 * don't already exist in the Model object are set.
	 * 
	 * You can set any property you like. Only those properties that have a corresponding schema entry will be 
	 * recognised as a database property. This potentially allows you to store any additional information that your 
	 * application may require that is not necessarily related to the Model's database schema.
	 * 
	 * The replaced properties are returned which has the benefit of allowing you to construct a new Model object or 
	 * use the result if the need arises.
	 *
	 * @param array $properties an associative array of the properties to set within the Model object.
	 * @return array an associative array of the replaced properties (propertyName => value, ...).
	 */
	public function set(array $properties)
	{
		$oldProperties = array();
		
		if($properties)
		{
			if ($this->properties)
			{
				$oldProperties = array_intersect_key($this->properties, $properties);
					
				if ($this->persisted)
				{
					/*
					 * Store the old persisted properties that have been changed as dirty.
					 * 
					 * This keeps the original schema properties that have been changed no matter
					 * how many times set() is called, gives us a handle on all the old schema
					 * properties that may need to be used. 
					 */
					if ($oldProperties)
					{
						/*
						 * Note that we don't check that dirty properties have been changed back to their original value.
						 * This can lead to performance problems with large column data. Once a property is dirty, it's dirty
						 * until resetDirty() is called.
						 */
						$this->dirtyProperties += $this->getSchemaProperties($oldProperties);
					}
				}
				/*
				 * Do a union on the arrays, properties we want set are on the left of the + operator.
				 */
				$this->properties = $properties + $this->properties;
			}
			else
			{
				$this->properties = $properties;
			}
		}
		return $oldProperties;
	}
	
	/**
	 * Reset the Model with the properties passed in.
	 * 
	 * Clears all the old properties in the Model and sets the new properties passed in. Returns the old properties.
	 * You can pass an empty array to clear the Model object. 
	 * 
	 * The Model will loose its persisted and dirty status.
	 * 
	 * @param array $properties an associative array of the new properties (propertyName => value, ...).
	 * @return array an associative array of the old properties (propertyName => value, ...).
	 */
	public function reset(array $properties = array())
	{
		$result = $this->get();
		$this->properties = $properties;
		$this->persisted = false;
		$this->dirtyProperties = array();
		
		return $result;
	}
	
	/**
	 * Convenience method to set a Model object's single property to the value passed in.
	 *
	 * @param string $property the name of the property to set.
	 * @param mixed $value the value of the property to set.
	 * @return array an associative array of the replaced property (propertyName => value) or an 
	 *               empty array if nothing was replaced.
	 */
	public function setValue($property, $value)
	{
		/*
		 * Convert to associative array and call set().
		 */
		return $this->set(array($property => $value));
	}
	
	/**
	 * Get a Model's properties into an associative array as per the property keys passed in. 
	 * 
	 * Useful for getting multiple properties at once. Calling without a parameter will get all the properties 
	 * within the Model object.
	 * 
	 * get() has the advantage over getValue() that it will always return definite entries in the Model even if the
	 * property value resolves to boolean false (which includes null, etc...).
	 * 
	 * eg.
	 * <code>
	 * ...
	 * ...
	 * if(!$model->get(array("name" => "nullOrValue")))
	 * {
	 *      // Is not ambiguous. It means the property "name" does not exist in the Model object.
	 *      // "nullOrValue" is ignored
	 * }
	 * ...
	 * ...
	 * </code>
	 * 
	 * @param array $properties an associative array of (propertyName => nullOrValue, etc...) to get. The values are 
	 * irrelevant ie. can be array(propertyName => null, etc...) , uses the propertyName(s) as a key to get the value(s).
	 *       
	 * @return array an associative array of (propertyName => value, etc...) matching the $properties within the Model. 
	 * $properties that may be specified but don't exist in the Model will not be returned.
	 */
	public function get(array $properties = array())
	{
		$result = $this->properties;
		
		if ($properties)
		{
			/*
			 * Get the properties wanted
			 */
			$result = array_intersect_key($this->properties, $properties);
		}
		
		return $result;
	}
	
	/**
	 * Get the Model object's property value from the $propertyName passed in.
	 * 
	 * Note that properties can take on any value including those that resolve to boolean false. So this method 
	 * has no way to indicate that the property does not exist in the Model object.
	 * 
	 * eg.
	 * <code>
	 * ...
	 * ...
	 * if(!$model->getValue("name"))
	 * {
	 *		// Is ambiguous. 
	 *		// It could mean that the value simply resolves to boolean false (this includes null, empty string, etc)
	 *		// OR the value does not exist in the model.
	 * }
	 * ...
	 * ...
	 * </code>
	 * 
	 * A more sophisticated method is to use get(array($propertyName => null)) to determine if the property actually 
	 * exists in the Model and has a definite value, including those that resolve to boolean false.
	 * 
	 * @param string $propertyName the property name we want the value of.
	 *       
	 * @return mixed the property value or null if the $propertyName does not exist in the Model. 
	 */
	public function getValue($propertyName)
	{
		return isset($this->properties[$propertyName]) ? $this->properties[$propertyName] : null;
	}
	
	/**
	 * Strip this Model's properties of null values.
	 *
	 * Used for stripping null properties from Model's that must have only definite values. This is 
	 * particularly useful for Model's returned from the database that may have null default column values. 
	 * 
	 * See PreparedStatementBuilder and how nulls affect where clauses.
	 * 
	 * @return array an associative array of the null Model properties that have been stripped from the Model.
	 */
	public function strip()
	{
		$strippedOfNulls = self::stripNulls($this->properties);
		$nullsStripped = array_diff_key($this->properties, $strippedOfNulls);
		
		$this->properties = $strippedOfNulls;
		
		return $nullsStripped;
	}
	
	/**
	 * Strip the array passed in of null values.
	 *
	 * @return array
	 */
	public static function stripNulls(array $properties)
	{
		return array_filter($properties, function ($value) {
			return $value !== null;
		});
	}
	
	/**
	 * Gets the Model's non-sequenced schema properties, either from itself or another set of properties perhaps related to 
	 * this schema. 
	 * 
	 * Non-sequenced properties are those that are not automatically allocated by the database and therfore can be 
	 * used in an insert or update statement.
	 *
	 * @param array $properties the properties to use. Calling this method with no parameters will use the Model's properties.
	 * @return array an associative array of schema (propertyName => value, ...) in the Model that can be used for 
	 * an insert/update.
	 */
	public function getNonSequencedProperties(array $properties = array())
	{
		$propertiesToSearch = $properties ? $properties : $this->properties;
		
		$schemaProperties = $this->getSchemaProperties($propertiesToSearch);
		$sequences = $this->getSequenceSchema()->get($this->getDBId());
		
		return array_diff_key($schemaProperties, $sequences);
	}
	
	/**
	 * Get the Model's schema properties, either from itself or another set of properties related to this schema.
	 * 
	 * Calling this method with no parameters will use the Model's properties.
	 * 
	 * Properties that don't have a schema entry will be ignored. You can store any property you like in a 
	 * Model object. It is only recognised as a database schema property if it's name appears in the schema.
	 *
	 * @param array $properties the properties to use. Calling this method with no parameters will use the Model's properties.
	 * @return array an associative array of schema (propertyName => value, ...).
	 */
	public function getSchemaProperties(array $properties = array())
	{
		$propertiesToSearch = $properties ? $properties : $this->properties;
		/*
		 * Get the intersection with the schema properties.
		 */ 
		return array_intersect_key($propertiesToSearch, $this->getColumnSchema()->get($this->getDBId()));
	}
		
	/**
	 * Gets the defined properties and their values that can form a key, either from itself or another set of properties 
	 * related to this schema.
	 * 
	 * Used for invoking key searches instead of relying on the full contents of a Model.
	 * The first full key match is returned. If no full key match is found then a key search cannot be done within a 
	 * find() operation.
	 * 
	 * eg.
	 * <code>
	 * ...
	 * ...
	 * $keyProperties = $model->getKeyProperties();
	 * if ($keyProperties)
	 * {
	 *   $oldProperties = $model->reset($keyProperties);
	 * }
	 * // This will do a key search if keyProperties are found
	 * $models = $model->find();
	 * ...
	 * // perhaps do something with the $oldProperties
	 * ...
	 * ...
	 * </code>
	 * @param array $properties the properties to build a key from. Calling this method with no parameters will use the 
	 * Model's properties.
	 * @return array an array of (propertyName => value, etc... ) that fills a key candidate or an empty array if no key 
	 * candidates can be filled.
	 * @see SchemaI::getKeyProperties()
	 */
	public function getKeyProperties(array $properties = array())
	{
		$propertiesToSearch = $properties ? $properties : $this->properties;
		
		$keyCandidateSchema = $this->getKeyCandidateSchema();
		
		return $keyCandidateSchema->getKeyProperties($this->getDBId(), $propertiesToSearch);
	}
	
	/**
	 * Copy a model object by value.
	 * Copy only the properties that are set in src.
	 *
	 * If a $copyTo object is defined, then the properties from $src are merged with it, effectively updating $copyTo
	 * with the new values.
	 *
	 * @param Model $src
	 * @param Model $copyTo
	 * @return Model either the updated copyTo Model or a copy of the $src Model or null if $copyTo is not the same class as $src
	 */
	public static function modelCopy(Model $src, Model $copyTo = null)
	{
		$class = get_class($src);
		$dest = $copyTo ? $copyTo : new $class(null, $src->getDB());
		
		// only copy objects of the same class
		if ($dest instanceof $class)
		{
			// Get all the properties that have been set in src and set them in the destination
			$dest->set($src->properties);
		}
		else
		{
			$dest = null;
		}
		return $dest;
	}
	
	/**
	 * Test each of $thisModel's schema properties for equivalence with the $otherModel.
	 *
	 * @param Model $thisModel the Model object
	 * @param Model $otherModel the Model object for comparison
	 * @return boolean true if each object's corresponding properties are equal, false otherwise
	 */
	public static function modelEquals(Model $thisModel, Model $otherModel)
	{
		$result = false;
		$otherClass = get_class($otherModel);
		
		if ($thisModel instanceof $otherClass)
		{
			$src = $thisModel->getSchemaProperties();
			$dest = $otherModel->getSchemaProperties();
			$result = $src == $dest; // Just check if we have the same keys/values, order is irrelevant.
		}
		return $result;
	}
	
	/**
	 * Get the schema properties that differ between the two Model objects.
	 *
	 * Return the associative array of $otherModel's properties that are different to $thisModel's.
	 *
	 * @param Model $thisModel Model object used as a basis.
	 * @param Model $otherModel Model object to get diffs from.
	 * @return array an associative array of property names => property values that are different.
	 */
	public static function modelDiff(Model $thisModel, Model $otherModel)
	{
		$result = array();
		$otherClass = get_class($otherModel);
		
		if ($thisModel instanceof $otherClass)
		{
			// Get all the valid schema properties from the class
			$thisProperties = $thisModel->getSchemaProperties();
			$otherProperties = $otherModel->getSchemaProperties();
			
			$result = array_diff_assoc($otherProperties, $thisProperties);
		}
		return $result;
	}
	
	/**
	 * This method correctly escapes characters in this Model's schema properties that may affect your query/statement.
	 * 
	 * You can use this before any database transactions commence on the Model if you are not using
	 * prepared statements.
	 */
	public function escapeModel()
	{
		// Go through the $properties array and get the property value
		$db = $this->getDB();
		foreach ($this->getSchemaProperties() as $propertyName => $propertyValue)
		{
			// The property is a string so escape it
			$this->properties[$propertyName] = $db->escapeProperty($propertyValue);
		}
	}
	
	/**
	 * Gets the new sequence value(s) for this Model.
	 *
	 * In most databases, sequences (or auto-incremented columns) are used to produce a key for a table column, the
	 * implementation varies but has the same aim of inserting a unique identifier. bizyStore interprets
	 * these columns as being a sequence with or without a name. Some databases require us to pass the name of the
	 * sequence to get the last value allocated.
	 *
	 * On an insert, the value of a sequence, if defined in your table, is inserted into it's column automatically
	 * by the database. This method gets the new sequence value(s).
	 *
	 * This is necessary to be able to use the sequence value as soon as it is created. You may want to 'create' other
	 * Model's with the value or be able to 'find' the Model instance from the database again with any reliability.
	 *
	 * @param Model $model
	 * @return array an associative array of (property name => new sequence value) for the $model. There can be more
	 * than one sequence specified for a table.
	 */
	private function getSchemaSequences()
	{
		$result = array();
		/*
		 * Populate sequences if any.
		 */
		$sequences = $this->getSequenceSchema();
		$db = $this->getDB();
		foreach ($sequences->get($db->getDBId()) as $columnName => $sequenceName)
		{
			/*
			 * Do our best to find a sequenceValue for all the sequenced columns
			 */
			$sequenceVal = ($sequenceVal = $db->getInsertId($sequenceName)) ? $sequenceVal :
								($sequenceName ? $db->getCurrentSequence($sequenceName) : null);
	
			if ($sequenceVal)
			{
				$result[$columnName] = $sequenceVal;
			}
		}
		return $result;
	}
	
	/**
	 * CRUD method to create this Model in the database based on the properties that are set in this Model.
	 *
	 * Sequenced properties are ignored on insert, allowing the database to allocate these values.
	 * The sequenced values that the database allocates are populated back into the Model after the insert. Other 
	 * values that the database allocates (i.e column defaults) will not be retreived. Use find() to get these values
	 * if required.
	 *
	 * @param $options Options are generally ignored for a create()
	 * @return PDOStatement to allow access to executed statement info.
	 * @throws ModelException
	 */
	public function create($options = array())
	{
		$statement = new CreatePreparedStatement($this, $options);
		
		$result = $statement->execute();
		/*
		 * The model has now been persisted into the database. 
		 * Set the Model with the sequenced properties from the insert.
		 */
		$sequencedProperties = $this->getSchemaSequences();
		if ($sequencedProperties)
		{
			/*
			 * Set the sequenced properties into the Model.
			 */
			$this->set($sequencedProperties);
		}
		/*
		 * The Model now has a persistent state. Set the persisted flag.
		 */
		$this->setPersisted(true);
		return $result;
	}
	
	/**
	 * Get's a CreatePreparedStatement to use for multiple inserts with different properties.
	 * 
	 * As usual, subsequent properties used when executing must have the same keys as the original Model.
	 * 
	 * Note that this may have a use where speed is an issue and that retrieval of sequenced properties 
	 * is not possible via this method. 
	 * 
	 * @param array $options
	 * @return \bizySoft\bizyStore\model\statements\CreatePreparedStatement
	 */
	public function getCreateStatement($options = array())
	{
		return new CreatePreparedStatement($this, $options);
	}
	
	/**
	 * CRUD method to update row(s) in the database based on the properties that are set in this Model.
	 *
	 * Use the properties in this Model to find the old row(s) in the db and use the $newProperties to update 
	 * the row(s) found. Takes into account any dirtied properties as well.
	 * 
	 * This method can use optimistic techniques if specified and will lock the row(s) found. It must be called in a fault tolerant 
	 * transactional scenario, using endTransaction() to release the locks.
	 * 
	 * To use optimistic techniques properly you can either: 
	 * 
	 * + set ALL the $oldModel properties to invoke version-less locking.
	 * + set the $oldModel key properties and set the $options with OptimisticUpdate::OPTION_LOCK_PROPERTY and the name of the versioned 
	 * property (column) before calling this method.
	 * 
	 * ie. $options = array(OptimisticUpdate::OPTION_LOCK_PROPERTY => "name of your versioned property");
	 *
	 * Care must be taken when using Model's that have no unique key, or using only non-unique properties 
	 * in the Model. Multiple rows can be updated in this scenario, by default this will throw an 
	 * Exception. For version-less Models, you can set the update policy of the transaction to 
	 * DBTransaction::UPDATE_POLICY_MULTIPLE to allow this behaviour.
	 *
	 * Care must still be taken when using unique keys that are not sequenced. Changing a key property may cause a 
	 * duplicate error. Use of a primary key is the best way to be sure you are updating a row correctly.
	 * 
	 * @param array $newProperties  new schema property values.
	 * @param o $oldModelObj the old model object to update.
	 * @return PDOStatement for access to statement info. Returns false if there is nothing found to update.
	 * @throws ModelException when a failure occurs.
	 * @see OptimisticWrite
	 */
	public function update(array $newProperties = array(), array $options = array())
	{
		$updater = new OptimisticUpdate($this, $newProperties, $options);
		return $updater->execute();
	}
	
	/**
	 * CRUD method to delete a row(s) from the database based on the properties that are set in this Model.
	 *
	 * This method uses optimistic techniques if specified and will lock the row(s) found. It has all the caveats
	 * that update() has.
	 * 
	 * See Model::update() for more information.
	 * 
	 * @param Model $oldModelObj the Model object to delete.
	 * @return PDOStatement for access to statement info. Returns false if there is nothing found to delete.
	 * @throws ModelException when a failure occurs.
	 */	
	public function delete(array $options = array())
	{
		$updater = new OptimisticDelete($this, $options);
		return $updater->execute();
	}
	
	/**
	 * CRUD method to find Model objects in the database based on the properties that are set in this Model.
	 * 
	 * This method is 'lazy' in that it will not populate any relationships defined in the Model schema.
	 * 
	 * For Model::find() methods, OPTION_INDEX_KEY can be used to index the result set based on the key field(s) 
	 * in the Model. OPTION_INDEX_KEY can either resolve to a boolean value or the name of a key candidate for the Model.
	 * 
	 * The key candiate name is the key column names concatenated with ".".
	 *
	 * eg. 'id' or 'firstName.lastName.dob', etc...
	 *
	 * A boolean value resolving to true will use the first key candidate found, a false value will turn off key indexing
	 * and return the result set as the default zero based array.
	 *
	 * @param $options array
	 * @return array an array of Models found.
	 * @throws ModelException
	 */
	public function find($options = array())
	{
		$statement = new FindPreparedStatement($this, $options);
		return $statement->objectSet();
	}
	
	/**
	 * Get's a FindPreparedStatement based on the Model for use with different properties.
	 * 
	 * As usual, subsequent properties used when executing must have the same keys as the original Model.
	 * 
	 * Note that this may have a use where speed is an issue.
	 * 
	 * @param array $options same options as find()
	 * @return \bizySoft\bizyStore\model\statements\FindPreparedStatement
	 */
	public function getFindStatement($options = array())
	{
		return new FindPreparedStatement($this, $options);
	}
	
	/**
	 * Gets an iterator to traverse the result set.
	 * 
	 * Note that this will execute the query.
	 *
	 * @param $options array
	 * @return StatementIterator
	 * @throws ModelException
	 */
	public function iterator($options = array())
	{
		$statement = new FindPreparedStatement($this, $options);
		return $statement->iterator($this->get(), $options);
	}
	
	/**
	 * Find a single Model object in the database based on properties that are set in this Model.
	 *
	 * This method is 'lazy' in that it will not populate any relationships defined in the Model schema.
	 * 
	 * There is no restriction on the properties that can be set in the Model. If you are expecting to find a unique
	 * result, then the properties that are unique in your Model should be filled in before calling this method. Or you
	 * can try your luck.
	 * 
	 * @param $options array
	 * @return Model the unique Model instance or false if there is no unique instance.
	 * @throws ModelException
	 */
	public function findUnique($options = array())
	{
		$instances = $this->find($options);
		
		$result = count($instances) == 1;
		if ($result)
		{
			$result = reset($instances);
		}
		return $result;
	}

	/**
	 * Support for pessimistic locking if required.
	 *
	 * Finds the Model instance(s) in the database and locks the rows found.
	 *
	 * This is database specific so we call the equivalent database method. 
	 * 
	 * It should be used within fault tolerant, transactinoal code to release the locks by calling $db->endTransaction().
	 *
	 * @param Model $modelObj
	 * @return array Always returns an associative array of Model objects.
	 * @throws ModelException when a failure occurs.
	 */
	public function findForUpdate()
	{
		$db = $this->getDB();
		return $db->findForUpdate($this);
	}
	
	/**
	 * Realise the relationships for this Model.
	 *
	 * Low impedance (result set processing), High current (amount of queries).
	 * 
	 * realise() is like find(), in addition it 'eagerly' sets ALL the relationships defined by the 
	 * ForeignKey/ForeignKeyReferee Schema's into the Model(s) found. 
	 * 
	 * Models returned are NOT SWIZZLED (see resolve()), the Model relationships are set as they appear in the database.
	 * 
	 * This method can return data structures that would be difficult to achieve using other database query methods. 
	 * Care should be taken to specify Model properties that are specific enough for the data you are concerned with. 
	 * Just like any other database query method, insufficent Model properties or large depths can bring back lots of 
	 * data which may effect performance.
	 * 
	 * The relationships are either Model instances (one) or arrays of Model instances (many) and are stored 
	 * under the relationship's property name in the Model.
	 * 
	 * The property name for a particular relationship is the table name of the declared foreign key and the 
	 * columns of the declared foreign key all concatenated with a '.'. This is true navigating from either end of the relationship.
	 * This must be so because realise() will resolve more than one relationship with another table if
	 * 
	 * eg. From a Member Model
	 *     membership.memberId  -- where 'membership' is the table that the foreignKey is declared in and 'memberId' is, 
	 *                             in this case, the single column that references the parent table.
	 *                             
	 * and from a Membership Model it will have the same name.
	 * 
	 * One-to-many (or one) relationships from this Model can be realised with 1 hops.
	 * Many-to-many relationships from this Model can be realised with 2 hops, which includes the junction 
	 * table information as a Model array.
	 * 
	 * If hops is 0 then realise() will act as a find() on this Model.
	 * 
	 * You can still perform the usual CRUD operations on any realised Model. CRUD operations do not recurse relationships, but you 
	 * can navigate the realised Model to perform CRUD operations on the child Models if you wish.
	 * 
	 * OPTION_INDEX_KEY can be used to index the returned array with database key fields. In this case, if you specify the 
	 * name of the key candidate, it will be used for the parent indexes (those on the Model your are realising). Child Model's 
	 * will pick the most appropriate key candidate if any.
	 * 
	 * @param int $depth the number of relationship hops from this Model to realise. 
	 * @param array $options valid options are OPTION_INDEX_KEY.
	 * @return array an array of Models.
	 */
	public function realise($hops = 0, array $options = array())
	{
		$result = $this->find($options);
	
		if ($hops > 0)
		{
			foreach ($result as $model)
			{
				/*
				 * Kick start the recursion on both ForeignKey*Schema's
				 */
				$foreighKeyRefereeSchema = $model->getForeignKeyRefereeSchema();
				$foreighKeyRefereeSchema->realise($model, $hops, null, $options);
				
				$foreignKeySchema = $model->getForeignKeySchema();
				$foreignKeySchema->realise($model, $hops, null, $options);
			}
		}
		return $result;
	}
	
	/**
	 * Resolve the Model with joins specified by the resolve path. 
	 * 
	 * High impedance (result set processing), Low current (a single query).
	 * 
	 * Similar to realise(), the difference is that resolve() will navigate only the defined relationships via the resolvePath 
	 * in a single database call.
	 * 
	 * By default, returns an array of Model's (the first on the resolve path) containing the subsequent Models on the resolve path 
	 * in swizzled order. (see Join::swizzle()).
	 * 
	 * The specified Model properties define the where clause that starts the resolve, so the Model does not resolve 
	 * itself, but all Models matching the given properties, just like find().
	 *
	 * If resolve path is empty, resolve() will act as a find() on this Model.
	 *                        
	 * @param string $resolvePath
	 * @param array $options valid options are self::OPTION_INDEX_KEY, JoinPreparedStatement::OPTION_SWIZZLE.
	 * @return array an array of Models with relationships set.
	 * @see JoinPreparedStatement
	 */
	public function resolve($resolvePath, $options = array())
	{
		$models = array();
		
		if (!$resolvePath)
		{
			$models = $this->find($options);
		}
		else 
		{
			$resolveProperties = $this->getSchemaProperties();
			$statement = new JoinPreparedStatement($this->getDB(), $resolvePath, $resolveProperties, $options);
			$models= $statement->objectSet();
		}
		return $models;
	}
	
	/**
	 * Allow access to a JoinPreparedStatement based on the Model for use with different properties.
	 * 
	 * As usual, subsequent properties used when executing must have the same keys as the original Model.
	 * 
	 * Note that this may have a use where speed is an issue.
	 *
	 * @param string $resolvePath
	 * @param array $properties
	 * @param array $options
	 */
	public function getJoinStatement($resolvePath, $properties = array(), $options = array())
	{
		$resolveProperties = $properties ? $properties : $this->getSchemaProperties();
		return new JoinPreparedStatement($this->getDB(), $resolvePath, $resolveProperties, $options);
	}
	
	/**
	 * Copy this Model object's properties to another object or make a copy of itself.
	 *
	 * @param Model $other
	 * @return Model a copy or updated copy of $this
	 */
	public function copy($other = null)
	{
		return self::modelCopy($this, $other);
	}
	
	/**
	 * Tests for schema property equality between Model instances.
	 *
	 * @param Model $other
	 * @return boolean true if the Model instances are equal.
	 */
	public function equals($other)
	{
		return self::modelEquals($this, $other);
	}
	
	/**
	 * Get the schema differences between Model instances.
	 *
	 * @param Model $other
	 * @return array an array of the other Model's properties that are different to this Model.
	 */
	public function diff($other)
	{
		return self::modelDiff($this, $other);
	}
	
	/**
	 * Is this Model from the database.
	 * 
	 * @return boolean
	 */
	public function isPersisted()
	{
		return $this->persisted;
	}
	
	/**
	 * Sets the persistent state of the Model.
	 * 
	 * This would NOT typically be used by your application code. It is usually reserved for the core code 
	 * when retreiving Model data.
	 * 
	 * @param unknown_type $persisted
	 */
	public function setPersisted($persisted)
	{
		$this->persisted = $persisted;
	}
	
	/**
	 * Allows resetting of the dirty properties on a persisted Model.
	 */
	public function resetDirty()
	{
		$this->dirtyProperties = array();
	}
	
	/**
	 * Gets the dirty properties for persisted Models
	 */
	public function getDirty()
	{
		return $this->dirtyProperties;
	}
	
	/**
	 * This is the magic setter for Model properties.
	 *
	 * You should NOT call this method from your application code. It is reserved for example,
	 * if using fetch or fetchAll with FETCH_CLASS to set the properties of the Model.
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name, $value)
	{
		$this->properties[$name] = $value;
	}
	
	/**
	 * This is the magic getter for Model properties.
	 *
	 * This function is not generally used, and is presented for completeness
	 * more than anything else. Some SPL functions may use this method.
	 *
	 * @param string $name
	 */
	public function __get($name)
	{
		return $this->properties[$name];
	}
}
?>