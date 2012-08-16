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


class Autoloader {

	private static $map = array();

	public static function load_class($classname) {
		if (isset(self::$map[$classname]))
			require_once(self::$map[$classname]);
	}

	public static function register($classname, $filename) {
		if (isset(self::$map[$classname]) && ($old = self::$map[$classname]) != $filename) {
			throw new Exception("duplicate file for class $classname: adding $filename, already got $old");
		}
		self::$map[$classname] = $filename;
	}

	private function __construct() {}
	private function __clone() {}
}

spl_autoload_register(array('Autoloader', 'load_class'));

