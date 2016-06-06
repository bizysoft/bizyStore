<?php
namespace bizySoft\bizyStore\model\statements;

/**
 * JoinSpec's are a public data holder for Join operations.
 * 
 * They are closely related to ForeignKeys in that they are defined in a similar way and you would most probably 
 * (but not necessarily) use Foreignkey columns for joining tables.
 * 
 * JoinSpecs are used in an array so that a join can be made between multiple JoinSpec's. They come in the form of 
 * 
 * table(joinColumn1[.joincolumn2...] [, assocjoinColumn1[.assocJoinColumn2...]])
 * 
 * where 
 * 
 * joinColumn1[.joincolumn2...] are the columns of the table to join with and specify a possibly unique key 
 * (an "and" in the case of multiple columns separated by ".")
 * 
 * assocJoinColumn's are optional and are used for association or junction tables where the secondary relationship 
 * must also be defined.
 * 
 * A real situation would declare a join with an '=>' separator
 * 
 * e.g. member(id) => membership(memberId)
 * 
 * or
 * 		author(id) => authorBooks(authorId, bookId) => book(id)
 * 
 * The JoinSpec's from the above are
 * member(id)
 * membership(memberId)
 * author(id)
 * authorBooks(authorId, bookId)
 * book(id)
 * 
 * The '=>' defines the join e.g. author.id is joined to authorBooks.authorId and authorBooks.bookId is joined to book.id
 * 
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license  See the LICENSE file with this distribution.
 */

class JoinSpec
{
	/**
	 * The database specified by the constructor joinSpec string.
	 * 
	 * @var string
	 */
	public $table = null;
	/**
	 * The columnNames specified by the constructor joinSpec string.
	 * 
	 * @var array
	 */
	public $columns = array();
	/**
	 * The column names of the association or junction table specified by the 
	 * constructor joinSpec string.
	 * 
	 * @var unknown_type
	 */
	public $assocColumns = array();
	/*
	 * This class is used while reading foreign key declarations from bizySoftConfig on start-up,
	 * so we have to allow the above class properties to be set before BizySoftConfig is fully built.
	 * 
	 * The remaining properties almost always require BizySoftConfig to be fully initialised so are left 
	 * to the calling code to set where required.
	 */
	/**
	 * An instance of the Model that this JoinSpec represents. 
	 * 
	 * Set by calling code where required.
	 * 
	 * @var Model
	 */
	public $model = null;
	/**
	 * An instance of the ColumnSchema for the Model.
	 * 
	 * Set by calling code where required.
	 * 
	 * @var ColumnSchema
	 */
	public $columnSchema = null;
	/**
	 * An instance of the KeyCandidateSchema for the Model.
	 *
	 * Set by calling code where required.
	 * 
	 * @var KeyCandidateSchema
	 */
	public $keyCandidateSchema = null;
	
	/**
	 * Is the downstream relationship unique.
	 * 
	 * Set by calling code where required.
	 * 
	 * @var boolean
	 */
	public $unique = false;
	/**
	 * Construct with the definition of the JoinSpec.
	 * 
	 * This will be a basic JoinSpec with just the column data specified. 
	 * 
	 * Classes that use this (e.g. Join) should complete construction of Model and Schema class properties as required.
	 * 
	 * @param string $joinSpec
	 */
	public function __construct($joinSpec)
	{
		$this->explode($joinSpec);
	}

	/**
	 * Explodes the join specification tableName(column1[.column2...] [, ...]) into the class variables
	 *
	 * 
	 * @param string $joinSpec
	 * @param boolean $first
	 * @return array
	 */
	public function explode($joinSpec)
	{
		$jSpec = explode("(", rtrim($joinSpec, ")"));
		
		$this->table = trim($jSpec[0]);
		$columns = explode(",", trim($jSpec[1]));
		$result = array();
		
		$tableColumns = explode(".", $columns[0]);
		foreach ($tableColumns as $tableColumn)
		{
			$column = trim($tableColumn);
			$this->columns[] = $column;
			/*
			 * Set the assocColumns as well but only if they are not specified.
			 */
			if (!isset($columns[1]))
			{
				$this->assocColumns[] = $column;
			}
		}
		if (isset($columns[1]))
		{
			$assocColumns = explode(".", $columns[1]);
			foreach ($assocColumns as $assocColumn)
			{
				$column = trim($assocColumn);
				$this->assocColumns[] = $column;
			}
		}
	}
}
?>