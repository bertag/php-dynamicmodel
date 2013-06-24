<?php
abstract class BasicModel
{
	protected $fields;

	public function __construct($fields)
	{
		$this->fields = $fields;
	}

	// Return a JSON string which represents this object by default
	public function __toString()
	{
		return(json_encode($this->toArray()));
	}

	public function toArray()
	{
		return $this->fields;
	}

	//General Get and Set Handlers
	public function __get($name)
	{
		if(method_exists($this, $functionName = 'get' . ucfirst($name)))
			return $this->$functionName();
		elseif(property_exists(get_class(), $varName = '_' . $name))
			return $this->$varName;
		elseif(array_key_exists($name, $this->fields))
			return $this->_getField($name);
		else
			throw new Exception("$name is not available in current scope");
	}

	public function __set($name, $value)
	{
		if(method_exists($this, $functionName = 'set' . ucfirst($name)))
			return $this->$functionName($value);
		elseif(property_exists(get_class(), $varName = '_' . $name))
			return $this->$varName = $value;
		elseif(array_key_exists($name, $this->fields))
			return $this->_setField($name, $value);
		else
			throw new Exception("$name is not available in current scope");

	}

	public function __isset($name)
	{
		if(property_exists(get_class(), $varName = '_' . $name) || array_key_exists($name, $this->fields))
			return true;
		else
			return false;
	}

	//General Field Handlers
	protected function _getField($name)
	{
		return $this->fields[$name];
	}

	protected function _setField($name, $value)
	{
		$this->fields[$name] = $value;
		return $this->fields[$name];
	}
}
