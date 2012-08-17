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
 * A basic XML representer that displays RawXMLDoc models as application/xml
 */
class RawXMLDocRepresenter extends Representer {

	public function list_types() {
		return array(
			'application/xml' => 1.0,
		);
	}

	public function can_do_model($m) {
		$m_classname = 'RawXMLDoc';
		return ($m instanceof $m_classname);
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'application/xml':
			return 1.0;
		case 'text/xml':
			return 0.9;
		case '*/*':
			return 0.001;
		default:
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('text/xml');
		} else {
			$response->content_type($t['option']);
		}
		$response->body( $m->doc );
	}
}

// ----- IMPORTANT ------------------------------------------------------
//
Application::register_representer( new RawXMLDocRepresenter() );
Application::register_class('RawXMLDoc', SYSDIR.'/models/raw-xml-model.php');
//
// ----- IMPORTANT ------------------------------------------------------

