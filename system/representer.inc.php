<?php

abstract class Representer {

	/** should return a boolean */
	abstract public function can_do_model($m);

	/**
	 * $t is of the form:
	 *    array('option'=>'foo/bar', 'raw'=>'foo/bar;q=0.8;baz=quux')
	 *
	 * Should return a number from 0.000 to 1.000, inclusive.
	 * (1 = I'd love to; 0 = dunno how)
	 */
	abstract public function preference_for_type($t);

	/**
	 * Advertise types we want to represent, in the form:
	 *    array( 'foo/bar'=>1.0, 'foo/quux'=>0.5, ...)
	 */
	abstract public function list_types();

	/**
	 * Picks the best type from $types, according to what the client
	 * wants (qvalue) and what this representer can represent.
	 */
	public function pick_best($types) {
		$best_type = NULL;
		$best_weight = 0;
		foreach ($types as $qt => $tt) {
			foreach ($tt as $type) {
				$w = $this->preference_for_type($type); // float from 0.000 to 1.000
				$w = $qt * $w; // qt is int from 0 to 1000
				$w = intval($w * 100); // final w is int from 0 to 100000 (5 precision, as per RFC2295/6)
				if ($w > $best_weight) {
					$best_type = $type;
					$best_weight = $w;
				}
			}
		}
		if ($best_type == NULL) return FALSE;
		return array( 'type' => $best_type, 'weight' => $best_weight );
	}

	/**
	 * Represent model $m as type $t.
	 *
	 * The framework guarantees to never invoke this with a $t that scored 0.000
	 * in #preference_for_type($t)
	 */
	abstract public function represent($m, $t, $response);
}

