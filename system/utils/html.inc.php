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

Application::register_class('HTMLNode',             SYSDIR.'/html/node.inc.php');
Application::register_class('HTMLHierarchyNode',    SYSDIR.'/html/hierarchynode.inc.php');
Application::register_class('HTMLTextNode',         SYSDIR.'/html/text.inc.php');
Application::register_class('HTMLCData',            SYSDIR.'/html/cdata.inc.php');
Application::register_class('HTMLComment',          SYSDIR.'/html/comment.inc.php');
Application::register_class('HTMLElement',          SYSDIR.'/html/element.inc.php');
Application::register_class('HTMLHead',             SYSDIR.'/html/head.inc.php');
Application::register_class('HTMLBody',             SYSDIR.'/html/body.inc.php');
Application::register_class('HTMLDocument',         SYSDIR.'/html/document.inc.php');
Application::register_class('HTMLDocumentFragment', SYSDIR.'/html/docfragment.inc.php');
require_once(SYSDIR.'/html/functions.inc.php');

