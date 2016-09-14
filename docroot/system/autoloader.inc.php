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
 * Static object that handles deferred-loading of PHP source files
 * when undefined classes are encountered.
 */
class Autoloader {

	/** @ignore */
	private static $map = array();

	/**
	 * Requires the PHP source file associated with the given classname, if any.
	 * @param string $classname the lowercased name of the class (and namespace) to be instantiated
	 */
	public static function load_class($classname) {
		if (isset(self::$map[$classname]))
			require_once(self::$map[$classname]);
	}

	/**
	 * Register a PHP source file to require in order to instantiate a class.
	 * @param string $classname the name of the class associated with the given file
	 * @param string $filename the file that defines the class
	 */
	public static function register($classname, $filename) {
		if (isset(self::$map[$classname]) && ($old = self::$map[$classname]) != $filename) {
			throw new Exception("duplicate file for class $classname: adding $filename, already got $old");
		}
		self::$map[$classname] = $filename;
	}

	/**#@+ @ignore */
	private function __construct() {}
	private function __clone() {}
	/**#@-*/
}

spl_autoload_register(array('Autoloader', 'load_class'));

