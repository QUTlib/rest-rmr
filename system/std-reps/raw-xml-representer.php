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

Application::register_class('XMLString', SYSDIR.'/std-models/xml-string-model.php');
Application::register_class('XMLFile',   SYSDIR.'/std-models/xml-file-model.php');

/**
 * A basic XML representer that displays raw XML document models as application/xml
 *
 * Supported internet media types (MIMEs):
 *   application/xml  q=1.0 [advertised,default]
 *   text/xml         q=0.9
 *   * / *            q=0.001
 */
class RawXMLDocRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('application', 'xml', 1.0, TRUE),
				new InternetMediaType('text',        'xml', 0.9),
				new InternetMediaType('*', '*', 0.001, FALSE, 'application/xml'),
			),
			array(),
			array(),
			array(
				'object:XMLString',
				'object:XMLFile',
			)
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c);
		$this->response_language($response, $l, FALSE);
		if ($mtime = $m->mtime()) {
			$response->last_modified($mtime);
			$response->cache();
		}
		if ($response->is_modified()) {
			$response->body( $m->doc() );
		}
	}
}

