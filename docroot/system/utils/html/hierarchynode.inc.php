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

abstract class HTMLHierarchyNode extends HTMLNode implements Iterator {
	private $children = array();
	private $parent = NULL;

	public function parent() { return $this->parent; }

	private function _inherit($node) {
		$node->parent = $this;
		return $node;
	}
	private function _disown($node) {
		$node->parent = NULL;
		return $node;
	}
	public function add_child($node)     { $this->children[] = $node;             return $this->_inherit($node); }
	public function append_child($node)  { array_push($this->children, $node);    return $this->_inherit($node); }
	public function prepend_child($node) { array_unshift($this->children, $node); return $this->_inherit($node); }
	public function remove_child($node) {
		$result = NULL;
		$newkids = array();
		foreach ($this->children as $kid) {
			if ($kid->is($node)) {
				$result = $this->_disown($kid);
			} else {
				$newkids[] = $kid;
			}
		}
		$this->children = $newkids;
		return $result;
	}

	public function length()  { return count($this->children); }
	public function rewind()  {        reset($this->children); }
	public function current() { return current($this->children); }
	public function key()     { return key($this->children); }
	public function next()    {        next($this->children); }
	public function valid()   { return (key($this->children) !== NULL); }

}

