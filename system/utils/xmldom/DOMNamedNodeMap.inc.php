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

class DOMNamedNodeMap {

	### MAGIC

	public $list = array();
	public $map = array();

	public function add($item, $name=NULL) {
		if (!$name) $name = $item->name; // this can fail
		$this->map[$name] = count($this->list);
		$this->list[] = $item;
	}
	public function removeName($name) {
		if (isset($map[$name])) {
			unset($this->list[ $map[$name] ]);
			unset($this->map[$name]);
			return TRUE;
		}
		return FALSE;
	}
	public function removeNode($oldnode) {
		foreach ($this->list as $i=>$n) {
			if ($n->isSameNode($oldnode)) {
				unset($this->list[$i]);
				foreach ($this->map as $name=>$index) {
					if ($index == $i) {
						unset($this->map[$name]);
						break;
					}
				}
				return TRUE;
			}
		}
		return FALSE;
	}


	### API

	public $length = 0;

	public function getNamedItem($name) {
		if (isset($this->map[$name])) {
			return $this->list[ $this->map[$name] ];
		} else {
			return NULL;
		}
	}
	#public function getNamedItemNS($namespaceURI, $localName) {}
	public function item($index) {
		if (isset($this->list[$index])) {
			return $this->list[$index];
		} else {
			return NULL;
		}
	}
}

