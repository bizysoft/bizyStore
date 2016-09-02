<?php
namespace bizySoft\tests;

use \Exception;
use \PDO;
use bizySoft\common\XMLToArrayTransformer;

/**
 *
 * PHPUnit test case class. Run some transformer tests used for our config.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
class TransformerTestCase extends ModelTestCase
{
	/*
	 * A contrived but typical config file.
	 */
	private $xml = '<?xml version="1.0"?>
<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
<bizySoft>
	<appName>yourAppName</appName>
	<bizyStore>
		<database>
			<id>A</id>
			<interface>MySQL</interface>
			<host>dbAHost</host>
			<port>dbAPort</port>
			<name>dbAName</name>
			<user>dbAUser</user>
			<password>dbAPassword</password>
			<charset>dbACharset</charset>
			<pdoOptions>
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
				PDO::ATTR_EMULATE_PREPARES => FALSE
			</pdoOptions>
			<pdoPrepareOptions>
				PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
			</pdoPrepareOptions>
			<modelPrepareOptions>
				cache => TRUE
			</modelPrepareOptions>
		</database>
	  	<database>
	  		<id>B</id>
	  		<interface>SQLite</interface>
	  		<name>dbDName</name>
			<pdoOptions>
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
	  			PDO::ATTR_EMULATE_PREPARES => FALSE
	  		</pdoOptions>
	  	</database>
		<options>
			cleanUp => commit
		</options>
	</bizyStore>
	<options>
		includePath => /some/include/path;
		logFile => /path/to/logFile
	</options>
