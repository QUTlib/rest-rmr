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

namespace RFC2616;

define('RFC2616\OCTET',   '[\x00-\xFF]'); //= <any 8-bit sequence of data>
define('RFC2616\CHAR',    '[\x00-\x7F]'); //= <any US-ASCII character (octets 0 - 127)>
define('RFC2616\UPALPHA', '[A-Z]'); //=<any US-ASCII uppercase letter "A".."Z">
define('RFC2616\LOALPHA', '[a-z]'); //=<any US-ASCII lowercase letter "a".."z">
define('RFC2616\ALPHA',   '(?:'.UPALPHA.'|'.LOALPHA.')');
define('RFC2616\DIGIT',   '[0-9]'); //=<any US-ASCII digit "0".."9">
define('RFC2616\CTL',     '(?:[\x00-\x1F]|\x7F)'); //=<any US-ASCII control character (octets 0 - 31) and DEL (127)
define('RFC2616\CR',      '\x0D'); //=<US-ASCII CR, carriage return (13)>
define('RFC2616\LF',      '\x0A'); //=<US-ASCII LF, linefeed (10)>
define('RFC2616\SP',      '\x20'); //=<US-ASCII SP, space (32)>
define('RFC2616\HT',      '\x09'); //=<US-ASCII HR, horizontal-tab (9)>
define('RFC2616\DQ',      '\x22'); //=<US-ASCII double-quote mark (34)>

define('RFC2616\CRLF',    CR.LF);

define('RFC2616\LWS',     '(?:'.CRLF.')?(?:'.SP.'|'.HT.')+'); //= [CRLF] 1*( SP | HT )

define('RFC2616\TEXT',    '(?:[^\x00-\x1F\x7F]|'.LWS.')'); //= <any OCTET except CTLs, but including LWS>

define('RFC2616\HEX',     '(?:[A-Fa-f]|'.DIGIT.')');

//                          ! # $ % & ' *   +   -   . 0-9A-Z  ^ _ `  a-z  |   ~
define('RFC2616\token',   '[!\x23-\x27\x2A\x2B\x2D\x2E0-9A-Z\x5E-\x60a-z\x7C\x7E]+'); //= 1*<any CHAR except CTLs or separators>
define('RFC2616\separators', '[()<>@,;:\\\\"\/\[\]?={}'.SP.HT.']');

define('RFC2616\quoted_pair', '\\\\'.CHAR);

define('RFC2616\qdtext',   '(?:[^\x00-\x1F\x7F"]|'.LWS.')'); //= <any TEXT except <">>
define('RFC2616\quoted_string', '"(?:'.qdtext.'|'.quoted_pair.')*"');

#define('RFC2616\comment', '\((?:'.ctext.'|'.quoted_pair.'|'.comment.'|)*\)'); //FIXME: recursive!?
define('RFC2616\ctext',   '(?:[^\x00-\x1F\x7F()]|'.LWS.')'); //= <any TEXT excluding "(" and ")">


define('RFC2616\star_token', '(?:\*|'.token.')'); // not defined in the spec, but used everywhere

/**
 * De-quoted a quoted-string.
 *
 * <pre>"a\bc\"d" => abc"d</pre>
 *
 */
function parse_quoted_string($quoted_string) {
	// Note: does not validate that the string is actually quoted
	return str_replace('\\', '', substr($quoted_string,1,-1));
}

/*
 * 14.1 Accept
 *   Accept           = "Accept" ":" #( media-range [ accept-params ] )
 *   media-range      = ( "*" "/" "*"
 *                    | ( type "/" "*" )
 *                    | ( type "/" subtype )
 *                    ) *( ";" parameter )
 *   accept-params    = ";" "q" "=" qvalue *( accept-extension )
 *   accept-extension = ";" token [ "=" ( token | quoted-string ) ]
 *
 * 3.6 Transfer Codings
 *   parameter  = attribute "=" value
 *   attribute  = token
 *   value      = token | quoted-string
 *
 * 3.7 Media Types
 *   type       = token
 *   subtype    = token
 *
 * 3.9 Quality Values
 *   qvalue     = ( "0" [ "." 0*3DIGIT ] )
 *              | ( "1" [ "." 0*3("0") ] )
 *
 * Note: I've included BWS (bad whitespace) in the following
 * patterns, e.g. around semicolons and equals-signs.  Therefore
 * anything that fails to parse must be Really Bad(tm).
 * This justifies my rather harsh policy of throwing 400 responses
 * at clients who send me such rubbish.
 */

