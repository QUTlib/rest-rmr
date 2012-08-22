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

class DOMImplementation {

	### MAGIC

	### API

	public function __construct() {}
	public function createDocument($namespaceURI=NULL, $qualifiedName=NULL, $doctype=NULL) {
		$doc = new DOMDocument();
		$doc->namespaceURI = $namespaceURI;
		$doc->localName = $qualifiedName;
		$doc->doctype = $doctype;
		return $doc;
	}
	public function createDocumentType($qualifiedName=NULL, $publicId=NULL, $systemId=NULL) {
		$dt = new DOMDocumentType();
		$dt->name = $qualifiedName;
		$dt->publicId = $publicId;
		$dt->systemId = $systemId;
	}
	public function hasFeature($feature, $version) { return FALSE; } #XXX

}

