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
 * A basic HTML representer that displays RawHTMLDoc models as text/html
 */
class RawHTMLDocRepresenter extends Representer {

	public function list_types() {
		return array(
			'text/html' => 1.0,
		);
	}

	public function can_do_model($m) {
		return is_a($m, 'RawHTMLDoc');
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'text/html':
			return 1.0;
		case '*/*':
			return 0.001;
		default:
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('text/html');
		} else {
			$response->content_type($t['option']);
		}
		$response->body( $m->doc );
	}
}

// ----- IMPORTANT ------------------------------------------------------
//
Application::register_representer( new RawHTMLDocRepresenter() );
Application::register_class('RawHTMLDoc', 'raw-html-model.php');
//
// ----- IMPORTANT ------------------------------------------------------

