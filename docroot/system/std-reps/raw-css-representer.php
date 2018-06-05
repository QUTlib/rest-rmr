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

Application::register_class('CSSString', SYSDIR.'/std-models/css-string-model.php');
Application::register_class('CSSFile',   SYSDIR.'/std-models/css-file-model.php');

/**
 * A basic CSS representer that displays raw CSS document models as text/css
 *
 * Supported internet media types (MIMEs):
 *
 *     text/css                q=1.0 [advertised,default]
 *     application/x-pointplus q=1.0 [converted to text/css]
 */
class RawCSSDocRepresenter extends BasicRepresenter {

	/** @ignore */
	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'css', 1.0, TRUE),
				new InternetMediaType('application', 'x-pointplus', 1.0, FALSE, 'text/css'), # yeah, yeah, shut up
			),
			array(),
			array(),
			array(
				'object:CSSString',
				'object:CSSFile',
			)
		);
	}

	/** @ignore */
	public function rep($m, $d, $t, $c, $l, $response) {
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

