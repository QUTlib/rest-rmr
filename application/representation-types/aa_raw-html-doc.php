<?php

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