</bizySoft>';

	public function getTransformerMap()
	{
		/*
		 * These are the XML tags that require some transformations.
		 * Use the same validations/transformations as BizyStoreConfig
		 */
			$transformerMap = array(
					self::BIZYSOFT_TAG => 'bizySoft\common\BizySoftTag',
					self::BIZYSTORE_TAG => 'bizySoft\bizyStore\services\core\BizyStoreTag',
					self::DATABASE_TAG => 'bizySoft\bizyStore\services\core\DatabaseTag',
					self::DB_RELATIONSHIPS_TAG => 'bizySoft\bizyStore\services\core\RelationshipsTag',
					self::REL_FOREIGN_KEYS_TAG => 'bizySoft\bizyStore\services\core\ForeignKeysTag',
					self::REL_RECURSIVE_TAG => 'bizySoft\bizyStore\services\core\RecursiveTag',
					self::DB_TABLES_TAG => 'bizySoft\bizyStore\services\core\TablesTag',
					self::PDO_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::PDO_PREPARE_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::MODEL_PREPARE_OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag',
					self::OPTIONS_TAG => 'bizySoft\common\ConstantOptionsTag'
			);
		
		return $transformerMap;
	}
	
	public function testXMLToArray()
	{
		$expected = array(
				self::APP_NAME_TAG => "yourAppName",
				self::BIZYSTORE_TAG => array(
				self::DATABASE_TAG => array(
						"A" => array(
								self::DB_HOST_TAG => "dbAHost",
								self::DB_NAME_TAG => "dbAName",
								self::DB_PORT_TAG => "dbAPort",
								self::DB_USER_TAG => "dbAUser",
								self::DB_PASSWORD_TAG => "dbAPassword",
								self::DB_CHARSET_TAG => "dbACharset",
								self::DB_INTERFACE_TAG => "MySQL",
								self::DB_ID_TAG => "A",
								self::PDO_OPTIONS_TAG => array(
										PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
										PDO::ATTR_EMULATE_PREPARES => false 
								),
								self::PDO_PREPARE_OPTIONS_TAG => array(
										PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL 
								),
								self::MODEL_PREPARE_OPTIONS_TAG => array(
										self::OPTION_CACHE => true 
								) 
						),
						"B" => array(
								self::DB_NAME_TAG => "dbDName",
								self::DB_INTERFACE_TAG => "SQLite",
								self::DB_ID_TAG => "B",
								self::PDO_OPTIONS_TAG => array(
										PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
										PDO::ATTR_EMULATE_PREPARES => false 
								) 
						) 
				),
				self::OPTIONS_TAG => array(
						self::OPTION_CLEAN_UP => "commit" 
				) 
			),
			self::OPTIONS_TAG => array(
					self::OPTION_INCLUDE_PATH => "/some/include/path",
					self::OPTION_LOG_FILE => "/path/to/logFile" 
			) 
		);
		
		$transformerMap = $this->getTransformerMap();
		
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $this->xml);
		$transformer = new XMLToArrayTransformer($xml, $transformerMap);
		
		$xmlArray = $transformer->grind();
		$this->assertEquals($xmlArray, $expected);
	}
	
	public function testMandatoryMissing()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<!--<appName>MySQL</appName>--> <!-- Error here -->
			<bizyStore>
				<database>
					<!--<id>A</id>-->   <!-- Error here. Note that the database tag is keyed on this field so it will have a value of 0 -->
					<!--<interface>MySQL</interface>-->   <!-- Error here -->
					<!--<host>dbAHost</host>-->   <!-- No error here, host is not mandatory -->
					<!--<name>dbAName</name>-->   <!-- Error here -->
					<user>dbAUser</user>
					<password>dbAPassword</password>
					<charset>dbACharset</charset>
					<pdoOptions>
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
						PDO::ATTR_EMULATE_PREPARES => FALSE
					</pdoOptions>
					<pdoPrepareOptions>
						PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
					</pdoPrepareOptions>
					<modelPrepareOptions>
						cache => TRUE
					</modelPrepareOptions>
				</database>
			</bizyStore>
		</bizySoft>';
		
		$transformerMap = $this->getTransformerMap();
		
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
		
		try 
		{
			$transformer = new XMLToArrayTransformer($xml, $transformerMap);
			$xmlArray = $transformer->grind();
			$this->fail();
		}
		catch(Exception $e)
		{
			$expected = "Line 3: Element bizySoft content does not follow the DTD, expecting " .
			"(appName , bizyStore? , rest? , options?), got (bizyStore)\n" .
			"Line 6: Element database content does not follow the DTD, expecting " .
			"(id , interface , host? , port? , socket? , name , schema? , user? , password? ," .
			" charset? , tables? , relationships? , pdoOptions? , pdoPrepareOptions? , modelPrepareOptions?), got (user password ". 
			"charset pdoOptions pdoPrepareOptions modelPrepareOptions)\n" .
			"<database> : Mandatory fields <id>,<interface>,<name> are missing.\n" .
			"<bizySoft> : Mandatory field <appName> is missing.\n";

			$this->assertEquals($expected, $e->getMessage());
		}
	}
	
	public function testMandatoryEmpty()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<appName></appName> <!-- Error here -->
			<bizyStore>
				<database>
					<id></id>  <!-- Error here. -->
					<interface>MySQL</interface>
					<host></host>   <!-- No error here, host is not mandatory -->
					<name></name>   <!-- Error here -->
					<user></user>   <!-- Error only validated when ConnectionManager is initialised, not testable here -->
					<password></password>  <!-- Error only validated when ConnectionManager is initialised, not testable here -->
					<charset>dbACharset</charset>
					<pdoOptions>
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
						PDO::ATTR_EMULATE_PREPARES => FALSE
					</pdoOptions>
					<pdoPrepareOptions>
						PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
					</pdoPrepareOptions>
					<modelPrepareOptions>
						cache => TRUE
					</modelPrepareOptions>
					</database>
			</bizyStore>
		</bizySoft>';
	
		$transformerMap = $this->getTransformerMap();
	
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
	
		try
		{
			$transformer = new XMLToArrayTransformer($xml, $transformerMap);
			$xmlArray = $transformer->grind();
			$this->fail();
		}
		catch(Exception $e)
		{
			$expected = "<database> : Mandatory fields <id>,<name> are missing.\n" .
					"<bizySoft> : Mandatory field <appName> is missing.\n";
	
			$this->assertEquals($expected, $e->getMessage());
		}
	}
	
	public function testInterfaceTagMissing()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<appName>yourAppName</appName>
			<bizyStore>
				<database>
					<id>A</id>
					<!--<interface>MySQL</interface>-->   <!-- Error here missing interface-->
					<host>dbAHost</host>
					<name>dbAName</name>
					<user>dbAUser</user>
					<password>dbAPassword</password>
					<charset>dbACharset</charset>
					<pdoOptions>
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
						PDO::ATTR_EMULATE_PREPARES => FALSE
					</pdoOptions>
					<pdoPrepareOptions>
						PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
					</pdoPrepareOptions>
					<modelPrepareOptions>
						cache => TRUE
					</modelPrepareOptions>
				</database>
			</bizyStore>
		</bizySoft>';
	
		$transformerMap = $this->getTransformerMap();
	
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
	
		try
		{
			$transformer = new XMLToArrayTransformer($xml, $transformerMap);
			$xmlArray = $transformer->grind();
			$this->fail();
		}
		catch(Exception $e)
		{
			$expected = "Line 6: Element database content does not follow the DTD, expecting " .
					"(id , interface , host? , port? , socket? , name , schema? , user? , password? , " .
					"charset? , tables? , relationships? , pdoOptions? , pdoPrepareOptions? , modelPrepareOptions?), got (id host " .
					"name user password charset pdoOptions pdoPrepareOptions modelPrepareOptions)\n" .
					"<database> : Mandatory field <interface> is missing.\n";
			$this->assertEquals($expected, $e->getMessage());
		}
	}
	
	public function testOptional()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<appName>yourAppName</appName>
			<bizyStore>
				<database>
					<id>A</id>
					<interface>MySQL</interface>
					<host>dbAHost</host>
					<port></port>  <!-- Error here -->
					<name>dbAName</name>
					<user>dbAUser</user>
					<password>dbAPassword</password>
					<charset></charset>  <!-- Error here -->
					<pdoOptions>
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
						PDO::ATTR_EMULATE_PREPARES => FALSE
					</pdoOptions>
					<pdoPrepareOptions>
						PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
					</pdoPrepareOptions>
					<modelPrepareOptions>
						cache => TRUE
					</modelPrepareOptions>
				</database>
			</bizyStore>
		</bizySoft>';
	
		$transformerMap = $this->getTransformerMap();
	
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
	
		try
		{
			$transformer = new XMLToArrayTransformer($xml, $transformerMap);
			$xmlArray = $transformer->grind();
			$this->fail();
		}
		catch(Exception $e)
		{
			$expected = "<database> : Optional fields <charset>,<port> have no value.\n";
			$this->assertEquals($expected, $e->getMessage());
		}
	}
	
	public function testDatabaseTagMissing()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<appName>yourAppName</appName>
			<bizyStore>
			</bizyStore>
		</bizySoft>';
		
		$transformerMap = $this->getTransformerMap();
		
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
		
		try 
		{
			$transformer = new XMLToArrayTransformer($xml, $transformerMap);
			$xmlArray = $transformer->grind();
			$this->fail();
		}
		catch(Exception $e)
		{
			$expected = "Line 5: Element bizyStore content does not follow the DTD, expecting (database+ , options?), got ()\n";
			$this->assertEquals($expected, $e->getMessage());
		}
	}
	
	public function testRelationshipsTag()
	{
		$testXML = '<?xml version="1.0"?>
		<!DOCTYPE bizySoft SYSTEM "file:///<BizyStoreDTDFileName>">
		<bizySoft>
			<appName>yourAppName</appName>
			<bizyStore>
				<database>
					<id>A</id>
					<interface>SQLite</interface>
					<name>dbAName</name>
					<relationships>
						<foreignKeys>
							membership(memberId) => member(id);
							membership(adminId) => member(id);
							uniqueKeyMembership(memberFirstName.memberLastName.memberDob) => uniqueKeyMember(firstName.lastName.dob)
						</foreignKeys>
						<recursive>
							membership.adminId
						</recursive>
					</relationships>
					<pdoOptions>
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
						PDO::ATTR_EMULATE_PREPARES => FALSE
					</pdoOptions>
					<pdoPrepareOptions>
						PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
					</pdoPrepareOptions>
					<modelPrepareOptions>
						cache => TRUE
					</modelPrepareOptions>
				</database>
			</bizyStore>
		</bizySoft>';
			
		$transformerMap = $this->getTransformerMap();
		$expected = array(
				self::APP_NAME_TAG => "yourAppName",
				self::BIZYSTORE_TAG => array(
						self::DATABASE_TAG => array(
								"A" => array(
										self::DB_ID_TAG => "A",
										self::DB_INTERFACE_TAG => "SQLite",
										self::DB_NAME_TAG => "dbAName",
										self::DB_RELATIONSHIPS_TAG => array(
												self::REL_FOREIGN_KEYS_TAG => array(
														"membership" => array(
																array("memberId" => "member.id"),
																array("adminId" => "member.id")
														),
														"uniqueKeyMembership" => array(
															array("memberFirstName" => "uniqueKeyMember.firstName",
																"memberLastName" => "uniqueKeyMember.lastName",
																"memberDob" => "uniqueKeyMember.dob")
														)
												),
												self::REL_RECURSIVE_TAG => array(
														"membership.adminId" => "membership.adminId"
												)
										),
										self::PDO_OPTIONS_TAG => array(
												PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
												PDO::ATTR_EMULATE_PREPARES => false
										),
										self::PDO_PREPARE_OPTIONS_TAG => array(
												PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL
										),
										self::MODEL_PREPARE_OPTIONS_TAG => array(
												self::OPTION_CACHE => true
										)
								)
						)
				)
		);
		
		$dtdFile = "bizySoft/config/bizySoft.dtd";
		$dtdFile = str_replace(DIRECTORY_SEPARATOR, "/", stream_resolve_include_path($dtdFile));
		$xml = str_replace("<BizyStoreDTDFileName>", $dtdFile, $testXML);
		$transformer = new XMLToArrayTransformer($xml, $transformerMap);
		$xmlArray = $transformer->grind();
		$this->assertEquals($xmlArray, $expected);
	}
}
?>