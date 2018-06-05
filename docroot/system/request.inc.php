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
 * Singleton object that provides all details about the current HTTP request.
 */
class Request {
	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	private static $client_ip = NULL;
	private static $protocol = NULL;
	private static $method  = NULL;
	private static $uri     = NULL;
	private static $https   = NULL;
	private static $headers = NULL;
	private static $headers_index = NULL;
	private static $get  = NULL;
	private static $post = NULL;
	private static $files  = NULL;
	private static $params = NULL;
	private static $entity_body = NULL;
	private static $preconditions = NULL;

	/**
	 * Initialise the request.  This is called from `index.php`
	 */
	public static function init() {
		if (isset($_GET['path']) && ($path = $_GET['path'])) {
			self::$uri = $path;
			unset($_GET['path']);
		} else {
			self::$uri = '/';
		}

		self::$client_ip   = self::get_client_ip();
		self::$protocol    = self::server_var('SERVER_PROTOCOL', 'HTTP/1.0');
		self::$method      = self::server_var('REQUEST_METHOD', 'GET');
		self::$headers = getallheaders();
		self::$get   = $_GET;
		self::$post  = $_POST;
		self::$https = (($https = self::server_var('HTTPS')) && ($https != 'off'));

		self::$files = array();
		foreach ($_FILES as $key=>$nest) {
			$flat = self::flatten_files($key, $nest['error'], $nest['name'], $nest['type'], $nest['size'], $nest['tmp_name']);
			foreach ($flat as $ident=>$data) {
				if (!$data['error']) { // UPLOAD_ERR_OK=0 so this is ok
					throw new Exception("file upload error ".$data['error']." for '$ident'"); # FIXME
				}
				self::$files[$ident] = array(
					'name' => $data['name'],
					'type' => $data['type'],
					'size' => $data['size'],
					'tmp_name' => $data['tmp_name'],
				);
			}
		}

		// Special edge-case, happens rarely but does happen
		if (strtoupper(self::$method) == 'OPTIONS' && self::$uri == '/*') {
			self::$uri = '*';
		}

		// build a header index
		$headers_index = array();
		foreach (self::$headers as $k=>$v) {
			$headers_index[strtolower($k)] = $k;
		}
		self::$headers_index = $headers_index;
	}

	/**
	 * Flattens arbitrarily-nested sub-arrays in $_FILES.
	 */
	private static function flatten_files($ident, &$error, &$name, &$type, &$size, &$tmp_name) {
		if (is_array($error)) {
			$results = array();
			foreach ($error as $key=>$foo) {
				$keyid = $ident.'['.$key.']';
				$array = flatten_files($keyid,
					$error[$key],
					$name[$key],
					$type[$key],
					$size[$key],
					$tmp_name[$key]
				);
				// $results = array_merge($results, $array);
				foreach ($array as $a=>$b) {
					$results[$a] = $b;
				}
			}
			return $results;
		} else {
			return array($ident => array($error, $name, $type, $size, $tmp_name));
		}
	}

	/** The IP address of the client who made the request */
	public static function client_ip() { return self::$client_ip; }

	/** The request URI path */
	public static function uri() { return self::$uri; }

	/** The protocol used to make the request (e.g. "HTTP/1.1") */
	public static function protocol() { return self::$protocol; }

	/** Whether the request was made over HTTPS */
	public static function is_https() { return self::$https; }

	/**
	 * The whole requested URI, including scheme, server hostname, and path.
	 */
	public static function full_uri() {
		$scheme = (self::$https ? 'https' : 'http');
		$host   = SITEHOST; #self::server_var('SERVER_NAME');
		$path   = self::$uri;
		return "$scheme://$host$path";
	}

	/**
	 * Best effort attempt to resolve the remote IP address.
	 * Uses $TRUSTED_PROXIES config setting.
	 *
	 * WARNING: it is more efficient to use Request::client_ip()
	 */
	public static function get_client_ip() {
		if (isset(self::$client_ip)) {
			return self::$client_ip;
		}
		global $TRUSTED_PROXIES;
		$rad = self::server_var('REMOTE_ADDR');
		$is_trusted = ($rad && isset($TRUSTED_PROXIES) && in_array($rad, $TRUSTED_PROXIES));
		if ($is_trusted && ($list = self::server_var('HTTP_X_FORWARDED_FOR')) || ($list = self::server_var('HTTP_CLIENT_IP'))) {
			$bits = explode(',', $list, 2);
			return $bits[0];
		} else {
			return $rad;
		}
	}

