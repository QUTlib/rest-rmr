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


class Response {
	private $version = '1.1';
	private $status = 200;
	private $header = array();
	private $body   = '';

	// special header info, stored separately
	private $last_modified = NULL;

	public $allow_compression = true;
	public $allow_not_modified = true;
	public $allow_auto_etag = true;

	private $committed = false;
	private $recording = false;

	public function __construct($http_version='1.1', $status=200) {
		if (func_num_args() > 0 && $http_version) $this->version = $http_version;
		if (func_num_args() > 1) $this->status($status); // including validation
		// Set the default headers
		if (($ua=Request::header('User-Agent')) && preg_match('/MSIE/',$ua)) { 
			$this->add_header('X-UA-Compatible', 'IE=edge');
			$this->add_header('X-Content-Type-Options', 'nosniff');
		}
		foreach (headers_list() as $header) {
			$parts = explode(': ', $header, 2);
			if (count($parts) == 2)
				$this->add_header($parts[0], $parts[1]);
		}
	}

	/**
	 * 0 args: gets the response's HTTP version as "major.minor"
	 * 1 args: sets the response's HTTP version, and returns $this
	 */
	public function http_version($value=NULL) {
		if (func_num_args() > 0) {
			if (preg_match('/^\d+\.\d+$/', $value)) {
				$this->version = $value;
				return $this;
			} else {
				throw new Exception("invalid HTTP version HTTP/${value}");
			}
		} else {
			return $this->version;
		}
	}

	/**
	 * 1 args: gets the value of header $name
	 * 2 args: sets the value of header $name to $value, and returns $this
	 */
	public function header($name, $value=NULL) {
		if (func_num_args() > 1) {
			$this->header[$name] = $value;
			return $this;
		} elseif (isset($this->header[$name])) {
			return $this->header[$name];
		} else {
			return null;
		}
	}

	/**
	 * Appends '$value' to the response header '$name', as an array.
	 * If the header already contains the given value, nothing changes.
	 * @chainable
	 */
	public function add_header($name, $value) {
		if (!isset($this->header[$name])) {
			$this->header[$name] = $value;
		} else {
			$hval = $this->header[$name];
			if (is_array($hval)) {
				if (array_search($value, $hval) === false) {
					$this->header[$name][] = $value;
				// else: do nothing, it's already set
				}
			} elseif ($hval != $value) {
				$this->header[$name] = array($hval, $value);
			//else: do nothing, it's already set
			}
		}
		return $this;
	}

	/**
	 * Appends '$value' to the response header '$name' linearly (comma-delimited).
	 * If $uniq is TRUE and the header already contains the given value, nothing changes.
	 * @chainable
	 */
	public function append_header($name, $value, $uniq=TRUE) {
		if (!isset($this->header[$name])) {
			$this->header[$name] = $value;
		} else {
			$hval = $this->header[$name];
			if (is_array($hval)) {
				throw new Exception("cannot append string-header to array-header");
			} elseif (!($uniq && preg_match('/(^|,)\s*'.preg_quote($value,'/').'\s*(,|$)/i', $hval))) {
				$this->header[$name] .= ', '.$value;
			//else: do nothing, it's already set
			}
		}
		return $this;
	}

	/**
	 * Removes a response header.
	 * If $value is given, only removes that value from the header.
	 * Returns the removed value (or NULL of nothing was removed).
	 * Note: this may be an array.
	 */
	public function remove_header($name, $value=null) {
		if (!isset($this->header[$name]))
			return null;
		if (func_num_args() == 1) {
			$val = $this->header[$name];
			unset($this->header[$name]);
			return $val;
		} elseif (is_array($this->header[$name])) {
			foreach ($this->header[$name] as $hidx => $hval) {
				if ($hval == $value) {
					unset($this->header[$name][$hidx]);
					return $value;
				}
			}
			return null;
		} elseif ($this->header[$name] == $value) {
			unset($this->header[$name]);
			return $value;
		} else {
			return null;
		}
	}

	/**
	 * Gets the current value of a response header, as an array (may be empty).
	 */
	public function header_array($name) {
		if (!isset($this->header[$name]))
			return array();
		$hval = $this->header[$name];
		if (is_array($hval)) {
			return $hval;
		} else {
			return array($hval);
		}
	}

