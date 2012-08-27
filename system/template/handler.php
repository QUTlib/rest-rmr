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

require_once(SYSDIR.'/template/engine.inc.php');

class TemplateHandler {

	public function get_asset($request) {
		$file = $request->uri();
		$dir = APPDIR; //NB: uri already includes '/assets/...'
		if (is_file($dir.$file)) {
			$response = new Response($request->http_version());
			$response->body(file_get_contents($dir.$file));

			$t = 'application/octet-stream';
			if (preg_match('/\.([^.]+)$/', $file, $m)) {
				switch ($m[1]) {
				case 'css': $t = 'text/css'; break;
				case 'gif': $t = 'image/gif'; break;
				case 'jpg': $t = 'image/jpeg'; break;
				case 'ico': $t = 'image/x-icon'; break;
				// etc. add as needed
				}
			}
			$response->content_type($t);

			return $response;
		} else {
			/*throw new NotFoundException();*/
			return Response::generate(404)->append("can't find '$dir$file'");;
		}
	}

}

