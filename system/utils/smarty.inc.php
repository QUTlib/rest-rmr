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

// FIXME: this precludes the use of non-bundled Smarty lib
require_once(SYSDIR.'/utils/smarty/Smarty.class.php');

/**
 * A Smarty template class.
 */
class SmartyTemplate extends Smarty {
	/** The name of the template (.tpl) file to display. */
	private $template_file = 'default.tpl';

	/**
	 * @param string $template_dir where all the .tpl files live
	 * @param string $config_dir if not given, uses $template_dir
	 * @param string $compile_dir where to write compiled templates; attempts to create if doesn't exist
	 * @param string $cache_dir where to write cached files; attempts to create if doesn't exist
	 */
	public function __construct($template_dir, $config_dir=NULL, $compile_dir='/tmp/smarty.template_c', $cache_dir='/tmp/smarty.cache') {
		parent::__construct();

		if ($config_dir === NULL) $config_dir = $template_dir;
		if (!is_dir($compile_dir)) mkdir($compile_dir, 01770, TRUE);
		if (!is_dir($cache_dir))   mkdir($cache_dir,   01770, TRUE);

		$this->setTemplateDir($template_dir);
		$this->setCompileDir($compile_dir);
		$this->setConfigDir($config_dir);
		$this->setCacheDir($cache_dir);
	}

	/**
	 * Gets or sets the .tpl file this template will display.
	 */
	public function template_file($val=NULL) {
		if (func_num_args() < 1) {
			return $this->template_file;
		} else {
			$this->template_file = $val;
			return $this;
		}
	}

	/**
	 * Displays this template's .tpl file.
	 * @see #template_file()
	 */
	public function fetch_default() {
		$this->fetch($this->template_file);
	}
}

