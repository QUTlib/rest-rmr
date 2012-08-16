<?php

require_once('core-serialisation-classes.php');

class BranchYAMLRepresenter extends YAMLRepresenter {

	public function can_do_model($model) {
		return is_a($model, 'BranchList');
	}

	public function represent($m, $t, $response) {
		return parent::represent($m->branches, $t, $response);
	}
}

Application::register_representer( new BranchYAMLRepresenter() );

// ----------------------------------------------------------------------

