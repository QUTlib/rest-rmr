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

class DOMNode {

	### MAGIC

	public function __construct() {
		$this->childNodes = new DOMNodeList();
		$this->attributes = new DOMNamedNodeMap();
	}

	public function insertAfter($newnode, $refnode=NULL) {
		$this->childNodes->insertAfter($newnode, $refnode);
		$newnode->parentNode = $this;
		return $newnode;
	}

	public function _xml() { return ''; }

	### API

	public $nodeName = NULL;
	public $nodeValue = NULL;
	public $nodeType = NULL;
	public $parentNode = NULL;
	public $childNodes = NULL;
	public $firstChild = NULL;
	public $lastChild = NULL;
	public $previousSibling = NULL;
	public $nextSibling = NULL;
	public $attributes = NULL;
	public $ownerDocument = NULL;
	public $namespaceURI = NULL;
	public $prefix = '';
	public $localName = NULL;
	public $baseURI = NULL;
	public $textContent = NULL;

	public function appendChild($newnode) {
		$this->childNodes->append($newnode);
		$newnode->parentNode = $this;
		return $newnode;
	}
	#public function C14N($exclusive=NULL, $with_comments=NULL, $xpath=NULL, $ns_prefixes=NULL) {}
	#public function C14NFile($uri, $exclusive=NULL, $with_comments=NULL, $xpath=NULL, $ns_prefixes=NULL) {}
	public function cloneNode($deep) {
		$n = clone $this;
		# todo: deep...
	}
	#public function getLineNo() {}
	#public function getNodePath() {}
	public function hasAttributes() { return $this->attributes->length > 0; }
	public function hasChildNodes() { return $this->childNodes->length > 0; }
	public function insertBefore($newnode, $refnode=NULL) {
		$this->childNodes->insertBefore($newnode, $refnode);
		$newnode->parentNode = $this;
		return $newnode;
	}
	#public function isDefaultNamespace($namespaceURI) {}
	public function isSameNode($node) {
		return spl_object_hash($node) == spl_object_hash($this);
	}
	public function isSupported($features, $version) { return FALSE; } #XXX
	#public function lookupNamespaceURI($prefix) {}
	#public function lookupPrefix($namespaceURI) {}
	public function normalize() {}
	public function removeChild($oldnode) {
		$this->childNodes->remove($oldnode);
		$oldnode->parentNode = NULL;
		return $oldnode;
	}
	public function replaceChild($newnode, $oldnode) {
		$this->childNodes->replace($newnode, $oldnode);
		$oldnode->parentNode = NULL;
		$newnode->parentNode = $this;
		return $oldnode;
	}
}

