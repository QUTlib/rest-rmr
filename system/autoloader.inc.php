<?php

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

