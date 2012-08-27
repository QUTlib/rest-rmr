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

class TemplateRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'html', 1.0, TRUE),
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/html'),
			),
			array(
				'object:TemplateEngine',
			)
		);
	}

	public function represent($m, $t, $response) {
		$response->content_type("text/html; charset=utf-8");
		$response->header('X-UA-Compatible', 'IE=edge');
		$response->body( $m->execFile() );
	}

}

// --- IMPORTANT: REMEMBER THIS BIT!

Application::register_representer( new TemplateRepresenter() );

// ----------------------------------------------------------------------