	/**
	 * Gets all the currently-defined headers from this response.
	 */
	public function headers() {
		return $this->header;
	}

	/**
	 * 0 args: gets the status code of this response
	 * 1 args: sets it directly, and returns $this
	 */
	public function status($value=NULL) {
		if (func_num_args() > 0) {
			if (is_int($value) || (is_float($value) && intval($value) == $value) || preg_match('/^\d+$/',$value)) {
				$this->status = intval($value);
			} else {
				throw new Exception("non-integer status code '$value'");
			}
			return $this;
		} else {
			return $this->status;
		}
	}

	/**
	 * 0 args: gets the content type of the response
	 * 1 args: sets it, and returns $this
	 */
	public function content_type($value=NULL) {
		if (func_num_args() > 0) {
			$this->header['Content-Type'] = $value;
			return $this;
		} else {
			return $this->header('Content-Type');
		}
	}

	/**
	 * 0 args: gets the content language of the response
	 * 1 args: sets it, and returns $this
	 */
	public function content_language($value=NULL) {
		if (func_num_args() > 0) {
			$this->header['Content-Language'] = $value;
			return $this;
		} else {
			return $this->header('Content-Language');
		}
	}

	/**
	 * Without parameters, this method returns the currently-assigned
	 * last-modified-time of the response object.
	 *
	 * If given, the $value (an integer timestamp, or a string that can
	 * be parsed by strtotime()) will be assigned to the property, and
	 * the function will return this response object.
	 *
	 * @see #is_modified()
	 */
	public function last_modified($value=NULL) {
		if (func_num_args() > 0) {
			if (is_int($value)) {
				$this->last_modified = $value;
				$value = httpdate($value);
			} else {
				$this->last_modified = strtotime($value);
			}
			$this->header['Last-Modified'] = $value;
			return $this;
		} else {
			return $this->last_modified;
		}
	}

	/**
	 * Without parameters, this method returns the currently-assigned
	 * etag of the response object.
	 *
	 * If given, the $value (an entity-tag as defined in RFC2616, S14.19)
	 * will be assigned to the property, and the function will return
	 * this response object.
	 *
	 * @see #is_modified()
	 */
	public function etag($value=NULL) {
		if (func_num_args() > 0) {
			if (preg_match('~^(W/)?"(\\\\"|[^"])*"$~', $value)) {
				$this->header['ETag'] = $value;
			} else {
				$this->header['ETag'] = '"'.str_replace('"','\\"',$value).'"';
			}
			return $this;
		} elseif (isset($this->header['ETag'])) {
			return $this->header['ETag'];
		} else {
			return NULL;
		}
	}

	/**
	 * Generates a strong ETag based on $from (or the current response's body.)
	 * This calls #etag()
	 */
	public function generate_strong_etag($from=NULL) {
		if (empty($from)) $from = $this->body;
		if (!is_string($from)) $from = serialize($from);
		return $this->etag(sprintf('"%08x:%s"', crc32($from), md5($from)));
	}

	/**
	 * Generates a weak ETag based on $from (or the current response's body.)
	 * This calls #etag()
	 */
	public function generate_weak_etag($from=NULL) {
		if (empty($from)) $from = $this->body;
		if (!is_string($from)) $from = serialize($from);
		return $this->etag(sprintf('W/"%08x:%s"', crc32($from), md5($from)));
	}

