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
 * A simple description of a database table.
 */
class DBTable {
	protected $name = NULL;
	protected $columns = NULL;
	protected $column_aliases = NULL;

	public function __construct($name, $columns) {
		$this->name = $name;

		$names = array();
		$cols = array();
		$akas = array();
		foreach ($columns as $column) {
			$cname = $column->name();
			if (isset($names[$cname]))
				throw new Exception("duplicate column definition '$cname'");
			$names[$cname] = TRUE;
			if ($column instanceof DBColumn) {
				$cols[$cname] = $column;
			} elseif ($column instanceof DBColumnAlias) {
				$akas[$cname] = $column;
			} else {
				throw new Exception("not a DBColumn: '".get_class($column)."'");
			}
		}
		$this->columns = $cols;
		$this->column_aliases = $akas;
	}

	public function name() { return $this->name; }
	public function columns() { return array_keys($this->columns); }

	/**
	 * Gets the name/definition of the primary keys in this DAO's table.  May be an array, or NULL.
	 *
	 * @param boolean $object [optional] if given and true, returns the actual object
	 * @param boolean $fatal [optiona] if given and true, throws an exception if no key defined
	 */
	public function primary_key($object=FALSE, $fatal=FALSE) {
		$keys = $this->primary_keys($object, $fatal);
		if (is_null($keys) || count($keys) > 1) {
			// either we found a bunch, or we didn't find any and
			// primary_keys didn't raise an exception.
			return $keys;
		} else {
			// just the one
			return $keys[0];
		}
	}

	/**
	 * Gets the name/definition of the primary keys in this DAO's table, as an array.  May be NULL.
	 *
	 * @param boolean $object [optional] if given and true, returns the actual object
	 * @param boolean $fatal [optiona] if given and true, throws an exception if no key defined
	 */
	public function primary_keys($object=FALSE, $fatal=FALSE) {
		$keys = array();
		foreach ($this->columns as $name=>$def) {
			if ($def->pkey()) {
				$keys[] = ($object ? $def : $name);
			}
		}
		if ($keys) {
			// found some
			return $keys;
		} elseif ($fatal) {
			// no keys -- die
			throw new Exception("no primary keys defined");
		} else {
			// no keys -- NULL
			return NULL;
		}
	}

	/**
	 * Get the column definition object for a given column name.
	 *
	 * @param boolean $fatal [optiona] if given and true, throws an exception if no such column
	 */
	protected function column($name, $fatal=FALSE) {
		// resolve aliases
		if ( isset($this->column_aliases[$name]) )
			$name = $this->column_aliases[$name]->real();

		// look it up
		if ( isset($this->columns[$name]) ) {
			return $this->columns[$name];
		} elseif ($fatal) {
			throw new Exception("invalid column '$name'");
		} else {
			return NULL;
		}
	}

	/**
	 * Resolves a column name/alias to the actual column name.
	 * $column can be an array.
	 * If $full is given and true, does the whole `table`.`column` thing.
	 */
	public function resolve($column, $full=FALSE) {
		if (is_array($column)) {
			$result = array();
			foreach ($column as $c) {
				$result[] = $this->resolve($c, $full);
			}
			return $result;
		} else {
			$col = $this->column($column, TRUE);
			if ($full) {
				$t = $this->name();
				$c = $col->name();
				return "`$t`.`$c`";
			} else {
				return $col->name();
			}
		}
	}

	/**
	 * Parses an array of field names into a well-formed SQL clause, as could
	 * be used in a SELECT statement.
	 *
	 * Throws an exception if any field name doesn't correspond with an actual
	 * column.
	 */
	public function parseFields($fields, $no_cast=FALSE) {
		$tbl = $this->name;
		// if NULL, default to primary keys
		if (!$fields) {
			$fields = $this->primary_keys();
		// if '*', replace with all, explicitly
		} elseif ($fields == '*') {
			#return "*\n";
			$fields = $this->columns();
		// otherwise, just make sure it's an array
		} elseif (! is_array($fields)) {
			$fields = array($fields);
		}

		$field_array = array();
		foreach ($fields as $field) {
			$column = $this->column($field, TRUE);
			$name = $column->name();
			if ($no_cast) {
				$field_array[] = "`$name`";
			} else {
				switch ($column->type()) {
				case DBColumn::BOOLEAN:
					$field_array[] = "CAST(`$tbl`.`$name` AS UNSIGNED) AS `$field`";
					break;
				case DBColumn::TIMESTAMP:
					$field_array[] = "DATE_FORMAT(`$tbl`.`$name`, '%Y-%m-%dT%T') AS $field";
					break;
				default:
					$field_array[] = "`$tbl`.`$name` AS `$field`";
					break;
				}
			}
		}
		return implode(",\n  ", $field_array);
	}

