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

function debug_test() {
	$array = array();
	$array[] = 'This is a string';
	$array[] = rand();
	$array['tau'] = (M_PI * 2.0);
	$array[] = array(1,2,"banana");
	$array['kid'] = new stdClass;
	$array['kid']->foo = 'foo';
	$array['kid']->bar = 'baz';

	$model = new Model($array);
	$model->metadata()->nocache()->dublincore()->creator('Matty K')->date('2013-03-22T11:12:00+1000');

	return $model;
}

function debug_headers() {
	$html = HTMLDocument::create('Headers');
	$html->body()->add('h1', 'Headers');

	$html->body()->add('h2', 'Preconditional');
	$any = false;
	foreach (Request::preconditions() as $header=>$val) {
		$html->body()->add('h3', $header);
		$ul = $html->body()->add('ul');
		if (is_array($val)) {
			foreach ($val as $etag) {
				$ul->add('li')->add('tt', $etag);
			}
		} elseif (is_int($val)) {
			$ul->add('li', date('r', $val));
		} else {
			$ul->add('li')->add('tt', $val);
		}
		$any = true;
	}
	if (!$any) $html->body()->add('p', '(None)', array('style'=>'font-style:italic'));

	// hax
	$html->body()->add('h2', 'Misc.');
	$ul = $html->body()->add('ul');
	foreach ($_SERVER as $k=>$v) {
		if (preg_match('/^(REDIRECT_)?HTTP_([A-Z_]+)$/', $k, $m)) {
			$bits = explode('_', $m[2]);
			$bits = array_map('strtolower', $bits);
			$bits = array_map('ucfirst', $bits);
			$bits = implode('-', $bits);
			$li = $ul->add('li');
			$li->add('b', $bits);
			$li->add_text(': ');
			$li->add('tt', $v);
		}
	}

	$response = new Response();
	$response->content_type('text/html');
	$response->body($html->html());
	$response->generate_strong_etag();
	$response->allow_not_modified = FALSE;
	return $response;
}

if (defined('DEBUG') && DEBUG) {
	URIMap::register('GET', '/debug/?', 'debug_index');
	URIMap::register('GET', '/debug/phpinfo/?', 'debug_phpinfo');
	URIMap::register('GET', '/debug/error-test/?', 'debug_error');
	URIMap::register('GET', '/debug/test/?', 'debug_test');
	URIMap::register('GET', '/debug/headers/?', 'debug_headers');
}

