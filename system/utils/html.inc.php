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

$dir = dirname(__FILE__).'/html/';
function __html_load_class($c, $f) {
	global $dir;
	Application::register_class('HTML'.$c, $dir.$f);
}

__html_load_class('Node',             'node.inc.php');
__html_load_class('HierarchyNode',    'hierarchynode.inc.php');
__html_load_class('TextNode',         'text.inc.php');
__html_load_class('CData',            'cdata.inc.php');
__html_load_class('Comment',          'comment.inc.php');
__html_load_class('Element',          'element.inc.php');
__html_load_class('Head',             'head.inc.php');
__html_load_class('Body',             'body.inc.php');
__html_load_class('Document',         'document.inc.php');
__html_load_class('DocumentFragment', 'docfragment.inc.php');

require_once($dir.'functions.inc.php');

