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

/**
 * A representer which will represent a HTTP API Problem
 * as a JSON or XML document.
 *
 * See: http://tools.ietf.org/html/draft-nottingham-http-problem-03
 *
 * Supported internet media types (MIMEs):
 *   application/api-problem+json q=1.0 [advertised,default]
 *   application/json             q=1.0
 *   application/api-problem+xml  q=1.0 [advertised]
 *   application/xml              q=1.0
 *   text/json                    q=0.9
 *   text/x-json                  q=0.9
 *   text/xml                     q=0.9
 *   * / *                        q=0.001
 */
class ProblemRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'problem+json',     1.0, TRUE),
				new InternetMediaType('application', 'problem+xml',      1.0, TRUE),
				new InternetMediaType('application', 'api-problem+json'), // from early draft specs
				new InternetMediaType('application', 'api-problem+xml'),  // from early draft specs
				new InternetMediaType('application', 'json',   0.5), # note: these are relatively low because they also represent
				new InternetMediaType('application', 'xml',    0.5), # hopefully-200OK responses, and I really want to lean towards
				new InternetMediaType('text',        'json',   0.4), # different content-types for Ok vs Problem responses
				new InternetMediaType('text',        'x-json', 0.4),
				new InternetMediaType('text',        'xml',    0.4),
				new InternetMediaType('*', '*', 1.0, FALSE, 'application/problem+json'),
			),
			array(),
			array(),
			array('object:Problem')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		// web browser hack because argh!
		if ($t['media-range']  == '*/*' && ($ua=Request::header('User-Agent')) && strpos($ua,'curl')===false) {
			$t['media-range']  = 'text/xml';
		}

		$response->allow_not_modified = false;
		$response->allow_auto_etag = false;
		$response->nocache();

		$response->status( $m->httpStatus() );
		$this->response_type($response, $t, NULL, TRUE, TRUE); // NULL charset because blarflrf
		$this->response_language($response, 'en', FALSE, TRUE); // ???force language???

		switch (strtolower($t['media-range'])) {
		case 'application/problem+json':
		case 'application/api-problem+json':
		case 'application/json':
		case 'text/json':
		case 'text/x-json':
		case '*/*':
			$response->body( json_encode($m->to_array()) );
			break;
		case 'application/problem+xml':
		case 'application/api-problem+xml':
		case 'application/xml':
		case 'text/xml':
			$response->body( $this->xml($m->to_array()) );
			break;
		default:
			throw new InternalServerErrorException("cannot represent ".get_class($m)." as '{$t}'");
		}
	}

	protected function xml($array) {
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>
<?xml-stylesheet href="/assets/problem.xsl" type="text/xsl" ?>
<problem xmlns="urn:ietf:rfc:7807">';
		foreach ($array as $k=>$v) {
			#$k = htmlspecialchars($k);
			$v = htmlspecialchars($v);
			$xml .= "<$k>$v</$k>";
		}
		$xml .= '</problem>';
		return $xml;
	}
}
