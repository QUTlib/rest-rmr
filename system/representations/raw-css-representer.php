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

Application::register_class('RawCSSDoc', SYSDIR.'/models/raw-css-model.php');

/**
 * A basic CSS representer that displays RawCSSDoc models as text/css
 *
 * Supported internet media types (MIMEs):
 *   text/css                q=1.0 [advertised,default]
 *   application/x-pointplus q=1.0 [converted to text/css]
 *   * / *                   q=0.001
 */
class RawCSSDocRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'css', 1.0, TRUE),
				new InternetMediaType('application', 'x-pointplus', 1.0, FALSE, 'text/css'), # yeah, yeah, shut up
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/css'),
			),
			array(),
			array(),
			array(
				'object:RawCSSDoc',
			)
		);
	}

	public function represent($m, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c);
		$this->response_language($response, $l, FALSE);
		$response->body( $m->doc );
	}
}

