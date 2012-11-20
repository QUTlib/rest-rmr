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
 *   application/json q=1.0 [advertised,default]
 *   text/json        q=0.9
 *   text/x-json      q=0.9
 *   * / *            q=0.001
 */
class JSONRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'json',   1.0, TRUE),
				new InternetMediaType('text',        'json',   0.9),
				new InternetMediaType('text',        'x-json', 0.9),
				new InternetMediaType('*', '*', 0.001, FALSE, 'application/json'),
			),
			array(),
			array(),
			array('object', 'array')
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		$this->response_type($response, $t, 'ISO-8859-1', TRUE, TRUE); // override charset because I control it in the encoding process
		$this->response_language($response, 'en', FALSE, TRUE); // ???force language???
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
 *   text/yaml          q=1.0 [advertised,default]
 *   application/x-yaml q=1.0 [advertised]
 *   text/x-yaml        q=0.9
 *   application/yaml   q=0.9
 *   * / *              q=0.001
 */
class YAMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text',        'yaml',   1.0, TRUE),
				new InternetMediaType('application', 'x-yaml', 1.0, TRUE),
				new InternetMediaType('text',        'x-yaml', 0.9),
				new InternetMediaType('application', 'yaml',   0.9),
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/yaml'),
			),
			array(),
			array(),
			array('integer','double','boolean','NULL','string','array','object')
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		$this->response_type($response, $t, 'ISO-8859-1', TRUE, TRUE); // override charset because I control it in the encoding process
		$this->response_language($response, 'en', FALSE, TRUE); // ???force language???
		$response
			->body("%YAML 1.2\n---\n")
			->append( $this->_yaml_encode($m, '', '', '', false, false) );
	}

	protected function _yaml_encode($o, $p1, $pa, $pn, $inarray, $inhash) {
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
			if (preg_match('/^\s|\s$|[\000-\031\\\'"\177-\377]|^[+-]?\d+(\.\d*)?$/', $o)) {
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
						if ($inhash) {
							$string .= "${p1}\n";
							$px = $pa;
						} else {
							$px = $p1;
						}
						$first = false;
					} else {
						$px = $pa;
					}
					$string .= $this->_yaml_encode($v, "${px}- ", "${px}  ", "${px}  ", true, false);
				}
			} else {
				$first = true;
				foreach ($o as $k=>$v) {
					if ($first) {
						if ($inhash) {
							$string .= "${p1}\n";
							$px = $pn;
						} else {
							$px = $p1;
						}
						$first = false;
					} else {
						$px = $pn;
					}
					$string .= $this->_yaml_encode($v, "${px}${k}: ", $px, "${px}  ", false, true);
				}
			}
			return $string;
		case 'object':
			#$key = '!<!object> '.get_class($o);
			$key = '!<!object:'.get_class($o).'> '.spl_object_hash($o);
			$val = $this->arrayify($o);
			return $this->_yaml_encode(array($key=>$val), $p1, $pa, $pn, $inarray, $inhash);
		default:
			throw new Exception("Can't convert variable of type '$type'");
		}
		return "${p1}${o}\n";
	}

	protected function arrayify($o) {
		$a = (array)$o;
		$b = array();
		foreach ($a as $k=>$v) {
			$k = preg_replace('/^(\0[^\0]+\0|\*)/', '', $k);
			$b[$k] = $v;
		}
		return $b;
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
 *   application/xml    q=1.0 [advertised,default]
 *   text/xml           q=0.9
 *   * / *              q=0.001
 */
class XMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'xml', 1.0, TRUE),
				new InternetMediaType('text',        'xml', 0.9),
				new InternetMediaType('*', '*', 0.001, FALSE, 'application/xml'),
			),
			array(),
			array(),
			array('integer','double','boolean','NULL','string','array','object')
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		$this->response_type($response, $t, 'ISO-8859-1', TRUE, TRUE); // override charset because I control it in the encoding process
		$this->response_language($response, 'en', FALSE, TRUE); // ???force language???

		if (is_object($m) && ($m instanceof SimpleXMLElement)) {
			$response->body( $m->asXML() );
		} elseif (is_object($m) && ($m instanceof DOMDocument)) {
			$response->body( $m->saveXML() );
		} else {
			$response
				->body( '' )
				->append_line( '<?xml version="1.0" encoding="ISO-8859-1"?>' )
				->append_line( '<?xml-stylesheet href="/assets/generic-xml.xsl" type="text/xsl"?>' )
				->append_line( '<document xmlns="http://www.library.qut.edu.au/generic-xml/">' )
				->append( $this->_xml_encode($m) )
				->append_line( '</document>' );
		}

		if (defined('DEBUG') && DEBUG)
			$response->append_line('<!-- Represented by '.get_class($this).'-->');
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
			$allints = TRUE;
			$i = 0;
			foreach ($o as $k=>$v) {
				if ($k !== $i) {
					$allints = FALSE;
					break;
				}
				$i ++;
			}
			if ($allints) {
				$key = "index";
			} else {
				$type = "map";
				$key  = "key";
			}
			$string = "$p<$type>\n";
			foreach ($o as $k=>$v) {
				$k = $this->_quote($k);
				$string .= "$p  <item $key=\"$k\">\n";
				$string .= $this->_xml_encode($v, "$p    ");
				$string .= "$p  </item>\n";
			}
			$string .= "$p</$type>\n";
			return $string;
		case 'object':
			if ($o instanceof SimpleXMLElement) {
				$xml = $o->asXML();
				// strip the first xml declaration thingy
				if (preg_match('~<\?xml([^?]|\?[^>])+\?>[\r\n]*~i', $xml, $m)) {
					$header = $m[0];
					$xml = substr($xml, $header);
				}
				// fix padding
				$xml = str_replace("\n", "\n$p");
				return $xml;
			}
			// otherwise, not a SimpleXMLElement; use regular iteration
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

