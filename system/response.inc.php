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

	private $recording = false;

	public function __construct($http_version='1.1', $status=200) {
		if (func_num_args() > 0 && $http_version) $this->version = $http_version;
		if (func_num_args() > 1) $this->status($status); // including validation
		// Set the default headers
		foreach (headers_list() as $header) {
			$parts = explode(': ', $header, 2);
			if (count($parts) == 2)
				$this->add_header($parts[0], $parts[1]);
		}
	}

### DEBUG
public function dump() {
	print("HTTP/" . $this->version . ' ' . $this->status . ' ' . $this->status_message() . "\n");
	foreach ($this->header as $k=>$v) print("$k: $v\n");
	print("\n");
	print($this->body);
}
### /DEBUG

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
	 * Appends '$value' to the response header '$name'.
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
	public function commit($request) {
		// HTTP Status Line
		$vrsn = $this->version;
		$code = $this->status;
		$msg  = $this->status_message();
		header("HTTP/$vrsn $code $msg", TRUE, $code);

		// get any response body that was printed, rather than appended
		if ($this->recording) {
			$this->stop_recording();
		}

		// mandatory header #1
		if (!$this->header('Date')) {
			$this->header('Date', gmdate('D, d M Y H:i:s T'));
		}

		// if the browser wants encoded (read: compressed) data, we should
		// try to accommodate it.
		if (!$this->header('Content-Encoding') && $request && ($accepted_encodings = $request->encodings())) {
			$this->attempt_compression($accepted_encodings);
		}

		// mandatory header #2
		// note: _after_ compression stuff, because compression can change the length
		if (!$this->header('Content-Length')) {
			$this->header('Content-Length', $this->length());
		}

		// secret magic
		$this->add_header('X-Powered-By', strip_tags(Application::TITLE.'/'.Application::VERSION));

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
		if ($request->method() != 'HEAD')
			print($this->body);
	}

	/**
	 * Manually applies compression, if possible/requested.
	 */
	protected function attempt_compression($methods) {
		// disable the PHP magic; apparently it doesn't play
		// nice with our Content-Length header anyway
		if (ini_get('zlib.output_compression'))
			ini_set('zlip.output_compression', 'Off');

		// todo: deal with the 'identity' encoding ..?
		foreach ($methods as $qvalue => $meths) {
			foreach ($meths as $comp) {
				$method = $comp['option'];
				switch ($method) {
				case 'x-gzip':
				case 'gzip':
					$body = gzencode($this->body, 9);
					return $this->_apply_compression($body, $method);
				case 'deflate':
					$body = gzcompress($this->body, 9);
					return $this->_apply_compression($body, $method);
				case 'bzip2':
					$body = bzcompress($this->body, 9);
					return $this->_apply_compression($body, $method);
				}
			}
		}
	}
	protected function _apply_compression($body, $method) {
		// we can still bail out here if there's not enough of an
		// improvement (currently 'enough' means 'any')
		if (strlen($body) < $this->length()) {
			// update the response body
			$this->body( $body );
			// update appropriate 'simple' headers (encoding, length)
			$this->header('Content-Encoding', $method);
			$this->header('Content-Length', $this->length());
			// if there's a Vary: header, add 'content-encoding' as an important thingy
			if ($vary = $this->header('Vary')) {
				if (! preg_match('/(^|,)\s*content-encoding\s*(,|$)/i', $vary)) {
					$this->header('Vary', $vary . ', content-encoding');
				}
			} else {
				$this->header('Vary', 'content-encoding');
			}
			return TRUE;
		}
		return FALSE;
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
		case 404: return "The request resource could not be found at this location.";
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

		return $response
			->content_type('text/html; charset=iso-8859-1')
			->body( self::generate_html($title, $message) );
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
			if (defined('DEBUG') && DEBUG)
				$m .= self::_source($e->getFile(), $e->getLine());
		}
		// voila
		return self::generate($s, $m, TRUE);
	}

	/**
	 * Immediately handles an unrecoverable error, and terminates the request.
	 */
	public static function error($title, $message, $errfile, $errline) {
		$code = self::_source($errfile, $errline);
		$message = '<p class="mesg">'.nl2br(htmlspecialchars($message)).'</p>';
		header('Content-Type: text/html; charset=iso-8859-1', TRUE, 500);
		echo self::generate_html($title, $message.$code);
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
				$code .= '<p class="orgn">In: <code>' . basename($errfile) . '</code>, line ' . $errline . '</p>';
				$code .= '<pre class="code">' . implode("",$lines) . '</pre>';
			}
		} catch (Exception $e) {
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
.mama {font-family:sans-serif;}
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
			$verstr = '<span class="mama">' . Application::TITLE . '</span> v' . Application::VERSION;
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

