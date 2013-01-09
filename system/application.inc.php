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


add_include_path(SYSDIR);
add_include_path(APPDIR);

require_once('http-exception.inc.php');

require_once('autoloader.inc.php');

require_once('content-negotiation/internet-media-type.inc.php');
require_once('content-negotiation/language.inc.php');
require_once('content-negotiation/charset.inc.php');

require_once('uri-map.inc.php');
require_once('utils/uri-registrar.inc.php');
require_once('utils/interfaced-uri-registrar.inc.php');

require_once('representation-manager.inc.php');
require_once('representer.inc.php');
require_once('basic-representer.inc.php');

require_once('request.inc.php');
require_once('response.inc.php');
require_once('rate-limiter.inc.php');

Autoloader::register('Splunk',         SYSDIR.'/splunk.inc.php');
Autoloader::register('TemplateEngine', SYSDIR.'/utils/template-engine.inc.php');
Autoloader::register('DBConn',         SYSDIR.'/utils/dbconn.inc.php');
require_once(SYSDIR.'/utils/dao.inc.php');
require_once(SYSDIR.'/utils/html.inc.php');

/**
 * Main namespace for all application-wide values and methods.
 * Not instantiable.
 */
class Application {
	/** The application framework version.  Updated by hand. (see: ./touch ) */
	const VERSION = '1.0-r188';
	/** The application framework name. */
	const TITLE = 'REST-RMR';

	/* standard interfaces */

	/** Public interface */
	const IF_PUBLIC  = 'pub';
	/** Authenticated interface. */
	const IF_AUTHED  = 'auth';

	private static $splunk = NULL;

	/**
	 * Initialises the application; loading resource request handlers, etc.
	 *
	 * Scans and includes all {APPDIR}/resource-types/\*.php in alphabetical order,
	 * then ditto {APPDIR}/representation-types/\*.php
	 */
	public static function init() {
		ini_set('display_errors', 'Off');
		set_error_handler(array('Application','error_response'), E_ALL & (~E_STRICT));
		register_shutdown_function(array('Application','shutdown_handler'));

		if (defined('RATELIMIT') && RATELIMIT > 0) {
			RateLimiter::maybe_throttle();
		}

		$paths = array(
			APPDIR.'/resources',
			APPDIR.'/representations',
		);
		foreach ($paths as $path) {
			if (is_dir($path)) {
				$candidates = scandir($path);
				foreach ($candidates as $filename) {
					$filename = $path . '/' . $filename;
					if (is_readable($filename) && substr($filename,-4) == '.php') {
						require_once($filename);
					}
				}
			}
		}

		if (defined('SPLUNK_LOG') && SPLUNK_LOG) {
			if (is_string(SPLUNK_LOG)) {
				self::$splunk = new Splunk(SPLUNK_LOG);
			} else {
				self::$splunk = new Splunk();
			}
		}
	}

	/**
	 * If SPLUNK_LOG is defined (in config), adds a key:value facet to the current
	 * log message.
	 * @see Splunk#set($key,$value)
	 */
	public static function log($key, $value) {
		if (!is_null(self::$splunk)) {
			self::$splunk->set($key, $value);
		}
	}

	/**
	 * Register a Class so that it can be loaded later, if required.
	 *
	 * @param String $classname the name of the class
	 * @param String $filename the name of the file that defines the class
	 */
	public static function register_class($classname, $filename) {
		Autoloader::register($classname, $filename);
	}

	/**
	 * Sets up a Representer which may be able to represent a model.
	 * @param Representer $representer the Representer object to register
	 */
	public static function register_representer($representer) {
		RepresentationManager::add($representer);
	}

	/**
	 * Returns a registrar object which lets you register URI handlers.
	 * @param String $module the name of the module
	 * @return InterfacedURIRegistrar the registrar
	 */
	public static function interfaced_uri_registrar($module) {
		return new InterfacedURIRegistrar($module);
	}

	/**
	 * Returns a registrar object which lets you register URI handlers.
	 * @param String $prefix the prefix of all URIs registered
	 * @return SimpleURIRegistrar the registrar
	 */
	public static function uri_registrar($prefix) {
		return new URIRegistrar($prefix);
	}

