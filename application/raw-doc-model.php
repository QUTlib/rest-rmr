<?php

/**
 * The simplest model of all.
 */
class RawDocument {
	public $doc = '';
	public function __construct($doc) {
		$this->doc = $doc;
	}
}

