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

require_once('data-cache.inc.php');

/**
 * An uninstantiable class what lets us reject requests pretty early
 * in the process, if they requester is being too requesty.
 */
class RateLimiter {
	/** How many client IP addresses to store before sweeping the cache */
	const CACHE_SIZE_LIMIT = 50;

	/**
	 * Terminates script execution early, if the request originated
	 * from a spammy DOSser.
	 *
	 * FIXME: this is not thread (or process) safe!
	 */
	public static function maybe_throttle() {
		if (!defined('RATELIMIT') || RATELIMIT <= 0) {
			return;
		}

		// FIXME: http://stackoverflow.com/a/7623231/765382
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$client_ip = $_SERVER['REMOTE_ADDR'];
		} else {
			// argh! don't know who they are!
			return;
		}
		//$now = intval(date('YmdHi'));
		$now = (time() - date('s'));
		//$now = floatval(date('Ymd.Hi'));

		/* FIVE MINUTE COOLDOWN JERKS */

		$bancache = new DataCache('request-rate-banlist', array());
		$banstore = $bancache->data();
		if (isset($banstore[$client_ip])) {
			$timeout = $banstore[$client_ip];
			if ($timeout >= $now) {
				// They didn't wait the full five minutes!
				#$banstore[$client_ip] = ($now + 5*60);
				#$bancache->data($banstore);

				header('HTTP/1.1 429 Too Many Requests', TRUE, 429);
				header('Retry-After: 300'); // come back in 5 minutes
				if (defined('DEBUG') && DEBUG) {
					// the time and date the cooldown wears off
					header('X-CoolDown-Time: '.$timeout);
					header('X-CoolDown-Date: '.gmdate('Y-m-d\TH:i:s',$timeout));
				}
				header('Content-Type: text/html; charset=ISO-8859-1');
				echo '<!DOCTYPE html><html><head><title>Too Many Requests</title></head><body><h1>429 Too Many Requests</h1><p>Too many requests this minute.  Try again in 5 minutes.</p></body></html>';
				exit;
			} else {
				// They chilled out; let's let them back in.
				unset($banstore[$client_ip]);
				$bancache->data($banstore);
			}
		}
		// TODO: sweep the banstore?

		/* REGULAR PEOPLE */

		$cache = new DataCache('request-rate-throttle', array());
		$store = $cache->data();

		if (!isset($store[$client_ip])) {
			// they haven't requested at all; set to 1
			$store[$client_ip] = array($now, 1);
		} else {
			list($minute, $count) = $store[$client_ip];
			if ($minute < $now) {
				// their last request was a minute ago; set to 1
				$store[$client_ip] = array($now, 1);
			} elseif ($count >= RATELIMIT) {
				// they've requested too many times this minute!
				// add them to the b& list, but let this request slide
				// if they try anything in the next five minute they'll
				// regret it
				$banstore[$client_ip] = ($now + 5*60);
				$bancache->data($banstore);
				unset($store[$client_ip]);
			} else {
				// they've requested already this minute; increment counter
				$store[$client_ip][1] ++;
			}
		}

		if (count($store) >= RateLimiter::CACHE_SIZE_LIMIT) {
			// the store is too full; sweep the entries and see
			// if any can be dropped (i.e. we haven't heard from
			// them yet this minute)
			$ips_to_drop = array();
			foreach ($store as $ipaddress=>$record) {
				list($minute, $count) = $record;
				if ($minute < $now) {
					$ips_to_drop[] = $ipaddress;
				}
			}
			foreach ($ips_to_drop as $ipaddress) {
				unset($store[$ipaddress]);
			}
		}

		$cache->data($store);
	}

	/**#@+ @ignore */
	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}
	/**#@-*/
}

