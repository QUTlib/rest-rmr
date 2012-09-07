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

class HTMLElement extends HTMLHierarchyNode {
	private $tag = NULL;
	private $attrs = array();
	private $can_hang = FALSE;

	public function __construct($tagname, $attributes=NULL, $can_hang=FALSE) {
		$this->tag = $tagname;
		if ($attributes) {
			foreach ($attributes as $key=>$val) {
				$this->attrs[$key] = $val;
			}
		}
		$this->can_hang = !!($can_hang);
	}

	public function tagname() { return $this->tag; }

	public function add_text($text) {
		$node = new HTMLTextNode($text, FALSE); # (will be escaped on output)
		return $this->add_child($node);
	}

	public function add_html($html) {
		$node = new HTMLTextNode($html, TRUE); # (already escaped)
		return $this->add_child($node);
	}

	public function add_tag($type, $attrs=NULL, $can_hang=FALSE) {
		$node = new HTMLElement($type, $attrs, $can_hang);
		return $this->add_child($node);
	}

	public function add_xml_value($type, $value, $attrs=NULL, $can_hang=FALSE) {
		$node = new HTMLElement($type, $attrs, $can_hang);
		if ($value)
			$node->add_text($value);
		return $this->add_child($node);
	}

	public function attribute_string() {
		$attr = '';
		foreach ($this->attrs as $key=>$val) {
			$attr .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
		}
		return $attr;
	}

	public function html() {
		$s = sprintf('<%s%s>', $this->tag, $this->attribute_string());
		foreach ($this as $child) {
			$s .= $child->html();
		}
		if ($this->length() || !($this->can_hang)) {
			$s .= sprintf('</%s>', $this->tag);
		}
		return $s;
	}

	public function xml() {
		$s = sprintf('<%s%s', $this->tag, $this->attribute_string());
		if ($this->length()) {
			$s .= '>';
			foreach ($this as $child) {
				$s .= $child->xml();
			}
			$s .= sprintf('</%s>', $this->tag);
		} else {
			$s .= '/>';
		}
		return $s;
	}

	/*-- CREATION HELPERS THAT COULD APPLY TO ANY NODE (in the body) --*/

	// yurk...
	public function add($type, $value=NULL, $attrs=NULL, $can_hang=FALSE) {
		if (func_num_args() == 2) {
			if (is_bool($value)) {
				$can_hang = $value;
				$value = NULL;
			} elseif (is_array($value)) {
				$attrs = $value;
				$value = NULL;
			}
		} elseif (func_num_args() == 3) {
			if (is_array($value)) {
				if (is_bool($attrs)) {
					$can_hang = $attrs;
				}
				$attrs = $value;
				$value = NULL;
			} elseif (is_bool($attrs)) {
				$can_hang = $attrs;
				$attrs = NULL;
			}
		}
		return $this->add_xml_value($type, $value, $attrs, $can_hang);
	}

	public function add_section($heading=NULL, $attrs=NULL) {
		if (func_num_args() == 1 && is_array($heading)) {
			$attrs = $heading;
			$heading = NULL;
		}
		if (!$attrs) $attrs = array();
		$node = $this->add_tag('section', $attrs);
		if ($heading) {
			$node->add_xml_value('h1', $heading);
		}
		return $node;
	}

	public function add_link($href, $text=NULL, $attrs=NULL) {
		if (func_num_args() == 2 && is_array($text)) {
			$attrs = $text;
			$text = NULL;
		}
		if (!$attrs) $attrs = array();
		$attrs['href'] = $href;
		$node = $this->add_xml_value('a', $text, $attrs);
		return $node;
	}

	public function add_list_item($value=NULL, $attrs=array()) {
		if (func_num_args() == 1 && is_array($value)) {
			$attrs = $value;
			$value = NULL;
		}

		switch (strtolower($this->tag)) {
		case 'select':
			$li = 'option';
			break;
		#case 'dl':
		#	$li = 'dd';//or dt..?
		#	break;
		case 'ol':
		case 'ul':
			$li = 'li';
			break;
		default:
			//$li = 'p';
			throw new Exception("not a list [".$this->tag."]");
		}

		$node = $this->add_xml_value($li, $value, $attrs);
		return $node;
	}

	/**
	 * Creates DT/DD elements.  Creates a DL if this element isn't already one.
	 * Returns the DL in question.
	 */
	public function define($terms) {
		if (strtolower($this->tag) == 'dl') {
			$dl = $this;
		} else {
			$dl = $this->add_tag('dl');
		}
		foreach ($terms as $dt=>$dd) {
			$dl->add_xml_value('dt', $dt);
			if (! is_array($dd)) $dd = array($dd);
			foreach ($dd as $dd0) {
				$dl->add_xml_value('dd', $dd0);
			}
		}
		return $dl;
	}


}

