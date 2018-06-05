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
 * A basic data bucket with a little bit of cleverness, for storing
 * Internet Media Type / MIME information.
 */
class InternetMediaType {
	private $type;
	private $subtype;
	private $qvalue;
	private $advertised;
	private $mapto;
	private $params = array();

	/**
	 * Creates a new InternetMediaType
	 * @param string $type the main type
	 * @param string $subtype
	 * @param float $qvalue
	 * @param bool $advertised
	 * @param InternetMediaType $mapto
	 */
	public function __construct($type, $subtype, $qvalue=1.0, $advertised=FALSE, $mapto=NULL) {
		if ($qvalue > 1.0) $qvalue = 1.0;
		if ($qvalue < 0.0) $qvalue = 0.0;

		if (is_string($mapto)) {
		} elseif (is_object($mapto) && ($mapto instanceof InternetMediaType)) {
			$mapto = $mapto->full_mime();
		} else {
			$mapto = NULL;
		}

		$this->type = $type;
		$this->subtype = $subtype;
		$this->qvalue = intval($qvalue * 1000);
		$this->advertised = !!$advertised;
		$this->mapto = $mapto;
	}

	/**
	 * Sets the value of a parameter on this InternetMediaType.
	 * @param string $name
	 * @param string $value
	 */
	public function set_param($name, $value) { if ($value === null) $this->rm_param($name); else $this->params[$name] = $value; }

	/**
	 * Gets the value of a parameter of this InternetMediaType.
	 * @param string $name
	 * @return string|NULL
	 */
	public function get_param($name) { if (isset($this->params[$name])) return $this->params[$name]; return NULL; }

	/**
	 * Removes a parameter from this InternetMediaType.
	 * @param string $name
	 */
	public function rm_param($name) { if (isset($this->params[$name])) unset($this->params[$name]); }

	/**
	 * Gets the main type.
	 * @return string
	 */
	public function type() { return $this->type; }

	/**
	 * Gets the subtype.
	 * @return string
	 */
	public function subtype() { return $this->subtype; }

	/**
	 * Gets the full MIME time (main type and subtype).
	 * @return string
	 */
	public function mime() { return $this->type . '/' . $this->subtype; }

	/**
	 * Gets the qvalue.
	 * @return float Float, with four digits of precision, in the range 0.000 to 1.000 (inclusive)
	 */
	public function qvalue() { return $this->qvalue / 1000.0; }

	/**
	 * Is this InternetMediaType advertised?
	 * @return bool
	 */
	public function advertised() { return $this->advertised; }

	/**
	 * Gets the mapped InternetMediaType, or NULL.
	 * @return InternetMediaType
	 */
	public function mapto() { return $this->mapto; }

	/**
	 * Is this InternetMediaType a wildcard?
	 * @return bool
	 */
	public function catchall()      { return $this->type == '*' && $this->subtype == '*'; }

	/**
	 * Is the subtype (but NOT the main type) a wildcard?
	 * @return bool
	 */
	public function type_catchall() { return $this->type != '*' && $this->subtype == '*'; }

	/**
	 * Gets the effective media type string for this InternetMediaType.
	 *
	 * This takes into account #mapto
	 *
	 * @return string
	 */
	public function effective_mime() {
		if ($this->mapto) return $this->mapto->effective_mime(); // FIXME: loops?
		else              return $this->full_mime();
	}

	/**
	 * Get the stringified internet media type.
	 * @param bool $include_qvalue TRUE=always, FALSE*=never, NULL=if not 1.000
	 * @return string
	 */
	public function full_mime($include_qvalue=FALSE) {
		$params = '';
		foreach ($this->params as $k=>$v) {
			$params .= ';'.$k.'='.$v;
		}
		if ($include_qvalue || (is_null($include_qvalue) && $this->qvalue != 1000)) {
			$params .= ';q='.$this->qvalue();
		}
		return $this->type . '/' . $this->subtype . $params;
	}

	/**
	 * If it's a good string, returns a new InternetMediaType object.
	 * Otherwise, dies.
	 *
	 * This conforms to RFC 2045 [section 5.1]
	 *
	 * @param string $string String to parse.
	 * @return InternetMediaType
	 */
	public static function parse($string) {
		// regular expressions
		$token = "[-!#$%&'*+.0-9A-Z^_`a-z{|}~]+";
		$quoted_string = '"(?:[^"\\\\\\015])+"';

		$type_pattern = "/^($token)\\/($token)$/";
		$param_pattern = "/^($token)=($token|$quoted_string)$/";

		// explode on ';', and extract the first chunk
		$paramaters = explode(';', $string);
		$full_type = array_shift($parameters);

		// check that the type part is gravy
		if (preg_match($type_pattern, $full_type, $matches)) {
			$type = $matches[1];
			$subtype = $matches[2];
		} else {
			throw new Exception("'$string' is not a valid media type: '$full_type' is not a valid 'type/subtype'");
		}

		// scan the parameters; grab a qvalue if there is one
		$qvalue = NULL;
		$params = array();
		foreach ($parameters as $parameter) {
			if (preg_match($param_pattern, $parameter, $matches)) {
				$attribute = $matches[1];
				$value = $matches[2];
				if ($attribute == 'q' && is_null($qvalue) && preg_match('/^(0(\.\d{0,3})?|1(\.0{0,3})?)$/', $value)) {
					$qvalue = $value;
				} else {
					$params[$attribute] = $value;
				}
			} else {
				throw new Exception("'$string' is not a valid media type: '$parameter' is not a valid parameter");
			}
		}

		// fallback if not otherwise specified
		if (is_null($qvalue)) $qvalue = 1.0;

		// construct the object, populate it, and return it
		$mime = new InternetMediaType($type, $subtype, $qvalue);
		foreach ($params as $k=>$v) {
			$mime->set_param($k, $v);
		}

		return $mime;
	}
}

