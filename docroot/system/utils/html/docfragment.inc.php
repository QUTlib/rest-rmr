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

// Note: this isn't just a HTMLHierarchyNode because I want to
// inherit the useful HTMLElement add_* methods.
class HTMLDocumentFragment extends HTMLElement {
	public function __construct() {
		parent::__construct('');
	}

	public function html() {
		$s = '';
		foreach ($this as $child) {
			$s .= $child->html();
		}
		return $s;
	}

	public function xml() {
		$s = '';
		foreach ($this as $child) {
			$s .= $child->xml();
		}
		return $s;
	}

}

