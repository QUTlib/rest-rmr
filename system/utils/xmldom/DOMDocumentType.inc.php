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

class DOMDocumentType extends DOMNode {

	### MAGIC

	public $nodeType = XML_DOCUMENT_TYPE_NODE;
	public $nodeName = '!DOCTYPE';

	public function __construct() {
		parent::__construct();
		$this->entities = new DOMNamedNodeMap();
		$this->notations = new DOMNamedNodeMap();
	}

	public function _xml() {
		$xml = '<!DOCTYPE '.$this->name;
		if ($this->publicId) {
			$xml .= ' PUBLIC "' . $this->publicId . '"';
		}
		if ($this->systemId) {
			$xml .= ' "' . $this->systemId . '"';
		}
		$xml .= '>';
		return $xml;
	}

	### API

	public $publicId = NULL;
	public $systemId = NULL;
	public $name = NULL;
	public $entities = NULL;
	public $notations = NULL;
	public $internalSubset = NULL;
}

