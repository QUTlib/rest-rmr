<?php

Application::register_class('Hours', 'hours-handler.php');

$registrar = Application::uri_registrar('hours');

$registrar->set_interface(Application::IF_PUBLIC);
$registrar->register_handler('GET', '/branches.xsl',            'Hours->branch_xsl');
$registrar->register_handler('GET', '/branches/?',              'Hours->get_branch_model');
$registrar->register_handler('GET', '/branches/:branch_name/?', 'Hours->get_branch_model');

$registrar->set_interface(Application::IF_MACHINE);
$registrar->register_handler('GET', '/now/?', 'Hours->get_status_model');

