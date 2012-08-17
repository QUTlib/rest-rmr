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
 * NOTE: because the classes defined in this file exhibit catch-all
 *       behaviour (i.e. will attempt to handle as many requests as
 *       possible), they should be registered AFTER any more specific
 *       representers.
 *
 *       See: representations/zz_core-serialisation.php
 */

/**
 * A generic representer which will represent any Object or Array
 * as a JSON document.
 *
 * Supported internet media types (MIMEs):
 *   application/json q=1.0 [advertised]
 *   text/json        q=0.9
 *   text/x-json      q=0.9
 *   * / *            q=0.001
 */
class JSONRepresenter extends Representer {

	public function list_types() {
		return array(
			'application/json' => 1.0,
		);
	}

	public function can_do_model($m) {
		return is_object($m) || is_array($m);
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'application/json':
			// ideal
			return 1.0;
		case 'text/json':
		case 'text/x-json':
			// dodgy type, but we can do it
			return 0.9;
		case '*/*':
			return 0.001;
		default:
			// sorry no bananas
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('application/json');
		} else {
			$response->content_type($t['option']);
		}
		$response->body( json_encode($m) );
	}
}

/**
 * A generic representer which will represent any PHP type other
 * than "resource" as a YAML document.
 *
 * Note: this is an experimental class, and is not guaranteed to
 *       work properly in all cases.
 *
 * Supported internet media types (MIMEs):
 *   text/yaml          q=1.0 [advertised]
 *   application/x-yaml q=1.0 [advertised]
 *   text/x-yaml        q=0.9
 *   application/yaml   q=0.9
 *   * / *              q=0.001
 */
class YAMLRepresenter extends Representer {

	public function list_types() {
		return array(
			'text/yaml' => 1.0,
			'application/x-yaml' => 1.0,
		);
	}

	public function can_do_model($m) {
		switch (gettype($m)) {
		case 'integer':
		case 'double':
		case 'boolean':
		case 'NULL':
		case 'string':
		case 'array':
		case 'object':
			return true;
		default:
			return false;
		}
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'text/yaml':
		case 'application/x-yaml':
			// I can do it!
			return 1.0;
		case 'text/x-yaml':
		case 'application/yaml':
			// dodgy names, pretty sure they're still mine though
			return 0.9;
		case '*/*':
			return 0.001;
		default:
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('text/yaml');
		} else {
			$response->content_type($t['option']);
		}
		$response
			->body("%YAML 1.2\n---\n")
			->append( $this->_yaml_encode($m, '', '', false, false) );
	}

	protected function _yaml_encode($o, $p1, $pn, $inarray, $inhash) {
		switch ($type = gettype($o)) {
		case 'integer':
		case 'double':
			break;
		case 'boolean':
			$o = ($o ? 'true' : 'false');
			break;
		case 'NULL':
			$o = 'null';
			break;
		case 'string':
			if (preg_match('/^\s|\s$|[\000-\031\\\'"\177-\377]|^\d+(\.\d*)?$/', $o)) {
				$o = '"' . addcslashes($o, "\000..\031\"\\\177..\377") . '"';
			}
			break;
		case 'array':
			$allints = TRUE;
			$i = 0;
			foreach ($o as $k=>$v) {
				if ($k !== $i) {
					$allints = FALSE;
					break;
				}
				$i ++;
			}
			$string = '';
			if ($allints) {
				$first = true;
				foreach ($o as $k=>$v) {
					if ($first) {
						$string .= $p1;
						if ($inhash) $string .= "\n${pn}";
						$first = false;
					} else {
						$string .= $pn;
					}
					$string .= $this->_yaml_encode($v, "{$pn}- ", "${pn}  ", true, false);
				}
			} else {
				$first = true;
				foreach ($o as $k=>$v) {
					if ($first) {
						$string .= $p1;
						if ($inhash) $string .= "\n${pn}";
						$first = false;
					} else {
						$string .= $pn;
					}
					$string .= $this->_yaml_encode($v, "${pn}${k}: ", "${pn}  ", false, true);
				}
			}
			return $string;
		case 'object':
			$string = '';
			$first = true;
			foreach ($o as $k=>$v) {
				if ($first) {
					$string .= $p1;
					if ($inhash) $string .= "\n${pn}";
					$first = false;
				} else {
					$string .= $pn;
				}
				$string .= $this->_yaml_encode($v, "${pn}${k}: ", "${pn}  ", false, true);
			}
			return $string;
		default:
			throw new Exception("Can't convert variable of type '$type'");
		}
		return "${p1}${o}\n";
	}

}