	/**
	 * Gets the value of a server variable as provided by PHP/Apache.
	 *
	 * @param string $name the server variable to fetch
	 * @param mixed $default value to return if no such variable is defined
	 */
	public static function server_var($name, $default=NULL) {
		if (isset($_SERVER[$name])) {
			return $_SERVER[$name];
		} elseif (isset($_SERVER['REDIRECT_'.$name])) {
			return $_SERVER['REDIRECT_'.$name];
		} else {
			return $default;
		}
	}

	/**
	 * Gets the request's HTTP version, as "major.minor"
	 * Returns FALSE if it doesn't look like HTTP.
	 */
	public static function http_version() {
		if (preg_match('@HTTP/(\d+\.\d+)@', self::$protocol, $m)) {
			return $m[1];
		} else {
			return FALSE;
		}
	}

	/**
	 * Gets the HTTP request method.
	 *
	 * May provide a list of allowed methods; if so, the request method is
	 * compared to them in turn, and if a match is not found the HTTP response
	 * is set to either 501 (Not Implemented) or 405 (Method Not Allowed),
	 * an additional Allow HTTP header is sent, and FALSE is returned.
	 *
	 * Note: RFC 2616 states that the "methods GET and HEAD MUST be supported
	 * by all general-purpose servers."
	 *  <http://www.w3.org/Protocols/rfc2616/rfc2616-sec5.html#sec5.1.1>
	 *
	 * @param string[] $allowed (optional) list of allowed methods
	 * @param integer  $errcode (optional) what statuc code to use if not allowed (405=Not Allowed, 501=Not Implemented,...)
	 * @return mixed the HTTP request method from the current request, or FALSE
	 */
	public static function method($allowed=NULL, $errcode=405) {
		if (func_num_args() > 0) {
			$allowed = func_get_args();
			$upcased = array();
			$get = false;
			$head = false;
			foreach($allowed as $meth) {
				$meth = strtoupper($meth);
				if (self::$method == $meth) return $meth; // short circuit
				if ($meth == 'GET') $get = true;
				if ($meth == 'HEAD') $head = true;
				$upcased[] = $meth;
			}
			// If we get here, the actual method wasn't in the allowed list
			$code = ($errcode ? $errcode : 405);
			$allow = implode(', ', $upcased);
			header("Allow: $allow", TRUE, $code);
			return FALSE;
		} else {
			return self::$method;
		}
	}

	/**
	 * Simple helper that returns TRUE if the request method was GET or HEAD.
	 * @return boolean
	 */
	public static function is_get_or_head() {
		return (self::$method == 'GET' || self::$method == 'HEAD');
	}

	/**
	 * Gets the precondition headers from the client.
	 *
	 * These are the headers that can possibly result in a '412 Precondition
	 * Failed' response.
	 *
	 * Result is an array of the form:
	 *
	 *     array(
	 *         header1 => timestamp, # for date-type precondition
	 *         header2 => '*',       # for ETag-type precondition
	 *         header3 => array(etag,..),
	 *                              # for ETag-type precondition
	 *     )
	 *
	 * Currently inspected headers are:
	 *
	 *  - If-Modified-Since (date-type)
	 *  - If-Unmodified-Since (date-type)
	 *  - If-None-Match (ETag-type)
	 *  - If-Match (ETag-type)
	 *  - If-Range (either type)
	 *
	 * @param string $header if given, only check that header. Like `Request::preconditions()[$header]`
	 * @return array|null
	 */
	public static function preconditions($header=NULL) {
		if (is_null(self::$preconditions)) {
			$precon = array();
			// Date-type preconditions
			foreach (array('If-Modified-Since', 'If-Unmodified-Since', 'If-Range') as $key) {
				if ($val = self::header($key) && ($stamp = @strtotime($val))) {
					$precon[$key] = $stamp;
				}
			}
			// ETag-type preconditions
			foreach (array('If-None-Match', 'If-Match', 'If-Range') as $key) {
				if (($val = self::header($key)) && ($etag = self::parse_etag_header($val))) {
					$precon[$key] = $etag;
				}
			}
			self::$preconditions = $precon;
		}
		if (is_null($header)) {
			return self::$preconditions;
		} elseif (isset(self::$preconditions[$header])) {
			return self::$preconditions[$header];
		} else {
			return NULL;
		}
	}

