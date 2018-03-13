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

if (!array_key_exists('REQUEST_TIME_FLOAT',$_SERVER))
	$_SERVER['REQUEST_TIME_FLOAT'] = microtime(TRUE);
if (!array_key_exists('REQUEST_TIME',$_SERVER))
	$_SERVER['REQUEST_TIME'] = (int)($_SERVER['REQUEST_TIME_FLOAT']);

include_once('config.inc.php');

if (defined('MAINTENANCE') && MAINTENANCE) {
	// The site is currently down for maintenance.
	// Fail hard, and fail fast.
	error_log('Request rejected; site is in maintenance mode');
	header('Content-Type: text/html;charset=iso-8859-1', TRUE, 503);
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Cache-Control: post-check=0, pre-check=0', FALSE);
	header('Pragma: no-cache');
	echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><title>503 Service Unavailable</title></head>
<body><h1>Service Unavailable</h1><p>This server is currently offline for maintenance.</p><p>Please try again shortly.</p><hr></body>
</html>
HTML;
	exit;
}

// If the ENVIRONMENT config is used, we might have to set up
// the SITEHOST and SITENAME intelligently.
//
// If ENVIRONMENT is not defined, but DEV_SITEHOST or PRD_SITEHOST
// are, we can intelligently guess/set ENVIRONMENT based on the
// current SERVER_ADDR.  If no amount of intelligence can divine
// the proper environment, default to production.
//
// The SITEHOST and SITENAME are set by, in order:
// 1. define('SITEXXXX')
// 2. define(ENVIRONMENT.'_SITEXXXX')
// 3. $_SERVER['SERVER_ADDR']
//
$__SITEHOST = $__SITENAME = $_SERVER['SERVER_ADDR'];
if (!defined('ENVIRONMENT')) {
	if (defined('PRD_SITEHOST') && ($prd_addrs = @gethostbynamel(PRD_SITEHOST)) && in_array($_SERVER['SERVER_ADDR'], $prd_addrs)) {
		define('ENVIRONMENT', 'PRD');
	} elseif (defined('TST_SITEHOST') && ($tst_addrs = @gethostbynamel(TST_SITEHOST)) && in_array($_SERVER['SERVER_ADDR'], $tst_addrs)) {
		define('ENVIRONMENT', 'TST');
	} elseif (defined('DEV_SITEHOST') && ($dev_addrs = @gethostbynamel(DEV_SITEHOST)) && in_array($_SERVER['SERVER_ADDR'], $dev_addrs)) {
		define('ENVIRONMENT', 'DEV');
	} else {
		define('ENVIRONMENT', 'PRD');
	}
}
if (defined('ENVIRONMENT')) {
	switch(ENVIRONMENT) {
	case 'PRD':
		if (defined('PRD_SITEHOST')) $__SITEHOST = PRD_SITEHOST;
		if (defined('PRD_SITENAME')) $__SITENAME = PRD_SITENAME;
		break;
	case 'TST':
		if (defined('TST_SITEHOST')) $__SITEHOST = TST_SITEHOST;
		if (defined('TST_SITENAME')) $__SITENAME = TST_SITENAME;
		break;
	case 'DEV':
		if (defined('DEV_SITEHOST')) $__SITEHOST = DEV_SITEHOST;
		if (defined('DEV_SITENAME')) $__SITENAME = DEV_SITENAME;
		break;
	default:
		error_log('Warning: unrecognised ENVIRONMENT specified: "'.ENVIRONMENT.'"');
	}
}
if (!defined('SITEHOST')) define('SITEHOST', $__SITEHOST);
if (!defined('SITENAME')) define('SITENAME', $__SITENAME);

$here = dirname(__FILE__);
if (!defined('ROOTDIR')) define('ROOTDIR',realpath($here));
if (!defined('SYSDIR')) define('SYSDIR',realpath(defined('SYSTEM_DIR') ? SYSTEM_DIR : 'system'));
if (!defined('APPDIR')) define('APPDIR',realpath(defined('APPLICATION_DIR') ? APPLICATION_DIR : 'application'));

require_once(SYSDIR.'/functions.inc.php');
require_once(SYSDIR.'/password.inc.php');
require_once(SYSDIR.'/autoloader.inc.php');
require_once(SYSDIR.'/application.inc.php');

Application::init();
Application::handle_request();

