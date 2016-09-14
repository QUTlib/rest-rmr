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

namespace RFC5234;

define('RFC5234\ALPHA',  '(?:[\x41-\x5A]|\x61-\x7A])');
define('RFC5234\CHAR',   '[\x01-\x7F]');
define('RFC5234\CR',     '\x0D');
define('RFC5234\LF',     '\x0A');
define('RFC5234\CRLF',   CR.LF);
define('RFC5234\CTL',    '(?:[\x00-\x1F]|\x7F)');
define('RFC5234\DIGIT',  '[\x30-\x39]');
define('RFC5234\DQUOTE', '\x22');
define('RFC5234\HEXDIG', '(?:['.DIGIT.']|[A-F]|[a-f])');
define('RFC5234\HTAB',   '\x09');
define('RFC5234\SP',     '\x20');
define('RFC5234\WSP',    '(?:'.SP.'|'.HTAB.')');
define('RFC5234\LWSP',   '(?:'.WSP.'|'.CRLF.WSP.')*'); // linear whitespace (caveats apply)
define('RFC5234\OCTET',  '[\x00-\xFF]');
define('RFC5234\VCHAR',  '[\x21-\x7E]');