	/**
	 * Parses a potential ETag header value.
	 *
	 * @param string $raw the raw header value
	 * @return string
	 */
	static function parse_etag_header($raw) {
		$raw = trim($raw);
		if ($raw == '*') {
			return $raw;
		}
		if (preg_match_all('~(?P<etag>(?:W/)?"(?:\\\\"|[^"])+")(?:\s+|$)|(?P<err>.+)~', $raw, $matches, PREG_PATTERN_ORDER)) {
			$err = implode('', $matches['err']);
			if (!empty($err)) {
				error_log("Warning: bad ETag header: '$raw'");
			} else {
				return $matches['etag'];
			}
		}
		return NULL;
	}

	/**
	 * Gets the preferred content type from the client.
	 *
	 * You may provide a list of preferred content types from this end; if so,
	 * the returned value is the highest-weighted of the supplied types.
	 *
	 * NOTE: this doesn't (yet) support parameters (e.g. "text/html;level=1")
	 *
	 * @param string[] $preferred (optional) list of preferred content types
	 * @return string|false the best content type, or FALSE if there's no nice resolution
	 */
	public static function content_type($preferred=NULL) {
		$client_types = self::content_types();
		if (func_num_args() > 0) {
			// build lookup tables for what the client wants to see
			$default_weight = 0;
			$type_weights = array();
			$subtype_weights = array();
			foreach ($client_types as $qvalue => $options) {
				foreach ($options as $option) {
					$range = $option['option'];
					if ($range == '*/*') {
						$default_weight = $qvalue;
					} elseif (substr($range,-2) == '/*') {
						$type_weights[substr($range,0,-2)] = $qvalue;
					} else {
						$subtype_weights[$range] = $qvalue;
					}
				}
			}
			// Now go through the server-supplied types, mapping them to the
			// client weightings.  Only record the best type/weight.
			$best_type = FALSE;
			$best_weight = -1;
			$best_spec = -1; # specificity (0=*/*, 1=foo/*, 2=foo/bar, 3=foo/bar;abc..)
			foreach (func_get_args() as $range) {
				if (isset($subtype_weights[$range])) {
					$weight = $subtype_weights[$range];
					$spec = 2;
				} else {
					$parts = explode('/', $range);
					$type = reset($parts);
					if (isset($type_weights[$type])) {
						$weight = $type_weights[$type];
						$spec = 1;
					} else {
						$weight = $default_weight;
						$spec = 0;
					}
				}
				if (($weight > $best_weight) || ($weight == $best_weight && $spec > $best_spec)) {
					$best_type = $range;
					$best_weight = $weight;
					$best_spec = $spec;
				}
			}
			return $best_type;
		} else {
			if ($client_types) {
				$best_types = reset($client_types);
				#$first_type = reset($best_types);
				#return $first_type['option'];
				$best_type = FALSE;
				$best_spec = -1;
				foreach ($best_types as $type) {
					$range = $type['option'];
					if ($range == '*/*') {
						$spec = 0;
					} elseif (substr($range,-2) == '/*') {
						$spec = 1;
					} else {
						$spec = 2;
					}
					if ($spec > $best_spec) {
						$best_type = $range;
						$best_spec = $spec;
					}
				}
				return $best_type;
			} else {
				return FALSE;
			}
		}
	}

