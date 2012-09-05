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


abstract class Representer {

	/** should return a boolean */
	abstract public function can_do_model($m);

	/**
	 * $type is a string like: '* /*', 'foo/*', or 'foo/bar'
	 *
	 * Should return a number from 0.000 to 1.000, inclusive.
	 * (1 = I'd love to; 0 = dunno how)
	 */
	abstract public function preference_for_type($type);

	/**
	 * Advertise types we want to represent, in the form:
	 *    array( 'foo/bar'=>1.0, 'foo/quux'=>0.5, ...)
	 */
	abstract public function list_types();

	/**
	 * $charset is a string, like: 'iso-8859-1', or '*'
	 *
	 * Should return a number from 0.000 to 1.000, inclusive.
	 * (1 = I'd love to; 0 = dunno how)
	 */
	abstract public function preference_for_charset($charset);

	/**
	 * Advertise types we want to represent, in the form:
	 *    array( 'iso-8859-1'=>1.0, 'utf-8'=>0.5, ...)
	 */
	abstract public function list_charsets();

	/**
	 * $lang is a string, like: 'en', 'en-US', or '*'
	 *
	 * Should return a number from 0.000 to 1.000, inclusive.
	 * (1 = I'd love to; 0 = dunno how)
	 */
	abstract public function preference_for_language($lang);

	/**
	 * Advertise types we want to represent, in the form:
	 *    array( 'en'=>1.0, 'fr'=>0.5, ...)
	 */
	abstract public function list_languages();

	/**
	 * Picks the best type from $types, according to what the client
	 * wants (qvalue) and what this representer can represent.
	 */
	protected function pick_best_of($name, $list) {
		$best = NULL;
		$best_weight = 0;
		foreach ($list as $qt => $array) {
			foreach ($array as $item) {
				$w = call_user_func(array($this,"preference_for_$name"), $item['option']); // float from 0.000 to 1.000
				$w = $qt * $w; // qt is int from 0 to 1000
				$w = intval($w * 100); // final w is int from 0 to 100000 (5 precision, as per RFC2295/6)
				if ($w > $best_weight) {
					$best = $item['option'];
					$best_weight = $w;
				}
			}
		}
		if ($best == NULL) return FALSE;
		return array( $name => $best, 'weight' => $best_weight );
	}

	/**
	 * Picks the best type, charset, and language, according to what the client
	 * wants (qvalue) and what this representer can represent.
	 */
	public function pick_best($types, $charsets, $languages) {
		return array(
			'type'     => $this->pick_best_of('type',     $types),
			'charset'  => $this->pick_best_of('charset',  $charsets),
			'language' => $this->pick_best_of('language', $languages),
		);
	}

	/**
	 * Represent model $m as type $t, charset $c, language $l.
	 *
	 * The framework guarantees to never invoke this with an attribute that scored 0.000
	 * in preference_for_FOO()
	 */
	abstract public function represent($m, $t, $c, $l, $response);
}

