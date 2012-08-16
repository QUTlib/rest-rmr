<?php

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
		case 401: return "Unauthorized";
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

	/**	
	 * Creates a new Response object with some pre-filled-out details.
	 */
	public static function generate($status=404, $message='', $html_message=FALSE) {
		$r = new Response(NULL, $status);
		$h = '';
		$m = '';
		switch ($status) {
		case 404:
			$h = 'Not Found';
			$m = '<p>The resource you requested was not found at this location.</p>';
			break;
		case 501:
			$h = 'Not Implemented';
			$m = '<p>The request method you supplied is not implemented by this server.</p>';
			break;
		default:
			$h = self::statusName($status, TRUE);
			$m = '';
		}

		if ($message) {
			if (! $html_message) {
				#$message = '<p class="mesg">'.nl2br(htmlspecialchars($message),false).'</p>';
				$message = '<p class="mesg">'.nl2br(htmlspecialchars($message)).'</p>';
			}
		} else {
			$message = '';
		}

		return $r
			->content_type('text/html; charset=iso-8859-1')
			->body( self::generate_html($h, $m.$message) );
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
.mesg {border:2px inset #777;border-radius:2px;background-color:#fe9;padding:5px;}
.code {background:#d8d8d8;border:2px inset #777;}
.line {background:#fa5;}
.numb {font-weight:bold;}
.mama {font-family:sans-serif;}
.time {font-family:sans-serif;font-size:90%}
.foot {color:#888;}
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

