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

class HTMLTextNode extends HTMLHierarchyNode {
	protected $escaped_value = '';
	protected $unescaped_value = '';

	public function __construct($value=NULL, $is_escaped=FALSE) {
		if ($value) {
			if ($is_escaped) {
				if (($p = strpos($value, '<')) !== FALSE) {
					throw new Exception("escaped text cannot contain html tags (found '<' at $p)");
				}
				$this->escaped_value = $value;
				$this->unescaped_value = html_entity_decode($value);
			} else {
				$this->escaped_value = htmlentities($value);
				$this->unescaped_value = $value;
			}
		}
	}

	public function value($v=NULL) {
		if (func_num_args() > 0) {
			$this->escaped_value = htmlentities($v);
			$this->unescaped_value = $v;
			return $this;
		} else {
			return $this->unescaped_value;
		}
	}

	public function html_value($v=NULL) {
		if (func_num_args() > 0) {
			$this->escaped_value = $value;
			$this->unescaped_value = html_entity_decode($value);
			return $this;
		} else {
			return $this->escaped_value;
		}
	}

	public function html() { return $this->escaped_value; }
	public function xml() { return $this->escaped_value; }

}

