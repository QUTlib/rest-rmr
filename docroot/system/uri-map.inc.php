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


class URIMap {

	// "All general-purpose servers MUST support the methods GET and HEAD."
	//  * <https://tools.ietf.org/html/rfc7231#section-4.1>
	private static $map = array('GET'=>array(), 'HEAD'=>array());

	/**
	 * Returns TRUE if there are any registered handlers for given $method,
	 * or FALSE otherwise.
	 *
	 * @param string $method
	 * @return bool
	 */
	public static function knows_method($method) {
		return isset( self::$map[$method] );
	}

	/**
	 * Returns a URIMapIterator object which can be used in a foreach() loop.
	 *
	 * @param string $method
	 * @return URIMapIterator
	 */
	public static function method($method) {
		if (isset(self::$map[$method])) {
			return new URIMapIterator( self::$map[$method] );
		} else {
			return new URIMapIterator( array() );
		}
	}

	/**
	 * Returns an array of all the HTTP methods we know.
	 *
	 * @return string[]
	 */
	public static function methods() {
		return array_keys( self::$map );
	}

	/**
	 * Returns an array of the HTTP methods what can access the given URI.
	 * (May be empty)
	 *
	 * @param string $uri the path component of the URI to check
	 * @return string[]
	 */
	public static function allowed_methods($uri) {
		$methods = array();
		foreach (self::$map as $meth=>$rules) {
			foreach ($rules as $rule) {
				$match = $rule['match'];
				$regex = $match[0];
				if (preg_match($regex, $uri)) {
					$methods[] = $meth;
					break;
				}
			}
		}
		return $methods;
	}

	/**
	 * Given a handler (like you'd find when iterating over #method() ),
	 * turn it into something that call_user_func() and friends would accept
	 * as a receiver.
	 *
	 * @param mixed $handler
	 * @return callable
	 */
	public static function realise_handler($handler) {
		// if the handler object is a string, treat it as a classname
		// and instantiate it
		if (is_array($handler) && count($handler) > 2) {
			$klass = $handler[0];
			$method = $handler[1];
			$object = new $klass();
			$handler = array($object, $method);
		}
		return $handler;
	}

	/**
	 * Sets up a handler to accept incoming requests.
	 *
	 * Note that GET handlers automatically set up an identical HEAD handler.
	 *
	 * URI pattern examples:
	 *
	 *     '/students/:sid/'
	 *       :: '/students/123/' => {"sid":"123"}
	 *     '/some/path/?'
	 *       :: '/some/path/' => {}
	 *       :: '/some/path'  => {}
	 *     '/branch/:name/?'
	 *       :: '/branch/gp/' => {"name":"gp"}
	 *       :: '/branch/kg'  => {"name":"kg"}
	 *       :: '/branch/'    => FALSE
	 *
	 * @param string $http_method the HTTP method to handle (e.g. GET, POST, etc.).
	 * @param string $uri_pattern
	 * @param mixed $handler {@see URIMap::realise_handler}
	 */
	public static function register($http_method, $uri_pattern, $handler) {
		$match = self::parse_uri_pattern($uri_pattern);
		$handler = self::parse_handler($handler);
		$http_method = strtoupper($http_method);
		self::_do_register($http_method, $match, $handler);
		if ($http_method == 'GET') {
			self::_do_register('HEAD', $match, $handler);
		}
	}

	/**
	 * The core work of #register()
	 */
	protected static function _do_register($method, $match, $handler) {
		if (!isset(self::$map[$method]))
			self::$map[$method] = array();
		self::$map[$method][] = array('match'=>$match, 'handler'=>$handler);
	}