define('RFC2616\media_type', '/^('.star_token.')\s*\/\s*('.star_token.')$/'); // groups 1:type, 2:subtype; this should _not_ allow BWS...
define('RFC2616\qvalue', '/^q\s*=\s*(0(?:\.\d{0,3})?|1(?:\.0{0,3})?)$/'); // groups 1:"q=(X.YYY)"
define('RFC2616\accept_extension', '/^('.token.')(?:\s*=\s*('.token.')|('.quoted_string.'))?$/'); // groups 1:attribute-token, 2:value-token, 3:value-quoted_string
define('RFC2616\parameter',        '/^('.token.')\s*=\s*(?:('.token.')|('.quoted_string.'))$/');  // groups 1:attribute-token, 2:value-token, 3:value-quoted_string

/**
 * Parses an Accept: HTTP header.
 *
 * Example:
 *
 * <pre>
 * "Accept: foo/bar;p1=v1;p2=v2;q=0.95;x=y;z, baz/*;q=0.5, quux/freb, text/*;enc=xyz"
 * #=>
 * ---
 * 1000:
 * - media-range: "quux/freb"
 *   media-type:
 *     full-type: "quux/freb"
 *     type: "quux"
 *     subtype: "freb"
 *     parameters: {}
 *   accept-params: {}
 * - media-range: "text/*;enc=xyz"
 *   media-type:
 *     full-type: "text/*"
 *     type: "text"
 *     subtype: "*"
 *     parameters:
 *       enc: "xyz"
 *   accept-params: {}
 * 950:
 * - media-range: "foo/bar;p1=v1;p2=v2"
 *   media-type:
 *     full-type: "foo/bar"
 *     type: "foo"
 *     subtype: "bar"
 *     parameters:
 *       p1: "v1"
 *       p2: "v2"
 *   accept-params:
 *     q: "0.95"
 *     x: "y"
 *     z: true
 * 500:
 * - media-range: "baz/*"
 *   media-type:
 *     full-type: "baz/*"
 *     type: "baz"
 *     subtype: "*"
 *     parameters: {}
 *   accept-params:
 *     q: "0.5"
 * </pre>
 */
function parse_Accept($header) {
	$elem_set = array(); // used for validation; not returned

	$result = array();
	$accept_list = preg_split('/\s*,\s*/', $header);
	foreach ($accept_list as $accept_item) {
		if (!$accept_item) continue; // #list ABNF allows " , , "
		$parts = preg_split('/\s*;\s*/', $accept_item); // include BWS

		$base_media_type = array_shift($parts);
		if (preg_match(media_type, $base_media_type, $m)) {
			$type    = $m[1];
			$subtype = $m[2];
			$media_range = $type . '/' . $subtype;
			$media_type  = array(
				'full-type'  => $media_range,
				'type'       => $type,
				'subtype'    => $subtype,
				'parameters' => array(), // populated below
			);
		} else {
			throw new \BadRequestException('invalid media-range "'.$base_media_type.'..." in Accept header');
		}

		$qvalue = 1000;           // default, if not overridden
		$media_type['parameters'] = array(); // part of the media-range rule
		$accept_params = array(); // including qvalue

		$got_qvalue = FALSE;
		foreach ($parts as $part) {
			if ($got_qvalue) {
				if (preg_match(accept_extension, $part, $m)) {
					$attribute = $m[1];
					if (array_key_exists($attribute, $accept_params)) {
						// duplicate attribute
						// case not covered by RFC 2616
						throw new \BadRequestException('duplicate accept-extension "'.$attribute.'" for media-range "'.$media_range.'" in Accept header');
					} elseif (isset($m[3])) {
						// value is a quoted_string; parse it down
						$accept_params[$attribute] = parse_quoted_string($m[3]);
					} elseif (isset($m[2])) {
						// value is a token; no further parsing required
						$accept_params[$attribute] = $m[2];
					} else {
						// no value supplied; set it to TRUE
						$accept_params[$attribute] = TRUE;
					}
				} else {
					#//warn("Invalid accept-extension in Accept header");
					#$accept_params[$part] = FALSE;
					throw new \BadRequestException('invalid accept-extension "'.$part.'" for media-range "'.$media_range.'" in Accept header');
				}
			} elseif (preg_match(qvalue, $part, $m)) {
				$accept_params['q'] = $m[1];
				$qvalue = (int)(floatval($m[1]) * 1000); // should be safe from rounding errors
				$got_qvalue = TRUE;
			} else {
				if (preg_match(parameter, $part, $m)) {
					$attribute = $m[1];
					if (array_key_exists($attribute, $media_type['parameters'])) {
						// duplicate attribute
						// case not covered by RFC 2616
						throw new \BadRequestException('duplicate media-range parameter "'.$attribute.'" for media-range "'.$media_range.';..." in Accept header');
					} elseif (isset($m[3])) {
						// value is a quoted_string; parse it down
						$media_type['parameters'][$attribute] = parse_quoted_string($m[3]);
						$media_range .= ';' . $m[3];
					} else {
						// value is a token; no further parsing required
						$media_type['parameters'][$attribute] = $m[2];
						$media_range .= ';' . $m[2];
					}
				} else {
					//warn("Invalid accept-extension in Accept header");
					$media_type['parameters'][$part] = FALSE;
				}
			}
		}
		if (array_key_exists($media_range, $elem_set)) {
			throw new \BadRequestException('duplicate "'.$media_range.'" in Accept header');
		} else {
			$elem_set[$media_range] = TRUE;
		}
		if (!array_key_exists($qvalue, $result)) $result[$qvalue] = array();
		$result[$qvalue][] = array(
			'media-range' => $media_range,
			'media-type'  => $media_type,
			'accept-params' => $accept_params,
		);
	}
	krsort($result);
	return $result;
}

