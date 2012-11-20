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
 * Note: this file starts with 'zz_' so that it is loaded last.
 */

/**
 * Uses phpinfo() to print debug information about the server.
 */
function debug_phpinfo() {
	$response = new Response(Request::http_version());
	$response->content_type('text/html');
	$response->start_recording();
	phpinfo();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about the current request object.
 * Always outputs as 'text/plain'.
 */
function debug_request() {
	$response = new Response(Request::http_version());
	$response->content_type('text/plain; charset=iso-8859-1');
	$response->start_recording();
	Request::dump();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about the current response object.
 * Always outputs as 'text/plain'.
 */
function debug_response() {
	$response = new Response(Request::http_version());
	$response->content_type('text/plain; charset=iso-8859-1');
	$response->start_recording();
	$response->dump();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about the currently-registered request handlers.
 * Always outputs as 'text/html'.
 */
function debug_handlers() {
	$response = new Response(Request::http_version());
	$response->start_recording();
	#$response->content_type('text/plain; charset=iso-8859-1');
	#URIMap::dump();
	$response->content_type('text/html; charset=iso-8859-1');
	URIMap::htmldump();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about the current-registered representers.
 * Always outputs as 'text/html'.
 */
function debug_representers() {
	$response = new Response(Request::http_version());
	$response->start_recording();
	#$response->content_type('text/plain; charset=iso-8859-1');
	#RepresentationManager::dump();
	$response->content_type('text/html; charset=iso-8859-1');
	RepresentationManager::htmldump();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about a single representer, by classname.
 */
function debug_representer() {
	$response = new Response(Request::http_version());
	$response->start_recording();

	$klass = Request::param('klass');
	$response->content_type('text/plain; charset=iso-8859-1');
	RepresentationManager::dumpClass($klass);

	$response->stop_recording();
	return $response;
}

/**
 * A simple listing of the various debug handlers.
 * Outputs as 'text/html'.
 */
function debug_index() {
	$response = new Response(Request::http_version());
	$response->content_type('text/html; charset=iso-8859-1');
	$response->body(<<<HTML
<!doctype html>
<html>
<head>
<title>Debug</title>
</head>
<body>
<h1>Debug</h1>
<ul>
<li><a href="/debug/phpinfo/">PHP Info</a></li>
<li><a href="/debug/request/">Request</a></li>
<li><a href="/debug/response/">Response</a></li>
<li><a href="/debug/handlers/">Handlers</a></li>
<li><a href="/debug/representers/">Representers</a></li>
<li><a href="/debug/error-test/">Error Test</a></li>
</ul>
</body>
</html>
HTML
	);
	return $response;
}

/**
 * Generates a random exception/error.
 */
function debug_error() {
	switch ( Request::parameter('code') ) {
		case 'exception':
		case '0':
			$key = 0;
			break;
		case 'http-exception':
		case '1':
			$key = 1;
			break;
		case 'user-error':
		case '2':
			$key = 2;
			break;
		case 'php-error':
		case '3':
			$key = 3;
			break;
		default:
			$key = mt_rand(0,3);
	}
	switch ($key) {
		case 0: throw new Exception("a debug exception occurred");
		case 1:
			$http_error_classes = array();
			foreach (get_declared_classes() as $c) {
				$r = new ReflectionClass($c);
				if (!$r->isAbstract() && $r->isSubclassOf('HttpException'))
					$http_error_classes[] = $c;
			}
			$http_error = $http_error_classes[ mt_rand(0, count($http_error_classes)-1) ];
			throw new $http_error();
		case 2: trigger_error('debug error', E_USER_ERROR);
		case 3: $x = 2 / 0;
	}
}

if (defined('DEBUG') && DEBUG) {
	URIMap::register('GET', '/debug/?', 'debug_index');
	URIMap::register('GET', '/debug/phpinfo/?', 'debug_phpinfo');
	URIMap::register('GET', '/debug/request/?', 'debug_request');
	URIMap::register('GET', '/debug/response/?', 'debug_response');
	URIMap::register('GET', '/debug/handlers/?', 'debug_handlers');
	URIMap::register('GET', '/debug/representers/?', 'debug_representers');
	URIMap::register('GET', '/debug/representers/:klass/?', 'debug_representer');
	URIMap::register('GET', '/debug/error-test/?', 'debug_error');
}
