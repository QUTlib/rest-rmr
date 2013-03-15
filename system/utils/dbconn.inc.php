<?php
/*
 * See the NOTICE file distributed with this work for information
 * regarding copyright ownership.  QUT licenses this file to you
 * under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */


class DBConn {
	private $link = NULL;
	private $key = NULL;
	private static $links = array();

	static function create_link($key, $server, $user, $pass, $dbname) {
		if (isset(self::$links[$key])) {
			self::$links[$key]['count'] ++;
		} else {
			self::$links[$key] = array(
				'link' => new mysqli($server, $user, $pass, $dbname),
				'count' => 1,
			);
		}
		return self::$links[$key]['link'];
	}

	static function drop_link($key) {
		if (self::$links[$key]['count'] < 2) {
			self::$links[$key]['link']->close();
			unset(self::$links[$key]);
		} else {
			self::$links[$key]['count'] --;
		}
	}

	/**
	 * Creates the DBConn object, and establishes a connection to the DB server.
	 * @throws Exception if a database connection cannot be established
	 */
	public function __construct($server, $user, $pass, $dbname)
	{
#		$this->key = "$user:$pass@$server/$dbname";
#		$this->link = self::create_link($this->key, $server, $user, $pass, $dbname);
		$this->link = new mysqli($server, $user, $pass, $dbname);
		if ($this->link->connect_error) {
			throw new Exception('Could not connect to database: ['.$this->link->connect_errno.']'.$this->link->connect_error);
		}
	}

	public function __destruct()
	{
		if ($this->link) {
#			self::drop_link($this->key);
			$this->link->close();
			$this->link = NULL;
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
		$query_result = $this->link->query($sql);
		if (!$query_result) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		} elseif (!is_object($query_result)) {
			// if query_result is not an object (i.e. it's TRUE) then the
			// user didn't send a SELECT-type query, so they can suck it up
			throw new Exception('query result is not an object (try using ::query() ?)');
		}
		#$array = $query_result->fetch_all(MYSQLI_ASSOC);
		$array = array();
		while (!is_null($row = $query_result->fetch_assoc())) {
			$array[] = $row;
		}
		$query_result->free();
		return $array;
	}

	public function call_and_select($call, $item) {
		$this->__assert();

		// validate the CALL query, the lazy way.
		$stmt = $this->link->prepare($call);
		if (!$stmt) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		}
		$stmt->close();

		// validate the item
		if (!preg_match('/^@[a-z_][a-z0-9_]*$/i', $item)) {
			throw new Exception("Invalid field identifier '$item'");
		}

		// build and execute the actual queries
		$sql = "$call; SELECT $item";
		if ($this->link->multi_query($sql)) {
			do {
				if ($result = $this->link->store_result()) {
					$array = $result->fetch_assoc();
					if (isset($array[$item])) {
						$result->free();
						return $array[$item];
					}
				}
			} while ($this->link->next_result());
			return NULL;
		} else {
			return FALSE;
		}
	}

	/**
	 * Sends a one-off MySQL INSERT statement, and returns the resulting id
	 * (if one of the fields in the table has the AUTO_INCREMENT attribute.)
	 * @throws Exception if there is no valid database connection
	 */
	public function insert($sql)
	{
		$this->__assert();

		$query_result = $this->link->query($sql);
		if (!$query_result) {
			throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
		}
		$id = $this->link->insert_id;
		if (is_object($query_result)) {
			$query_result->free();
		}
		return $id;
	}

	/**
	 * Sends a MySQL query to the connected server.
	 * If you want to SELECT, use the `select()` method; to INSERT, use `insert()`.
	 * @throws Exception if there is no valid database connection
	 */
	public function query($sql)
	{
		$this->__assert();

		$query_result = $this->link->query($sql);
		if (!$query_result) {
			if (defined('DEBUG') && DEBUG) {
				throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error."\n\n".$sql);
			} else {
				throw new Exception('Query error: ['.$this->link->errno.']'.$this->link->error);
			}
		}
		$affected = $this->link->affected_rows;
		if (is_object($query_result)) {
			$query_result->free();
		}
		return $affected;
	}

	/**
	 * Escapes a string so it is safe to include in an SQL query.
	 * Also works for dates, times, datetimes, blobs, etc.
	 */
	public function escape($str)
	{
		$this->__assert();
		return $this->link->real_escape_string($str);
	}

	/**
	 * Armours an integer so it is safe to include in an SQL query.
	 */
	public function int($i)
	{
		return intval($i);
	}

	/**
	 * Asserts that the given value is an integer.
	 */
	public function check_int($i)
	{
		if (!(is_int($i) || preg_match('/^[+-]?\d+$/',$i))) throw new Exception("not an integer '$i'");
		return TRUE;
	}

	/**
	 * Armours a float so it is safe to include in an SQL query.
	 */
	public function float($f)
	{
		return floatval($f);
	}

	/**
	 * Asserts that the given value is a floating point number.
	 * This also allows integers.
	 */
	public function check_float($f)
	{
		if (!(is_float($f) || is_int($f) || preg_match('/^[+-]?(\d*\.)?\d+$/',$f))) throw new Exception("not a floating point number '$f'");
		return TRUE;
	}

	/**
	 * Converts a boolean to a bit-representation for use in an SQL query.
	 */
	public function bool($b)
	{
		return ("b'" . ($b ? 1 : 0) . "'");
	}
}

