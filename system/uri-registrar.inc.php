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


class URIRegistrar {
	protected $prefix = null;

	/**
	 * Constructs a new registrar, on which one may call #register_handler
	 */
	public function __construct($prefix) {
		$this->prefix = '/' . trim($prefix, '/');
		if ($this->prefix == '/') $this->prefix = '';
	}

	/**
	 * Sets up a handler to accept incoming requests.
	 *
	 * Note that GET handlers automatically set up an identical HEAD handler.
	 *
	 * URI pattern examples:
	 *   '/students/:sid/'
	 *     :: '/students/123/' => {"sid":"123"}
	 *   '/some/path/?'
	 *     :: '/some/path/' => {}
	 *     :: '/some/path'  => {}
	 *   '/branch/:name/?'
	 *     :: '/branch/gp/' => {"name":"gp"}
	 *     :: '/branch/kg'  => {"name":"kg"}
	 *     :: '/branch/'    => FALSE
	 *
	 * @param String $http_method the HTTP method to handle (e.g. GET, POST, etc.).
	 * @param String $uri_pattern
	 * @param Mixed $handler 'function', 'class->method', 'class::static_method', array(object,'method'), array('class','method')
	 */
	public function register_handler($http_method, $uri_pattern, $handler) {
		if ($uri_pattern && substr($uri_pattern,0,1) != '/') $uri_pattern = '/' . $uri_pattern;
		$full_pattern = $this->prefix . $uri_pattern;
		if (! $full_pattern) {
			#$full_pattern = '/';
			throw new Exception("invalid handler; prefix and URI pattern are both blank");
		}
		URIMap::register($http_method, $full_pattern, $handler);
	}

	/**
	 * Sets up a handler to accept incoming requests, like #register_handler .
	 * Also sets up a redirect handler, so that any request not ending in slash
	 * is automatically reidrected to a slashed equivalent.
	 *
	 * c.f. http://httpd.apache.org/docs/2.2/mod/mod_dir.html#directoryslash
	 */
	public function register_with_redirect($http_method, $uri_pattern, $handler) {
		if (substr($uri_pattern,-1) == '/') {
			$uri_pattern2 = substr($uri_pattern,0,-1);
		} else {
			$uri_pattern2 = $uri_pattern;
			$uri_pattern .= '/';
		}
		$this->register_handler($http_method, $uri_pattern, $handler);
		$this->register_handler($http_method, $uri_pattern2, 'URIRegistrar::redirect_with_slash');
	}

	/**
	 * Sets up a GET handler.
	 * @param string $uri_pattern {@see #register_handler}
	 * @param Mixed $handler {@see #register_handler}
	 * @param boolean $redirect_slash if given and true, uses #register_with_redirect
	 */
	public function get($uri_pattern, $handler, $redirect_slash=FALSE) {
		if ($redirect_slash) {
			$this->register_with_redirect('GET', $uri_pattern, $handler);
		} else {
			$this->register_handler('GET', $uri_pattern, $handler);
		}
	}

	/**
	 * Sets up a HEAD handler.
	 * @param string $uri_pattern {@see #register_handler}
	 * @param Mixed $handler {@see #register_handler}
	 * @param boolean $redirect_slash if given and true, uses #register_with_redirect
	 */
	public function head($uri_pattern, $handler, $redirect_slash=FALSE) {
		if ($redirect_slash) {
			$this->register_with_redirect('HEAD', $uri_pattern, $handler);
		} else {
			$this->register_handler('HEAD', $uri_pattern, $handler);
		}
	}

	/**
	 * Sets up a POST handler.
	 * @param string $uri_pattern {@see #register_handler}
	 * @param Mixed $handler {@see #register_handler}
	 */
	public function post($uri_pattern, $handler) {
		$this->register_handler('POST', $uri_pattern, $handler);
	}

	/**
	 * Creates a Response that directs the client to the requested
	 * uri with an appended slash.
	 * @return Response a HTTP 301 with an appended slash
	 */
	public static function redirect_with_slash() {
		return Response::generate_redirect(Request::uri().'/', TRUE);
	}

}

