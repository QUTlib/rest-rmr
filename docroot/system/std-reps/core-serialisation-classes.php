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
 */
class JSONRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'json',   1.0, TRUE),
				new InternetMediaType('text',        'json',   0.9),
				new InternetMediaType('text',        'x-json', 0.9),
			),
			array(),
			array(
				# Advertised, compatible charsets
				# Note: we actually use ASCII-7, and all these are supersets
				new CharacterSet('Windows-1252',     1.0, TRUE), # WhatWG says that ISO-8859-1 = Windows-1252
				new CharacterSet('UTF-8',            0.9, TRUE), # not entirely untrue..
				new CharacterSet('US-ASCII',         1.0, TRUE),
				new CharacterSet('ISO-8859-1',       1.0, TRUE), # mandated by RFC 2616, removed by RFC 7231
				new CharacterSet('*', 1.0, FALSE, 'Windows-1252'),
			),
			array('object', 'array')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c, TRUE, TRUE);
		$response->body( json_encode_v2($m) );
	}
}

/**
 * A generic representer which will represent any Object or Array
 * as a JSON sequence.  RFC 7464
 *
 * Supported internet media types (MIMEs):
 *   application/json-seq q=1.0 [advertised,default]
 *   text/json-seq        q=0.9
 */
class JSONSeqRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'json-seq', 1.0, TRUE),
				new InternetMediaType('text',        'json-seq', 0.9),
			),
			array(),
			array(
				new CharacterSet('UTF-8', 1.0, TRUE), # mandated by RFC 7464
				new CharacterSet('*', 1.0, FALSE, 'UTF-8'),
			),
			array('object', 'array')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c, TRUE, TRUE);
		$response->body( "\x1E" . json_encode_v2($m) . "\x0A" );
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
 */
class YAMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text',        'yaml',   1.0, TRUE),
				new InternetMediaType('application', 'x-yaml', 1.0, TRUE),
				new InternetMediaType('text',        'x-yaml', 0.9),
				new InternetMediaType('application', 'yaml',   0.9),
			),
			array(),
			array(
				# Advertised, compatible charsets
				# Note: we actually use ASCII-7, and all these are supersets
				new CharacterSet('Windows-1252',     1.0, TRUE), # WhatWG says that ISO-8859-1 = Windows-1252
				new CharacterSet('US-ASCII',         1.0, TRUE),
				new CharacterSet('UTF-8',            0.9, TRUE), # not entirely untrue..
				new CharacterSet('ISO-8859-1',       1.0, TRUE), # mandated by RFC 2616, removed by RFC 7231
				new CharacterSet('*', 1.0, FALSE, 'Windows-1252'),
			),
			array('integer','double','boolean','NULL','string','array','object')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c, TRUE, TRUE);
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
			if (count($o) == 0) {
				$o = '{}';
				break;
			}

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
 */
class XMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'xml', 1.0, TRUE),
				new InternetMediaType('text',        'xml', 0.9),
			),
			array(),
			array(),
			array('object:HTMLDocument', 'object:SimpleXMLElement', 'object:DOMDocument')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		if (($ua=Request::header('User-Agent')) && strpos($ua,'MSIE') !== FALSE) { 
			$response->add_header('X-UA-Compatible', 'IE=edge');
			$response->add_header('X-Content-Type-Options', 'nosniff');
		}
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
 * A generic representer which will represent some objects as XHTML.
 *
 * Note: this is an experimental class, and is not guaranteed to
 *       work properly in all cases.
 *
 * Supported internet media types (MIMEs):
 *   application/xhtml+xml q=1.0 [advertised,default]
 *   application/xml       q=1.0 [advertised]
 *   text/html             q=0.5
 */
class XHTMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'xhtml+xml', 1.0, TRUE),
				new InternetMediaType('text',        'xml',       1.0, TRUE),
				new InternetMediaType('text',        'html',      0.5),
			),
			array(),
			array(),
			array() // note: I'm overriding can_do_model myself
		);
	}

	public function can_do_model($model) {
		$m = $this->extract_model_datum($model);
		return (is_object($m) && ($m instanceof HTMLDocument))
		    or (is_object($m) && ($m instanceof SimpleXMLElement) && strtolower($m->getName()) == 'html')
		    or (is_object($m) && ($m instanceof DOMDocument) && $m->getElementsByTagName('html')->length > 0);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		if (($ua=Request::header('User-Agent')) && strpos($ua,'MSIE') !== FALSE) { 
			$response->add_header('X-UA-Compatible', 'IE=edge');
			$response->add_header('X-Content-Type-Options', 'nosniff');
		}
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
 */
class HTMLRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text',        'html', 1.0, TRUE),
				new InternetMediaType('application', 'html', 0.5),
			),
			array(),
			array(),
			array('object:HTMLDocument')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		if (($ua=Request::header('User-Agent')) && strpos($ua,'MSIE') !== FALSE) { 
			$response->add_header('X-UA-Compatible', 'IE=edge');
			$response->add_header('X-Content-Type-Options', 'nosniff');
		}

		if ($x = $m->encoding()) $this->response_type($response, $t, $x, TRUE, TRUE); // strict type, force charset
		else $this->response_type($response, $t, $c); // strict type, only set charset if recognised (i.e. never?)

		if ($x = $m->lang()) $this->response_language($response, $x, FALSE, TRUE); // force language
		#else $this->response_language($response, $l); // only set language if recognised (i.e. never?)

		$response->body( $m->html() );
	}
}