	/**
	 * pattern = "/", {slash_chunk}, [ final_chunk ];
	 * slash_chunk = chunk, "/";
	 * final_chunk = optslash_chunk | chunk;
	 * optslash_chunk = chunk,  "/", "?" ;
	 * chunk = named_param | literal_chunk;
	 * named_param = ":", alpha_underscore, { alphanum_underscore };
	 * literal_chunk = { char }-;
	 * char = (? URL character ?) - ( "?" | "#" | "/" );
	 */
	protected static function parse_uri_pattern($pattern) {
		if ($pattern{0} != '/')
			throw new Exception("invalid pattern '$pattern': no leading slash");
		if ($pattern == '/?')
			throw new Exception("invalid pattern '$pattern': leading slash cannot be optional");

		$optslash = false;
		if (substr($pattern,-2) == '/?') {
			$optslash = true;
			$pattern = substr($pattern,0,-1);
		}

		if (strpos($pattern,'?') !== false)
			throw new Exception("invalid pattern '$pattern': ?query not allowed");
		if (strpos($pattern,'#') !== false)
			throw new Exception("invalid pattern '$pattern': #fragment not allowed");

		$query = '#^';
		$names = array();
		$result = array('');
		preg_match_all('#(?<=^|/):([A-Z_][A-Z0-9_]+)(?=/|$)|((?:[^/]|/[^:])+|/)#i', $pattern, $parts, PREG_SET_ORDER);
		foreach ($parts as $i => $part) {
			if (isset($part[1]) && ($name = $part[1])) {
				if (isset($names[$name]))
					throw new Exception("duplicate param :#{name} in pattern '$pattern'");
				$names[$name] = true;
				$query .= '([^/]+)';
				$result[] = $name;
			} else {
				$query .= preg_quote($part[2], '#');
			}
		}
		if ($optslash) $query .= '?';
		$query .= '$#';
		$result[0] = $query;
		return $result;
	}

	/**
	 * array('foo', 'bar')  => (new foo())->bar()
	 * array($foo, 'bar')   => $foo->bar()
	 * array(null, 'bar')   => bar()
	 * array('bar')         => bar()
	 * 'foo::bar'           => foo::bar()
	 * 'foo->bar'           => (new foo())->bar()
	 * 'bar'                => bar()
	 */
	protected static function parse_handler($handler) {
		$o = null;
		$m = null;
		$static = false;
		if (is_array($handler)) {
			$n = count($handler);
			if ($n == 1) {
				$m = reset($handler);
			} elseif ($n == 2) {
				$o = reset($handler);
				$m = next($handler);
			} else {
				throw new Exception("invalid handler (expects 1 or 2 elements in array, found $n)");
			}
		} elseif (($i = strpos($handler, ':')) !== FALSE) {
			$o = substr($handler,0,$i);
			if (strpos($handler, ':', $i+1) == $i+1) {
				$m = substr($handler,$i+2);
				$static = true;
			} else {
				$m = substr($handler,$i+1);
			}
		} elseif (($i = strpos($handler, '->')) !== FALSE) {
			$o = substr($handler,0,$i);
			$m = substr($handler,$i+2);
		} else {
			$m = $handler;
		}

		if (!is_string($m))
			throw new Exception("invalid handler (method name should be a String, found ".gettype($m).")");

#		// Impossible:
#		if ($static && !is_string($o))
#			throw new Exception("invalid handler (for static method, classname should be a String, found ".gettype($o).")");

		if (is_null($o)) {
			return $m;
		} elseif ($static || is_object($o)) {
			return array($o, $m);
		} elseif (is_string($o)) {
			return array($o, $m, true);
		} else {
			throw new Exception("invalid handler (expected object or classname, found ".gettype($o).")");
		}
	}

	private function __construct() {}
	private function __clone() {}
}

/**
 * A simple iterator of callables.
 * @see URIMap::method()
 */
class URIMapIterator implements Iterator {
	private $array = null;
	/** @ignore */
	public function __construct(&$array) {
		$this->array =& $array;
	}
	/** @ignore */ public function rewind()  {        reset($this->array); }
	/** @ignore */ public function current() { return current($this->array); }
	/** @ignore */ public function key()     { return key($this->array); }
	/** @ignore */ public function next()    {        next($this->array); }
	/** @ignore */ public function valid()   { return (key($this->array) !== NULL); }
}

