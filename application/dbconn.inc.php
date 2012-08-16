<?php

class DBConn {
	/**
	 * Creates the DBConn object, and establishes a connection to the DB server.
	 * @throws Exception if a database connection cannot be established
	 */
	public function __construct($admin = false)
	{
		if ($admin) {
			$user = 'hours_admin';
			$pass = 'jh@A%2d0Lw';
		} else {
			$user = 'hours_user';
			$pass = 'qp8^mKLJx.g';
		}
		$this->link = new mysqli('dbdev1.library.qut.edu.au', $user, $pass, 'dev_demo12_library_qut_edu_au');
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

		$result = $this->link->query($sql, MYSQLI_USE_RESULT);
		if (!$result) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		}

		$array = array();
		while ($row = $result->fetch_assoc()) {
			$array[] = $row;
		}

		$result->close();
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

