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
 * Provides simple mechanisms for regitering internet media types and
 * representing PHP values / objects.
 */
abstract class BasicRepresenter extends Representer {

	private $types = array();
	private $all_models = FALSE;
	private $model_types = NULL;
	private $model_classes = NULL;

	/**
	 * Creates a new BasicRepresenter object.
	 *
	 * $types should be an array of InternetMediaType objects, or media type strings.
	 *
	 * $models should be either:
	 * * TRUE, if this representer can do any PHP value; or
	 * * an array of strings, either:
	 *   * the type of a value supported (as per gettype()), or
	 *   * a description of the classname supported (e.g. 'object:ClassName')
	 */
	public function __construct($types, $models) {
		foreach ($types as $t) {
			if (is_object($t) && ($t instanceof InternetMediaType)) {
				$this->types[ $t->mime() ] = $t;
			} elseif (is_string($t)) {
				$t = InternetMediaType::parse($t);
				$this->types[ $t->mime() ] = $t;
			} else {
				throw new Exception("not an internet media type: '$t'");
			}
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
		$type = $t['option'];
		if (isset($this->types[$type])) {
			return $this->types[$type]->qvalue();
		}
		return 0.0;
	}

	/**
	 * Gets the InternetMediaType object associated with the given MIME ($t).
	 * May be NULL.
	 */
	protected function pick_type($t) {
		$type = $t['option'];
		if (isset($this->types[$type])) {
			return $this->types[$type];
		}
		return NULL;
	}

	/**
	 * Sets the response Content-Type string ($t).
	 *
	 * Throws an exception if I don't have a type for $t.
	 */
	protected function response_type($response, $t) {
		$type = $t['option'];
		if (isset($this->types[$type])) {
			$mime = $this->types[$type];
			$response->content_type( $mime->effective_mime() );
			return;
		}
		throw new Exception("unrecognised type '$type'");
	}

}

