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
 * Character Set information.
 */
class CharacterSet {
	private $charset;
	private $qvalue;
	private $advertised;
	private $mapto;

	public function __construct($charset, $qvalue=1.0, $advertised=FALSE, $mapto=NULL) {
		if ($qvalue > 1.0) $qvalue = 1.0;
		if ($qvalue < 0.0) $qvalue = 0.0;

		if (is_string($mapto)) {
		} elseif (is_object($mapto) && ($mapto instanceof CharacterSet)) {
			$mapto = $mapto->charset();
		} else {
			$mapto = NULL;
		}

		$this->charset = $charset;
		$this->qvalue = intval($qvalue * 1000);
		$this->advertised = !!$advertised;
		$this->mapto = $mapto;
	}

	public function qvalue() { return $this->qvalue / 1000.0; }
	public function advertised() { return $this->advertised; }
	public function mapto() { return $this->mapto; }

	public function catchall()      { return $this->charset == '*'; }

	/**
	 * Gets the effective media type string for this CharacterSet.
	 *
	 * This takes into account #mapto
	 */
	public function effective_charset() {
		if ($this->mapto) return $this->mapto;
		else              return $this->charset();
	}

	/**
	 * @param $include_qvalue TRUE=always, FALSE*=never, NULL=if not 1.000
	 */
	public function charset($include_qvalue=FALSE) {
		$qvalue = '';
		if ($include_qvalue || (is_null($include_qvalue) && $this->qvalue != 1000)) {
			$qvalue .= ';q='.$this->qvalue();
		}
		return $this->charset . $qvalue;
	}

	/**
	 * If it's a good string, returns a new CharacterSet object.
	 * Otherwise, dies.
	 *
	 * This conforms to RFC 2616 [section 14.2]
	 */
	public static function parse($string) {
		// regular expressions
		$token = "[-!#$%&'*+.0-9A-Z^_`a-z{|}~]+";
		$quoted_string = '"(?:[^"\\\\\\015])+"';

		$charset_pattern = "/^($token)$/";
		$param_pattern = "/^($token)=($token|$quoted_string)$/";

		// explode on ';', and extract the first chunk
		$paramaters = explode(';', $string);
		$charset = array_shift($parameters);

		// check that the type part is gravy
		if (!preg_match($charset_pattern, $charset)) {
			throw new Exception("'$string' is not a valid character set: '$charset' is not a valid token");
		}

		// scan the parameters; grab a qvalue if there is one
		$qvalue = NULL;
		foreach ($parameters as $parameter) {
			if (preg_match($param_pattern, $parameter, $matches)) {
				$attribute = $matches[1];
				$value = $matches[2];
				if ($attribute == 'q' && is_null($qvalue) && preg_match('/^(0(\.\d{0,3})?|1(\.0{0,3})?)$/', $value)) {
					$qvalue = $value;
				} else {
					throw new Exception("'$string' is not a valid character set: '$parameter' is not a valid qvalue and character sets do not allow other parameters");
				}
			} else {
				throw new Exception("'$string' is not a valid character set: '$parameter' is not a valid parameter");
			}
		}

		// fallback if not otherwise specified
		if (is_null($qvalue)) $qvalue = 1.0;

		// construct the object, populate it, and return it
		$cs = new CharacterSet($charset, $qvalue);
		return $cs;
	}
}

