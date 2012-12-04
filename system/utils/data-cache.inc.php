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
 * A thing what can save data across requests and sessions.
 * It knows about APC, and one day might know about memcache.
 */
class DataCache {
	const CACHE_APC      = 1;
	const CACHE_MEMCACHE = 2;
	const CACHE_TMPFILE  = 0;

	private $method = DataCache::CACHE_TMPFILE;
	private $tmpfilename = NULL;

	private $id = NULL;
	private $loaded = FALSE;
	private $data = NULL;
	private $default_data = NULL;

	public function __construct($id, $default_data=NULL) {
		$this->id = $id;
		$this->default_data = $default_data;

		if (extension_loaded('apc') && ini_get('apc.enabled')) {
			$this->method = DataCache::CACHE_APC;			
#		} elseif (extension_loaded('memdata')) {
#			$this->method = DataCache::CACHE_MEMCACHE;
		} else {
			#$this->tmpfilename = tempnam('/tmp', $id); // idiot Matty
			$this->tmpfilename = '/tmp/datacache-'.$id.'.dat';
		}
	}

	public function load() {
		switch ($this->method) {
		case DataCache::CACHE_APC:
			$data = apc_fetch($this->id, $joy);
			if ($joy) {
				$this->loaded = TRUE;
				$this->data = $data;
			} else {
				return FALSE;
			}
			break;
		case DataCache::CACHE_TMPFILE:
			if (is_file($this->tmpfilename)) {
				$data = file_get_contents($this->tmpfilename);
				$this->loaded = TRUE;
				$this->data = unserialize($data);
			} else {
				return FALSE;
			}
			break;
		default:
			throw new Exception("invalid cache method {$this->method}");
		}
		return TRUE;
	}

	public function save() {
		// todo: assert( loaded ) ?
		switch ($this->method) {
		case DataCache::CACHE_APC:
			return apc_store($this->id, $this->cache);
		case DataCache::CACHE_TMPFILE:
			$data = serialize($this->data);
			$result = file_put_contents($this->tmpfilename, $data);
			return ($result !== FALSE);
		default:
			throw new Exception("invalid cache method {$this->method}");
		}
	}

	/**
	 * Gets (or sets) the cached data.
	 */
	public function data($value=NULL) {
		// set (and save) data
		if (func_num_args() > 0) {
			$this->data = $value;
			$this->save();
		// (maybe) load data, with fallback
		} elseif (! $this->loaded) {
			if (! $this->load()) {
				$this->data = $this->default_data;
			}
		}
		return $this->data;
	}
}