/**
 * A generic representer which will represent any PHP type other
 * than "resource" as an XML document.
 *
 * Note: this is an experimental class, and is not guaranteed to
 *       work properly in all cases.
 *
 * Supported internet media types (MIMEs):
 *   application/xml    q=1.0 [advertised]
 *   text/xml           q=0.9
 *   * / *              q=0.001
 */
class XMLRepresenter extends Representer {

	public function list_types() {
		return array(
			'application/xml' => 1.0,
		);
	}

	public function can_do_model($m) {
		switch (gettype($m)) {
		case 'integer':
		case 'double':
		case 'boolean':
		case 'NULL':
		case 'string':
		case 'array':
		case 'object':
			return true;
		default:
			return false;
		}
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'application/xml':
			// I can do it!
			return 1.0;
		case 'text/xml':
			// dodgy name, pretty sure it's still mine though
			return 0.9;
		case '*/*':
			return 0.001;
		default:
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('application/xml');
		} else {
			$response->content_type($t['option']);
		}
		$response
			->body( '' )
			->append_line( '<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>' )
			->append_line( '<document xmlns="http://www.library.qut.edu.au/generic-xml/">' )
			->append( $this->_xml_encode($m) )
			->append_line( '</document>' );
	}

	protected function _quote($s) {
		#return htmlentities($s, ENT_QUOTES|ENT_XML1, 'ISO-8859-1');
		$r = '';
		//                                   1   2   3    4   5    6                   7
		while (strlen($s) && preg_match('/^(?:(<)|(>)|(")|(\')|(&)|([\\x{20}-\\x{7E}])|([\\x{00}-\\x{FF}]))/', $s, $m)) {
			    if ($m[1] != '') $r .= '&lt;';
			elseif ($m[2] != '') $r .= '&gt;';
			elseif ($m[3] != '') $r .= '&quot;';
			elseif ($m[4] != '') $r .= '&apos;';
			elseif ($m[5] != '') $r .= '&amp;';
			elseif ($m[6] != '') $r .= $m[6];
			else {
				// Urgh, any other cruft in here is a yucky byte. Since
				// I don't know the encoding of the original string, I'll
				// export them to XML on a byte-by-byte basis.
				$h = unpack('H*', $m[7]);
				$r .= '&#x' . strtoupper($h[1]) . ';';
			}
			$s = substr($s,1);
		}
		return $r;
	}

	protected function _xml_encode($o, $p='  ') {
		switch ($type = gettype($o)) {
		case 'integer':
		case 'double':
			break;
		case 'boolean':
			$o = ($o ? 'true' : 'false');
			break;
		case 'string':
			$o = $this->_quote($o);
			break;
		case 'NULL':
			return "$p<null/>\n";
		case 'array':
			$string = "$p<$type>\n";
			foreach ($o as $k=>$v) {
				$k = $this->_quote($k);
				$string .= "$p  <item index=\"$k\">\n";
				$string .= $this->_xml_encode($v, "$p    ");
				$string .= "$p  </item>\n";
			}
			$string .= "$p</$type>\n";
			return $string;
		case 'object':
			$c = $this->_quote(get_class($o));
			$h = $this->_quote(spl_object_hash($o));
			$string = "$p<$type classname=\"$c\" hash=\"$h\">\n";
			foreach ($o as $k=>$v) {
				$k = $this->_quote($k);
				$string .= "$p  <property name=\"$k\">\n";
				$string .= $this->_xml_encode($v, "$p    ");
				$string .= "$p  </property>\n";
			}
			$string .= "$p</$type>\n";
			return $string;
		default:
			throw new Exception("Can't convert variable of type '$type'");
		}
		return "$p<$type>$o</$type>\n";
	}

}

