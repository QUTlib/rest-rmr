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

class HTMLHead extends HTMLElement {

	public function __construct($title) {
		parent::__construct('head');
		$this->add_tag('title')->add_text($title, FALSE);
	}

	public function add_httpmeta($equiv, $content, $attrs=NULL) {
		if (!$attrs) $attrs = array();
		$attrs['http-equiv'] = $equiv;
		$attrs['content'] = $content;
		return $this->add_tag('meta', $attrs, TRUE);
	}

	public function add_meta($name, $content, $attrs=NULL) {
		if (!$attrs) $attrs = array();
		$attrs['name'] = $name;
		$attrs['content'] = $content;
		return $this->add_tag('meta', $attrs, TRUE);
	}

	public function add_link($rel, $href, $attrs=NULL) {
		if (!$attrs) $attrs = array();
		$attrs['rel'] = $rel;
		$attrs['href'] = $href;
		return $this->add_tag('link', $attrs, TRUE);
	}

	public function add_js_link($url) {
		return $this->add_tag('script', array('type'=>'text/javascript', 'src'=>$url));
	}

	public function add_js($js) {
		$script = $this->add_tag('script', array('type'=>'text/javascript'));
		$script->add_text('//');
		$script->add_child(new HTMLCData("\n".$js."\n//"));
		return $script;
	}

	public function add_css_link($url) {
		return $this->add_tag('link', array('rel'=>'stylesheet', 'type'=>'text/css', 'href'=>$url));
	}

	public function add_css($css) {
		$script = $this->add_tag('style', array('type'=>'text/css'));
		$script->add_text('/*');
		$script->add_child(new HTMLCData('*/ '.$css.' /*'));
		$script->add_text('*/');
		return $script;
	}

}

