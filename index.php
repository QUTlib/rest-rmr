<?php

require_once('config.inc.php');

if (!defined('SYSDIR')) define('SYSDIR','system');
if (!defined('APPDIR')) define('SYSDIR','application');

require_once(SYSDIR.'/core.inc.php');
require_once(SYSDIR.'/application.inc.php');

Application::init();
Application::handle_request();