	/**
	 * Tests the response to see if it has been modified.
	 *
	 * A response is considered unmodified if it has a status of '200 OK', and
	 * - has a #last_modified time, and
	 * - the current Request included a valid If-Modified-Since header that
	 *   is less than the response's #last_modified time,
	 * OR
	 * - has an #etag , and
	 * - the current Request used the GET or HEAD methods, and
	 * - the current Request included a valid If-None-Match header that does
	 *   not include the response's #etag
	 * OR
	 * - does not have an #etag , and
	 * - the current Request used the GET or HEAD methods, and
	 * - the current Request included an If-None-Match header "*"
	 *
	 * @return boolean
	 */
	public function is_modified() {
		if ($this->allow_not_modified && $this->status == 200) {
			if (!isset($this->header['ETag']) && $this->allow_auto_etag) {
				$this->generate_strong_etag();
			}
			if (($inm = Request::preconditions('If-None-Match')) && Request::is_get_or_head()) {
				if ($inm == '*') {
					if (isset($this->header['ETag'])) return FALSE; #=> 304
				} else {
					if (isset($this->header['ETag']) && in_array($this->header['ETag'], $inm)) {
						return FALSE; #=> 304
					}
				}
			}
			if (isset($this->last_modified) && is_int($ims = Request::preconditions('If-Modified-Since'))) {
				if ($ims >= $this->last_modified) {
					return FALSE; #=> 304
				}
			}
		}
		return TRUE;
	}

	/**
	 * Tests the response to see if its preconditions have passed.
	 *
	 * A response is considered to have passed if it has a status not '200 OK', or
	 * - has a #last_modified time, and
	 * - the current Request included a valid If-Unmodified-Since header that
	 *   is less than or equal to the response's #last_modified time.
	 *
	 * @return the response object if everything passed, otherwise FALSE
	 */
	public function preconditions_passed() {
		if ($this->status == 200) {
			if ($ius = Request::header('If-Unmodified-Since')) {
			$stamp = @strtotime($ius); // todo: parse this better
				if (!empty($stamp)) {
					if (!isset($this->last_modified) || $stamp > $this->last_modified) {
						return FALSE;
					}
				}
			}
		}
		return TRUE;
	}

	/**
	 * Sets the appropriate headers to indicate that this response is cacheable.
	 * NOTE: as yet this DOES NOT perform any server-side caching of the response.
	 *
	 * @param $duration either a number of seconds, or a string that #strtotime can handle.
	 * @chainable
	 */
	public function cache($duration='+1 year') {
		if (is_int($duration)) {
			$expires = time() + $duration;
		} else {
			$expires = strtotime($duration);
		}
		unset($this->header['Pragma']);
		$this->header['Cache-Control'] = 'public';
		$this->header['Expires'] = httpdate($expires);
		return $this;
	}

	/**
	 * Sets the appropriate headers to indicate that this response is NOT cacheable.
	 * @chainable
	 */
	public function nocache() {
		$this->header['Cache-Control'] = 'no-cache';
		$this->header['Expires'] = 'Thu, 23 Oct 1980 22:15:00 GMT';
		$this->header['Pragma'] = 'no-cache';
		return $this;
	}

