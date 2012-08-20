<?php

class DBConn {
	/**
	 * Creates the DBConn object, and establishes a connection to the DB server.
	 * @throws Exception if a database connection cannot be established
	 */
	public function __construct($server, $user, $pass, $dbname)
	{
		$this->link = new mysqli($server, $user, $pass, $dbname);
		if ($this->link->connect_error) {
			throw new Exception('Could not connect to database: ['.$this->link->connect_errno.']'.$this->link->connect_error);
		}
	}

	public function __destruct()
	{
		if ($this->link) {
			$this->link->close();
			$this->link = null;
		}
	}

	/**
	 * Raises an error if there is no valid connection.
	 * @throws Exception
	 */
	public function __assert()
	{
		if (!$this->link) {
			throw new Exception('No connection.');
		}
	}

	/**
	 * Sends a one-off MySQL SELECT statement, and returns the resulting table
	 * as an array of associative arrays.
	 * @throws Exception if there is no valid database connection
	 */
	public function select($sql)
	{
		$this->__assert();

		$stmt = $this->link->prepare($sql);
		if (!$stmt) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		}

		$stmt->execute();

		// NOTE: everything from this point down would be infinitely cleaner
		// if my sysadmins would upgrade PHP from 5.2.6 and I could use
		// something nice like: $stmt->get_result()
		$names = array();
		$params = array();
		$meta = $stmt->result_metadata();
		while ($field = $meta->fetch_field()) {
			$var = $field->name;
			$$var = null;
			$names[] = $var;
			$params[] = &$$var;
		}
		call_user_func_array(array($stmt,'bind_result'), $params);

		$array = array();
		while ($stmt->fetch()) {
			$row = array();
			foreach ($names as $i=>$n) {
				$row[$n] = $params[$i];
			}
			$array[] = $row;
		}

		$stmt->close();
		return $array;
	}

	/**
	 * Sends a MySQL query to the connected server.
	 * If you want to SELECT, use the `select()` method.
	 * @throws Exception if there is no valid database connection
	 */
	public function query($sql)
	{
		$this->__assert();

		$query_result = $this->link->query($sql, MYSQLI_USE_RESULT);
		if (!$query_result) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		}
		$affected = $this->link->affected_rows;

		$query_result->free();
		return $affected;
	}

	/**
	 * Escapes a string so it is safe to include in an SQL query.
	 */
	public function escape($str)
	{
		$this->__assert();
		return $this->link->real_escape_string($str);
	}
}

