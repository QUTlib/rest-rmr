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

require_once('utils/xmldom.inc.php');

class XHTMLFactory {
	private function __construct() {}

	/**
	 * Creates a SimpleXMLElement which is an empty XHTML document.
	 */
	public static function create() {
		$doc = new DOMDocument('1.0', 'ISO-8859-1');

		$doctype = new DOMDocumentType();
		$doctype->name = 'html';
		$doctype->publicId = '-//W3C//DTD XHTML Basic 1.1//EN';
		$doctype->systemId = 'http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd';
		$doc->appendChild($doctype);

		$html = $doc->createElement('html');
		$html->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
		$html->setAttribute('xml:lang', 'en');
		$doc->appendChild($html);

		return $doc;
	}

	/**
	 * Creates a new document fragment.
	 */
	public static function create_frag() {
		$doc = new DOMDocument();
		$frag = $doc->createDocumentFragment();
		return $frag;
	}

	/**
	 * Creates a HEAD element, attached to an XHTML document.
	 */
	public static function head($xhtml, $title=NULL) {
		$head = $xhtml->createElement('head');
		if ($title) {
			$_title = $xhtml->createElement('title', $title);
			$head->appendChild($_title);
		}
		$xhtml->getElementsByTagName('html')->first()->appendChild($head);
		return $head;
	}

	/**
	 * Creates a BODY element, attached to an XHTML document.
	 */
	public static function body($xhtml) {
		$body = $xhtml->createElement('body');
		$xhtml->getElementsByTagName('html')->first()->appendChild($body);
		return $body;
	}

	/**
	 * Creates an A element, with all the appropriate whatsernames.
	 */
	public static function link($parent, $href, $text=NULL, $attrs=array()) {
		if (! $attrs && is_array($text)) {
			$attrs = $text;
			$text = '';
		}

		$attrs['href'] = $href;
		$a = self::add($parent, 'a', $text, $attrs);
		return $a;
	}

	/**
	 * Creates any HTML TAG, with all the appropriate whatsernames.
	 */
	public static function add($parent, $tag, $value=NULL, $attrs=array()) {
		if (! $attrs && is_array($value)) {
			$attrs = $value;
			$value = '';
		}

		$doc = $parent->ownerDocument;
		$e = $doc->createElement($tag, $value);
		foreach ($attrs as $k=>$v) {
			$e->setAttribute($k, $v);
		}

		$parent->appendChild($e);
		return $e;
	}

}

