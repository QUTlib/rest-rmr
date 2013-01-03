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

$GLOBALS['__STARTUP__'] = microtime(TRUE);

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

$here = dirname(__FILE__);
if (!defined('ROOTDIR')) define('ROOTDIR',realpath($here));
if (!defined('SYSDIR')) define('SYSDIR',realpath(defined('SYSTEM_DIR') ? SYSTEM_DIR : 'system'));
if (!defined('APPDIR')) define('APPDIR',realpath(defined('APPLICATION_DIR') ? APPLICATION_DIR : 'application'));

require_once(SYSDIR.'/core.inc.php');
require_once(SYSDIR.'/application.inc.php');

Application::init();
Application::handle_request();

