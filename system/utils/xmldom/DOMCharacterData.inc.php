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

class DOMCharacterData extends DOMNode {

	### MAGIC

	protected function _l() {
		$this->length = strlen($this->data);
		$this->nodeValue = $this->data;
	}

	### API

	public $data;
	public $length;

	public function appendData($data) {
		$this->data .= $data;
		$this->_l();
	}

	public function deleteData($offset, $count) {
		if ($count < 0) throw new DOMException(DOM_INDEX_SIZE_ERR);

		$r = '';
		$tail = $offset + $count;

		if ($offset > 0)
			$r .= substr($this->data, 0, $offset);

		if ($tail < strlen($this->data))
			$r .= substr($this->data, $tail);

		$this->data = $r;
		$this->_l();
	}

	public function insertData($offset, $data) {
		if ($count < 0) throw new DOMException(DOM_INDEX_SIZE_ERR);

		$r = '';

		if ($offset > 0)
			$r .= substr($this->data, 0, $offset);

		$r .= $data;

		if ($offset < strlen($this->data))
			$r .= substr($this->data, $offset);

		$this->data = $r;
		$this->_l();
	}

	public function replaceData($offset, $count, $data) {
		if ($count < 0) throw new DOMException(DOM_INDEX_SIZE_ERR);

		$r = '';
		$tail = $offset + $count;

		if ($offset > 0)
			$r .= substr($this->data, 0, $offset);

		$r .= $data;

		if ($tail < strlen($this->data))
			$r .= substr($this->data, $tail);

		$this->data = $r;
		$this->_l();
	}

	public function substringData($offset, $count) {
		if ($count < 0) throw new DOMException(DOM_INDEX_SIZE_ERR);
		return substr($this->data, $offset, $count);
	}
}

