<?php
/*
 * Note: this file starts with 'zz_' so that it is loaded last.
 */

/**
 * Uses phpinfo() to print debug information about the server.
 */
function debug_phpinfo($request) {
	$response = new Response($request->http_version());
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
function debug_request($request) {
	$response = new Response($request->http_version());
	$response->content_type('text/plain; charset=iso-8859-1');
	$response->start_recording();
	$request->dump();
	$response->stop_recording();
	return $response;
}

/**
 * Prints debug info about the current response object.
 * Always outputs as 'text/plain'.
 */
function debug_response($request) {
	$response = new Response($request->http_version());
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
function debug_handlers($request) {
	$response = new Response($request->http_version());
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
function debug_representers($request) {
	$response = new Response($request->http_version());
	$response->start_recording();
	#$response->content_type('text/plain; charset=iso-8859-1');
	#RepresentationManager::dump();
	$response->content_type('text/html; charset=iso-8859-1');
	RepresentationManager::htmldump();
	$response->stop_recording();
	return $response;
}

/**
 * A simple listing of the various debug handlers.
 * Outputs as 'text/html'.
 */
function debug_index($request) {
	$response = new Response($request->http_version());
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
function debug_error($request) {
	switch ( $request->parameter('code') ) {
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
		case 0: throw new Exception("an exception occurred");
		#case 1: throw new ForbiddenException();
		case 1:
			$http_error_classes = array();
			foreach (get_declared_classes() as $c) {
				$r = new ReflectionClass($c);
				if (!$r->isAbstract() && $r->isSubclassOf('HttpException'))
					$http_error_classes[] = $c;
			}
			$http_error = $http_error_classes[ mt_rand(0, count($http_error_classes)-1) ];
			throw new $http_error();
		case 2: trigger_error('test error', E_USER_ERROR);
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
	URIMap::register('GET', '/debug/error-test/?', 'debug_error');
}

