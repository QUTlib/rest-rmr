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
 * A very simple description of a column in a database table.
 */
class DBColumn {
	const INTEGER = 0;
	const STRING = 1;
	const BOOLEAN = 2;
	const DATE = 3;
	const TIME = 4;
	const DATETIME = 5;
	const TIMESTAMP = 6;
	const FLOAT = 7;

	private $name = NULL;
	private $type = NULL;
	private $pkey = NULL;
	public function __construct($name, $type, $pkey=FALSE) {
		$this->name = $name;
		$this->type = $type;
		$this->pkey = $pkey;
	}
	public function name() { return $this->name; }
	public function type() { return $this->type; }
	public function pkey() { return $this->pkey; }
}

