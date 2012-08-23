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


class Request {
	public static $HTTP_METHODS = array(
		#'OPTIONS' => 'OPTIONS', // TO DO
		'GET'  => 'GET',
		'HEAD' => 'HEAD',
		'POST' => 'POST',
		'PUT'  => 'PUT',
		'DELETE'  => 'DELETE',
		#'TRACE'   => 'TRACE',
		#'CONNECT' => 'CONNECT',
	);

	private $protocol = NULL;
	private $method  = NULL;
	private $uri     = NULL;
	private $headers = NULL;
	private $get  = NULL;
	private $post = NULL;
	private $params = NULL;

	public function __construct() {
		if (isset($_GET['path']) && ($path = $_GET['path'])) {
			$this->uri = $path;
			unset($_GET['path']);
		} else {
			$this->uri = '/';
		}

		$this->protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
		$this->method = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
		$this->headers = getallheaders();
		$this->get  = $_GET;
		$this->post = $_POST;
	}

### DEBUG
public function dump() {
	print($this->method . ' ' . $this->uri . ' ' . $this->protocol . "\n");
	foreach ($this->headers as $k=>$v) print("$k: $v\n");
	print("\n");
	if ($this->params) {
		print("PARAMS:\n");
		foreach ($this->params as $k=>$v) print("$k: $v\n");
	}
	if ($this->get) {
		print("GET:\n");
		foreach ($this->get as $k=>$v) print("$k: $v\n");
	}
	if ($this->post) {
		print("POST:\n");
		foreach ($this->post as $k=>$v) print("$k: $v\n");
	}
}
### /DEBUG

	public function uri() { return $this->uri; }
	public function protocol() { return $this->protocol; }

