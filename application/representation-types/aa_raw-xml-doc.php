<?php

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
		return is_a($m, 'RawXMLDoc');
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
Application::register_class('RawXMLDoc', 'raw-xml-model.php');
//
// ----- IMPORTANT ------------------------------------------------------

