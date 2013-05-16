<?php
abstract class MySQLModel extends BasicModel
{
	protected $db;
	protected $table;
	protected $primary_key;

	public function __construct($fields, $table = null, $primary_key = null)
	{
		parent::__construct($fields);
		$this->db = MySQLConnection::getConnection();

		//If no table name is explicitly set, assume the name of the extending class is the name of the table
		//TODO: How should case sensitivity be handled
		if(!isset($table))
			$table = get_class($this);
		$this->table = $table;

		$this->fields = $fields;

		//If no primary key is explicitly set, assume the first field is the primary key
		if(!isset($primary_key))
		{
			reset($fields);
			$primary_key = key($fields);
		}
		$this->primary_key = $primary_key;
	}

	public function create()
	{
		$fieldList = '';
		$valueList = '';
		$values = array();

		foreach($this->fields as $key => $value)
		{
			if(isset($value) && $value != '')
			{
				$fieldList .= "`" . $key . "`,";
				$valueList .= "?,";
				$values[] = $value;
			}
		}

		$sql = "INSERT INTO `" . $this->table . "` (" . substr($fieldList, 0, -1) . ") VALUES (" . substr($valueList, 0, -1) . ")";

		$query = $this->db->prepare($sql);
		$query->excecute($values);
		return $this->db->lastInsertId();
	}

	public function retrieve()
	{
		$index = $this->_getField($this->primary_key);
		if(!isset($index) || $index == '')
			throw new Exception("Record ID not defined");

		$fieldList = '';
		foreach($this->fields as $key => $value)
		{
			$fieldList .= "`" . $key . "`,";
		}

		$sql = "SELECT " . substr($fieldList, 0, -1) . " FROM `" . $this->table . "` WHERE `" . $this->primary_key . "` = ? LIMIT 1";
		$query = $this->db->prepare($sql);
		$query->execute(array($index));

		$response = $query->fetch(PDO::FETCH_ASSOC);
		if($response === false)
			throw new Exception("No results found with record ID = $index");

		foreach($response as $field=>$value)
		{
			$this->_setField($field,$value);
		}
	}

	public function update()
	{
		$index = $this->_getField($this->primary_key);
		if(!isset($index) || $index == '')
			throw new Exception("Record ID not defined");

		$fieldList = '';
		$values = array();
		foreach($this->fields as $key=>$value)
		{
			$fieldList .= "`" . $key . "` = ?,";
			$values[] = $value;
		}
		$values[] = $index;

		$sql = "UPDATE `" . $this->table . "` SET " . substr($fieldList, 0, -1) . " WHERE `" . $this->primary_key . "` = ? LIMIT 1";
		$query = $this->db->prepare($sql);
		$query->execute($values);
		return $index;
	}

	public function delete()
	{
		$index = $this->_getField($this->primary_key);
		if(!isset($index) || $index == '')
			throw new Exception("Record ID not defined");
		
		$sql = "DELETE FROM `" . $this->table . "` WHERE `" . $this->primary_key . "` = ? LIMIT 1";		
		$query = $this->db->prepare($sql);
		$query->execute(array($index));
	}
}
