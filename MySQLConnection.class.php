<?php

class MySQLConnection
{
	public static $db;

	public static function init($connectionString, $user, $pass)
	{
		self::$db = new PDO($connectionString, $user, $pass);
		return self::$db;
	}

	public static function getConnection()
	{
		if(!isset(self::$db))
		{
			throw new Exception('Database connection not set');
		}
		return self::$db;
	}
}

?>