	/**
	 * Casts a PHP value to match a database type.
	 */
	protected function _cast($value, $type, $db) {
		if ($value === NULL || ($value === '' && $type != DBColumn::STRING)) {
			return 'NULL';
		}
		switch ($type) {
		case DBColumn::BOOLEAN:
			switch (strtolower($value)) {
			case 'true':  $value = "b'1'"; break;
			case 'false': $value = "b'0'"; break;
			default:      $value = "b'" . ($value ? 1 : 0) . "'";
			}
			break;
		case DBColumn::STRING:
		case DBColumn::TIME:
		case DBColumn::DATE:
		case DBColumn::DATETIME:
			$value = "'" . $db->escape($value) . "'";
			break;
		case DBColumn::FLOAT:
			$db->check_float($value);
			break;
		default:
			$db->check_int($value);
			break;
		}
		return $value;
	}

	/**
	 * Builds a query fragment: "$col = $val"
	 *
	 * If $val is an array, builds a bunch of conditions
	 * ORed together, in brackets.
	 *
	 * A $val can start with =, !=, <=, >=, <, or >
	 */
	public function compare($db, $col, $val) {
		$tbl = $this->name();
		$def = $this->column($col, TRUE);
		$name = $def->name();
		$type = $def->type();

		if (is_array($val)) {
			$c = array();
			foreach ($val as $v) {
				if (preg_match('/^([!<>]?=|[<>])\s*(.*)/', $v, $m)) {
					$v = $m[2];
					$o = $m[1];
				} else {
					$o = '=';
				}
				$v = $this->_cast($v, $type, $db);
				$c[] = "`$tbl`.`$name` $o $v";
			}
			$equals = '(' . implode(' OR ', $c) . ')';
		} else {
			if (preg_match('/^([!<>]?=|[<>])\s*(.*)/', $val, $m)) {
				$val = $m[2];
				$op = $m[1];
			} else {
				$op = '=';
			}
			$val = $this->_cast($val, $type, $db);
			$equals = "`$tbl`.`$name` $op $val";
		}
		return $equals;
	}

	/**
	 * Given $matches = array( 'col1'=>'val1', 'col2'=>'val2' ),
	 * builds a query fragment: "col1 = val1 AND col2 = val2"
	 *
	 * Any val can be an array, in which case it is ORed together
	 * internally. E.g.:
	 *
	 *    _filter($tb, $table, array( 'col1'=>'val1', 'col2'=>array('x','y') ))
	 *      #=> "col1 = val1 AND (col2 = x OR col2 = y)"
	 *
	 * A val can start with =, !=, <=, >=, <, or >
	 */
	public function filter($db, $matches) {
		$f = array();
		foreach ($matches as $column=>$value) {
			$f[] = $this->compare($db, $column, $value);
		}
		return implode(' AND ', $f);
	}

	/**
	 * Builds a query fragment: "$col = $val"
	 */
	public function set($db, $col, $val) {
		$tbl = $this->name();
		$def = $this->column($col, TRUE);
		$name = $def->name();
		$type = $def->type();

		$val = $this->_cast($val, $type, $db);
		$equals = "`$tbl`.`$name` = $val";
		return $equals;
	}

	/**
	 * Builds a query fragment: "$col = $val"
	 */
	public function val($db, $col, $val) {
		$tbl = $this->name();
		$def = $this->column($col, TRUE);
		$type = $def->type();

		$val = $this->_cast($val, $type, $db);
		return $val;
	}

	/**
	 * Given $values = array( 'col1'=>'val1', 'col2'=>'val2' ),
	 * builds a query fragment: "val1, val2"
	 */
	public function valueFields($db, $values) {
		$f = array();
		foreach ($values as $column=>$value) {
			$f[] = $this->val($db, $column, $value);
		}
		return implode(', ', $f);
	}

	/**
	 * Given $values = array( 'col1'=>'val1', 'col2'=>'val2' ),
	 * builds a query fragment: "col1 = val1, col2 = val2"
	 */
	public function updateFields($db, $values) {
		$f = array();
		foreach ($values as $column=>$value) {
			$f[] = $this->set($db, $column, $value);
		}
		return implode(', ', $f);
	}

}

