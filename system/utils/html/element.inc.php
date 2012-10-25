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

class HTMLElementHolder {
	private $obj = NULL;
	public function __construct($obj) {
		$this->obj = $obj;
	}
	public function __call($name, $args) {
		$result = call_user_func_array(array($this->obj, $name), $args);
		// HACK! Any call that, e.g. creates a child, now returns $this
		if (is_object($result) && $result instanceof HTMLHierarchyNode) {
			return $this;
		} else {
			return $result;
		}
	}
	// This is the only standard HTMLHierarchyNode method that returns a node
	// that we don't want to override using the __call hack.
	public function parent() { return $this->obj->parent(); }

	// ...
	public function release() { return $this->obj; }

	// Erk
	public function hold() { return $this; }
}

class HTMLElement extends HTMLHierarchyNode {
	private $tag = NULL;
	private $attrs = array();
	private $can_hang = FALSE;
	private $holder = NULL;

	public function __construct($tagname, $attributes=NULL, $can_hang=FALSE) {
		$this->tag = $tagname;
		if ($attributes) {
			foreach ($attributes as $key=>$val) {
				$this->attrs[$key] = $val;
			}
		}
		$this->can_hang = !!($can_hang);
	}

	public function hold() {
		if (!isset($this->holder)) {
			$this->holder = new HTMLElementHolder($this);
		}
		return $this->holder;
	}
	// FIXME: should this just error?
	public function release() { return $this; }

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
			// array( 'w', 'x', 'y'=>true, 'z'=>true ) becomes 'w="w" x="x" y="y" z="z"'
			if (is_int($key)) {
				$key = $val;
			} elseif ($val === TRUE) {
				$val = $key;
			}
			if ($val !== NULL) {
				$attr .= sprintf(' %s="%s"', $key, htmlspecialchars($val));
			}
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
				if (is_object($dd0) && $dd0 instanceof HTMLHierarchyNode) {
					$dl->add_child($dd0);
				} else {
					$dl->add_xml_value('dd', $dd0);
				}
			}
		}
		return $dl;
	}

	/**
	 * Adds a definition term (DT), and a blank definition element (DD).
	 * Creates a DL if this element isn't already one.
	 * Returns the DD.
	 */
	public function def($term) {
		if (strtolower($this->tag) == 'dl') {
			$dl = $this;
		} else {
			$dl = $this->add_tag('dl');
		}
		$dl->add_xml_value('dt', $term);
		return $dl->add_tag('dd');
	}

	/**
	 * Creates an INPUT element.
	 */
	public function add_input($name, $value=NULL, $attrs=array()) {
		if (func_num_args() == 2 && is_array($value)) {
			$attrs = $value;
			$value = NULL;
		}
		if (!$attrs) $attrs = array();
		if (!isset($attrs['type'])) $attrs['type'] = 'text';
		if (!isset($attrs['name'])) $attrs['name'] = $name;
		if (!isset($attrs['value'])) $attrs['value'] = $value;
		$node = $this->add_tag('input', $attrs, TRUE);
		return $node;
	}

	/**
	 * Creates a TEXTAREA element.
	 */
	public function add_textarea($name, $value=NULL, $attrs=array()) {
		if (func_num_args() == 2 && is_array($value)) {
			$attrs = $value;
			$value = NULL;
		}
		if (!$attrs) $attrs = array();
		if (!isset($attrs['name'])) $attrs['name'] = $name;
		$node = $this->add_tag('textarea', $attrs, TRUE);
		$node->add_text($value);
		return $node;
	}

	/**
	 * Creates a button INPUT element.
	 */
	public function add_button($caption, $name=NULL, $attrs=array()) {
		if (func_num_args() == 2 && is_array($name)) {
			$attrs = $name;
			$name = NULL;
		}
		if (!$attrs) $attrs = array();
		if (!isset($attrs['type'])) $attrs['type'] = 'button';
		if (!isset($attrs['value'])) $attrs['value'] = $caption;
		if (!isset($attrs['name'])) $attrs['name'] = $name;
		$node = $this->add_tag('input', $attrs, TRUE);
		return $node;
	}

	public function add_dropdown($name, $values, $selected=NULL, $attrs=array()) {
		if (func_num_args() == 2 && is_array($selected)) {
			$attrs = $selected;
			$selected = NULL;
		}
		if ($selected) $selected = strtolower($selected);
		if (!$attrs) $attrs = array();
		if (!isset($attrs['name'])) $attrs['name'] = $name;
		$node = $this->add_tag('select', $attrs);
		foreach ($values as $k=>$v) {
			if (is_int($k)) $k = $v;
			$opt_attrs = array('value'=>$k);
			if ($selected && $selected == strtolower($k)) {
				$opt_attrs['selected'] = 'selected';
			}
			$node->add_xml_value('option', $v, $opt_attrs);
		}
		return $node;
	}

}

