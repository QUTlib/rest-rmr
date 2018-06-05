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

require_once(SYSDIR.'/uri-registrar.inc.php');

/**
 * Like {@see URIRegistrar} but with explicit abstractions for
 * interface and module prefixes.
 */
class InterfacedURIRegistrar extends URIRegistrar {
	private $module = null;
	private $interface = null;

	/**
	 * Constructs a new registrar, on which one may call {@see register_handler()}
	 *
	 * @param string $module common module prefix prepended to all registered URI patterns
	 * @param string $interface (optional) interface prefix prepended to all registered URI patterns
	 */
	public function __construct($module, $interface=NULL) {
		$this->module = $module;
		if (func_num_args() > 1)
			$this->set_interface($interface);
	}

	/**
	 * Update the current interface of this registrar.
	 *
	 * See the IF_... consts in {@see Application}
	 *
	 * @param string $interface
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
	 *
	 *       '/students/:sid/'
	 *         :: '/students/123/' => {"sid":"123"}
	 *       '/some/path/?'
	 *         :: '/some/path/' => {}
	 *         :: '/some/path'  => {}
	 *       '/branch/:name/?'
	 *         :: '/branch/gp/' => {"name":"gp"}
	 *         :: '/branch/kg'  => {"name":"kg"}
	 *         :: '/branch/'    => FALSE
	 *
	 * @param string $http_method the HTTP method to handle (e.g. GET, POST, etc.).
	 * @param string $uri_pattern
	 * @param mixed $handler {@see URIMap::realise_handler}
	 */
	public function register_handler($http_method, $uri_pattern, $handler) {
		if (is_null($this->interface)) throw new Exception("interface not set");
		parent::register_handler($http_method, $uri_pattern, $handler);
	}

}

