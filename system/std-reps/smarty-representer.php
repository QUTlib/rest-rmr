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

// FIXME: this class assumes Smarty template are always and only
//        used to create HTML documents.

/**
 * A representer which represents SmartyTemplate objects as HTML.
 *
 * Supported internet media types (MIMEs):
 *   text/html        q=1.0 [advertised,default]
 *   application/html q=0.5
 *   * / *            q=0.001
 */
class SmartyRepresenter extends BasicRepresenter {

	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text',        'html',   1.0, TRUE),
				new InternetMediaType('application', 'html',   0.5),
				new InternetMediaType('*', '*', 0.001, FALSE, 'text/html'),
			),
			array(),
			array(),
			array('object:SmartyTemplate')
		);
	}

	public function rep($m, $d, $t, $c, $l, $response) {
		// At this point Smarty completely takes over, and sends it own headers
		// and completely does its own thing.
		//
		// Let's just let it do it's thang, and abort the framework.
		$m->display( $m->template_file() );
		exit;
	}
}

