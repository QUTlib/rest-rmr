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

$dir = dirname(__FILE__);
chdir($dir);

require_once('node.inc.php');
require_once('hierarchynode.inc.php');
require_once('text.inc.php');
require_once('cdata.inc.php');
require_once('comment.inc.php');
require_once('element.inc.php');
require_once('head.inc.php');
require_once('body.inc.php');
require_once('document.inc.php');
require_once('docfragment.inc.php');

require_once('functions.inc.php');

/* ------------------------------------------------------------------- */

$tmp = file_get_contents('../../../application/default-template.thtml');
foreach (array(true,false) as $trim) {
	$dom = parse_html($tmp, $trim);
	$trim = $trim ? 'true' : 'false';
	echo "===== HTML [$trim] =====\n";
	echo $dom->html();
	echo "\n";
	echo "===== XML [$trim] =====\n";
	echo $dom->xml();
	echo "\n";
}

