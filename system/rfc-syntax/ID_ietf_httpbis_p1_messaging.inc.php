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

