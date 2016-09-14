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

/**
 * A model that refers to a file in the filesystem.
 *
 * Everything is lazy-initialised.
 *
 * API-identical to StringModel
 */
class FileModel {
	private $filename = NULL;
	private $doc = NULL;
	private $mtime = NULL;
	public function __construct($filename) {
		$this->filename = $filename;
	}
	public function doc() {
		if (!isset($this->doc)) {
			$this->doc = file_get_contents($this->filename);
		}
		return $this->doc;
	}
	public function mtime() {
		if (!isset($this->mtime)) {
			$this->mtime = filemtime($this->filename);
		}
		return $this->mtime;
	}
}