/*
 * 14.2 Accept-Charset
 *   Accept-Charset  = "Accept-Charset" ":" 1#( ( charset | "*" )[ ";" "q" "=" qvalue ] )
 *
 * 3.2 Character Sets
 *   charset         = token
 */

/**
 * @see #parse_qvalued_list()
 */
function parse_Accept_Charset($header) {
	$qlist = parse_qvalued_list($header, 'Accept-Charset');
	if (!$qlist) {
		// make it explicit that any charset is gravy
		return array(
			1000 => array('*'),
		);
	}

	$result = array();
	$iso_8859_1 = FALSE;
	foreach ($qlist as $qvalue=>$charsets) {
		$result[$qvalue] = array();
		foreach ($charsets as $charset) {
			if (preg_match('/^'.star_token.'$/', $charset)) {
				if ($charset == '*' || strtoupper($charset) == 'ISO-8859-1') {
					$iso_8859_1 = TRUE;
				}
				$result[$qvalue][] = $charset;
			} else {
				throw new \BadRequestException('invalid charset "'.$charset.'" in Accept-Charset header');
			}
		}
	}
	// ensure that ISO-8859-1 is included (defaults to q=1 if missing)
	if (!$iso_8859_1) {
		if (!array_key_exists(1000, $result)) {
			$result[1000] = array();
			krsort($result);
		}
		$result[1000][] = 'ISO-8859-1';
	}
	return $result;
}

/*
 * 14.3 Accept-Encoding
 *   Accept-Encoding = "Accept-Encoding" ":" 1#( codings [ ";" "q" "=" qvalue ] )
 *   codings         = ( content-coding | "*" )
 *
 * 3.5 Content Codings
 *   content-coding  = token
 */

/**
 * @see #parse_qvalued_list()
 * @fixme are content-codings meant to be case-sensitive?
 */
function parse_Accept_Encoding($header) {
	$qlist = parse_qvalued_list($header, 'Accept-Encoding');
	if (!$qlist) {
		// if empty, only the "identity" encoding is acceptable
		return array(
			1000 => array('identity'),
		);
	}

	$result = array();
	$identity = FALSE;
	foreach ($qlist as $qvalue=>$content_codings) {
		$result[$qvalue] = array();
		foreach ($content_codings as $content_coding) {
			if (preg_match('/^'.star_token.'$/', $content_coding)) {
				if ($content_coding == '*' || $content_coding == 'identity') {
					$identity = TRUE;
				}
				$result[$qvalue][] = $content_coding;
			} else {
				throw new \BadRequestException('invalid content_coding "'.$content_coding.'" in Accept-Encoding header');
			}
		}
	}
	// ensure that the identity coding is present, but give it
	// the lowest preference
	if (!$identity) {
		if (!array_key_exists(1, $result)) {
			$result[1] = array();
		}
		$result[1][] = 'identity';
	}
	return $qlist;
}

