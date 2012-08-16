<?php

function debug_phpinfo($request) {
	$response = new Response($request->http_version());
	$response->content_type('text/html');
	$response->start_recording();
	phpinfo();
	$response->stop_recording();
	return $response;
}

function debug_request($request) {
	$response = new Response($request->http_version());
	$response->content_type('text/plain');
	$response->start_recording();
	$request->dump();
	$response->stop_recording();
	return $response;
}

function debug_response($request) {
	$response = new Response($request->http_version());
	$response->content_type('text/plain');
	$response->start_recording();
	$response->dump();
	$response->stop_recording();
	return $response;
}

function debug_handlers($request) {
	$response = new Response($request->http_version());
	$response->start_recording();
	#$response->content_type('text/plain');
	#URIMap::dump();
	$response->content_type('text/html');
	URIMap::htmldump();
	$response->stop_recording();
	return $response;
}

function debug_representers($request) {
	$response = new Response($request->http_version());
	$response->start_recording();
	#$response->content_type('text/plain');
	#RepresentationManager::dump();
	$response->content_type('text/html');
	RepresentationManager::htmldump();
	$response->stop_recording();
	return $response;
}

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

function debug_error($request) {
	switch (mt_rand(0,3)) {
	case 0: throw new Exception("an exception occurred");
	case 1: throw new ForbiddenException();
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

