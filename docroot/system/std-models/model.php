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
 * A generic data model.
 *
 * Contains a datum, without any restriction on type or nature,
 * and a collection of metadata describing that datum.
 */
class Model implements Serializable {
	protected $datum;
	protected $metadata;
	public function __construct($datum) {
		$this->datum = $datum;
		$this->metadata = new Metadata();
	}
	public function datum($val=NULL) {
		if (func_num_args() < 1) {
			return $this->datum;
		} else {
			$this->datum = $val;
			return $this;
		}
	}
	public function metadata() {
		return $this->metadata;
	}
	function serialize() {
		$array = array(
			'datum' => $this->datum,
			'metadata' => $this->metadata,
		);
		return serialize($array);
	}
	function unserialize($serialized) {
		$array = unserialize($serialized);
		$datum = $array['datum'];
		$metadata = $array['metadata'];
		$this->datum = $datum;
		$this->metadata = $metadata;
	}
}

/**
 * A set of metadata describing a Model.
 *
 * This implementation defines a semi-restrictive set of metadata.
 */
class Metadata implements Serializable {
	protected $fields;
	private $dcwrapper = NULL;
	public function __construct() {
		$this->fields = array();
	}

	/**
	 * Gets/sets an arbitrary field.
	 * No type safety or validation is performed.
	 * @return the current field value (getter), or $this (setter).
	 */
	public function field($key, $val=NULL) {
		if (func_num_args() < 2) {
			return (array_key_exists($key, $this->fields) ? $this->fields[$key] : NULL);
		} else {
			$this->fields[$key] = $val;
			return $this;
		}
	}

	/**
	 * Gets/sets current cacheability setting.
	 *
	 * Can set it to NULL (unknown), FALSE (no-cache), or the time() at which it expires.
	 * <ul>
	 *   <li>cache(NULL)</li>
	 *   <li>cache(FALSE) or cache('no-cache')</li>
	 *   <li>cache( time() + 500 )</li>
	 *   <li>cache( '+1 day' )</li>
	 * </ul>
	 *
	 * @return the current cacheability setting (getter), or $this (setter).
	 */
	public function cache($val=NULL) {
		if (func_num_args() < 1) {
			return (array_key_exists('cache', $this->fields) ? $this->fields['cache'] : NULL);
		} elseif (!$val || /*$val === TRUE ||*/ is_int($val) || is_float($val)) {
			$this->fields['cache'] = $val;
		} elseif ($val == 'no-cache') {
			$this->fields['cache'] = FALSE;
		} elseif (is_string($val)) {
			$this->fields['cache'] = strtotime($val);
		} else {
			throw new Exception("invalid value <".gettype($val)."> '$val'");
		}
		return $this;
	}
	/**
	 * Sets cacheability to false.
	 * @return $this
	 */
	public function nocache() {
		$this->fields['cache'] = FALSE;
		return $this;
	}

	/**
	 * Gets/sets the last modified date.
	 * @return the last modified date (getter), or $this (setter).
	 */
	public function last_modified($val=NULL) {
		if (func_num_args() < 1) {
			return (array_key_exists('last-modified', $this->fields) ? $this->fields('last-modified') : NULL);
		} elseif ($val === NULL || is_int($val) || is_float($val)) {
			$this->fields['last-modified'] = $val;
		} elseif (is_string($val)) {
			$this->fields['last-modified'] = strtotime($val);
		} else {
			throw new Exception("invalid value <".gettype($val)."> '$val'");
		}
		return $this;
	}

	/**
	 * Extracts the Dublin Core set from this metadata.
	 */
	public function dublincore() {
		if (!isset($this->dcwrapper)) {
			$this->dcwrapper = new DCMetadata($this);
		}
		return $this->dcwrapper;
	}

	public function serialize() {
		$f = $this->fields;
		ksort($f);
		return serialize($f);
	}
	public function unserialize($serialized) {
		$this->fields = unserialize($serialized);
	}
}

/**
 * Dublin Core metadata wrapper for Metadata object.
 */
class DCMetadata {
	static $fieldnames = array(
		'contributor', 'coverage', 'creator', 'date', 'description', 'format',
		'identifier', 'language', 'publisher', 'relation', 'rights', 'source',
		'subject', 'title', 'type',
	);

	private $metadata;
	public function __construct($metadata) {
		$this->metadata = $metadata;
	}

	protected function _wrap($field, $args=NULL) {
		if (!$args) {
			return $this->metadata->field($field);
		} else {
			$this->metadata->field($field, $args[0]);
			return $this;
		}
	}

	public function contributor($val=NULL) {
		return $this->_wrap('dc.contributor', func_get_args());
	}

	public function coverage($val=NULL) {
		return $this->_wrap('dc.coverage', func_get_args());
	}

	public function creator($val=NULL) {
		return $this->_wrap('dc.creator', func_get_args());
	}

	public function date($val=NULL) {
		// FIXME: should I enforce ISO 8601 here?
		return $this->_wrap('dc.date', func_get_args());
	}

	public function description($val=NULL) {
		return $this->_wrap('dc.description', func_get_args());
	}

	public function identifier($val=NULL) {
		// FIXME: should I enforce RFC 3986/1738/3305 here?
		return $this->_wrap('dc.identifier', func_get_args());
	}

	public function publisher($val=NULL) {
		return $this->_wrap('dc.publisher', func_get_args());
	}

	public function relation($val=NULL) {
		// ???
		return $this->_wrap('dc.relation', func_get_args());
	}

	public function rights($val=NULL) {
		return $this->_wrap('dc.rights', func_get_args());
	}

	public function source($val=NULL) {
		return $this->_wrap('dc.source', func_get_args());
	}

	public function subject($val=NULL) {
		return $this->_wrap('dc.subject', func_get_args());
	}

	public function title($val=NULL) {
		return $this->_wrap('dc.title', func_get_args());
	}

	public function type($val=NULL) {
		return $this->_wrap('dc.type', func_get_args());
	}

	/* WARNING: this is a representation-specific datum */
	public function format($val=NULL) {
		return $this->_wrap('dc.format', func_get_args());
	}

	/* WARNING: this is (mostly) a representation-specific datum */
	public function language($val=NULL) {
		return $this->_wrap('dc.language', func_get_args());
	}

	/**
	 * Gets an RDF XML fragment encoding this DC metadata.
	 * If no fields are defined, returns an empty string.
	 */
	public function xml_fragment($about) {
		$xml = '';
		foreach (DCMetadata::$fieldnames as $fn) {
			if ($x = $this->_wrap("dc.$fn")) {
				$x = htmlspecialchars($x);
				$xml .= "    <dc:$fn>$x</dc:$fn>\n";
			}
		}
		if ($xml) {
			return <<<XML
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <rdf:Description about="$about">
$xml
  </rdf:Description>
</rdf:RDF>
XML;
		} else {
			return '';
		}
	}
}

