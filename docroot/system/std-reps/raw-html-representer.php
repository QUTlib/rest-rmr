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

Application::register_class('HTMLString', SYSDIR.'/std-models/html-string-model.php');
Application::register_class('HTMLFile',   SYSDIR.'/std-models/html-file-model.php');

/**
 * A basic HTML representer that displays raw HTML document models as text/html
 *
 * Supported internet media types (MIMEs):
 *
 *     text/html        q=1.0 [advertised,default]
 */
class RawHTMLDocRepresenter extends BasicRepresenter {

	/** @ignore */
	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'html', 1.0, TRUE),
			),
			array(),
			array(),
			array(
				'object:HTMLString',
				'object:HTMLFile',
			)
		);
	}

	/** @ignore */
	public function rep($m, $d, $t, $c, $l, $response) {
		$this->response_type($response, $t, $c);
		$this->response_language($response, $l, FALSE);
		if (($ua=Request::header('User-Agent')) && strpos($ua,'MSIE') !== FALSE) { 
			$response->add_header('X-UA-Compatible', 'IE=edge');
			$response->add_header('X-Content-Type-Options', 'nosniff');
		}
		if ($mtime = $m->mtime()) {
			$response->last_modified($mtime);
			$response->cache();
		}
		if ($response->is_modified()) {
			$response->body( $m->doc() );
		}
	}
}

