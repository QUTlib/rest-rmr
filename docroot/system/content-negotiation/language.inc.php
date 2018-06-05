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
 * ContentLanguage information.
 */
class ContentLanguage {
	private $primarytag;
	private $subtags;
	private $qvalue;
	private $advertised;
	private $mapto;

	/**
	 * Creates a new ContentLanguage
	 * @param string $primarytag
	 * @param string[] $subtags
	 * @param float $qvalue
	 * @param bool $advertised
	 * @param ContentLanguage $mapto
	 */
	public function __construct($primarytag, $subtags=array(), $qvalue=1.0, $advertised=FALSE, $mapto=NULL) {
		$n = func_num_args();
		if ($n >= 2 && $n < 5 && is_numeric($subtags)) {
			// new ContentLanguage('en', 1.0, ...) # missing the subtags array
			$qvalue = func_get_arg(2);
			if ($n >= 3) $advertised = func_get_arg(3); else $advertised = FALSE;
			if ($n >= 4) $mapto      = func_get_arg(4); else $mapto      = NULL;
		}

		if ($qvalue > 1.0) $qvalue = 1.0;
		if ($qvalue < 0.0) $qvalue = 0.0;

		if (is_string($mapto)) {
		} elseif (is_object($mapto) && ($mapto instanceof ContentLanguage)) {
			$mapto = $mapto->language();
		} else {
			$mapto = NULL;
		}

		if (!is_array($subtags)) {
			$subtags = array();
		}

		$this->primarytag = $primarytag;
		$this->subtags = $subtags;
		$this->qvalue = intval($qvalue * 1000);
		$this->advertised = !!$advertised;
		$this->mapto = $mapto;
	}

	/**
	 * Gets the primary language tag.
	 * @return string
	 */
	public function primarytag() { return $this->primarytag; }

	/**
	 * Gets the subtags.
	 * @return string[]
	 */
	public function subtags() { return $this->subtags; }

	/**
	 * Gets the qvalue.
	 * @return float Float, with four digits of precision, in the range 0.000 to 1.000 (inclusive)
	 */
	public function qvalue() { return $this->qvalue / 1000.0; }

	/**
	 * Is this Language advertised?
	 * @return bool
	 */
	public function advertised() { return $this->advertised; }

	/**
	 * Gets the mapped Language, or NULL.
	 * @param Language
	 */
	public function mapto() { return $this->mapto; }

	/**
	 * Is this Language a wildcard?
	 * @return bool
	 */
	public function catchall()      { return $this->primarytag == '*'; }

	/**
	 * Gets the effective language string for this ContentLanguage.
	 *
	 * This takes into account #mapto
	 *
	 * @return string
	 */
	public function effective_language() {
		if ($this->mapto) return $this->mapto->effective_language(); // FIXME: loops?
		else              return $this->language();
	}

	/**
	 * Get the stringified language.
	 * @param $include_qvalue TRUE=always, FALSE*=never, NULL=if not 1.000
	 * @return string
	 */
	public function language($include_qvalue=FALSE) {
		$qvalue = '';
		if ($include_qvalue || (is_null($include_qvalue) && $this->qvalue != 1000)) {
			$qvalue .= ';q='.$this->qvalue();
		}
		$lang = $this->primarytag;
		foreach ($this->subtags as $sub) {
			$lang .= '-' . $sub;
		}
		return $lang . $qvalue;
	}

	/**
	 * If it's a good string, returns a new ContentLanguage object.
	 * Otherwise, dies.
	 *
	 * This conforms to RFC 1766
	 *
	 * @param string $string String to parse.
	 * @return Language
	 */
	public static function parse($string) {
		// regular expressions
		$tag = "[a-z]{1,8}";
		$token = "[-!#$%&'*+.0-9A-Z^_`a-z{|}~]+";
		$quoted_string = '"(?:[^"\\\\\\015])+"';

		$lang_pattern = "/^($tag)((?:-$tag)*)$/";
		$param_pattern = "/^($token)=($token|$quoted_string)$/";

		// explode on ';', and extract the first chunk
		$paramaters = explode(';', $string);
		$full_lang = array_shift($parameters);

		// check that the type part is gravy
		if (preg_match($lang_pattern, $full_lang, $matches)) {
			$primarytag = $matches[1];
			$subtags = explode('-', $matches[2]);
		} else {
			throw new Exception("'$string' is not a valid language: '$full_type' is not a valid 'type/subtype'");
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
					throw new Exception("'$string' is not a valid language: '$parameter' is not a valid qvalue and languages do not allow other parameters");
				}
			} else {
				throw new Exception("'$string' is not a valid language: '$parameter' is not a valid parameter");
			}
		}

		// fallback if not otherwise specified
		if (is_null($qvalue)) $qvalue = 1.0;

		// construct the object, populate it, and return it
		$lang = new ContentLanguage($primarytag, $subtags, $qvalue);
		return $lang;
	}
}

