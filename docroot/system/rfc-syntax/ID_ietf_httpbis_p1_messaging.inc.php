<?php
/*
 * See the NOTICE file distributed with this work for information
 * regarding copyright ownership.  QUT licenses this file to you
 * under the Apache License, Version 2.0 (the "License")); you may
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

namespace ID_ietf_httpbis_p1_messaging;

require_once(__DIR__.'/RFC5234.inc.php');

define('ID_ietf_httpbis_p1_messaging\ALPHA',  \RFC5234\ALPHA);
define('ID_ietf_httpbis_p1_messaging\DIGIT',  \RFC5234\DIGIT);
define('ID_ietf_httpbis_p1_messaging\DQUOTE', \RFC5234\DQUOTE);
define('ID_ietf_httpbis_p1_messaging\HTAB',   \RFC5234\HTAB);
define('ID_ietf_httpbis_p1_messaging\SP',     \RFC5234\SP);
define('ID_ietf_httpbis_p1_messaging\VCHAR',  \RFC5234\VCHAR);

define('ID_ietf_httpbis_p1_messaging\OWS', '(?:'.SP.'|'.HTAB.')*'); // optional whitespace
define('ID_ietf_httpbis_p1_messaging\BWS', OWS);                    // bad whitespace

#Connection
#Content-Length
#HTTP-Message
#HTTP-name
#HTTP-version
#Host

define('ID_ietf_httpbis_p1_messaging\RWS', '('.SP.'|'.HTAB.')+'); // required whitespace

#TE
#Trailer
#Transfer-Encoding

#URI-reference
#Upgrade

#Via

#absolute-URI
#absolute-form
#absolute-path
#asterisk-form
#attribute
#authority
#authority-form

#chunk
#chunk-data
#chunk-ext
#chunk-ext-name
#chunk-ext-val
#chunk-size
#chunked-body
#comment
#connection-option
#ctext

#field-content
#field-name
#field-value

#header-field
#http-URI
#https-URI

#last-chunk

#message-body
#method

#obs-fold
define('ID_ietf_httpbis_p1_messaging\obs_text', '[\x80-\xFF]');
#origin-form

#partial-URI
#path-abempty
#port
#protocol
#protocol-name
#protocol-version
#pseudonum

define('ID_ietf_httpbis_p1_messaging\qdtext', '(?:'.HTAB.'|'.SP.'|\!|[\x23-\x5B]|[\x5D-\x7E]|'.obs_text.')');
#qdtext-nf
#query
#quoted-cpair
define('ID_ietf_httpbis_p1_messaging\quoted_pair', '\\\\(?:'.HTAB.'|'.SP.'|'.VCHAR.'|'.obs_text.')');
#quoted-str-nf
define('ID_ietf_httpbis_p1_messaging\quoted_string', DQUOTE.'(?:'.qdtext.'|'.quoted_pair.')*'.DQUOTE);

define('ID_ietf_httpbis_p1_messaging\rank', '(?:0(?:\.'.DIGIT.'{0,3})?|1(?:\.0{0,3})?)');
#reason-phrase
#received-by
#received-protocol
#relative-part
#request-line
#request-target

#segment

#special
#start-line
#status-code
#status-line

#t-codings
#t-ranking
define('ID_ietf_httpbis_p1_messaging\tchar', '(?:\!|#|\$|%|&|\'|\*|\+|-|\.|\^|_|`|\||~|'.DIGIT.'|'.ALPHA.')');
define('ID_ietf_httpbis_p1_messaging\token', tchar.'+');
#trailer-part
#transfer-coding
#transfer-extension
#transfer-parameter

#uri-host

define('ID_ietf_httpbis_p1_messaging\word', '(?:'.token.'|'.quoted_string.')');
define('ID_ietf_httpbis_p1_messaging\value', word);


#########################################################################
## I-D.snell-http-prefer
#
# Derives from ID.ietf-httpbis-p1-messaging
#
#########################################################################

namespace ID_snell_http_prefer;

define('ID_snell_http_prefer\token', \ID_ietf_httpbis_p1_messaging\token);
define('ID_snell_http_prefer\word',  \ID_ietf_httpbis_p1_messaging\word);

define('ID_snell_http_prefer\preference_parameter', '/^('.token.')(?:\s*=\s*('.word.'))?$/');

/**
 * Parses a Prefer header.
 *
 * Example:
 *
 * <pre>
 * "Prefer: a,b=1,c;x=2,d=3;y=4"
 * #=>
 * ---
 * a:
 *   a: true
 * b:
 *   b: 1
 * c:
 *   c: true
 *   x: 2
 * d:
 *   d: 3
 *   y: 4
 * </pre>
 */
function parse_Prefer($header) {
	$result = array();
	$list = preg_split('/\s*,\s*/', $header);
	foreach ($list as $item) {
		if (!$item) continue; // #list ABNF allows " , , " for legacy reasons
		$parts = preg_split('/\s*;\s*/', $item); // include OWS

		$name = NULL;
		$params = array();
		foreach ($parts as $part) {
			if (preg_match(preference_parameter, $part, $m)) {
				if ($name === NULL) $name = $m[1];
				if (isset($m[2])) {
					if ($m[2]{0} == '"') {
						// quoted-string
						$value = str_replace('\\', '', substr($m[2],1,-1));
					} else {
						// token
						$value = $m[2];
					}
				} else {
					// no value, set to true
					$value = TRUE;
				}
				$params[$m[1]] = $value;
			} else {
				throw new \BadRequestException('invalid parameter in Prefer header: "'.$part.'"');
			}
		}
		if (array_key_exists($name, $result)) {
			throw new \BadRequestException('duplicate "'.$name.'" in Prefer header');
		} else {
			$result[$name] = $params;
		}
	}
	return $result;

}