	/**
	 * Sets the status of this response to 'OK'
	 * @chainable
	 */
	public function statusOk() {
		$this->status = 200;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Created', and informs the client
	 * of a URL it can GET to see the created thingy.
	 * @chainable
	 */
	public function statusCreated($url) {
		$this->status = 201;
		$this->header('Location', $url);
		return $this;
	}
	/**
	 * Sets the status of this response to 'Accepted', implying that the
	 * request has been queued up, but we haven't done anything with it yet.
	 *
	 * The response body should talk about the state of the request, etc.
	 *
	 * @chainable
	 */
	public function statusAccepted() {
		$this->status = 202;
		return $this;
	}
	/**
	 * Sets the status of this response to 'No Content'.
	 *
	 * This will block the response from having any content.
	 *
	 * @chainable
	 */
	public function statusNoContent() {
		$this->status = 204;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Reset Content', implying that
	 * the browser should reset the form it submitted to get here (or whatever).
	 *
	 * This will block the response from having any content.
	 *
	 * @chainable
	 */
	public function statusResetContent() {
		$this->status = 205;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Moved Permanently'.
	 * (i.e. redirect)
	 * @chainable
	 */
	public function statusMovedPermanently($url) {
		$this->status = 301;
		$this->header('Location', $url);
		return $this;
	}
	/**
	 * Sets the status of this response to 'Found', which is the
	 * poorly-implemented temporary-redirect.
	 * @chainable
	 */
	public function statusFound($url) {
		$this->status = 302;
		$this->header('Location', $url);
		return $this;
	}
	/**
	 * Sets the status of this response to 'See Other', which tells
	 * the browser to GET some other URL to see the result of this action.
	 * @chainable
	 */
	public function statusSeeOther($url) {
		$this->status = 303;
		$this->header('Location', $url);
		return $this;
	}
	/**
	 * Sets the status of this response to 'Temporary Redirect', telling
	 * the browser that the requested resource is currently found elsewhere.
	 *
	 * This SHOULD break non-idempotent requests (POST, PUT, DELETE, etc.)
	 *
	 * @chainable
	 */
	public function statusTemporaryRedirect($url) {
		$this->status = 307;
		$this->header('Location', $url);
		return $this;
	}
	/**
	 * Sets the status of this response to 'Unauthorized', telling the
	 * browser that it needs to supply some sort of authentication
	 * credentials.
	 *
	 * Common auth_methods are Basic and Digest
	 * e.g. 'Basic realm="Secret Pages"'
	 *
	 * @chainable
	 */
	public function statusUnauthorized($auth_method='Basic realm="Authenticated Area"') {
		$this->status = 401;
		$this->add_header('WWW-Authenticate', $auth_method);
		return $this;
	}
	/**
	 * Alias for #statusUnauthorized()
	 */
	public function statusUnauthorised() {
		return call_user_func_array(array($this, 'statusUnauthorized'), func_get_args());
	}
	/**
	 * Sets the status of this response to 'Forbidden'.
	 * @chainable
	 */
	public function statusForbidden() {
		$this->status = 403;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Not Found'.
	 * @chainable
	 */
	public function statusNotFound() {
		$this->status = 404;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Not Acceptable' (i.e. we can't
	 * match the 'Accept:' header they sent)
	 * @chainable
	 */
	public function statusNotAcceptable() {
		$this->status = 406;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Unsupported Media Type'
	 * (i.e. the entity in the request is unreadable)
	 * @chainable
	 */
	public function statusUnsupportedMediaType() {
		$this->status = 415;
		return $this;
	}
	/**
	 * Sets the status of this response to 'Internal Server Error'
	 * (i.e. we dropped the ball)
	 * @chainable
	 */
	public function statusInternalServerError() {
		$this->status = 500;
		return $this;
	}
	/**
	 * Alias for #statusInternalServerError()
	 */
	public function statusError() {
		return call_user_func_array(array($this, 'statusInternalServerError'), func_get_args());
	}

	/**
	 * Tells the browser/client to go away.
	 * Can optionally tell them to never come back.
	 * @chainable
	 */
	public function redirect($url, $permanent=false) {
		if ($permanent)
			$this->statusMovedPermanently($url);
		else
			$this->statusTemporaryRedirect($url);
		return $this;
	}

	/**
	 * Gets a human-readable form of the current status code of this response.
	 */
	public function status_message() {
		return Response::statusName($this->status, true);
	}

	/**
	 * Appends $string to the response body.
	 * @chainable
	 */
	public function append($string) {
		$this->body .= $string;
		return $this;
	}

	/**
	 * Appends $string to the response body.
	 * @chainable
	 */
	public function append_line($string) {
		$this->body .= $string . "\n";
		return $this;
	}

	/**
	 * Gets/sets the current response body.
	 */
	public function body($value=NULL) {
		if (func_num_args() > 0) {
			$this->body = $value;
			return $this;
		} else {
			return $this->body;
		}
	}

	/**
	 * Start buffering output.
	 */
	public function start_recording() {
		if ($this->recording) {
			#throw new Exception("already recording");
		} else {
			$this->recording = true;
			ob_start();
		}
	}

	/**
	 * Stop buffering output and append it to the body.
	 */
	public function stop_recording() {
		if (!$this->recording) {
			#throw new Exception("not recording");
		} else {
			$output = ob_get_clean();
			$this->recording = false;
			$this->append($output);
		}
	}

	/**
	 * Stop buffering output, and drop anything that was recorded.
	 */
	public function abort_recording() {
		if (!$this->recording) {
			#throw new Exception("not recording");
		} else {
			ob_end_clean();
			$this->recording = false;
		}
	}

	/**
	 * Discard the buffer and/or body.
	 */
	public function discard($buffer=true, $body=false) {
		if ($buffer && $this->recording) ob_clean();
		if ($body) $this->body = '';
	}

	/**
	 * Get the number of bytes in the current body.
	 */
	public function length() {
		#return mb_strlen($this->body, 'latin1');
		return strlen($this->body);
	}

	/**
	 * Executes the response, sends it to the browser, etc.
	 */
	public function commit() {
		if ($this->committed) {
			throw new Exception("already committed");
		}
		$this->committed = TRUE;

		// get any response body that was printed, rather than appended
		if ($this->recording) {
			$this->stop_recording();
		}

		// mandatory header #1
		if (!$this->header('Date')) {
			$this->header('Date', httpdate());
		}

		// if the browser has a cached copy, skip some network traffic
		// (only do it for '200 OK' responses)
		if (! $this->is_modified()) {
			$this->status = 304;
			$this->body = '';
		}

		// if the browser wants encoded (read: compressed) data, we should
		// try to accommodate it.
		if ($this->allow_compression && $this->length() && !$this->header('Content-Encoding') && ($accepted_encodings = Request::encodings())) {
			$this->attempt_compression($accepted_encodings);
		}

		// mandatory header #2
		// note: _after_ compression stuff, because compression can change the length
		if (! $this->header('Content-Length')) {
			$this->header('Content-Length', $this->length());
		}

		// optional, but cool, header
		// ditto compression
		if (! $this->header('Content-MD5') && $this->length()) {
			$this->header('Content-MD5', base64_encode( md5($this->body, TRUE) ));
		}

		// secret magic
		$this->add_header('X-Powered-By', strip_tags(Application::TITLE.'/'.Application::VERSION));

		// ------- NO OUTPUT ABOVE THIS LINE ----------------------------

		// HTTP Status Line
		$vrsn = $this->version;
		$code = $this->status;
		$msg  = $this->status_message();
		header("HTTP/$vrsn $code $msg", TRUE, $code);

		// Registered headers
		foreach ($this->headers() as $k=>$a) {
			$replace = TRUE;
			if (is_array($a)) {
				foreach ($a as $v) {
					header("$k: $v", $replace);
					$replace = FALSE;
				}
			} else {
				header("$k: $a", TRUE);
			}
		}

		// Send the entity, if there is one
		if (Request::method() != 'HEAD')
			print($this->body);
	}

	/**
	 * Manually applies compression, if possible/requested.
	 */
	protected function attempt_compression($methods) {
		// disable the PHP magic; apparently it doesn't play
		// nice with our Content-Length header anyway
		if (ini_get('zlib.output_compression'))
			ini_set('zlib.output_compression', 'Off');

		$data = $this->body;
		$size = $this->length();
		foreach ($methods as $qvalue => $meths) {
			$identity = FALSE;
			$bestmethod = FALSE;
			$bestdata = $data;
			$bestsize = $size;
			foreach ($meths as $method) {
				switch ($method) {
				case 'x-gzip':
				case 'gzip':
					$zip = gzencode($this->body, 9);
					break;
				case 'deflate':
					// *** NOTE ***
					//
					// RFC 2616 states clearly that “Transfer-Encoding: deflate” means
					// that the entity has been compressed according to the zlib
					// format specified by RFC 1950. The zlib format is just a very
					// thin wrapper around the deflate format specified by RFC 1951.
					// The problem is that IE interprets “Transfer-Encoding: deflate”
					// as meaning the raw RFC 1951 format rather than the wrapped
					// RFC 1950 format stipulated by the standard.
					//
					// So in other words, we need to use gzdeflate (no-headers) instead
					// of gzcompress (headers)
					#$zip = gzcompress($this->body, 9);
					$zip = gzdeflate($this->body, 9);
					break;
				case 'bzip2':
					$zip = bzcompress($this->body, 9);
					break;
				case 'identity':
					$identity = TRUE;
					#fallthrough:
				default:
					#continue 2;
					continue;
				}
				$zipsize = strlen($zip);
				if ($zipsize < $bestsize) {
					$bestmethod = $method;
					$bestdata = $zip;
					$bestsize = $zipsize;
				}
			}
			if ($bestmethod) {
				// apply the most-compact encoding at this qvalue-level
				$this->_apply_compression($bestdata, $bestmethod);
				return;
			} elseif ($identity) {
				// if none of the other encodings at this qvalue-level was
				// any good and the client said they'd like 'identity',
				// we should just send the data unencoded.
				// Note: the 'identity' content-coding SHOULD NOT be used
				// in the Content-Encoding header (RFC 2616, 3.5)
				return;
			}
		}
	}
	protected function _apply_compression($body, $method) {
#		// we can still bail out here if there's not enough of an
#		// improvement (currently 'enough' means 'any')
#		if (strlen($body) < $this->length()) {
			// update the response body
			$this->body( $body );
			// update appropriate 'simple' headers (encoding, length)
			$this->header('Content-Encoding', $method);
			$this->header('Content-Length', $this->length());
			$this->header('Content-MD5', base64_encode( pack('H*',md5($body)) ));
			// scrap the ETag header, since the old one (if any) no longer applies
			unset($this->header['ETag']);
			// if there's a Vary: header, add 'Accept-Encoding' as an important thingy
			$this->append_header('Vary', 'Accept-Encoding');
#			return TRUE;
#		}
#		return FALSE;
	}

	public static function statusName($code, $allow_unknown=false) {
		switch ($code) {
		// Informational
		case 100: return "Continue";
		case 101: return "Switching Protocols";
		// Success
		case 200: return "OK";
		case 201: return "Created";
		case 202: return "Accepted";
		case 203: return "Non-Authoritative Information";
		case 204: return "No Content";
		case 205: return "Reset Content";
		case 206: return "Partial Content";
		// Redirection
		case 300: return "Multiple Choices";
		case 301: return "Moved Permanently";
		case 302: return "Moved Temporarily";
		case 303: return "See Other";
		case 304: return "Not Modified";
		case 305: return "Use Proxy";
		case 307: return "Temporary Redirect";
		// Client Error
		case 400: return "Bad Request";
		case 401: return "Unauthorised";
		case 402: return "Payment Required";
		case 403: return "Forbidden";
		case 404: return "Not Found";
		case 405: return "Method Not Allowed";
		case 406: return "Not Acceptable";
		case 407: return "Proxy Authentication Required";
		case 408: return "Request Time-out";
		case 409: return "Conflict";
		case 410: return "Gone";
		case 411: return "Length Required";
		case 412: return "Precondition Failed";
		case 413: return "Request Entity Too Large";
		case 414: return "Request-URI Too Large";
		case 415: return "Unsupported Media Type";
		case 416: return "Requested range not satisfiable";
		case 417: return "Expectation Failed";
		case 418: return "I'm a teapot";
		// Server Error
		case 500: return "Internal Server Error";
		case 501: return "Not Implemented";
		case 502: return "Bad Gateway";
		case 503: return "Service Unavailable";
		case 504: return "Gateway Time-out";
		case 505: return "HTTP Version not supported";
		default:
			if ($allow_unknown) {
				switch (intval($code / 100)) {
				case 1: return "Unknown Information $code";
				case 2: return "Unknown Success $code";
				case 3: return "Unknown Redirection $code";
				case 4: return "Unknown Client Error $code";
				case 5: return "Unknown Server Error $code";
				default: return "Unknown Status $code";
				}
			} else {
				throw new Exception("invalid status code ${code}");
			}
		}
	}

	public static function statusMessage($code, $allow_unknown=false) {
		switch ($code) {
		// Informational - contain no body by definition
		case 100: return "";
		case 101: return "";
		// Success - these are all silent
		case 200: return ""; # to be populated later
		case 201: return "";
		case 202: return "";
		case 203: return "";
		case 204: return "";
		case 205: return "";
		case 206: return "";
		// Redirection
		case 300: return ""; # should be populated with an appropriate list of alternatives
		case 301: return ""; # should be populated with an appropriate link to the new location
		case 302: return ""; # should be populated with an appropriate link to the new location
		case 303: return ""; # should be populated with an appropriate link to the new location
		case 304: return ""; # The 304 response MUST NOT contain a message-body.
		case 305: return ""; # probably should have a description of the proxy location
		case 307: return ""; # should be populated with an appropriate link to the new location
		// Client Error
		case 400: return "The request could not be understood by the server.";
		case 401: return "The request requires authentication.";
		case 402: return ""; # reserved for future use
		case 403: return "The server understood the request but is refusing to fulfill it. Authorisation will not help.";
		case 404: return "The requested resource could not be found at this location.";
		case 405: return "The method specified in the request is not allowed for this resource.";
		case 406: return "The requested resource cannot be represented acceptably according to your request parameters.";
		case 407: return "The request requires proxy authentication.";
		case 408: return "The client did not produce a request within the time that the server was prepared to wait.";
		case 409: return "The request could not be completed due to a conflict with the current state of the resource.";
		case 410: return "The requested resource is no longer available at this location and no forwarding address is known.";
		case 411: return "The server refuses to accept the request without a defined Content-Length.";
		case 412: return "A precondition in the request evaluated to false when it was tested on the server.";
		case 413: return "The provided request entity is too large.";
		case 414: return "The request URI is too long.";
		case 415: return "The entity given in the request is not in a format supported by the requested resource.";
		case 416: return "None of the range specifiers given in the request overlap the current extent of the selected resource.";
		case 417: return "The expectation given in the request could not be met by this server.";
		case 418: return "Attempting to brew coffee with a teapot.";
		// Server Error
		case 500: return "The server encountered an unexpected condition which prevented it from fulfilling the request.";
		case 501: return "The server does not support the functionality requred to fulfill the request.";
		case 502: return "The server received an invalid response from an upstream server.";
		case 503: return "The server is currently unable to handle the request. Please try again later.";
		case 504: return "The server did not receive a timely response from an upstream server.";
		case 505: return "The server does not support the HTTP protocol version that was used in the request message.";
		default:
			if ($allow_unknown) {
				switch (intval($code / 100)) {
				case 1: return "";
				case 2: return ""; # The request was successfully received, understood, and accepted.
				case 3: return "Further action needs to be taken by the user agent in order to fulfill the request.";
				case 4: return "The client seems to have erred."; # should include a better description, including permanence of the error
				case 5: return "The server has erred and is incapable of performing the request."; # ''
				default: return ""; # ???
				}
			} else {
				throw new Exception("invalid status code ${code}");
			}
		}
	}

	/**	
	 * Creates a new Response object with some pre-filled-out details.
	 */
	public static function generate($status=404, $message='', $html_message=FALSE) {
		$response = new Response(NULL, $status);
		$title = self::statusName($status, TRUE);

		if ($message) {
			if (! $html_message) {
				#$message = '<p class="mesg">'.nl2br(htmlspecialchars($message),false).'</p>';
				$message = '<p class="mesg">'.nl2br(htmlspecialchars($message)).'</p>';
			}
		} else {
			$message = '<p class="mesg">' . htmlspecialchars( self::statusMessage($status, TRUE) ).'</p>';
		}

		$response->allow_not_modified = FALSE;
		$response->allow_auto_etag = FALSE;
		$response->nocache();

		return $response
			->content_type('text/html; charset=iso-8859-1')
			->body( self::generate_html($title, $message) );
	}

	/**
	 * Creates a new Response object with enough pre-filled-out details
	 * to instruct a browser to go somewhere else.
	 */
	public static function generate_redirect($url, $permanent=false) {
		$response = new Response();
		$response->allow_not_modified = FALSE;
		$response->allow_auto_etag = FALSE;
		$response->nocache();
		return $response
			->redirect($url, $permanent)
			->content_type('text/html; charset=iso-8859-1')
			->body( self::generate_html('Moved', '<p>The document you requested has moved to <a href="'.$url.'">'.$url.'</a>.</p>', TRUE) );
	}

	/**
	 * Creates a new Response object as an appropriate rection to the given
	 * Exception.
	 */
	public static function generate_ex($e) {
		// custom message, if any
		if ($m = $e->getMessage())
			$m = '<p class="mesg">'.nl2br(htmlspecialchars($m)).'</p>';
		// status code, extra source, etc.
		if ($e instanceof HttpException) {
			$s = $e->status();
			// don't show the source of where this was thrown,
			// it's a regular HTTP flow thing (and should be self-
			// explanatory)
		} else {
			$s = 500;
			// in debug mode, show the source line that died
			if (defined('DEBUG') && DEBUG) {
				$m .= self::_source($e->getFile(), $e->getLine());
				$m .= self::_stack($e->getTrace());
			}
		}
		// voila
		return self::generate($s, $m, TRUE);
	}

	/**
	 * Immediately handles an unrecoverable error, and terminates the request.
	 */
	public static function error($title, $message, $errfile, $errline, $stack=NULL) {
		$code = self::_source($errfile, $errline);
		if ($stack) $code .= self::_stack($stack);
		$message = '<p class="mesg">'.nl2br(htmlspecialchars($message)).'</p>';
		#header('Content-Type: text/html; charset=iso-8859-1', TRUE, 500);
		#echo self::generate_html($title, $message.$code);

		$r = new Response(NULL, 500);
		$r->allow_auto_etag = FALSE;
		$r->content_type('text/html; charset=iso-8859-1');
		$r->body( self::generate_html($title, $message.$code) );
		$r->commit();
		exit;
	}

	/**
	 * Attempts to load lines of a PHP source file and present them as HTML.
	 * Always returns a String; should never fail gracelessly.
	 */
	protected static function _source($errfile, $errline, $around=2) {
		$code = '';
		try {
			if ($errfile && $errline) {
				#$file = explode("\n", file_get_contents($errfile));
				$file = preg_split('/\r\n|\r|\n/', file_get_contents($errfile));
				$lines = array();
				for ($i = $errline-$around; $i <= $errline+$around; $i++) {
					$css = ($i == $errline) ? 'line' : '';
					if (isset($file[$i-1])) $lines[] = sprintf('<div class="%s"><span class="numb">%3d: </span>',$css,$i) . htmlspecialchars($file[$i-1]) . '</div>';
				}
				$code .= '<p class="orgn">In: <code>' . $errfile . '</code>, line ' . $errline . '</p>';
				$code .= '<pre class="code">' . implode("",$lines) . '</pre>';
			}
		} catch (Exception $e) {
		}
		return $code;
	}

	/**
	 * Generates a HTML backtrace from a stack trace array.
	 */
	protected static function _stack($trace) {
		$code = '';
		foreach ($trace as $caller) {
			$code .= '<p>... called by <code>';
			if (isset($caller['class']))
				$code .= $caller['class'] . $caller['type'];
			$code .= $caller['function'] . '(';

			$args = array();
			foreach ($caller['args'] as $arg) {
				$args[] = gettype($arg);
			}
			$code .= implode(', ', $args);

			$code .= ')</code>:</p>';
			if (isset($caller['file']) && isset($caller['line'])) {
				$code .= self::_source($caller['file'], $caller['line']);
			}
		}
		return $code;
	}

	/**
	 * Template method: wraps the given $title and $body in a HTML string.
	 */
	public static function generate_html($title, $body) {
		$css = <<<CSS
.mesg {border:1px solid #ccc;border-radius:2px;background-color:#fff8cc;padding:5px;}
.code {background:#d8d8d8;border:2px inset #777;}
.line {background:#fa5;}
.numb {font-weight:bold;}
.prod {font-family:sans-serif;}
.time {font-family:sans-serif;font-size:90%}
.foot {color:#888;}
CSS;
		$template = new TemplateEngine();
		if ($template->canExec()) {
			$template->set_title($title);
			$template->append_css($css);
			$template->content($body);
			return $template->execFile();
		} else {
			// if the template fails, do it by hand minimally
			$now = date('c');
			$verstr = '<span class="prod">' . Application::TITLE . '</span> v' . Application::VERSION;
			return <<<HTML
<!doctype html>
<html lang="en">
<head>
<title>$title</title>
<style type="text/css">
html,body {margin:0;padding:0;}
body {padding:0.5em 1em;background:#fff;color#000;}
$css
</style>
</head>
<body>
<h1>$title</h1>
$body
<hr><div class="foot"><p>Response generated at <span class="time">$now</span> by $verstr.</p></div>
</body>
</html>
HTML;
		}
	}
}