	/**
	 * Responds to an incoming HTTP request by invoking the appropriate registered handler.
	 *
	 * Creates and commits an appropriate Response object.
	 */
	public static function handle_request() {
		Request::init();
		self::log('REQUEST_HTTPS', (int) Request::is_https());
		self::log('REQUEST_METHOD', Request::method());
		self::log('REQUEST_URI', Request::uri());

		$http_method = strtoupper(Request::method());
		if ($http_method == 'OPTIONS') {
			// If we get an OPTIONS request, try to handle it according to
			// the HTTP/1.1 spec. (i.e. report the Allowed HTTP methods)
			$uri = Request::uri();
			if ($uri == '*') {
				// general server capabilities; usually Apache intercepts this,
				// but if we do receive it, report all the HTTP methods we know
				$methods = URIMap::methods();
			} else {
				// report which HTTP methods would theoretically work for the
				// requested URI
				$methods = URIMap::allowed_methods($uri);
			}
			if ($methods) {
				// create a 200 OK response, and poke in an Allow header for the
				// HTTP methods we think are cool
				$response = new Response(Request::http_version());
				$response->header('Allow', implode(', ', $methods));
			} else {
				// no such resource; no methods would work. Send a 404 Not Found.
				$response = Response::generate(404);
			}
		} elseif ($http_method == 'TRACE') {
			// this one is flat out refused, even if it gets past Apache.
			// sending a 403 Forbidden, instead of a 405 Method Not Allowed.
			$response = Response::generate(403);
		} elseif ($http_method == 'BREW') {
			// RFC 2324
			$response = Response::generate(418);
		} else {
			// probably one of the more standard HTTP methods (GET, POST, PUT, DELETE)
			// pass it off to the handlers, and see what comes of it.
			$model = self::get_model();
			if ($model instanceof Response) {
				// short circuit; usually means get_model_for failed
				$response = $model;
			} else {
				// we have a model, now we need to represent it
				$response = self::get_response_for($model);
			}
		}
		$response->commit();
	}

	/**
	 * Uses the URIMap to route the incoming request to the most appropriate
	 * registered handler.
	 *
	 * If the handler succeeds, it returns a Model.  If not, it returns a
	 * Response object.
	 *
	 * @return mixed a Model (success), or a Response (failure)
	 */
	protected static function get_model() {
		$httpmethod = strtoupper( Request::method() );
		$uri = Request::uri();

		if (!URIMap::knows_method($httpmethod)) {
			return self::_tweak_allow_headers(Response::generate(501), $uri);
		}

		$final_response = null;
		foreach (URIMap::method($httpmethod) as $rule) {
			$match = $rule['match'];
			$regex = $match[0];
			if (preg_match($regex, $uri, $hits)) {
				$result = array_combine($match, $hits);
				array_shift($result); // drop $result[0] -- pattern=>uri
				Request::_set_params($result);

				$handler = $rule['handler'];
				$handler = URIMap::realise_handler($handler);

				if (is_callable($handler, FALSE, $handler_name)) {
					try {
						$model = call_user_func($handler);
						return $model;
					} catch (Exception $e) {
						if (is_null($final_response)) {
							$final_response = Response::generate_ex($e);
						}
					}
				} else {
					if (defined('DEBUG') && DEBUG) {
						$message = '<p class="mesg">Handler for <code>'.$uri.'</code><br>matched by pattern <code>'.$regex.'</code><br>is set to <code>'.var_export($rule['handler'],1).'</code><br>which resolves to <code>'.$handler_name.'</code><br>which is undefined.</p>';
					} else {
						$message = '<p class="mesg">Misconfigured handler for <code>'.$uri.'</code>: no such method <code>'.$handler_name.'</code></p>';
					}
					$final_response = Response::generate(500, $message, TRUE);
				}
			}
		}

		if (is_null($final_response)) {
			// could they have made any request at all against that URI?
			$allowed_methods = URIMap::allowed_methods($uri);
			if ($allowed_methods) {
				// yep -- current method not allowed
				$status = 405;
			} else {
				// nope -- no such resource
				$status = 404;
			}

			if (defined('DEBUG') && DEBUG)
				$final_response = Response::generate($status, "<p>No $httpmethod <a href=\"/debug/handlers\">handler registered</a> for '<code>$uri</code>'</p>", TRUE);
			else
				$final_response = Response::generate($status);

			if ($allowed_methods) {
				$final_response->header('Allow', implode(', ', $allowed_methods));
			}
		}

		return $final_response;
	}

