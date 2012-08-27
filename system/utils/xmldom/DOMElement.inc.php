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

class DOMElement extends DOMNode {

	### MAGIC

	public $nodeType = XML_ELEMENT_NODE;

	private function _searchName(&$nodes, $list, $name) {
		foreach ($list as $node) {
			if (($node instanceof DOMElement) && $node->tagName == $name)
				$nodes->add($node);
			if ($node->hasChildNodes()) {
				$this->_searchName($nodes, $node->childNodes, $name);
			}
		}
	}

	public function _xml($xmlstyle=TRUE) {
		$xml = '<' . $this->tagName;

		$attrlist = array();
		foreach ($this->attributes->list as $attr) {
#if (is_object($attr)){
			$attrlist[] = $attr->_xml();
#}else{
#$attrlist[]='-- '.var_dump($attr,1).' --';
#}
		}
		if ($attrlist) {
			$xml .= ' ' . implode(' ', $attrlist);
		}

		if ($this->hasChildNodes()) {
			$xml .= '>';
			foreach ($this->childNodes as $node) {
				$xml .= $node->_xml();
			}
			$xml .= '</'.$this->tagName.'>';
		} elseif ($xmlstyle) {
			$xml .= ' />';
		} else {
			$xml .= '>';
		}

		return $xml;
	}

	### API

	public $schemaTypeInfo = NULL;
	public $tagName = NULL;

	public function __construct($name, $value=NULL, $namespaceURI=NULL) {
		parent::__construct();
		# XXX argh! namespace!
		$this->tagName = $name;
		$this->nodeName = $name;
		$this->nodeValue = $value;
		if ($value) {
			$text = new DOMText($value);
			$text->ownerDocument = $this->ownerDocument;
			$this->appendChild($text);
		}
	}

	public function getAttribute($name) {
		$attr = $this->getAttributeNode($name);
		if (!is_null($attr)) {
			$attr = $attr->value;
		}
		return $attr;
	}
	public function getAttributeNode($name) {
		return $this->attributes->getNamedItem($name);
	}
	#public function getAttributeNodeNS($namespaceURI, $localname) {}
	#public function getAttributeNS($namespaceURI, $localname) {}
	public function getElementsByTagName($name) {
		$list = new DOMNodeList();
		$this->_searchTag($list, $this->childNodes, $name);
		return $list;
	}
	#public function getElementsByTagNameNS($namespaceURI, $localname) {}
	public function hasAttribute($name) {
		return isset($this->attributes->map[$name]);
	}	
	#public function hasAttributeNS($namespaceURI, $localname) {}
	public function removeAttribute($name) {
		return $this->attributes->removeName($name);
	}
	public function removeAttributeNode($oldnode) {
		return $this->attributes->removeNode($oldnode);
	}
	#public function removeAttributeNS($namespaceURI, $localName) {}
	public function setAttribute($name, $value) {
		if ($node = $this->attributes->getNamedItem($name)) {
			$node->set($value);
		} else {
			$attr = new DOMAttr($name, $value);
			$attr->ownerElement = $this;
			$attr->ownerDocument = $this->ownerDocument;
			$this->attributes->add($attr, $name);
		}
	}
	public function setAttributeNode($attr) {
		$name = $attr->name;
		$oldnode = $this->attributes->getNamedItem($name);
		$this->attributes->removeName($name);
		$this->attributes->add($attr, $name);
		return $oldnode;
	}
	#public function setAttributeNodeNS($attr) { return $this->setAttributeNode($attr); }
	#public function setAttributeNS($namespaceURI, $qualifiedName, $value) {}
	public function setIdAttribute($name, $isId) {
		$node = $this->attributes->getNamedItem($name);
		if (is_null($node)) throw new DOMException(DOM_NOT_FOUND);

		if ($isId) {
			foreach ($this->attributes->list as $attr) {
				$attr->isId = false;
			}
		}
		$node->isId = $idId;
	}
	public function setIdAttributeNode($attr, $idId) {
		$node = NULL;
		foreach ($this->attributes->list as $a) {
			if ($a->isSameNode($attr)) {
				$node = $a;
				break;
			}
		}
		if (is_null($node)) throw new DOMException(DOM_NOT_FOUND);

		if ($isId) {
			foreach ($this->attributes->list as $attr) {
				$attr->isId = false;
			}
		}
		$node->isId = $idId;
	}
	#public function setIdAttributeNS($namespaceURI, $localName, $isId) {}

}

