<?php
namespace bizySoft\examples\services;

use bizySoft\bizyStore\model\core\DB;
use bizySoft\bizyStore\model\unitTest\Member;
use \Exception;

/**
 * Service class to exemplify uses of bizyStore functionality in a service layer.
 *
 * A service layer usually provides methods to implement the business rules of the App.
 * This can include transactional access to the database. Here we use static methods to
 * implement services.
 *
 * This is a simple example to show some basics of bizyStore. We wrap the basic CRUD
 * Model interface in transactions, allowing some fault tolerance. We then provide
 * some simple finders.
 *
 * Note that these examples are a trivial case to persist a single model object (single table row).
 * In reality service classes would be much more complex and would possibly include reads from or writes 
 * to multiple tables in the db. Something you can easily achieve with bizyStore.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 * 
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */
class MemberService
{
	/**
	 * Create a Member model object in the database atomically.
	 *
	 * @param Member $member. The Member model object to persist. This may have been populated from the POST data.
	 * @throws Exception
	 */
	public static function create(Member $member)
	{
		$db = $member->getDB(); // A Member cannot be constructed without a valid database reference.
		try
		{
			$db->beginTransaction();
			
			// There could be many checks here to see if the member already exists etc, but
			// seeing that this is an example, we just do a create.
			
			// Fill in the created time at least.
			$dateCreated = array(
					"dateCreated" => $db->getConstantDateTime());
			$member->set($dateCreated);
			
			$member->create();
			
			$db->endTransaction(DB::COMMIT);
		}
		catch ( Exception $e )
		{
			/*
			 * We can't really determine what has gone wrong here. In the unlikely event that the exception 
			 * has come from beginTransaction(), should we roll-back in that case? Take a look at delete() 
			 * below which addresses this concern by using a transaction reference.
			 */
			$db->endTransaction(DB::ROLLBACK);
			throw $e;
		}
	}
	
	/**
	 * Update a Member Model object in the database atomically.
	 *
	 * @param $oldMember. The old Member Model object to update.
	 * @param $newProperties. The properties with all the changed fields.
	 * @throws Exception
	 */
	public static function update(Member $oldMember, array $newProperties)
	{
		$db = $oldMember->getDB();
		try
		{
			/*
			 * Get the database from the oldMember.
			 */
			$db->beginTransaction();
			
			// Use the newMember properties to update the oldMember
			$oldMember->update($newProperties);
			
			$db->endTransaction(DB::COMMIT);
		}
		catch ( Exception $e )
		{
			$db->endTransaction(DB::ROLLBACK);
			throw $e;
		}
	}
	
	/**
	 * Delete a Member model object in the database atomically.
	 * 
	 * This is an example using the transaction reference. $txn will be null if beginTransaction() fails.
	 *
	 * @param $member. The Member model object to delete.
	 * @throws Exception
	 */
	public static function delete(Member $member)
	{
		$db = $member->getDB();
		$txn = null;
		try
		{
			$txn = $db->beginTransaction();
			
			/*
			 * Delete from the db
			 */
			$member->delete();
			
			$txn->commit();
		}
		catch ( Exception $e )
		{
			if ($txn)
			{
				$txn->rollBack();
			}
			throw $e;
		}
	}
	
	/**
	 * Find all Member model objects in the db.
	 *
	 * @param DB $db we can optionally pass in the db identifier
	 *       
	 * @return array
	 */
	public static function findAll(DB $db = null)
	{
		/*
		 * Construct and empty Member related to the db
		 * If $db is null, it will be created in the default db
		 */
		$member = new Member(null, $db);
		/*
		 * A find() based on an empty Model means get every instance.
		 */
		return $member->find();
	}
	
	/**
	 * Get a Member Model object.
	 *
	 * This is an example that shows how you use a service method to populate a Model object from the 
	 * specified database by it's id. The id could have been posted from a web form etc...
	 * 
	 * @param $memberId the memberId to find in the database
	 * @param $dbId optionally pass in a database reference or pass in nothing to use the default database.
	 */
	public static function findById($memberId, $db = null)
	{
		/*
		 * In dealing with databases, your PHP code would possibly build the HTML front end
		 * form with elements that have database id's behind them.
		 *
		 * This sort of method would be useful to get the Model details from the id
		 * provided when you POST your form.
		 *
		 * Since id's are unique, here we just return the Member object or null if not found.
		 */
		$member = new Member(array("id" => $memberId ), $db);
		
		return $member->findUnique();
	}
	
	/**
	 * Get the Member model object(s) from the specified db based on the name parameters.
	 *
	 * This example can return more than one member.
	 *
	 * @param string $firstName.
	 * @param string $lastName.
	 *
	 * @return array. The array of Member objects or an empty array if the name does not exist.
	 */
	public static function findByName($firstName, $lastName, $db = null)
	{
		$properties = array(
				"firstName" => $firstName,
				"lastName" => $lastName 
		);
		$member = new Member($properties, $db);
		
		return $member->find();
	}
}

?>