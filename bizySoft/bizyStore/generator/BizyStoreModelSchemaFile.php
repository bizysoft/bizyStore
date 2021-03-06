<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\model\core\SchemaConstants;

/**
 * Concrete class defining methods that are used for generating the Schema class files via the ModelGenerator.
 *
 * Model generation happens once for a bizyStore installation unless your schema or the bizySoftConfig file changes.
 * Generated Schema classes represent the meta-data for a database table. They hold useful information on the database
 * tables and columns which is used to provide CRUD functionality.
 *
 * This class forms part of the code generation framework and is only referenced by the ModelGenerator.
 *
 * Produces class files that are PSR-4 compliant wrt the bizyStore installation.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class BizyStoreModelSchemaFile extends ClassFile implements SchemaConstants
{
	/**
	 * @var UniqueProperties
	 */
	public $primaryProperties;

	/**
	 * @var UniqueProperties
	 */
	public $uniqueProperties;

	/**
	 * @var ForeignProperties
	 */
	public $foreignProperties;

	/**
	 * @var SequencedProperties
	 */
	public $sequencedProperties;

	/**
	 * @var ColumnProperties
	 */
	public $columnProperties;

	/**
	 * Construct with class variables tableName and dBId.
	 *
	 * @param string $tableName        	
	 * @param string $dbId        	
	 */
	public function __construct($className, $dbId, Config $config = null)
	{
		$this->className = $className;
		$this->dbId = $dbId;
		
		$this->primaryProperties = new UniqueProperties();
		$this->uniqueProperties = new UniqueProperties();
		$this->foreignProperties = new ForeignProperties();
		$this->sequencedProperties = new SequencedProperties();
		$this->columnProperties = new ColumnProperties();
		parent::__construct($config);
	}

	/**
	 * Generate the header of the file.
	 *
	 * The header includes everything before the 'class' definition like PHP tag's,
	 * namespaces, use statements and licence information etc.
	 *
	 * @return string the class header.
	 */
	public function generateHeader()
	{
		$config = $this->getConfig();
		$nameSpace = $config->getModelNamespace();
		$schemaClassFileContentsHeader = "<?php\n";
		$schemaClassFileContentsHeader .= "\nnamespace $nameSpace;\n\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\TableSchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\ColumnSchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\SequenceSchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\PrimaryKeySchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\UniqueKeySchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\ForeignKeySchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\ForeignKeyRefereeSchema;\n";
		$schemaClassFileContentsHeader .= "use bizySoft\\bizyStore\\model\\core\\KeyCandidateSchema;\n\n";
		$schemaClassFileContentsHeader .= self::$licenseContents . "\n";
		
		return $schemaClassFileContentsHeader;
	}

	/**
	 * Populate the schema properties from the $tableSchema wrt the bizySoftConfig file.
	 */
	public function populate()
	{
		/*
		 * Generate the schema info.
		 * 
		 * Each $tableSchema describes a table for a database
		 */
		foreach ($this->schema as $dbId => $tableSchema)
		{
			/*
			 * Process all the columns of the table.
			 */
			foreach ($tableSchema as $index => $columnSchema)
			{
				$table_name = $columnSchema[self::TABLE_NAME];
				$keyType = $columnSchema[self::KEY_TYPE];
				$primary = $keyType == self::PRIMARY_KEY;
				$sequenced = $columnSchema[self::SEQUENCED] == "true";
				$unique = $keyType == self::UNIQUE;
				$foreign = $keyType == self::FOREIGN_KEY;
				
				if ($primary)
				{
					/*
					 * Add the primary key column.
					 * Primary keys may or may not be sequenced and may contain more than one column in the table.
					 * 
					 * They are a key candidate.
					 */
					$this->primaryProperties->add($dbId, $columnSchema);
				}
				if ($sequenced)
				{
					/*
					 * Add the sequenced column.
					 * Sequenced columns may or may not be part of a unique or primary key declaration for the table.
					 * 
					 * They are a key candidate.
					 */
					$this->sequencedProperties->add($dbId, $columnSchema);
				}
				if ($unique)
				{
					/*
					 * Add the unique column.
					 * They may contain more than one column and any of those columns may be part of another unique key 
					 * declaration for the table.
					 * 
					 * They are a key candidate.
					 */
					$this->uniqueProperties->add($dbId, $columnSchema);
				}
				if ($foreign)
				{
					/*
					 * Add the foreign key column.
					 * Foreign keys are a reference to a unique row in another table. In practice, they are usually a 
					 * reference to a primary key, but can consist of more than one column referencing a unique key 
					 * declaration in the other table.
					 * 
					 * Foreign keys are NOT unique by themselves in the table they are declared in. They can refer 
					 * to the same row instance in the other table.
					 * 
					 * They cannot be sequenced as they are a reference to a unique key declaration in another table.
					 * 
					 * They are NOT a key candidate by themselves because they are not unique within the table declared in.
					 * They can only form part of a key candidate when included as part of a unique or primary key declaration 
					 * for the table.
					 */
					$this->foreignProperties->add($dbId, $columnSchema);
				}
				else 
				{
					/*
					 * You can specify foreign keys in the bizySoftConfig file. If no database foreign keys are 
					 * specified for a particular column in the table, then we use configured entries if they exist.
					 */
					$configForeignColumnSchema = $this->getConfigForeignColumnSchema($dbId, $columnSchema);
					if ($configForeignColumnSchema)
					{
						$this->foreignProperties->add($dbId, $configForeignColumnSchema);
					}
				}
				/*
				 * Always add the column data
				 */
				$this->columnProperties->add($dbId, $columnSchema);
			}
		}
	}

	/**
	 * Check if this particular database/table/column has a foreignKey from bizySoftConfig.
	 * 
	 * There
	 * 
	 * @param string $dbId
	 * @param array $columnSchema
	 * @return array
	 */
	private function getConfigForeignColumnSchema($dbId, $columnSchema)
	{
		$config = $this->getConfig();
		$dbConfig = $config->getProperty(self::DATABASE_TAG);
		$dbConfig = $dbConfig[$dbId];
		$tableName = $columnSchema[self::TABLE_NAME];
		$columnName = $columnSchema[self::COLUMN_NAME];
		
		$relationships = isset($dbConfig[self::DB_RELATIONSHIPS_TAG]) ? 
								$dbConfig[self::DB_RELATIONSHIPS_TAG] : array();
		$foreignKeySpecs = isset($relationships[self::REL_FOREIGN_KEYS_TAG]) ?
								$relationships[self::REL_FOREIGN_KEYS_TAG] : array();
		
		/*
		 * Foreign keys are configured as follows under the FOREIGN_KEY_TAG for a database:
		 * 
		 * array(foreignKeyTable1 => array(array(foreignKeyColumn1 => referencedTable.referencedColumn1,
		 *                                       foreignKeyColumn2 => referencedTable.referencedColumn2, 
		 *                                       ...
		 *                                      ),
		 *                                 etc...
		 *                                ),
		 * 
		 *       foreignKeyTable2 => array(array(foreignKeyColumn => referencedTable.referencedColumn, ..., ...),
		 *                                 etc...
		 *                                )
		 *      );
		 * 
		 * Match with tableName.
		 */
		$foreignKeyTableSpecs = isset($foreignKeySpecs[$tableName]) ? $foreignKeySpecs[$tableName] : array();
		$foreignKeySchema = null;
		foreach ($foreignKeyTableSpecs as $index => $foreignKeys)
		{
			/*
			 * We need to loop here to set the key index.
			 */
			$i = 0;
			foreach ($foreignKeys as $column => $foreignKey)
			{
				if ($column == $columnName)
				{
					$referenced = explode(".", $foreignKey);
					if (count($referenced) > 1)
					{
						$foreignKeyColumns = array();
						$foreignKeyColumns[self::KEY_TYPE] = self::FOREIGN_KEY;
						$foreignKeyColumns[self::KEY_NAME] = $index;
						$foreignKeyColumns[self::KEY_INDEX] = $i;
						$foreignKeyColumns[self::REFERENCED_TABLE] = $referenced[0];
						$foreignKeyColumns[self::REFERENCED_COLUMN] = $referenced[1];
						/*
						 * Merge the foreign key info into the columnSchema
						 */
						$foreignKeySchema = $foreignKeyColumns + $columnSchema;
						break;
					}
				}
				$i++;
			}
		}
		return $foreignKeySchema;
	}
	/**
	 * Generate the class definition for the particular Schema class.
	 *
	 * @param ReferencedProperties $multiTableReferencedProperties        	
	 * @return string the class definition.
	 */
	public function generateDefinition(ReferencedProperties $multiTableReferencedProperties = null)
	{
		$schemaClassName = $this->className;
		
		$schemaClassFileContents = "class " . $schemaClassName . "Schema\n{\n";
		
		$compatibleIds = array_keys($this->schema);
		$compatibleIdStr = "";
		$comma = "";
		foreach ($compatibleIds as $compatibleId)
		{
			$compatibleIdStr .= $comma . "\"$compatibleId\" => \"$compatibleId\"";
			$comma = ", ";
		}
				
		$schemaClassFileContents .= "\tpublic \$compatibleDBIds = array($compatibleIdStr);\n";
		$schemaClassFileContents .= "\tpublic \$defaultDBId = \"" . $compatibleIds[0] . "\";\n";
		$schemaClassFileContents .= "\tpublic \$tableSchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$columnSchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$sequenceSchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$primaryKeySchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$uniqueKeySchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$foreignKeySchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$foreignKeyRefereeSchema = null;\n";
		$schemaClassFileContents .= "\tpublic \$keyCandidateSchema = null;\n\n";
		
		$schemaClassFileContents .= "\tpublic function __construct()\n\t{\n";
		$comma = "";
		
		/*
		 * Table name mappings.
		 */
		$schemaClassFileContents .= "\t\t\$this->tableSchema = new TableSchema(\n\t\t\tarray(";
		foreach ($this->tableNames as $dbId => $tableName)
		{
			$schemaClassFileContents .= $comma . "\"$dbId\" => \"$tableName\"";
			$comma = ", ";
		}
		$schemaClassFileContents .= "));\n\n";
		
		/*
		 * Columns, sequences, primary keys, unique keys, foreign keys
		 */
		$schemaClassFileContents .= "\t\t\$this->columnSchema = new ColumnSchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->columnProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		$schemaClassFileContents .= "\t\t\$this->sequenceSchema = new SequenceSchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->sequencedProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		$schemaClassFileContents .= "\t\t\$this->primaryKeySchema = new PrimaryKeySchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->primaryProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		$schemaClassFileContents .= "\t\t\$this->uniqueKeySchema = new UniqueKeySchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->uniqueProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		$schemaClassFileContents .= "\t\t\$this->foreignKeySchema = new ForeignKeySchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->foreignProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		/*
		 * Produce the foreign key references for this schema.
		 */
		$referencedFKProperties = new ReferencedProperties();
		foreach ($this->tableNames as $dbId => $tableName)
		{
			$referencedProperties = $multiTableReferencedProperties ? $multiTableReferencedProperties->getReferencedProperties($dbId, $tableName) : array();
			foreach ($referencedProperties as $dbId => $columnSchema)
			{
				foreach ($columnSchema as $columnProperties)
				{
					$referencedFKProperties->add($dbId, $columnProperties);
				}
			}
		}
		$schemaClassFileContents .= "\t\t\$this->foreignKeyRefereeSchema = new ForeignKeyRefereeSchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $referencedFKProperties->codify();
		$schemaClassFileContents .= "\n\t\t));\n\n";
		
		/*
		 * Produce the key candidates.
		 */
		$primaryKeyCandidates = $this->primaryProperties->keyCandidates();
		$sequencedKeyCandidates = $this->sequencedProperties->keyCandidates();
		$uniqueKeyCandidates = $this->uniqueProperties->keyCandidates();
		
		$keyCandidates = array();
		
		$stashKeyCandidate = function (array $candidates) use (&$keyCandidates)
		{
			foreach ($candidates as $dbId => $keyInfo)
			{
				if (! isset($keyCandidates[$dbId]))
				{
					$keyCandidates[$dbId] = array();
				}
				foreach ($keyInfo as $keyName => $columnInfo)
				{
					$index = implode(".", array_keys($columnInfo));
					// Don't overwrite keys if they are already there
					if (! isset($keyCandidates[$dbId][$index]))
					{
						$keyCandidates[$dbId][$index] = $columnInfo;
					}
				}
			}
		};
		
		/*
		 * Output the key candidates in this order of preference if defined:
		 * 
		 * Primary Key with a sequenced column
		 * Primary Key non-sequenced
		 * Unique Keys with a sequenced column
		 * Sequenced column
		 * Unique Key non-sequenced
		 */
		$stashKeyCandidate($primaryKeyCandidates[self::SEQUENCED]);
		$stashKeyCandidate($primaryKeyCandidates[self::NON_SEQUENCED]);
		$stashKeyCandidate($uniqueKeyCandidates[self::SEQUENCED]);
		$stashKeyCandidate($sequencedKeyCandidates[self::SEQUENCED]);
		$stashKeyCandidate($uniqueKeyCandidates[self::NON_SEQUENCED]);
		
		$schemaClassFileContents .= "\t\t\$this->keyCandidateSchema = new KeyCandidateSchema(\n\t\t\tarray(";
		$schemaClassFileContents .= $this->uniqueProperties->stringify($keyCandidates);
		$schemaClassFileContents .= "\n\t\t));\n\n";
		$schemaClassFileContents .= "\t}\n";
		$schemaClassFileContents .= "}\n";
		
		return $schemaClassFileContents;
	}

	/**
	 * Gets the name of the file we are generating.
	 *
	 * @return string
	 */
	public function getFileName()
	{
		return $this->className . "Schema.php";
	}
}
?>