<?php

/**
 * The simplest model of all.
 *
 * Contains a single, basic PHP value (usually a String).
 */
class RawDocument {
	public $doc = '';
	public function __construct($doc) {
		$this->doc = $doc;
	}
}

