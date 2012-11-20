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
 * Provides simple mechanisms for registering internet media types and
 * representing PHP values / objects.
 */
abstract class BasicRepresenter extends Representer {

	/**#@+ @ignore */
	private $types = array();
	private $charsets = array();
	private $languages = array();
	private $all_models = FALSE;
	private $model_types = NULL;
	private $model_classes = NULL;
	/**#@-*/

	/**
	 * Creates a new BasicRepresenter object.
	 *
	 * $types should be an array of InternetMediaType objects, or media type strings.
	 * $languages should be an array of ContentLanguage objects, or language strings.
	 * $charsets should be an array of CharacterSet objects, or character set strings.
	 *
	 * $models should be either:
	 * - TRUE, if this representer can do any PHP value; or
	 * - an array of strings, either:
	 *   - the type of a value supported (as per gettype()), or
	 *   - a description of the classname supported (e.g. 'object:ClassName')
	 *
	 * @param array $types
	 * @param array $languages
	 * @param array $charsets
	 * @param mixed $models
	 */
	public function __construct($types, $languages, $charsets, $models) {
		foreach ($types as $t) {
			if (is_object($t) && ($t instanceof InternetMediaType)) {
			} elseif (is_string($t)) {
				$t = InternetMediaType::parse($t);
			} else {
				throw new Exception("not an internet media type: '$t'");
			}
			$this->types[ strtolower($t->mime()) ] = $t;
		}
		foreach ($languages as $l) {
			if (is_object($l) && ($l instanceof ContentLanguage)) {
			} elseif (is_string($l)) {
				$l = ContentLanguage::parse($l);
			} else {
				throw new Exception("not a language: '$l'");
			}
			$this->languages[ strtolower($l->language()) ] = $l;
		}
		foreach ($charsets as $c) {
			if (is_object($c) && ($c instanceof CharacterSet)) {
			} elseif (is_string($c)) {
				$c = CharacterSet::parse($c);
			} else {
				throw new Exception("not a character set: '$c'");
			}
			$this->charsets[ strtolower($c->charset()) ] = $c;
		}

		if ($models === TRUE) {
			$this->all_models = TRUE;
		} else {
			$datatypes = array();
			$classnames = array();
			if (!is_array($models)) $models = array($models);
			foreach ($models as $m) {
				if (preg_match('/^(?:(integer|double|boolean|NULL|string|array|object|resource)|object:(\S+))$/', $m, $x)) {
					if ($x[1]) {
						$datatypes[$x[1]] = TRUE;
					} else {
						$classnames[] = $x[2];
					}
				} else {
					// assume it's a classname
					$classnames[] = $m;
				}
			}
			$this->model_types = $datatypes;
			$this->model_classes = $classnames;
		}
	}

	public function list_types() {
		$result = array();
		foreach ($this->types as $t) {
			if ($t->advertised())
				$result[ $t->mime() ] = $t->qvalue();
		}
		return $result;
	}

	public function list_charsets() {
		$result = array();
		foreach ($this->charsets as $c) {
			if ($c->advertised())
				$result[ $c->charset() ] = $c->qvalue();
		}
		return $result;
	}

	public function list_languages() {
		$result = array();
		foreach ($this->languages as $l) {
			if ($l->advertised())
				$result[ $l->language() ] = $l->qvalue();
		}
		return $result;
	}

	public function can_do_model($model) {
		if ($this->all_models) return TRUE;

		$modeltype = gettype($model);
		if (isset($this->model_types[$modeltype])) {
			return TRUE;
		} elseif (is_object($model)) {
			foreach ($this->model_classes as $klass) {
				if ($model instanceof $klass) return TRUE;
			}
			return FALSE;
		} else {
			return FALSE;
		}
	}

	public function preference_for_type($t) {
		if (count($this->types) == 0) return 1.0;
		$t = strtolower($t);
		if (isset($this->types[$t])) {
			return $this->types[$t]->qvalue();
		}
		return 0.0;
	}

	public function preference_for_charset($c) {
		if (count($this->charsets) == 0) return 1.0;
		$c = strtolower($c);
		if (isset($this->charsets[$c])) {
			return $this->charsets[$c]->qvalue();
		}
		return 0.0;
	}

	public function preference_for_language($l) {
		if (count($this->languages) == 0) return 1.0;
		$l = strtolower($l);
		if (isset($this->languages[$l])) {
			return $this->languages[$l]->qvalue();
		}
		return 0.0;
	}

	/**
	 * Sets the response Content-Type string ($t), with optional charset ($c).
	 *
	 * Throws an exception if I don't have a type for $t, and $strict is TRUE.
	 * Sets it anyway, if $strict is FALSE and $force is TRUE.
	 *
	 * Sets unrecognised charsets if $force is TRUE, irrsepective of $strict.
	 *
	 * WARNING: be careful if this representer was constructed without any
	 * types, as $strict will cause it to _always_ fail.
	 *
	 * @param string $_t content-type
	 * @param string $_c charset
	 * @param boolean $strict if TRUE (default) throws exception for unknown content-type
	 * @param boolean $force if given and TRUE sets the content-type and/or charset even if unknown
	 */
	protected function response_type($response, $_t, $_c, $strict=TRUE, $force=FALSE) {
		$t = strtolower($_t);
		$c = strtolower($_c);
		if (isset($this->types[$t])) {
			$mime = $this->types[$t];
			while (($mapto = $mime->mapto()) && isset($this->types[strtolower($mapto)])) {
				// FIXME: if there's a mapto, but it's invalid, the following
				// charset thing will have no effect.
				$mime = $this->types[strtolower($mapto)];
			}
			// if we have a matching character set, poke it onto the MIME type
			// FIXME: is this the right thing to do?
			if (isset($this->charsets[$c])) {
				$mime = clone $mime;
				$mime->set_param('charset', $this->charsets[$c]->charset());
			} elseif ($force) {
				$mime = clone $mime;
				$mime->set_param('charset', $_c);
			}
			// note: effective_mime is only required in the case there's an
			// invalid mapto somewhere
			$response->content_type( $mime->full_mime() );
			return;
		}
		if ($strict) {
			throw new Exception("unrecognised type '$_t'");
		} elseif ($force) {
			$response->content_type( $_t.';charset='.$_c );
		}
	}

	/**
	 * Sets the response Content-Language string ($l).
	 *
	 * Throws an exception if I don't have a language for $l, and $strict is TRUE.
	 * Sets it anyway, if $strict is FALSE and $force is TRUE.
	 *
	 * @param string $_l language
	 * @param boolean $strict if TRUE (default) throws exception for unknown language
	 * @param boolean $force if given and TRUE sets the language even if unknown (only has effect if $strict is FALSE)
	 */
	protected function response_language($response, $_l, $strict=TRUE, $force=FALSE) {
		$l = strtolower($_l);
		if (isset($this->languages[$l])) {
			$lang = $this->languages[$l];
			$response->content_language( $lang->language() );
			return;
		}
		if ($strict) {
			throw new Exception("unrecognised language '$_l'");
		} elseif ($force) {
			$response->content_language( $_l );
		}
	}

}