	/**
	 * Gets the request's HTTP version, as "major.minor"
	 * Returns FALSE if it doesn't look like HTTP.
	 */
	public function http_version() {
		if (preg_match('@HTTP/(\d+\.\d+)@', $this->protocol, $m)) {
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
	 * @param String[] $allowed (optional) list of allowed methods
	 * @return the HTTP request method from the current request, or FALSE
	 */
	public function method($allowed=NULL) {
		if (func_num_args() > 0) {
			$allowed = func_get_args();
			$upcased = array();
			$get = false;
			$head = false;
			foreach($allowed as $meth) {
				$meth = strtoupper($meth);
				if ($this->method == $meth) return $meth; // short circuit
				if ($meth == 'GET') $get = true;
				if ($meth == 'HEAD') $head = true;
				$upcased[] = $meth;
			}
			// If we get here, the actual method wasn't in the allowed list
			if (isset(Request::$HTTP_METHODS[$method])) {
				$code = 405; // Method Not Allowed
			} else {
				$code = 501; // Not Implemented
			}
			$allow = implode(', ', $upcased);
			header("Allow: $allow", TRUE, $code);
			return FALSE;
		} else {
			return $this->method;
		}
	}

	/**
	 * Gets the preferred content type from the client.
	 *
	 * You may provide a list of preferred content types from this end; if so,
	 * the returned value is the highest-weighted of the supplied types.
	 *
	 * NOTE: this doesn't (yet) support parameters (e.g. "text/html;level=1")
	 *
	 * @param String[] $preferred (optional) list of preferred content types
	 * @return the best content type, or FALSE if there's no nice resolution
	 */
	public function content_type($preferred=NULL) {
		$client_types = $this->content_types();
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
			$best_weight = 0;
			foreach (func_get_args() as $range) {
				if (isset($subtype_weights[$range])) {
					$weight = $subtype_weights[$range];
				} else {
					$parts = explode('/', $range);
					$type = reset($parts);
					if (isset($type_weights[$type])) {
						$weight = $type_weights[$type];
					} else {
						$weight = $default_weight;
					}
				}
				if ($weight > $best_weight) {
					$best_type = $range;
					$best_weight = $weight;
				}
			}
			return $best_type;
		} else {
			if ($client_types) {
				$best_types = reset($client_types);
				$first_type = reset($best_types);
				return $first_type['option'];
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
	 * @see #parse_qvalues for more description
	 *
	 * @return accepted content types, or FALSE
	 */
	public function content_types() {
		if (isset($this->headers['Accept']) && ($accept = $this->headers['Accept'])) { }
		elseif (isset($_SERVER['HTTP_ACCEPT']) && ($accept = $_SERVER['HTTP_ACCEPT'])) { }
		else return FALSE;

		return $this->parse_qvalues($accept);
	}

	/**
	 * Gets a nice prioritised list of the preferred charsets.
	 *
	 * If the client didn't supply any accepts charsets, returns FALSE.
	 *
	 * Note: RFC 2616 has explicit rules about requiring ISO-8859-1 in the
	 * charsets, but this method does not enforce the RFC.
	 *
	 * @see #parse_qvalues for more description
	 *
	 * @return accepted charsets, or FALSE
	 */
	public function charsets() {
		if (isset($this->headers['Accept-Charset']) && ($charset = $this->headers['Accept-Charset'])) { }
		elseif (isset($_SERVER['HTTP_ACCEPT_CHARSET']) && ($charset = $_SERVER['HTTP_ACCEPT_CHARSET'])) { }
		else return FALSE;

		$results = $this->parse_qvalues($charset);

		// ensure that ISO-8859-1 is in there (default: q=1)
		$iso_8859_1 = false;
		$q1000 = false;
		foreach ($results as $qvalue => $options) {
			if ($qvalue == 1000) $q1000 = true;
			if (!$iso_8859_1) {
				foreach ($options as $option) {
					if ($option['option'] == '*' || strtoupper($option['option']) == 'ISO-8859-1')
						$iso_8859_1 = true;
				}
			}
		}
		// if it's missing, add it
		if (!$iso_8859_1) {
			if (!$q1000) {
				$results[1000] = array();
				krsort($results);
			}
			$results[1000][] = array('option'=>'ISO-8859-1', 'raw'=>'ISO-8859-1');
		}

		return $results;
	}

	/**
	 * Gets a nice prioritised list of the preferred encodings.
	 *
	 * If the client didn't supply any accepted encodings, returns FALSE.
	 *
	 * @see #parse_qvalues for more description
	 *
	 * @return accepted encodings, or FALSE
	 */
	public function encodings() {
		if (isset($this->headers['Accept-Encoding']) && ($accept = $this->headers['Accept-Encoding'])) { }
		elseif (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && ($accept = $_SERVER['HTTP_ACCEPT_ENCODING'])) { }
		else return FALSE;

		return $this->parse_qvalues($accept);
	}

	/**
	 * Gets a nice prioritised list of options from an "Accept"-type HTTP
	 * request header.
	 *
	 * Note: RFC 2616 limits the precision of the qvalue to 3 decimal places,
	 * as such the priorities returned by this method will actually be the
	 * specified value * 1000.
	 *
	 * This method parses qvalues according to RFC 2616, so invalid values
	 * will be ignored (defaulting to 1).
	 *
	 * The result array is of the form:
	 *
	 *   array(
	 *      900 => array(
	 *        array('option'=>"a/b", 'raw'=>"a/b;q=0.9,x=yz"),
	 *        ...
	 *      ),
	 *      ...
	 *   )
	 *
	 * Within a single qvalue, options will be in the order they were
	 * specified in the header.
	 *
	 * @param String $raw_header the header from the client, e.g. $_SERVER['HTTP_ACCEPT']
	 * @return accepted content types, or FALSE
	 */
	public function parse_qvalues($raw_header) {
		$result = array();
		$options = preg_split('/\s*,\s*/', $raw_header);
		foreach ($options as $raw_option) {
			$parts = preg_split('/\s*;\s*/', $raw_option);
			$option = array_shift($parts);
			$qvalue = 1000;
			foreach ($parts as $part) {
				if (preg_match('/^q=(0(\.\d{0,3})?|1(\.0{0,3})?)$/', $part, $m)) {
					$qvalue = floor(floatval($m[1]) * 1000);
					break;
				}
			}
			if (!isset($result[$qvalue])) $result[$qvalue] = array();
			$result[$qvalue][] = array('option'=>$option, 'raw'=>$raw_option);
		}
		krsort($result);
		return $result;
	}


	public function _set_params($params) {
		$this->params = $params;
	}

	/**
	 * Gets the value of the named parameter.
	 *
	 * First checks parameters in the request's URI; then if not found, checks
	 * for an appropriate request entity.
	 *
	 * If still not found, returns NULL.
	 */
	public function parameter($name) {
		if (isset($this->params[$name])) return $this->params[$name];
		if (isset($this->get[$name])) return $this->get[$name];
		if (isset($this->post[$name])) return $this->post[$name];
		return NULL;
	}

	/**
	 * Gets the value of the named uri parameter (after URI Pattern matching
	 * has taken place).
	 * Returns NULL if not found.
	 */
	public function uri_parameter($name) {
		if (isset($this->params[$name])) return $this->params[$name];
		return NULL;
	}

	/** alias for #uri_parameter() */
	public function param($name) {
		if (isset($this->params[$name])) return $this->params[$name];
		return NULL;
	}

	/**
	 * Gets the value of the named query parameter.
	 * Returns NULL if not found.
	 */
	public function query_parameter($name) {
		if (isset($this->get[$name])) return $this->get[$name];
		return NULL;
	}

	/**
	 * Gets the value of the named parameter from the request entity, if any.
	 * Returns NULL if not found.
	 */
	public function entity_parameter($name) {
		if (isset($this->post[$name])) return $this->post[$name];
		return NULL;
	}

	/**
	 * Gets the value of the named request header, if any.
	 * Returns NULL if not found.
	 */
	public function header($name) {
		if (isset($this->headers[$name])) return $this->headers[$name];
		return NULL;
	}
}

