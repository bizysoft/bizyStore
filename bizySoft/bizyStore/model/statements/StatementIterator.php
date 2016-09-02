<?php
namespace bizySoft\bizyStore\model\statements;

use \Iterator;
use bizySoft\bizyStore\model\statements\Statement;

/**
 * Abstract base class to allow Iteration via foreach through the result set rows of a Statement.
 *
 * <span style="color:orange">If you find our software helpful, the best way to contribute
 * is to hire us to work for you. </span> Details at <a href="http://www.bizysoft.com.au">http://www.bizysoft.com.au</a>
 *
 * @author Chris Maude, chris@bizysoft.com.au
 * @copyright Copyright (c) 2016, bizySoft
 * @license LICENSE MIT License
 */
abstract class StatementIterator implements Iterator
{
	/**
	 * The Statement that has been executed and is waiting for the data
	 * to be fetched.
	 *
	 * @var Statement
	 */
	protected $statement = null;
	
	/**
	 * Properties for the iterator query.
	 * 
	 * @var array
	 */
	private $properties = array();
	
	/**
	 * The row data retrieved from the statement.
	 *
	 * @var mixed
	 */
	private $row = false;
	
	/**
	 * Current zero based index position into the result set (models an array index).
	 *
	 * @var int
	 */
	private $key = -1;
	
	/**
	 * Set the class variables.
	 *
	 * @param Statement $statement
	 */
	public function __construct(Statement $statement, $properties = array())
	{
		$this->statement = $statement;
		$this->properties = $properties;
	}
	
	/**
	 * Executes the statement and sets the iterator to the first element.
	 */
	public function rewind()
	{
		if ($this->row !== false)
		{
			$this->statement->close();
		}
		$this->key = -1;
		$this->statement->query($this->properties);
		$this->next();
	}
	
	/**
	 * Is the iterator at a valid position.
	 *
	 * @return boolean
	 */
	public function valid()
	{
		return $this->row !== false;
	}
	
	/**
	 * Gets the current value at the iterator position.
	 *
	 * @return mixed depends on implementation.
	 */
	public function current()
	{
		return $this->row;
	}
	
	/**
	 * Gets the current value of the key.
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->key;
	}
	
	/**
	 * Fetch the next row from the statement result set.
	 *
	 * @return mixed depends on implementation.
	 */
	protected abstract function fetchNext();
	
	/**
	 * Fetch the next value from the statement and adjust the position.
	 * 
	 * @return mixed depends on implementation.
	 */
	public final function next()
	{
		// Try to get the next row.
		$this->row = $this->fetchNext();
		if ($this->row !== false)
		{
			$this->key++;
		}
		else
		{
			$this->statement->close();
		}
	}
	
	/**
	 * Get the current count of rows fetched.
	 *
	 * @return integer
	 */
	public final function count()
	{
		return $this->key + 1;
	}
}

?>