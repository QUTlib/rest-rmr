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

/*
 * This file exists because our horrible sysadmins don't let us
 * use PHP DOM, and SimpleXML is terribly insufficient.
 */

class DOMNodeList implements Iterator {

	### MAGIC

	private $array = NULL;

	private function _l() {	
		// recalculate length
		$this->length = count($this->array);
		// recalculate next/previous links
		$p = NULL;
		foreach ($this->array as $e) {
			if ($p) $p->nextSibling = $e;
			$e->previousSibling = $p;
			$p = $e;
		}
		if ($p) $p->nextSibling = NULL;
	}

	public function __construct($items=NULL) {
		if (!is_array($items)) $items = array();

		$this->array = $items;
		$this->_l();
	}

	public function append($item) {
		$this->array[] = $item;
		$this->_l();
	}

	public function insertBefore($newnode, $refnode=NULL) {
		if ($refnode) {
			$x = false;
			$a = array();
			foreach ($this->array as $n) {
				if ($n->isSameNode($refnode)) {
					$a[] = $newnode;
					$x = true;
				}
				$a[] = $n;
			}
			if (!$x) throw new DOM_NOT_FOUND();
			$this->array = $a;
		} else {
			array_unshift($this->array, $newnode);
		}
		$this->_l();
	}

	public function insertAfter($newnode, $refnode=NULL) {
		if ($refnode) {
			$x = false;
			$a = array();
			foreach ($this->array as $n) {
				$a[] = $n;
				if ($n->isSameNode($refnode)) {
					$a[] = $newnode;
					$x = true;
				}
			}
			if (!$x) throw new DOM_NOT_FOUND();
			$this->array = $a;
		} else {
			array_unshift($this->array, $newnode);
		}
		$this->_l();
	}


	public function remove($oldnode) {
		$x = false;
		$a = array();
		foreach ($this->array as $n) {
			if ($n->isSameNode($oldnode)) {
				$x = true;
			} else {
				$a[] = $n;
			}
		}
		if (!$x) throw new DOM_NOT_FOUND();
		$this->array = $a;
		$this->_l();
	}

	public function replace($newnode, $oldnode) {
		$x = false;
		$a = array();
		foreach ($this->array as $n) {
			if ($n->isSameNode($oldnode)) {
				$a[] = $newnode;
				$x = true;
			} else {
				$a[] = $n;
			}
		}
		if (!$x) throw new DOM_NOT_FOUND();
		$this->array = $a;
		$this->_l();
	}

	public function first() {
		return reset($this->array);
	}

	public function rewind()  {        reset($this->array); }
	public function current() { return current($this->array); }
	public function key()     { return key($this->array); }
	public function next()    {        next($this->array); }
	public function valid()   { return (key($this->array) !== NULL); }

	### API

	public $length = 0;

	public function item($index) {
		if (isset($this->array[$index])) {
			return $this->array[$index];
		} else {
			return NULL;
		}
	}
}

