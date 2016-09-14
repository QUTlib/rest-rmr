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

Application::register_class('Model',    SYSDIR.'/std-models/model.php');
Application::register_class('Metadata', SYSDIR.'/std-models/model.php');


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
				switch ($m) {
				case 'integer':
				case 'double':
				case 'boolean':
				case 'NULL':
				case 'string':
				case 'array':
				case 'object':
				case 'resource':
					$datatypes[$m] = TRUE;
					break;
				default:
					if (strpos($m, 'object:') === 0) {
						$classnames[] = substr($m,7);
					} else {
						// assume it's a classname
						$classnames[] = $m;
					}
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

		$model = $this->extract_model_datum($model);
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

	/** Gets the first one I define that isn't in $all */
	protected function first_language_excluding($all) {
		foreach ($this->languages as $l) {
			if (!array_key_exists($l->language(), $all)) {
				return $l;
			}
		}
		return FALSE;
	}

	/** Gets the first one I define that isn't in $all */
	protected function first_charset_excluding($all) {
		foreach ($this->charsets as $c) {
			if (!array_key_exists($c->charset(), $all)) {
				return $c;
			}
		}
		return FALSE;
	}

	/** Gets the first one I define that isn't in $all */
	protected function first_type_excluding($all) {
		foreach ($this->types as $t) {
			if (!array_key_exists($t->full_mime(), $all)) {
				return $t;
			}
		}
		return FALSE;
	}
	protected function first_subtype_excluding($all, $type) {
		foreach ($this->types as $t) {
			if ($t['type'] == $type && !array_key_exists($t->full_mime(), $all)) {
				return $t;
			}
		}
		return FALSE;
	}

	public function preference_for_type($t, $all) {
		if (count($this->types) == 0) return 1.0;
#		if ($t['media-type']['parameters']) {
#			// FIXME: canonicalise parameters, so string matching will work (?)
#		} else {
			$type = strtolower($t['media-range']);
#		}
		if (isset($this->types[$type])) {
			return $this->types[$type]->qvalue();
		}

		if ($t['media-type']['subtype'] == '*') {
			if (($type = $t['media-type']['type']) == '*') {
				// Client accepts anything. Give them
				// a good one.
				if ($first_type = $this->first_type_excluding($all)) {
					return array($first_type->qvalue(), $first_type->full_mime());
				}
			} else {
				// Client accepts any subtype. Give them
				// a good one.
				if ($first_type = $this->first_subtype_excluding($all, $type)) {
					return array($first_type->qvalue(), $first_type->full_mime());
				}
			}
		}

		return 0.0;
	}

	public function preference_for_charset($c, $all) {
		if (count($this->charsets) == 0) return 1.0;
		$c = strtolower($c);
		if (isset($this->charsets[$c])) {
			return $this->charsets[$c]->qvalue();
		}
		// The special value "*" ... matches every character set
		// which is not mentioned elsewhere in the field.
		if ($c == '*' && ($chst = $this->first_charset_excluding($all))) {
			return array($chst->qvalue(), $chst->charset());
		}
		return 0.0;
	}

	public function preference_for_language($l, $all) {
		if (count($this->languages) == 0) return 1.0;
		$l = strtolower($l['language-range']);
		// A language-range ($l) matches a language-tag ($this->languages)
		// if it exactly equals the tag...
		if (isset($this->languages[$l])) {
			return $this->languages[$l]->qvalue();
		}
		// ... or if it exactly equals a prefix of the tag such that the
		// first tag character following the prefix is "-".
		$p = $l.'-';
		$n = strlen($p);
		foreach ($this->languages as $tag=>$obj) {
			if (substr($tag, 0, $n) == $p) {
				return array($obj->qvalue(), $obj->language());
			}
		}
		// The special range "*" ... matches every tag not matched by any
		// other range present in the Accept-Language field.
		if ($l == '*' && ($lang = $this->first_language_excluding($all))) {
			return array($lang->qvalue(), $lang->language());
		}
		return 0.0;
	}

	public function represent($m, $t, $c, $l, $response) {
		$model = $this->extract_model_datum($m);
		$meta  = $this->extract_model_metadata($m);
		if ($cache = $meta->cache()) {
			$response->cache(date('r',$cache));
		} elseif ($cache === FALSE) {
			$response->nocache();
		}
		return $this->rep($model, $meta, $t, $c, $l, $response);
	}

	abstract public function rep($model, $metadata, $type, $charset, $language, $response);

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
		$t = strtolower($_t['media-range']);
		$c = strtolower($_c);
		if (isset($this->types[$t])) {
			$mime = $this->types[$t];
			while ($mapto = $mime->mapto()) {
				if (isset($this->types[strtolower($mapto)])) {
					$mime = $this->types[strtolower($mapto)];
				} else {
					throw new Exception($mime->full_mime()." maps to invalid type '$mapto'");
				}
			}
			// if we have a matching character set, poke it onto the MIME type
			if (isset($this->charsets[$c])) {
				$mime = clone $mime;
				$mime->set_param('charset', $this->charsets[$c]->effective_charset());
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
			throw new Exception("unrecognised type '{$_t['media-range']}'");
		} elseif ($force) {
			$response->content_type( $_t['media-range'].';charset='.$_c );
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
		$l = strtolower($_l['language-range']);
		if (isset($this->languages[$l])) {
			$lang = $this->languages[$l];
			$response->content_language( $lang->effective_language() );
			return;
		}
		if ($strict) {
			throw new Exception("unrecognised language '$_l'");
		} elseif ($force) {
			$response->content_language( $_l );
		}
	}

	/**
	 * Unwraps the datum from a Model.
	 * If $model is not a Model, returns it unmodified.
	 */
	public function extract_model_datum($model) {
		$class = 'Model';
		if (is_object($model) && $model instanceof $class) {
			return $model->datum();
		}
		return $model;
	}

	/**
	 * Extracts a Metadata object from a Model.
	 */
	public function extract_model_metadata($model) {
		$class = 'Model';
		if (is_object($model) && $model instanceof $class) {
			return $model->metadata();
		}
		return new Metadata();
	}

}

