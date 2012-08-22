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

class DOMText extends DOMCharacterData {

	### MAGIC

	public $nodeType = XML_TEXT_NODE;
	public $nodeName = '#TEXT';

	protected function _l() {
		parent::_l();
		$this->wholeText = $this->data;
	}

	public function _xml() {
		return $this->wholeText;
	}

	### API

	public $wholeText;

	public function __construct($value=NULL) {
		parent::__construct();
		$this->data = (string)$value;
		$this->_l();
	}

	public function isWhitespaceInElementContent() {
		return preg_match('/^\s*$/', $this->text);
	}

	public function splitText($offset) {
		$before = '' . substr($this->data, 0, $offset);
		if ($this->parentNode) {
			$after = '' . substr($this->data, $offset);
			$newnode = new DOMCharacterData($after);
			$this->parentNode->insertAfter($newnode, $this);
		}
		$this->data = $before;
		$this->_l();
	}

}

