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
	private $module = null;
	private $interface = null;
	private $prefix = null;

	/**
	 * Constructs a new registrar, on which one may call #register_handler
	 */
	public function __construct($module, $interface=NULL) {
		$this->module = $module;
		if (func_num_args() > 1)
			$this->set_interface($interface);
	}

	/**
	 * Update the current interface of this registrar.
	 *
	 * @see the IF_... consts in Application
	 */
	public function set_interface($interface) {
		$this->interface = $interface;
		$this->prefix = '/' . $this->interface . '/' . $this->module;
		if (!preg_match('#^/[a-z]+/[a-z_][a-z0-9_-]*$#', $this->prefix))
			throw new Exception("invalid interface/module ['{$this->prefix}']");
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
		if (is_null($this->interface)) throw new Exception("interface not set");
		if (substr($uri_pattern,0,1) != '/') $uri_pattern = '/' . $uri_pattern;
		URIMap::register($http_method, $this->prefix . $uri_pattern, $handler);
	}

}