	/**
	 * Gets a nice prioritised list of the preferred content types.
	 *
	 * If the client didn't supply any accepted content types, returns FALSE.
	 *
	 * @see RFC2616\parse_Accept()
	 * @return string[]|false accepted content types, or FALSE
	 */
	public static function content_types() {
		if (($accept = self::header('Accept')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_ACCEPT'])) { $accept = $_SERVER['HTTP_ACCEPT']; }
		else return FALSE;

		return RFC2616\parse_Accept($accept);
	}

	/**
	 * Gets a nice prioritised list of the preferred charsets.
	 *
	 * If the client didn't supply any accepts charsets, returns FALSE.
	 *
	 * Note: RFC 2616 has explicit rules about requiring ISO-8859-1 in the
	 * charsets, but this method does not enforce the RFC.
	 *
	 * @see RFC2616\parse_Accept_Charset()
	 * @return string[]|false accepted charsets, or FALSE
	 */
	public static function charsets() {
		if (($accept = self::header('Accept-Charset')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) { $accept = $_SERVER['HTTP_ACCEPT_CHARSET']; }
		else return FALSE;

		return RFC2616\parse_Accept_Charset($accept);
	}

	/**
	 * Gets a nice prioritised list of the preferred encodings.
	 *
	 * If the client didn't supply any accepted encodings, returns FALSE.
	 *
	 * @see RFC2616\parse_Accept_Encodings()
	 * @return string[]|false accepted encodings, or FALSE
	 */
	public static function encodings() {
		if (($accept = self::header('Accept-Encoding')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) { $accept = $_SERVER['HTTP_ACCEPT_ENCODING']; }
		else return FALSE;

		return RFC2616\parse_Accept_Encoding($accept);
	}

	/**
	 * Gets a nice prioritised list of the preferred encodings.
	 *
	 * If the client didn't supply any accepted encodings, returns FALSE.
	 *
	 * @see RFC2616\parse_TE()
	 * @return string[]|false accepted encodings, or FALSE
	 */
	public static function transfer_encodings() {
		if (($accept = self::header('TE')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_TE'])) { $accept = $_SERVER['HTTP_TE']; }
		else return FALSE;

		return RFC2616\parse_TE($accept);
	}

	/**
	 * Gets a nice prioritised list of the preferred languages.
	 *
	 * If the client didn't supply any accepted languages, returns FALSE.
	 *
	 * @see RFC2616\parse_Accept_Language()
	 * @return string[]|false accepted languages, or FALSE
	 */
	public static function languages() {
		if (($accept = self::header('Accept-Language')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE']; }
		else return FALSE;

		return RFC2616\parse_Accept_Language($accept);
	}

	/**
	 * Gets a map of "Prefer" preferences.
	 *
	 * @see <http://tools.ietf.org/html/draft-snell-http-prefer-18>
	 *
	 * If the client didn't supply any preferences, returns FALSE.
	 *
	 * Example:
	 *
	 *        Prefer: a,b=1,c;x=2,d=3;y=4
	 *     =>
	 *        array(
	 *            'a' => array('a'=>TRUE),
	 *            'b' => array('b'=>1),
	 *            'c' => array('c'=>TRUE, 'x'=>2),
	 *            'd' => array('d'=>3, 'y'=>4),
	 *        )
	 *
	 * @see ID_snell_http_prefer\parse_Prefer()
	 * @return array of {preference=>value+parameters} pairs, or FALSE
	 */
	public static function preferences() {
		if (($prefer = self::header('Prefer')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_PREFER'])) { $prefer = $_SERVER['HTTP_PREFER']; }
		else return FALSE;

		return ID_snell_http_prefer\parse_Prefer($prefer);
	}

	/**
	 * Gets a list of "Range" byte-ranges.
	 *
	 * If the client didn't request any ranges, returns FALSE.
	 *
	 * Example:
	 *
	 *        Range: 0-0,5-10,20-
	 *     =>
	 *        array(
	 *            'ranges' => array(
	 *                array(0, 0),
	 *                array(5, 10),
	 *            ),
	 *            'open-range' => 20,
	 *            'suffix' => -1,
	 *        )
	 *
	 * Example:
	 *
	 *        Range: 0-10,5-15,-20
	 *     =>
	 *        array(
	 *            'ranges' => array(
	 *                array(0, 15),
	 *            ),
	 *            'open-range' => -1,
	 *            'suffix' => 20,
	 *        )
	 *
	 * @see RFC2616\parse_Range()
	 * @return string[]|false
	 */
	public static function ranges() {
		if (($range = self::header('X-Range')) !== NULL) {}
		elseif (isset($_SERVER['HTTP_X_RANGE'])) { $range = $_SERVER['HTTP_X_RANGE']; }
		else return FALSE;

		return RFC2616\parse_Range($range);
	}

	/** @internal */
	public static function _set_params($params) {
		self::$params = $params;
	}

	/**
	 * Gets the value of the named parameter.
	 *
	 * First checks parameters in the request's URI; then if not found, checks
	 * for an appropriate request entity.
	 *
	 * If still not found, returns $default (NULL).
	 *
	 * @param string $name the parameter to retrieve
	 * @param mixed $default value to return if no such parameter is defined
	 */
	public static function parameter($name, $default=NULL) {
		if (isset(self::$params[$name])) return self::$params[$name];
		if (isset(self::$get[$name])) return self::$get[$name];
		if (isset(self::$post[$name])) return self::$post[$name];
		return $default;
	}

	/**
	 * Gets the value of the named uri parameter (after URI Pattern matching
	 * has taken place).
	 * Returns $default (NULL) if not found.
	 *
	 * @param string $name the parameter to retrieve
	 * @param mixed $default value to return if no such parameter is defined
	 */
	public static function uri_parameter($name, $default=NULL) {
		if (isset(self::$params[$name])) return self::$params[$name];
		return $default;
	}

	/** alias for #uri_parameter() */
	public static function param($name, $default=NULL) {
		if (isset(self::$params[$name])) return self::$params[$name];
		return $default;
	}

	/**
	 * Gets the value of the named query parameter.
	 * Returns $default (NULL) if not found.
	 *
	 * @param string $name the parameter to retrieve
	 * @param mixed $default value to return if no such parameter is defined
	 */
	public static function query_parameter($name, $default=NULL) {
		if (isset(self::$get[$name])) return self::$get[$name];
		return $default;
	}

	/** alias for #query_parameter() */
	public static function query_param($name, $default=NULL) {
		if (isset(self::$get[$name])) return self::$get[$name];
		return $default;
	}

	/**
	 * Gets the names and values of all parameters that match the given
	 * regular expression pattern.
	 *
	 * @param regexp $pattern filtering pattern
	 * @param integer $group the matched regex group to return as the name (default = whole string)
	 */
	public static function query_params_like($pattern, $group=0) {
		$results = array();
		foreach (self::$get as $key=>$value) {
			if (preg_match($pattern, $key, $match)) {
				$newkey = $match[$group];
				$results[$newkey] = $value;
			}
		}
		return $results;
	}

	/**
	 * Gets the value of the named parameter from the request entity, if any.
	 * Returns $default (NULL) if not found.
	 *
	 * @param string $name the parameter to retrieve
	 * @param mixed $default value to return if no such parameter is defined
	 */
	public static function entity_parameter($name, $default=NULL) {
		if (isset(self::$post[$name])) return self::$post[$name];
		return $default;
	}

	/** alias for #entity_parameter() */
	public static function entity_param($name, $default=NULL) {
		if (isset(self::$post[$name])) return self::$post[$name];
		return $default;
	}

	/**
	 * Gets the names and values of all parameters that match the given
	 * regular expression pattern.
	 *
	 * @param regexp $pattern filtering pattern
	 * @param integer $group the matched regex group to return as the name (default = whole string)
	 */
	public static function entity_params_like($pattern, $group=0) {
		$results = array();
		foreach (self::$post as $key=>$value) {
			if (preg_match($pattern, $key, $match)) {
				$newkey = $match[$group];
				$results[$newkey] = $value;
			}
		}
		return $results;
	}

	/**
	 * Gets the value of the named request header, if any.
	 * Returns $default (NULL) if not found.
	 *
	 * @param string $name the header field value to retrieve
	 * @param mixed $default value to return if no such parameter is defined
	 * @return string|string[]
	 */
	public static function header($name, $default=NULL) {
		$name = strtolower($name);
		if (isset(self::$headers_index[$name])) return self::$headers[ self::$headers_index[$name] ];
		return $default;
	}

	/**
	 * Gets the raw value of the request entity, if any.
	 * Always returns a String.
	 *
	 * FIXME: this won't work with multipart/form-data entities
	 * because PHP.  >_<
	 *
	 * FIXME: clarify if this is the entity-body or message-body
	 * (do clients ever use a Transfer-Encoding like gzip?)
	 *
	 * @return string
	 */
	public static function entity_body() {
		if (is_null(self::$entity_body)) {
			self::$entity_body = file_get_contents('php://input');
			if ($md5 = self::header('Content-MD5')) {
				$cksum = base64_encode( md5(self::$entity_body, TRUE) );
				if ($md5 != $cksum) {
					throw new BadRequestException("Supplied Content-MD5 did not match entity body");
				}
			}
		}
		return self::$entity_body;
	}

	// FIXME: use Content-Length as trigger for there being an entity..?
	/**
	 * Gets the request entity, if any.
	 *
	 * @see entity_body()
	 * @return string|array
	 */
	public static function entity() {
		$type = self::header('Content-Type');
		if (strtoupper(self::$method) == 'POST') {
			if (!$type) {
				return self::$post;
			} elseif (preg_match('@^application/x-www-form-urlencoded(\s*;|$)@i', $type)) {
				return self::$post;
			} elseif (preg_match('@^multipart/form-data(\s*;|$)@i', $type)) {
				return array('strings'=>self::$post, 'files'=>self::$files);
			} elseif (preg_match('@^multipart/@i', $type)) {
				return self::parse_multipart_entity($type);
			} else {
				return self::entity_body();
			}
		} else {
			if (preg_match('@^multipart/@i', $type)) {
				return self::parse_multipart_entity($type);
			} else {
				return self::entity_body();
			}
		}
	}

	/**
	 * Moves an uploded file.
	 *
	 * If no file is known by that identifier, returns NULL.
	 * If something goes wrong, returns FALSE as per `move_uploaded_file()`.
	 *
	 * @param string $ident local identifier of uploaded file
	 * @param string $dest destination filename
	 * @return bool
	 */
	public static function move_file($ident, $dest) {
		if (!isset(self::$files[$ident])) {
			return NULL;
		}
		return move_uploaded_file(self::$files[$ident]['tmp_name'], $dest);
	}

	/**
	 * Opens an uploaded file for reading.
	 *
	 * Returns a file handle, as per `fopen()`.
	 * If no file is known by that identifier, returns NULL.
	 *
	 * @param string $ident local identifier of uploaded file
	 * @return resource
	 */
	public static function open_file($ident) {
		if (!isset(self::$files[$ident])) {
			return NULL;
		}
		return fopen(self::$files[$ident]['tmp_name'], 'r');
	}

	/**
	 * Read the contents of an uploaded file into a string.
	 *
	 * Returns a string, as per `file_get_contents()`.
	 * If no file is known by that identifier, returns NULL.
	 *
	 * @param string $ident local identifier of uploaded file
	 * @return string|false
	 */
	public static function get_file_contents($ident) {
		if (!isset(self::$files[$ident])) {
			return NULL;
		}
		return file_get_contents(self::$files[$ident]['tmp_name']);
	}

	protected static function parse_multipart_entity($type) {
		$bcharsnospace = "-0-9A-Z'()+_,./:=?";
		$bchars = $bcharsnospace . ' ';
		if (!preg_match('@^multipart/.*;\s*boundary=("?)(['.$bchars.']{0,69}['.$bcharsnospace.'])\\1@iU', $type, $matches)) {
			throw new BadRequestException("can't determine multipart entity boundary");
		}
		$delimiter = "\r\n--".$matches[2];
		$body = self::entity_body();
		// 1. strip the epilogue (if any)
		$tmp = explode($delimiter."--\r\n", $body, 2);
		$body = $tmp[0];
		// 2. break on delimiters
		$tmp = explode($delimiter, $body);
		// 3. dump the preamble (if any)
		$tmp = array_slice($tmp, 1);
		// NOTE: I'm not parsing the individual thingies here, they can go as-is,
		// including headers fields and whatnot
		return $tmp;
	}

	/**
	 * Gets the parts of the URI.
	 *
	 *    /interface/module/page...
	 *
	 * Missing parts are NULL.
	 */
	public static function interface_module_page() {
		if (is_null(self::$__uri_parts)) {
			$url = ltrim(self::$uri, '/');
			$parts = explode('/', $url, 3);
			while (count($parts) < 3) $parts[] = NULL;
			self::$__uri_parts = $parts;
		}
		return self::$__uri_parts;
	}
	private static $__uri_parts = NULL;

	/**
	 * Gets the page part of the URI.
	 * Returns NULL if there isn't one.
	 */
	public static function get_interface() {
		$parts = self::interface_module_page();
		return $parts[0];
	}

	/**
	 * Gets the page part of the URI.
	 * Returns NULL if there isn't one.
	 */
	public static function get_module() {
		$parts = self::interface_module_page();
		return $parts[1];
	}

	/**
	 * Gets the page part of the URI.
	 * Returns NULL if there isn't one.
	 */
	public static function get_page() {
		$parts = self::interface_module_page();
		return $parts[2];
	}
}

