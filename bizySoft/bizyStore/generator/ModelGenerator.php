<?php
namespace bizySoft\bizyStore\generator;

use bizySoft\bizyStore\model\core\ModelException;
use bizySoft\bizyStore\model\core\SchemaI;
use bizySoft\bizyStore\services\core\BizyStoreConfig;
use bizySoft\bizyStore\services\core\BizyStoreLogger;
use bizySoft\bizyStore\services\core\BizyStoreOptions;
use bizySoft\bizyStore\services\core\DBManager;

/**
 * Class to allow generation of bizyStore Model and Schema classes supporting your application's database(s).
 *
 * It is the called by the BizyStoreAutoloader for automatic Model class generation or the generateModel.php 
 * utility for manual generation.
 * 
 * This class uses the database schema information to produce the required PHP classes for retrieval and storage of data.
 * It uses the database config items specified in the bizySoftConfig file for your application.
 *
 * Generation produces two sets of files, &lt;Model&gt;.php and &lt;Model&gt;Schema.php in the "bizySoft/bizyStore/model/&lt;appName&gt;" 
 * directory. Where &lt;Model&gt; is the PHP class name representing a table in your database and &lt;appName&gt; is from the bizySoftConfig file.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class ModelGenerator
{
	/**
	 * The set of ClassFiles that represent the Model classes we are generating.
	 *
	 * @var BizyStoreModelFileSet
	 */
	private $classFileSet;
	
	/**
	 * The set of ClassSchemaFiles that represent the Schema classes we are generating.
	 *
	 * @var BizyStoreModelSchemaFileSet
	 */
	private $classSchemaFileSet;
	
	/**
	 * Set the class variables.
	 */
	public function __construct()
	{
		$this->classFileSet = new BizyStoreModelFileSet();
		$this->classSchemaFileSet = new BizyStoreModelSchemaFileSet();
	}
	
	/**
	 * Gets all the tables from all databases configured.
	 * 
	 * Specific tables can be configured for each database in the bizySoftConfig file if only a subset is required 
	 * for your application.
	 * 
	 * This is the default for Schema file generation if no database tables are specified via generate().
	 * 
	 * @return an associative array of array("dbId1" => array("table1", "table2", ...), "dbId2" => array(...), ...)
	 */
	private function getAllDBTables()
	{
		$result = array();
		
		$dbIds = DBManager::getDBIds();

		foreach ($dbIds as $dbId)
		{
			$db = DBManager::getDB($dbId);
			$tableNames = $db->getTableNames();
			$result[$dbId] = $tableNames;
		}
		return $result;
	}
	
	/**
	 * Initialise with the database tables specified.
	 * 
	 * Grab the database schema for each database and populate the classFileSets with the data we need to generate 
	 * all the class definitions. Sending no parameters will generate all class files for all databases
	 * 
	 * @param $dbTables an associative array of array("dbId1" => array("table1", "table2", ...), "dbId2" => array(...), ...)
	 */
	private function initialise(array $dbTables = array())
	{
		$tableLock = array();
		
		$dbTablesToProcess = $dbTables ? $dbTables : $this->getAllDBTables();
		// Sort out the class files that we need to support the configured databases
		foreach ($dbTablesToProcess as $dbId => $tableNames)
		{
			foreach ($tableNames as $tableName)
			{
				BizyStoreLogger::log("Processing $dbId:$tableName");
				$db = DBManager::getDB($dbId);
				// Get the schema for each table.
				$tableSchema = $db->getSchema($tableName);
				if ($tableSchema)
				{
					/*
					 * Get the class name as a key.
					 * 
					 * Here we uppercase the first char of the table name.
					 */	
					$className = ucfirst($tableName);
					
					// For both ClassFiles and ClassSchemaFiles, one per unique className
					$tableLocked = array_key_exists($className, $tableLock);
					
					if (!$tableLocked)
					{
						/*
						 * Don't allow duplicate class names across databases. Rather, we just store the
						 * particular table schema for each db as an array keyed on dbId. We don't
						 * have to assume then, that the schema is exactly the same across databases. The
						 * column names may be the same (but may not be), and may not be the case
						 * for other meta-data.
						 *
						 * bizyStore allows you to have a single Model class describing multiple schemas.
						 * 
						 * Lock it for this className
						 */
						$tableLock[$className] = $dbId;
						/*
						 * and create a ClassFile.
						 * These become the class files we must generate later
						 */ 
						$classFile = new BizyStoreModelFile($className, $dbId);
						$classFile->schema[$dbId] = $tableSchema;
						$this->classFileSet->classFiles[$className] = $classFile;
						// These become the class schema files we must generate later
						$classSchemaFile = new BizyStoreModelSchemaFile($className, $dbId);
						$classSchemaFile->schema[$dbId] = $tableSchema;
						$classSchemaFile->tableNames[$dbId] = $tableName;
						$this->classSchemaFileSet->classFiles[$className] = $classSchemaFile;
					}
					else
					{
						// Update the existing ClassFile and ClassSchema files
						$classFile = $this->classFileSet->classFiles[$className];
						$classSchemaFile = $this->classSchemaFileSet->classFiles[$className];
						$classFile->schema[$dbId] = $tableSchema;
						$classSchemaFile->schema[$dbId] = $tableSchema;
						$classSchemaFile->tableNames[$dbId] = $tableName;
					}
				}
			}
		}
	}
	
	/**
	 * Control the generation of the class files with respect to bizySoftConfig settings.
	 * 
	 * The Model and Schema class files are stored in "bizySoft/bizyStore/model/&lt;appName&gt;"
	 * Where &lt;appName&gt; is specified in the bizySoftConfig file. Appropriate permissions should be given to this 
	 * directory so the web server or CLI program can write to it.
	 * 
	 * Class generation is usually automatic, being handled by the auto-loader. In this case generate() is called
	 * by the auto-loader with no parameters, so all tables from all databases configured will have Model and Schema
	 * class files generated. You can specify the tables for each database by using the &lt;tables&gt; tag in bizySoftConfig
	 * 
	 * If you need to create or alter a table with Model and Schema support from within your application, then you 
	 * must generate the Model and Schema files by calling generate() with an array of the table names keyed on dbId after the 
	 * table(s) have been created/altered in the database(s). Note that for this case any existing tables that are referred 
	 * to by foreign keys in the new/altered table(s) must also be included in the array.
	 * 
	 * Alternatively, you can also generate the class files manually with 'generateModel.php' provided in the distribution. 
	 * This may be your choice if you require the generated class files to be bound to a versioned software release. Just 
	 * generate and put in your code repository.
	 * 
	 * If your database schema or bizySoftConfig file has changed, then regeneration of class files may be 
	 * necessary. This can be done simply by removing the "bizySoft/bizyStore/model/&lt;appName&gt;" directory
	 * or re-generating manually.
	 * 
	 * @param $dbTables an associative array of array("dbId1" => array("table1", "table2", ...), "dbId2" => array(...), ...)
	 * @throws ModelException
	 */
	public function generate(array $dbTables = array())
	{
		$modelDir = BizyStoreConfig::getProperty(BizyStoreOptions::BIZYSTORE_MODEL_DIR);
		
		if (!file_exists($modelDir))
		{
			if (!mkdir($modelDir))
			{
				/*
				 * We can't create the required model directory, so bail.
				 */
				throw new ModelException("Unable to create $modelDir");
			}
		}
		
		if (!is_writable($modelDir))
		{
			/*
			 * We need to throw here because we have no place to write the class files.
			 */
			throw new ModelException("Unable to write class files to $modelDir");
		}
		
		BizyStoreLogger::log("Starting Model generation");
		BizyStoreLogger::log("Generating Model class files.");
		/*
		 * Init the classFileSets
		 */
		$this->initialise($dbTables);
		/*
		 * Here we can just generate the Model class files straight away.
		 */
		foreach ($this->classFileSet->classFiles as $classFile)
		{
			$fileContents = $classFile->generateFile();
			$fileName = $modelDir . DIRECTORY_SEPARATOR . $classFile->getFileName();
			file_put_contents($fileName, $fileContents);
			BizyStoreLogger::log(__METHOD__ . ": Generating $fileName");
		}
		
		BizyStoreLogger::log("Generating Schema class files.");
		/*
		 * The schema is generated a little differently. We populate the schema and add any foreign keys to the 
		 * $referencedProperties object, producing a multi-table instance. 
		 * 
		 * Then we use the multi-table instance to augment the schema generation for each table.
		 */
		$referencedProperties = new ReferencedProperties();
		foreach ($this->classSchemaFileSet->classFiles as $classFileSchema)
		{
			$classFileSchema->populate();
			
			$foreignKeys = $classFileSchema->foreignProperties;
			$foreignProperties = $foreignKeys->getProperties();
			foreach ($foreignProperties as $dbId => $columnSchema)
			{
				$dbConfig = DBManager::getDBConfig($dbId);
				/*
				 * Recursive relationship declarations are ignored in the schema generation for ForeignKeyReferee's.
				 * 
				 * eg. <relationships>
				 *         <recursive>membership.adminId</recursive>
				 *     </relationships>	
				 *     
				 * Here we disallow any ForeignKeyReferee's having a <recursive> declaration.
				 */
				$relationships = isset($dbConfig[BizyStoreOptions::DB_RELATIONSHIPS_TAG]) ?
										$dbConfig[BizyStoreOptions::DB_RELATIONSHIPS_TAG] : array();
				$recursives = isset($relationships[BizyStoreOptions::REL_RECURSIVE_TAG]) ?
										$relationships[BizyStoreOptions::REL_RECURSIVE_TAG] : array();
				
				foreach ($columnSchema as $columnProperties)
				{
					$referencedTable = $columnProperties[SchemaI::TABLE_NAME];
					$referencedColumn = $columnProperties[SchemaI::COLUMN_NAME];
					$resursiveKey = $referencedTable . "." . $referencedColumn;
					if (!isset($recursives[$resursiveKey]))
					{
						$referencedProperties->add($dbId, $columnProperties);
					}
				}
			}
		}
		foreach ($this->classSchemaFileSet->classFiles as $classFileSchema)
		{
			$fileContents = $classFileSchema->generateFile($referencedProperties);
			$fileName = $modelDir . DIRECTORY_SEPARATOR . $classFileSchema->getFileName();
			file_put_contents($fileName, $fileContents);
			BizyStoreLogger::log(__METHOD__ . ": Generating $fileName");
		}
		BizyStoreLogger::log("Finished Model generation");
	}
}

?>