<?php
/**
 * SelectQuery
 *
 * This class allows for easy, programmatic construction of a SQL "select" query.
 *
 * @package Coligo
 * @author bertag <scott_bertagnole@byu.edu>
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 */
class SelectQuery
{
	const LOGIC_INCLUDE = 0;
	const LOGIC_EXCLUDE = 1;

	private $db;
	private $column_array;
	private $from_string;
	private $where_string;
	private $value_array;

	/**
	 * Class constructor
	 *
	 * Sets the connection to the database
	 *
	 * @param PDO $db [REQUIRED] Defines the PDO connection to the Coligo database
	 */
	public function  __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Adds a where clause to the query
	 *
	 * @param string $column [REQUIRED] The name of the column to filter against
	 * @param mixed $values [REQUIRED] The value (or array of values) to use as filters
	 * @param int $logic [OPTIONAL] Whether to return rows matching the provided values (self::LOGIC_INCLUDE), or rows NOT matching the provided values (self::LOGIC_EXCLUDE). Defaults to LOGIC_INCLUDE.
	 * @param bool $or_where [OPTIONAL] IF true, this entire "where" clause will be prefaced by the "OR" operator instead of the default "AND" operator.  If this is the first "where" clause of the query, this parameter will have no effect.
	 */
	public function where($column, $values, $logic = self::LOGIC_INCLUDE, $or_where = false)
	{
		if(!is_array($values))
			$values = array($values);

		$equator = '=';
		$joiner  = 'OR';

		if($logic == self::LOGIC_EXCLUDE)
		{
			$equator = '!=';
			$joiner = 'AND';
		}

		$where_string = '';
		foreach($values as $value)
		{
			$where_string .= " $column $equator ? $joiner ";
			$this->value_array[] = $value;
		}

		$where_string = substr($where_string, 0, (-1 - strlen($joiner)));

		if(isset($this->where_string))
		{
			if($or_where)
				$this->where_string .= " OR ($where_string) ";
			$this->where_string .= " AND ($where_string) ";
		}
		else
		{
			$this->where_string = " WHERE ($where_string) ";
		}
	}

	/**
	 * Adds an optional where clause to the query
	 *
	 * This function is an alias for the where() function, but automatically sets
	 * that function's $or_where parameter to true
	 *
	 * @param string $column [REQUIRED] The name of the column to filter against
	 * @param mixed $values [REQUIRED] The value (or array of values) to use as filters
	 * @param int $logic [OPTIONAL] Whether to return rows matching the provided values (self::LOGIC_INCLUDE), or rows NOT matching the provided values (self::LOGIC_EXCLUDE). Defaults to LOGIC_INCLUDE.
	 */
	public function or_where($column, $values, $logic = self::LOGIC_INCLUDE)
	{
		if(!isset($this->where_string))
			throw new Exception('No where string set. Run SelectQuery::where() before running SelectQuery::or_where()');
		$this->where_string .= $this->where($column, $values, $logic, true);
	}

	/**
	 * Adds a "FROM" clause to the SQL. Required before execute() can be called
	 *
	 * @param string $from_string [REQUIRED] The string to use as the "FROM" clause.  Do NOT include the keyword "FROM" at the beginning.
	 */
	public function from($from_string)
	{
		$this->from_string = " FROM $from_string ";
	}

	/**
	 * Add columns to be returned using this query
	 *
	 * @param string $column [REQUIRED] The name of the column (or array of column names) to return
	 */
	public function select($column)
	{
		if(is_array($column))
			$this->column_array = $this->column_array + $column;
		else
			$this->column_array[] = $column;
	}

	/**
	 * Run this query and return the resulting PDOStatement
	 *
	 * @return PDOStatement
	 * @throws Exception If the "from" clause is not yet set
	 */
	public function execute()
	{
		//A FROM string must be set, either here or from an earlier function call
		if(!isset($this->from_string))
			throw new Exception("From string not set");

		$sql = $this->construct_sql();

		$query = $this->db->prepare($sql);
		$query->execute($this->value_array);

		return $query;
	}

	/**
	 * Combine all the class data into a single SQL statement
	 *
	 * @return string
	 */
	private function construct_sql()
	{
		//We will populate this bogus FROM statement if none is set, for the sake
		//of the __toString() function.  But execute() still won't run until from() is 
		//properly called.
		$from_string = ' FROM `UNKNOWN_TABLE` ';;
		if(isset($this->from_string))
			$from_string = $this->from_string;

		//A WHERE string is optional
		$where_string = '';
		if(isset($this->where_string))
			$where_string = $this->where_string;
		
		//Identify which columns to select, default to * if nothing was set
		$column_string = '';
		if(sizeof($this->column_array) == 0)
			$column_string = '*';
		else
		{
			$column_string = implode(',', $this->column_array);
		}

		$sql = "SELECT $column_string $from_string $where_string";
		return $sql;
	}

	/**
	 * Prints out the query in its current form
	 *
	 * @return string
	 */
	public function __toString()
	{
		$input = $this->construct_sql();
		$values = $this->value_array;

		//Iterate through each ? in the sql query, replacing it with the next value in our $this->value_array list.
		$output = '';
		$i = 0;
		foreach(explode('?', $input) as $part)
		{
			$value = isset($values[$i])  ?  "'" . $values[$i++] . "'" :  '';
			$output .= $part . $value;
		}

		return $output;
	}
}

?>
