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

class HTMLParseException extends Exception {}

/**
 * A little bit of magic.
 *
 * A subset of the standard HTML5 tags that don't require a closing
 * tag, mapped to:
 *  - FALSE: no content
 *  - TRUE: optional text content
 *  - tag(s): what other tag(s) close this one
 */
$HTML_HANGING_TAGS = array(
	'br'     => FALSE,
	'hr'     => FALSE,
	'link'   => TRUE,
	'meta'   => FALSE,
	'script' => TRUE,
	'p'      => array('p', 'div', 'table', 'blockquote'),
	'tr'     => 'tr',
	'td'     => array('tr', 'td'),
	'option' => TRUE,
	'optgroup' => 'optgroup',
	'li'     => 'li',
	'dt'     => array('dt', 'dd'),
	'dd'     => array('dt', 'dd'),
	'img'    => FALSE,
);

function parse_html($html, $trim_ws=TRUE) {
	// first clear off xml declarations, doctype, etc.
	$offset = 0;
	while ($html && preg_match('/^\s*(<\?xml\b([^?]+|\?[^>])*\?>|<!doctype\s[^>]*>)/i', $html, $matches)) {
		$strlen = strlen($matches[0]);
		$offset += $strlen;
		$html = substr($html, $strlen);
	}
	$roots = get_html_doms($html, $offset, $trim_ws);
	$htmlroot = NULL;
	foreach ($roots as $root) {
		if ($root instanceof HTMLElement) {
			$tag = $root->tagname();
			if (strtolower($tag) == 'html') {
				if ($htmlroot) {
					throw new HTMLParseException("found duplicate <html> tag");
				} else {
					$htmlroot = $root;
				}
			} else {
				throw new HTMLParseException("found <$tag> tag, expected <html>");
			}
		}
	}
	if ($htmlroot) {
		$head = NULL;
		$body = NULL;
		foreach ($htmlroot as $node) {
			if ($node instanceof HTMLElement) {
				$tag = $node->tagname();
				if (strtolower($tag) == 'head') {
					if ($head) {
						throw new HTMLParseException("found duplicate <head> tag");
					} else {
						$head = $node;
					}
				} elseif (strtolower($tag) == 'body') {
					if ($body) {
						throw new HTMLParseException("found duplicate <body> tag");
					} else {
						$body = $node;
					}
				} else {
					throw new HTMLParseException("found <$tag> tag, expected <html>");
				}
			}
		}
		if ($head && $body) {
			return new HTMLDocument($head, $body);
		} else {
			if ($head) throw new HTMLParseException("no <body> tag");
			elseif ($body) throw new HTMLParseException("no <head> tag");
			else throw new HTMLParseException("no <head> or <body> tags");
		}
	} else {
		throw new HTMLParseException("no <html> tag");
	}
}

function parse_tag_attrs($tag, $string, $offset=0) {
#if PCRE.VERSION >= 8.00
/*
	// this is a very relaxed pattern
	$pattern = '/^([^\s=]+)\s*=\s*(?|"([^"]*)"|\'([^\']*)\')|^([^\s=]+)/';
	$attrs = array();
	while ($string = trim($string)) {
		if (preg_match($pattern, $string, $matches)) {
			$string = substr($string, strlen($matches[0]));
			if (isset($matches[3])) {
				// if the tag is like <foo attr> interpret it as <foo attr="attr">
				$attrs[ $matches[3] ] = $matches[3];
			} else {
				$attrs[ $matches[1] ] = $matches[2];
			}
		} else {
			throw new HTMLParseException("can't parse attributes in $tag tag around offset $offset");
		}
	}
*/
#else
	// damn you to hell, red hat
	$pattern = '/^([^\s=]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\')|^([^\s=]+)/';
	$attrs = array();
	while ($string = trim($string)) {
		if (preg_match($pattern, $string, $matches)) {
			$string = substr($string, strlen($matches[0]));
			if (!empty($matches[4])) {
				// if the tag is like <foo attr> interpret it as <foo attr="attr">
				$attrs[ $matches[4] ] = $matches[4];
			} elseif (!empty($matches[3])) {
				$attrs[ $matches[1] ] = $matches[3];
			} else {
				$attrs[ $matches[1] ] = $matches[2];
			}
		} else {
			throw new HTMLParseException("can't parse attributes in $tag tag around offset $offset");
		}
	}
#endif
	return $attrs;
}

