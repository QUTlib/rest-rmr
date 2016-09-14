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
 * Allows an API developer to specify problems with finer
 * granularity, or more terseness, than is afforded by basic
 * HTTP response status codes.
 *
 * See: https://tools.ietf.org/html/rfc7807
 */
class Problem {
	private $type;
	private $title;
	private $status;
	private $detail;
	private $instance;

	/**
	 * Creates a new Problem, with the minimum set of required attributes.
	 */
	public function __construct($type, $title, $status=null, $detail=null) {
		$this->type = "$type";
		$this->title = "$title";
		if ($status !== NULL) $this->status = (int)$status;
		if ($detail !== NULL) $this->detail = "$detail";
	}

	/** Get the immutable type URI of this problem. */
	public function type() { return $this->type; }
	/** Get the immutable short, human-readable summary of this problem. */
	public function title() { return $this->title; }

	/** Get or set the optional HTTP status code set by the server for this occurrence of the problem. */
	public function status($val=null) {
		if (func_num_args() == 0) return $this->status;
		$this->status = (int)$val;
	}

	/** Get or set the optional human readable explanation specific to this occurrence of the problem. */
	public function detail($val=null) {
		if (func_num_args() == 0) return $this->detail;
		$this->detail = "$val";
	}

	/** Get or set the optional URI that identifies the specific occurrence of the problem. */
	public function instance($val=null) {
		if (func_num_args() == 0) return $this->instance;
		$this->instance = "$val";
	}

	/** Get an associative-array representation of this problem object. */
	public function to_array() {
		$array = array(
			'type' => $this->type,
			'title' => $this->title,
			'status' => $this->status,
		);
		if (isset($this->detail)) $array['detail'] = $this->detail;
		if (isset($this->instance)) $array['instance'] = $this->instance;
		return $array;
	}

	/**
	 * Constructs a new Problem object with a default type='about:blank', and
	 * status/title as given.
	 *
	 * If not specified, $title defaults to the normal english HTTP status phrase for
	 * the given $status code.
	 */
	public static function from_status_code($status, $title=null) {
		if (func_num_args() < 2) {
			switch ((int)$status) {
			/*
			 * This is the same list of codes as used in /assets/problem.xsl,
			 * drawn from the IANA registry at:
			 * http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
			 */
			case 100: $title = 'Continue'; break;
			case 101: $title = 'Switching Protocols'; break;
			case 102: $title = 'Processing'; break;
			case 200: $title = 'OK'; break;
			case 201: $title = 'Created'; break;
			case 202: $title = 'Accepted'; break;
			case 203: $title = 'Non-Authoritative Information'; break;
			case 204: $title = 'No Content'; break;
			case 205: $title = 'Reset Content'; break;
			case 206: $title = 'Partial Content'; break;
			case 207: $title = 'Multi-Status'; break;
			case 208: $title = 'Already Reported'; break;
			case 300: $title = 'Multiple Choices'; break;
			case 301: $title = 'Moved Permanently'; break;
			case 302: $title = 'Moved Temporarily'; break;
			case 303: $title = 'See Other'; break;
			case 304: $title = 'Not Modified'; break;
			case 305: $title = 'Use Proxy'; break;
			case 307: $title = 'Temporary Redirect'; break;
			case 308: $title = 'Permanent Redirect'; break;/* I.D */
			case 400: $title = 'Bad Request'; break;
			case 401: $title = 'Unauthorised'; break;
			case 402: $title = 'Payment Required'; break;
			case 403: $title = 'Forbidden'; break;
			case 404: $title = 'Not Found'; break;
			case 405: $title = 'Method Not Allowed'; break;
			case 406: $title = 'Not Acceptable'; break;
			case 407: $title = 'Proxy Authentication Required'; break;
			case 408: $title = 'Request Time-out'; break;
			case 409: $title = 'Conflict'; break;
			case 410: $title = 'Gone'; break;
			case 411: $title = 'Length Required'; break;
			case 412: $title = 'Precondition Failed'; break;
			case 413: $title = 'Request Entity Too Large'; break;
			case 414: $title = 'Request-URI Too Large'; break;
			case 415: $title = 'Unsupported Media Type'; break;
			case 416: $title = 'Requested range not satisfiable'; break;
			case 417: $title = 'Expectation Failed'; break;
			case 418: $title = "I'm a teapot"; break;/* not IANA */
			case 422: $title = 'Unprocessable Entity'; break;
			case 423: $title = 'Locked'; break;
			case 424: $title = 'Failed Dependency'; break;
			case 426: $title = 'Upgrade Required'; break;
			case 428: $title = 'Precondition Required'; break;
			case 429: $title = 'Too Many Requests'; break;
			case 431: $title = 'Request Header Fields Too Large'; break;
			case 500: $title = 'Internal Server Error'; break;
			case 501: $title = 'Not Implemented'; break;
			case 502: $title = 'Bad Gateway'; break;
			case 503: $title = 'Service Unavailable'; break;
			case 504: $title = 'Gateway Time-out'; break;
			case 505: $title = 'HTTP Version not supported'; break;
			case 506: $title = 'Variant Also Negotiates'; break;/* Experimental - TCN [RFC 2295] */
			case 507: $title = 'Insufficient Storage'; break;
			case 508: $title = 'Loop Detected'; break;
			case 510: $title = 'Not Extended'; break;
			case 511: $title = 'Network Authentication Required'; break;
			default:  $title = "${title}"; break;
			}
		}
		return new self('about:blank', $title, $status);
	}
}
