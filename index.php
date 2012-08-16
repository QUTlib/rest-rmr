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


require_once('config.inc.php');

if (!defined('SYSDIR')) define('SYSDIR','system');
if (!defined('APPDIR')) define('SYSDIR','application');

require_once(SYSDIR.'/core.inc.php');
require_once(SYSDIR.'/application.inc.php');

Application::init();
Application::handle_request();

