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

/**
 * A generic Data Access Object.
 */
abstract class DAO {

	protected $tables = NULL;
	protected $_db = NULL;

	public function __construct($tables) {
		$this->tables = array();
		foreach ($tables as $name=>$columns) {
			$this->tables[$name] = new DBTable($name, $columns);
		}
	}

	/** This should spawn a new DBConn() object. */
	protected abstract function createdb();

	// lazy init
	public function db() {
		if (is_null($this->_db)) {
			$this->_db = $this->createdb();
		}
		return $this->_db;
	}

	public function table($name, $allow_null=FALSE) {
		if (isset($this->tables[$name])) {
			return $this->tables[$name];
		} elseif ($allow_null) {
			return NULL;
		} else {
			throw new Exception("no table '$name'");
		}
	}

	/**
	 * clause('foo', array('bar'=>1, 'baz'=>'!=2', 'quux'=>array(7, '>10')))
	 * => "`foo`.`bar` = 1 AND `foo`.`baz` != 2 AND (`foo`.`quux` = 7 OR `foo`.`quux` > 10)"
	 */
	protected function clause($table, $matches) {
		if (is_string($table)) $table = $this->table($table);
		return $table->filter($this->db(), $matches);
	}
	/**
	 * where('foo', array('bar'=>1, 'baz'=>'!=2', 'quux'=>array(7, '>10')))
	 * => "WHERE `foo`.`bar` = 1 AND `foo`.`baz` != 2 AND (`foo`.`quux` = 7 OR `foo`.`quux` > 10)"
	 */
	protected function where($table, $matches) {
		if ($matches) {
			return 'WHERE ' . $this->clause($table, $matches);
		} else {
			return '';
		}
	}
	/**
	 * orderby('foo', array('bar', 'baz'=>'DESC', 'quux'=>'ASC')
	 * => "ORDER BY `foo`.`bar`, `foo`.`baz` DESC, `foo`.`quux` ASC"
	 */
	protected function orderby($table, $keys) {
		if (is_string($table)) $table = $this->table($table);

		if ($keys) {
			$oparts = array();
			if (!is_array($keys)) $keys = array($keys);
			foreach ($keys as $k=>$v) {
				if (is_string($k) && preg_match('/^(ASC|DESC)$/i', $v)) {
					$oparts[] = $table->resolve($k,TRUE) . ' ' . $v;
				} else {
					$oparts[] = $table->resolve($v,TRUE);
				}
			}
			return 'ORDER BY ' . implode(', ', $oparts);
		} else {
			return '';
		}
	}

	/**
	 * naturaljoin('foo', 'bar')
	 * => "JOIN `bar` ON `foo`.`baz` = `bar`.`baz`"
	 */
	protected function natural_join($table1, $table2) {
		if (is_string($table1)) $table1 = $this->table($table1);
		if (is_string($table2)) $table2 = $this->table($table2);
		$cols = array_intersect( $table1->columns(), $table2->columns() );
		$t1 = $table1->name();
		$t2 = $table2->name();
		if (!$cols) {
			throw new Exception("no natural join between table $t1 and $t2");
		} else {
			$array = array();
			foreach ($cols as $c) {
				$array[] = "`$t1`.`$c` = `$t2`.`$c`";
			}
			return "  JOIN `$t2` ON " . implode(' AND ', $array);
		}
	}

	/**
	 * join('foo', 'bar', 'baz')
	 * => "JOIN `bar` ON `foo`.`baz` = `bar`.`baz`"
	 *
	 * join('foo', 'bar', array('baz','quux'))
	 * => "JOIN `bar` ON `foo`.`baz` = `bar`.`baz` AND `foo`.`quux` = `bar`.`quux`"
	 */
	protected function join($table1, $table2, $keys) {
		if (is_string($table1)) $table1 = $this->table($table1);
		if (is_string($table2)) $table2 = $this->table($table2);
		if (!is_array($keys)) $keys = array($keys);
		$t1 = $table1->name();
		$t2 = $table2->name();
		$array = array();
		foreach ($keys as $c) {
			$array[] = "`$t1`.`$c` = `$t2`.`$c`";
		}
		return "JOIN `$t2` ON " . implode(' AND ', $array);
	}

	/** builds and returns a SELECT statement */
	protected function get_query($table, $fields=NULL, $filters=NULL, $sort=NULL) {
		if (is_string($table)) $table = $this->table($table);

		$tname = $table->name();
		$field_str = $table->parseFields($fields);
		$where   = $this->where($table, $filters);
		$orderby = $this->orderby($table, $sort);

		return <<<SQL
SELECT
  $field_str
FROM `$tname`
$where
$orderby
SQL;
	}

	/**
	 * Does the heavy lifting of getting fields from a database table.
	 */
	protected function get($table, $fields=NULL, $filters=NULL, $sort=NULL) {
		$query = $this->get_query($table, $fields, $filters, $sort);
		return $this->db()->select($query);
	}

	/** builds and returns an INSERT statement */
	protected function insert_query($table, $values) {
		if (is_string($table)) $table = $this->table($table);

		$tname = $table->name();
		$field_str = $table->parseFields(array_keys($values), TRUE);
		$value_str = $table->valueFields($this->db(), $values);

		return <<<SQL
INSERT INTO `$tname`
(
  $field_str
)
VALUES
  ($value_str)
SQL;
	}

	/**
	 * Does the heavy lifting of adding a new object to the database.
	 * @param mixed $table
	 * @param array $values a key=>value map of fields and values for the new object
	 * @return the value of the first AUTO_INCREMENT field in the created record
	 */
	protected function create($table, $values) {
		$query = $this->insert_query($table, $values);
		$result = $this->db()->insert($query);
		return $result;
	}

	/** builds and returns an UPDATE statement */
	protected function update_query($table, $fields, $filters) {
		if (is_string($table)) $table = $this->table($table);

		$tname = $table->name();
		$field_str = $table->updateFields($this->db(), $fields);
		$where   = $this->where($table, $filters);

		return <<<SQL
UPDATE `$tname`
SET
  $field_str
$where
SQL;
	}

	/**
	 * Does the heavy lifting of getting fields from a database table.
	 */
	protected function set($table, $fields, $filters) {
		$query = $this->update_query($table, $fields, $filters);
		return $this->db()->query($query);
	}

}

