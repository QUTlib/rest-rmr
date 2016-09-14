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
 * Looks and quacks a bit like a DBColumn, but actually represents
 * a fake column, which is an alias for another.
 */
class DBColumnAlias {
	private $name = NULL;
	private $real = NULL;
	public function __construct($name, $real) {
		$this->name = $name;
		$this->real = $real;
	}
	public function name() { return $this->name; }
	public function real() { return $this->real; }
}