/**
 * A generic representer which will represent some objects as XHTML.
 *
 * Note: this is an experimental class, and is not guaranteed to
 *       work properly in all cases.
 *
 * Supported internet media types (MIMEs):
 *   application/xhtml+xml q=1.0 [advertised,default]
 *   application/xml       q=1.0 [advertised]
 *   text/html             q=0.5
 *   * / *                 q=0.001
 */
class XHTMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'xhtml+xml', 1.0, TRUE),
				new InternetMediaType('text',        'xml',       1.0, TRUE),
				new InternetMediaType('text',        'html',      0.5),
				new InternetMediaType('*', '*', 0.001, FALSE, 'application/xhtml+xml'),
			),
			array(),
			array(),
			array() // note: I'm overriding can_do_model myself
		);
	}

	public function can_do_model($m) {
		return (is_object($m) && ($m instanceof HTMLDocument))
		    or (is_object($m) && ($m instanceof SimpleXMLElement) && strtolower($m->getName()) == 'html')
		    or (is_object($m) && ($m instanceof DOMDocument) && $m->getElementsByTagName('html')->length > 0);
	}

	public function represent($m, $t, $c, $l, $response) {
		if ($m instanceof HTMLDocument) {
			if ($x = $m->encoding()) $this->response_type($response, $t, $x, TRUE, TRUE); // strict type, force charset
			else $this->response_type($response, $t, $c); // strict type, only set charset if recognised (i.e. never?)

			if ($x = $m->lang()) $this->response_language($response, $x, FALSE, TRUE); // force language
			#else $this->response_language($response, $l); // only set language if recognised (i.e. never?)

			$response->body( $m->xml() );
		} elseif ($m instanceof SimpleXMLElement) {
			$this->response_type($response, $t, $c); // strict type, only set charset if recognised (i.e. never?)
			#$this->response_language($response, $l, FALSE); // only set language if recognised (i.e. never?)
			$response->body( $m->asXML() );
		} else {
			if ($x = $m->xmlEncoding) {
				// override the requested/matched charset with that
				// specified in the document itself.
				$this->response_type($response, $t, $x, TRUE, TRUE); // strict type, force charset
			} else {
				// must be what was requested/matched
				$this->response_type($response, $t, $c); // strict type, only set charset if recognised (i.e. never?)
			}
			#$this->response_language($response, $l, FALSE);
			$response->body( $m->saveXML() );
		}
	}
}

/**
 * A generic representer which will represent some objects as HTML.
 *
 * Note: this is an experimental class, and is not guaranteed to
 *       work properly in all cases.
 *
 * Supported internet media types (MIMEs):
 *   text/html             q=1.0 [advertised,default]
 *   application/html      q=0.5
 *   * / *                 q=0.001
 */
class HTMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text',        'html', 1.0, TRUE),
				new InternetMediaType('application', 'html', 0.5),
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/html'),
			),
			array(),
			array(),
			array('object:HTMLDocument')
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		if ($x = $m->encoding()) $this->response_type($response, $t, $x, TRUE, TRUE); // strict type, force charset
		else $this->response_type($response, $t, $c); // strict type, only set charset if recognised (i.e. never?)

		if ($x = $m->lang()) $this->response_language($response, $x, FALSE, TRUE); // force language
		#else $this->response_language($response, $l); // only set language if recognised (i.e. never?)

		$response->body( $m->html() );
	}
}

