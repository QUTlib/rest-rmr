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

/*
 * This file exists because our horrible sysadmins don't let us
 * use PHP DOM, and SimpleXML is terribly insufficient.
 */

function __dom_load_class($c) {
	$here = dirname(__FILE__).'/xmldom/';
	Application::register_class($c, $here.$c.'.inc.php');
}

$error_codes = array(
	'_PHP',
	'_INDEX_SIZE',
	'STRING_SIZE',
	'_HIERARCHY_REQUEST',
	'_WRONG_DOCUMENT',
	'_INVALID_CHARACTER',
	'_NO_DATA_ALLOWED',
	'_NO_MODIFICATION_ALLOWED',
	'_NOT_FOUND',
	'_NOT_SUPPORTED',
	'_INUSE_ATTRIBUTE',
	'_INVALID_STATE',
	'_SYNTAX',
	'_INVALID_MODIFICATION',
	'_NAMESPACE',
	'_INVALID_ACCESS',
	'_VALIDATION',
);
foreach ($error_codes as $v=>$n) {
	$c = 'DOM'.$n.'_ERR';
	if (!defined($c))
		define($c, $v);
}

$node_types = array(
	1=>'ELEMENT',
	'ATTRIBUTE',
	'TEXT',
	'CDATA_SECTION',
	'ENTITY_REF',
	'ENTITY',
	'PI',
	'COMMENT',
	'DOCUMENT',
	'DOCUMENT_TYPE',
	'DOCUMENT_FRAG',
	'NOTATION',
	'HTML_DOCUMENT',
	'DTD',
	'ELEMENT_DECL',
	'ATTRIBUTE_DECL',
	'ENTITY_DECL',
	'NAMESPACE_DECL',
);
foreach ($node_types as $v=>$n) {
	$c = 'XML_'.$n.'_NODE';
	if (!defined($c))
		define($c, $v);
}

$attr_types = array(
	1=>'CDATA',
	'ID',
	'IDREF',
	'IDREFS',
	'ENTITY',
	7=>'NMTOKEN',
	'NMTOKENS',
	'ENUMERATION',
	'NOTATION',
);
foreach ($attr_types as $v=>$n) {
	$c = 'XML_ATTRIBUTE_'.$n;
	if (!defined($c))
		define($c, $v);
}

__dom_load_class('DOMAttr');
__dom_load_class('DOMCdataSection');
__dom_load_class('DOMCharacterData');
__dom_load_class('DOMComment');
__dom_load_class('DOMDocument');
__dom_load_class('DOMDocumentFragment');
__dom_load_class('DOMDocumentType');
__dom_load_class('DOMElement');
__dom_load_class('DOMEntity');
__dom_load_class('DOMEntityReference');
__dom_load_class('DOMException');
__dom_load_class('DOMImplementation');
__dom_load_class('DOMNamedNodeMap');
__dom_load_class('DOMNode');
__dom_load_class('DOMNodeList');
__dom_load_class('DOMNotation');
__dom_load_class('DOMProcessingInstruction');
__dom_load_class('DOMText');
__dom_load_class('DOMXPath');