/*
 * 14.4 Accept-Language
 *   Accept-Language = "Accept-Language" ":" 1#( language-range [ ";" "q" "=" qvalue ] )
 *   language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
 *
 * 3.10 Language Tags
 *   language-tag    = primary-tag *( "-" subtag )
 *   primary-tag     = 1*8ALPHA
 *   subtag          = 1*8ALPHA
 */

define('RFC2616\primary_tag',    ALPHA.'{1,8}');
define('RFC2616\subtag',         ALPHA.'{1,8}');
define('RFC2616\language_tag',   '('.primary_tag.')((?:-'.subtag.')*)'); // groups 1=primary_tag, 2=subtags
define('RFC2616\language_range', '/^(?:'.language_tag.'|\*)$/'); // groups 1=primary_tag, 2=subtags

/**
 *
 * Example:
 *
 * <pre>
 * "Accept-Language: da, en-gb;q=0.8, en;q=0.7"
 * #=>
 * ---
 * 1000:
 * - language-range: "da"
 *   primary-tag: "da"
 *   subtags: {}
 * 800:
 * - language-range: "en-gb"
 *   primary-tag: "en"
 *   subtags:
 *   - "gb"
 * 700:
 * - language-range: "en"
 *   primary-tag: "en"
 *   subtags: {}
 * </pre>
 *
 * @see #parse_qvalued_list()
 */
function parse_Accept_Language($header) {
	$result = array();
	$qlist = parse_qvalued_list($header, 'Accept-Language');
	foreach ($qlist as $qvalue=>$language_ranges) {
		$result[$qvalue] = array();
		foreach ($language_ranges as $language_range) {
			if (preg_match(language_range, $language_range, $m)) {
				if ($language_range == '*') {
					$result[$qvalue][] = array(
						'language-range' => '*',
						'primary-tag' => NULL,
						'subtags' => array(),
					);
				} else {
					$subtags = explode('-', $m[2]);
					array_shift($subtags); // first item will be ""
					$result[$qvalue][] = array(
						'language-range' => $language_range,
						'primary-tag' => $m[1],
						'subtags' => $subtags,
					);
				}
			} else {
				throw new \BadRequestException('invalid language_range "'.$language_range.'" in Accept-Language header');
			}
		}
	}
	return $result;
}

/**
 * Parses a HTTP header whose value is a list of THINGS which may have qvalues.
 * This _does not_ do any parsing or validation of the things themselves, except
 * that they must not contain semicolons.
 *
 * Example:
 *
 * <pre>
 * "Accept-Foo: a, b;q=0.8, c;q=1"
 * #=>
 * ---
 * 1000:
 * - "a"
 * - "c"
 * 800:
 * - "b"
 * </pre>
 */
function parse_qvalued_list($header, $header_type, $default_qvalue=1000) {
	$elem_set = array(); // used for validation; not returned

	$result = array();
	$list = preg_split('/\s*,\s*/', $header);
	foreach ($list as $item) {
		if (!$item) continue; // #list ABNF allows " , , "
		$parts = preg_split('/\s*;\s*/', $item); // include BWS
		$elem = array_shift($parts);
		if (array_key_exists($elem, $elem_set)) {
			throw new \BadRequestException('duplicate "'.$elem.'" in '.$header_type.' header');
		} else {
			$elem_set[$elem] = TRUE;
		}

		$qvalue = $default_qvalue;
		$got_qvalue = FALSE;
		foreach ($parts as $part) {
			if (preg_match(qvalue, $part, $m)) {
				if ($got_qvalue) {
					throw new \BadRequestException('duplicate qvalue in '.$header_type.' header "'.$header.'"');
				} else {
					$qvalue = (int)(floatval($m[1]) * 1000); // should be safe from rounding errors
					$got_qvalue = TRUE;
				}
			} else {
				throw new \BadRequestException('attribute is not qvalue in '.$header_type.' header "'.$header.'"');
			}
		}
		if (!array_key_exists($qvalue, $result)) $result[$qvalue] = array();
		$result[$qvalue][] = $elem;
	}
	krsort($result);
	return $result;
}

