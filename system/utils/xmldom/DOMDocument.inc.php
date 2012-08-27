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

class DOMDocument extends DOMNode {

	### MAGIC

	public function _xml() {
		$version = 'version="' . ($this->xmlVersion ? $this->xmlVersion : '1.0') . '"';
		$encoding = ($this->xmlEncoding ? 'encoding="'.$this->xmlEncoding.'"' : '');
		$standalone = 'standalone="' . ($this->xmlStandalone ? 'yes' : 'no') . '"';

		$string = "<?xml $version $encoding $standalone ?".">\n";
		foreach ($this->childNodes as $node) {
			$string .= $node->_xml();
		}
		return $string;
	}

	public $nodeType = XML_DOCUMENT_NODE;

	private $classmap = array();

	private function _setup($node) {
		$node->ownerDocument = $this;
		return $node;
	}

	private function _searchId($list, $id) {
		foreach ($list as $node) {
			if ($node instanceof DOMElement) {
				foreach ($node->attributes->list as $a) {
					if ($a->isId() && $a->value == $id) {
						// that's the guy!
						return $node;
					}
				}
			}
			if ($node->hasChildNodes()) {
				// it ain't him, check his family
				$result = $this->_searchId($node->childNodes, $id);
				if (!is_null($result)) {
					// it's his kid!
					return $result;
				}
			}
		}
		// get outta here
		return NULL;
	}

	private function _searchTag(&$nodes, $list, $name) {
		foreach ($list as $node) {
			if (($node instanceof DOMElement) && $node->tagName == $name)
				$nodes->append($node);
			if ($node->hasChildNodes()) {
				$this->_searchTag($nodes, $node->childNodes, $name);
			}
		}
	}

	private function _classname($baseclass) {
		if (isset($this->classmap[$baseclass]))
			return $this->classmap[$baseclass];
		return $baseclass;
	}

	### API

	public $actualEncoding = NULL;
	public $config = NULL;
	public $doctype = NULL;
	public $documentElement = NULL;
	public $documentURI = NULL;
	public $encoding = NULL;
	public $formatOutput = NULL;
	public $implementation = NULL;
	public $preserveWhitespace = TRUE;
	public $recover = NULL;
	public $resolveExternals = NULL;
	public $standalone = NULL;
	public $strictErrorChecking = TRUE;
	public $substituteEntities = NULL;
	public $validateOnParse = FALSE;
	public $version = NULL;
	public $xmlEncoding = NULL;
	public $xmlStandalone = NULL;
	public $xmlVersion = NULL;

	public function __construct($version=NULL, $encoding=NULL) {
		parent::__construct();
		$this->version = $version;
		$this->encoding = $encoding;
	}
	public function createAttribute($name) {
		$klass = $this->_classname('DOMAttr');
		return $this->_setup( new $klass($name) );
	}
	#public function createAttributeNS($namespaceURI, $qualifiedName) {}
	public function createCDATASection($data) {
		$klass = $this->_classname('DOMCdataSection');
		return $this->_setup( new $klass($data) );
	}
	public function createComment($data) {
		$klass = $this->_classname('DOMComment');
		return $this->_setup( new $klass($data) );
	}
	public function createDocumentFragment() {
		$klass = $this->_classname('DOMDocumentFragment');
		return $this->_setup( new $klass() );
	}
	public function createElement($name, $value=NULL) {
		$klass = $this->_classname('DOMElement');
		return $this->_setup( new $klass($name, $value) );
	}
	#public function createElementND($namespaceURI, $qualifiedName, $value=NULL) {}
	public function createEntityReference($name) {
		$klass = $this->_classname('DOMEntityReference');
		return $this->_setup( new $klass($name) );
	}
	public function createProcessingInstruction($target, $data=NULL) {
		$klass = $this->_classname('DOMProcessingInstruction');
		return $this->_setup( new $klass($target, $data) );
	}
	public function createTextNode($content) {
		$klass = $this->_classname('DOMText');
		return $this->_setup( new $klass($content) );
	}
	public function getElementById($elementId) {
		return $this->_searchId($this->childNodes, $elementId);
	}
	public function getElementsByTagName($name) {
		$list = new DOMNodeList();
		$this->_searchTag($list, $this->childNodes, $name);
		return $list;
	}
	#public function getElementsByTagNameNS($namespaceURI, $localName) {}
	#public function importNode($importedNode, $deep=NULL) {}
	#public function load($filename, $options=0) {}
	#public function loadHTML($source) {}
	#public function loadHTMLFile($filename) {}
	#public function loadXML($source, $options=0) {}
	public function normalizeDocument() {}
	public function registerNodeClass($baseclass, $extendedclass) {
		$this->classmap[$baseclass] = $extendedclass;
	}
	#public function relaxNGValidate($filename) {}
	#public function relaxNGValidateSource($source) {}
	#public function save($filename, $options=NULL) {}
	#public function saveHTML($node=NULL) {}
	#public function saveHTMLFile($filename) {}
	public function saveXML($node=NULL, $options=NULL) {
		return $this->_xml();
	}
	#public function schemaValidate($filename) {}
	#public function schemaValidateSource($source) {}
	#public function validate() {}
	#public function xinclude($options=NULL) {}
}

