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

/**
 * This representer specialises in pushing TemplateEngine data models
 * as 'text/html'.
 *
 * See `{SYSDIR}/utils/template-engine.inc.php`
 */
class TemplateRepresenter extends BasicRepresenter {

	/** @ignore */
	public function __construct() {
		parent::__construct(
			array(
				new InternetMediaType('text', 'html', 1.0, TRUE),
			),
			array(),
			array(),
			array(
				'object:TemplateEngine',
			)
		);
	}

	/** @ignore */
	public function rep($m, $d, $t, $c, $l, $response) {
		$response->content_type("text/html; charset=utf-8");
		// todo: magical translation magic? (fixme)
		if ($lang = $m->language())
			$response->content_language($lang);
		if (($ua=Request::header('User-Agent')) && strpos($ua,'MSIE') !== FALSE) { 
			$response->add_header('X-UA-Compatible', 'IE=edge');
			$response->add_header('X-Content-Type-Options', 'nosniff');
		}
		$response->body( $m->execFile() );
		// Allow auto-etag generation based on the template.
		// This works because TemplateEngine objects serialize
		// themselves with enough data to generate a weak ETag.
		if ($response->allow_auto_etag && !$response->etag()) {
			$response->generate_weak_etag( $m );
		}
	}

}

