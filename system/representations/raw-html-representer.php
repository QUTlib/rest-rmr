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

Application::register_class('RawHTMLDoc', SYSDIR.'/models/raw-html-model.php');
Application::register_class('HTMLFile',   SYSDIR.'/models/html-file-model.php');

/**
 * A basic HTML representer that displays RawHTMLDoc models as text/html
 *
 * Supported internet media types (MIMEs):
 *   text/html        q=1.0 [advertised,default]
 *   * / *            q=0.001
 */
class RawHTMLDocRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'html', 1.0, TRUE),
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/html'),
			),
			array(),
			array(),
			array(
				'object:RawHTMLDoc',
				'object:HTMLFile',
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
		if ($response->modified_response()) {
			$response->body( $m->doc() );
		}
	}
}