function get_html_doms($html, $offset=0, $trim_ws=TRUE) {
	global $HTML_HANGING_TAGS;

	if ($trim_ws) {
		$html = preg_replace('/(>)\s+(.*?)\s+(<)/', '$1 $2 $3', $html);
	}

	$pattern = '~^(?:
			\s*<!--((?:  [^-]+  |  -[^-]  |  --[^>]  )*)-->         # 1 = comment
		|	\s*<!\[CDATA\[(  [^]]+  |  \][^]]  |  \]\][^>  ])\]\]>  # 2 = cdata
		|	\s*<\s*([^\s>/]+)([^>]*)>  # 3 = tag, 4 = attributes (very relaxed)
		|	(\s+)(?=<|$)               # 5 = whitespace node (ignore)
		|	([^<]+)                    # 6 = text node
		|	\s*<\/\s*([^\s>]+)\s*>     # 7 = closing tag
		)~xi';

	$root  = NULL;
	$roots = array();
	$stack = array();
	while ($html) {
		$head = reset($stack);
		$htag = ($head ? strtolower($head->tagname()) : false);
		if (preg_match($pattern, $html, $matches)) {
			$strlen = strlen($matches[0]);
			$offset += $strlen;
			$html = substr($html, $strlen);

			$node = NULL;
			if (isset($matches[7])) {
				// CLOSING TAG (!!)
				$tag = strtolower($matches[7]);
				while ($htag) {
					if ($htag == $tag || array_key_exists($htag, $HTML_HANGING_TAGS)) {
						// close the current $head tag (by shifting it off the stack)
						array_shift($stack);
						if ($htag == $tag) {
							// break out of this while loop, and continue the one above
							continue 2;
						} else {
							// iterate with the grandparent
							$head = reset($stack);
							$htag = ($head ? strtolower($head->tagname()) : false);
						}
					} else {
						throw new HTMLParseException("found </$tag> at $offset, expected </$htag>");
					}
				}
				// if we got here, we fell off the top of the tree
				throw new HTMLParseException("unexpected </$tag> at $offset");
			} elseif (isset($matches[6])) {
				// TEXT NODE
				$node = new HTMLTextNode($matches[6], TRUE);
			} elseif (isset($matches[5])) {
				// WHITESPACE NODE -- IGNORE
				if ($trim_ws) {
					// next chunk of text, thanks
					continue;
				} else {
					// hang on, this might be important ...
					$node = new HTMLTextNode($matches[5], TRUE);
				}
			} elseif (isset($matches[3])) { # and 4
				// TAG
				$tagstr = $matches[3];
				$attstr = $matches[4];
				if (preg_match('#^(.*)/\s*$#sU', $attstr, $amatches)) {
					// this is a Bad Way(tm) to do this...
					// Add a fake closing-tag to the start of the current html
					// string, and give it a negative offset to compensate.
					$node = new HTMLElement($tagstr, parse_tag_attrs($tagstr, $amatches[1], $offset), isset($HTML_HANGING_TAGS[$tagstr]));
					$ctag = '</'.$tagstr.'>';
					$html = $ctag . $html;
					$offset -= strlen($ctag);
				} else {
					$node = new HTMLElement($tagstr, parse_tag_attrs($tagstr, $attstr, $offset), isset($HTML_HANGING_TAGS[$tagstr]));
				}
			} elseif (isset($matches[2])) {
				// CDATA: <![CDATA[(*)]]>
				$node = new HTMLCData($matches[2]);
			} else {
				// COMMENT: <!--(*)-->
				$node = new HTMLComment($matches[1]);
			}

			if ($node instanceof HTMLElement) {
				// check to see if any previous tags are closed by the current one
				while ($head && array_key_exists($htag, $HTML_HANGING_TAGS) && (
					$HTML_HANGING_TAGS[$htag] === FALSE ||
					$HTML_HANGING_TAGS[$htag] === TRUE  ||
					(is_string($HTML_HANGING_TAGS[$htag]) && strtolower($node->tagname()) == $HTML_HANGING_TAGS[$htag]) ||
					(is_array($HTML_HANGING_TAGS[$htag]) && in_array(strtolower($node->tagname()), $HTML_HANGING_TAGS[$htag]))
				)) {
					array_shift($stack);
					$head = reset($stack);
					$htag = ($head ? strtolower($head->tagname()) : false);
				}

				if ($head) {
					// we're still in a current DOM; append me, and add me to the stack
					$head->append_child($node);
					array_unshift($stack, $node);
				} else {
					// this is a new DOM hierarchy; push the current one (if any) and 
					// start over with me as the root
					if ($root) {
						$roots[] = $root;
					}
					$root = $node;
					$stack = array($node);
				}
			} else {
				// check to see if any previous tags are closed by this node
				// (tags like <br> are always implicitly closed by any subsequent nodes)
				if ($head && array_key_exists($htag, $HTML_HANGING_TAGS) && $HTML_HANGING_TAGS[$htag] === FALSE) {
					array_shift($stack);
					$head = reset($stack);
					$htag = ($head ? strtolower($head->tagname()) : false);
				}

				if ($head) {
					// we're still in a current DOM; append me, but leave the stack as-is
					$head->append_child($node);
				} else {
					// this is a new DOM hierarchy; push the current one (if any), push
					// me (as a stand-alone hierarchy), and start over with an empty tree
					if ($root) {
						$roots[] = $root;
					}
					$roots[] = $node;
					$root = NULL;
					$stack = array();
				}

			}

		} else {
			throw new HTMLParseException("can't parse from $offset");
		}
	}
	if ($root) {
		$roots[] = $root;
	}
	return $roots;
}

