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

class HTMLDocument extends HTMLElement {
	private $lang = 'en';
	private $encoding = 'UTF-8';
	private $head = NULL;
	private $body = NULL;

	public static function create($title, $attrs=NULL) {
		return new HTMLDocument( new HTMLHead($title), new HTMLBody($attrs) );
	}

	public function __construct($head, $body=NULL) {
		parent::__construct('html');
		$this->head = $this->add_child($head);
		$this->body = $this->add_child($body);
	}

	public function lang($v=NULL) {
		if (func_num_args() > 0) { $this->lang = $v; return $this; }
		else { return $this->lang; }
	}
	public function encoding($v=NULL) {
		if (func_num_args() > 0) { $this->encoding = $v; return $this; }
		else { return $this->encoding; }
	}
	public function head() { return $this->head; }
	public function body() { return $this->body; }

	public function html() {
		$enc = $this->encoding;
		$lang = $this->lang;
		$string = '';
		$string .= "<!DOCTYPE html>\n";
		$string .= "<html lang=\"$lang\">";
		$string .= $this->head->html();
		$string .= $this->body->html();
		$string .= "</html>";
		return $string;
	}

	public function xml() {
		$enc = $this->encoding;
		$lang = $this->lang;
		$string = '';
		$string .= "<?xml version=\"1.0\" encoding=\"$enc\" ?".">\n";
		$string .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
		$string .= "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"$lang\">";
		$string .= $this->head->xml();
		$string .= $this->body->xml();
		$string .= "</html>";
		return $string;
	}

}