	/**
	 * If the URI Map recognises the URI for any methods at all, tack
	 * an 'Allow' header onto the response.
	 * @param Response $response the current Response object, whose headers to tweak
	 * @param String $uri the current Request uri
	 * @return mixed $response
	 */
	protected static function _tweak_allow_headers($response, $uri) {
		$allowed_methods = URIMap::allowed_methods($uri);
		if ($allowed_methods)
			$response->header('Allow', implode(', ', $allowed_methods));
		return $response;
	}

	/**
	 * Uses the RepresentationManager to represent a given Model according
	 * to the incoming request.
	 * @param mixed $model the thing to represent
	 * @return Response
	 */
	protected static function get_response_for($model) {
		try {
			$response = RepresentationManager::represent($model);
		} catch (Exception $e) {
			$response = Response::generate_ex($e);
		}
		return $response;
	}

	/**
	 * Called by PHP when an error occurs.
	 * Does a little bit of niceification, then passes it off to Response.
	 * @param int $errno the level of the error raised
	 * @param string $errstr the error message
	 * @param string $errfile the filename in which the error was raised
	 * @param int $errline the line number on which the error was raised
	 */
	public static function error_response($errno, $errstr, $errfile, $errline) {
		if (error_reporting()==0) return;
		$tmp = array();
		for ($i = 0; $i < 15; $i++) {
			switch ($errno & pow(2,$i)) {
			case E_ERROR:             $tmp[] = 'E_ERROR'; break;
			case E_WARNING:           $tmp[] = 'E_WARNING'; break;
			case E_PARSE:             $tmp[] = 'E_PARSE'; break;
			case E_NOTICE:            $tmp[] = 'E_NOTICE'; break;
			case E_CORE_ERROR:        $tmp[] = 'E_CORE_ERROR'; break;
			case E_CORE_WARNING:      $tmp[] = 'E_CORE_WARNING'; break;
			case E_COMPILE_ERROR:     $tmp[] = 'E_COMPILE_ERROR'; break;
			case E_COMPILE_WARNING:   $tmp[] = 'E_COMPILE_WARNING'; break;
			case E_USER_ERROR:        $tmp[] = 'E_USER_ERROR'; break;
			case E_USER_WARNING:      $tmp[] = 'E_USER_WARNING'; break;
			case E_USER_NOTICE:       $tmp[] = 'E_USER_NOTICE'; break;
			case E_STRICT:            $tmp[] = 'E_STRICT'; break;
			case E_RECOVERABLE_ERROR: $tmp[] = 'E_RECOVERABLE_ERROR'; break;
			#case E_DEPRECATED:        $tmp[] = 'E_DEPRECATED'; break;
			#case E_USER_DEPRECATED:   $tmp[] = 'E_USER_DEPRECATED'; break;
			}
		}
		if ($tmp) $title = 'PHP Error: ' . implode(' | ', $tmp);
		else $title = "PHP Error: #$errno";
		$stack = debug_backtrace();
		array_shift($stack); // remove error_response()
		array_shift($stack); // remove errfile/errline
		Response::error($title, $errstr, $errfile, $errline, $stack);
	}

	/**
	 * Last ditch effort to catch otherwise uncaught errors.
	 * While here, apply the logger.
	 */
	public static function shutdown_handler() {
		$error_info = error_get_last();
		if ($error_info !== null && ($error_info['type'] & E_ERROR)) {
			self::error_response($error_info['type'], $error_info['message'], $error_info['file'], $error_info['line']);
		}
		if (self::$splunk) {
			self::$splunk->log();
		}
	}

	/**#@+ @ignore */
	private function __construct() {}
	private function __clone() {}
	/**#@-*/
}

