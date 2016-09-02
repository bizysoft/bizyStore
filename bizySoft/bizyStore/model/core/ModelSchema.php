<?php
namespace bizySoft\bizyStore\model\core;

use bizySoft\bizyStore\services\core\BizyStoreConfig;

/**
 * Delegates the required Model method calls to the generated schema class instance.
 * 
 * It stores the required components for the delegation and also acts to decouple database specifics from the 
 * Model parent. 
 * 
 * Generated Model classes extend this class.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class ModelSchema extends Model
{
	/**
	 * Optimisation to get dbId
	 * 
	 * @var string
	 */
	private $dbId;

	/**
	 * The database reference for this Model.
	 * 
	 * @var DB
	 */
	private $db;
	
	/**
	 * A reference to the Schema common to this Model.
	 * 
	 * @var Schema
	 */
	private $schema;

	/**
	 * Instances of this class are constructed under controlled conditions from the generated Model class.
	 * 
	 * @param array $properties The properties for this Model.
	 * @param Schema $schema A reference to the Schema for this Model.
	 * @param DB $db A reference to the database for this Model.
	 * 
	 * @throws ModelException if Model is incompatible with the db specified.
	 */
	public function __construct(array $properties, $schema, DB $db = null)
	{
		/*
		 * Set the schema first
		 */
		$this->schema = $schema;
		if ($db)
		{
			$this->dbId = $db->getDBId();
			if (!$this->isCompatible($this->dbId))
			{
				throw new ModelException(" Model class is not compatible with database " . $this->dbId);
			}
			$this->db = $db;
		}
		else
		{
			/*
			 * The default database will always be compatible
			 */
		 	$this->db = $this->getDefaultDB();
			$this->dbId = $this->db->getDBId();
		}
		
		parent::__construct($properties);
	}

	/**
	 * @see \bizySoft\bizyStore\model\core\ModelI::getDB()
	 */
	public function getDB()
	{
		return $this->db;
	}

	/**
	 * @see \bizySoft\bizyStore\model\core\ModelI::getDBId()
	 */
	public function getDBId()
	{
		return $this->dbId;
	}

	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getTableName()
	 */
	public function getTableName()
	{
		return $this->schema->tableSchema->get($this->dbId);
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getColumnSchema()
	 */
	public function getColumnSchema()
	{
		return $this->schema->columnSchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getSequenceSchema()
	 */
	public function getSequenceSchema()
	{
		return $this->schema->sequenceSchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getPrimaryKeySchema()
	 */
	public function getPrimaryKeySchema()
	{
		return $this->schema->primaryKeySchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getUniqueKeySchema()
	 */
	public function getUniqueKeySchema()
	{
		return $this->schema->uniqueKeySchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getForeignKeySchema()
	 */
	public function getForeignKeySchema()
	{
		return $this->schema->foreignKeySchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getForeignKeyRefereeSchema()
	 */
	public function getForeignKeyRefereeSchema()
	{
		return $this->schema->foreignKeyRefereeSchema;
	}
	
	/**
	 * @see \bizySoft\bizyStore\model\core\SchemaI::getKeyCandidateSchema()
	 */
	public function getKeyCandidateSchema()
	{
		return $this->schema->keyCandidateSchema;
	}
	
	/**
	 * @see bizySoft\bizyStore\model\core.ModelI::getCompatibleDBIds()
	 */
	public function getCompatibleDBIds()
	{
		return $this->schema->compatibleDBIds;
	}
	
	/**
	 * Is this schema compatible with the database Id.
	 * 
	 * @param string $dbId
	 * @return boolean
	 */
	public function isCompatible($dbId)
	{
		return isset($this->schema->compatibleDBIds[$dbId]);
	}
	
	/**
	 * @see bizySoft\bizyStore\model\core.ModelI::getDefaultDBId()
	 */
	public function getDefaultDBId()
	{
		return $this->schema->defaultDBId;
	}
	
	/**
	 * @see bizySoft\bizyStore\model\core.ModelI::getDefaultDB()
	 */
	public function getDefaultDB()
	{
		$config = BizyStoreConfig::getInstance();
		
		return $config->getDB($this->getDefaultDBId());
	}
}

?>