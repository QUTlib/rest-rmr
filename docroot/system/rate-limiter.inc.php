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

require_once('utils/data-cache.inc.php');

/**
 * An uninstantiable class that lets us reject requests pretty early
 * in the handling process, if the requester is being too requesty.
 *
 * Uses the RATELIMIT value, optionally defined in config.
 */
class RateLimiter {
	/** How many client IP addresses to store before sweeping the cache */
	const CACHE_SIZE_LIMIT = 50;

	/** How many seconds to wait before servicing requests from a spammer */
	const COOLDOWN_PERIOD = 300;
	const COOLDOWN_TEXT = '5 minutes';

	/**
	 * Terminates script execution early, if the request originated
	 * from a spammy DOSser.
	 *
	 * WARNING: this is not thread (or process) safe!
	 */
	public static function maybe_throttle() {
		if (!defined('RATELIMIT') || RATELIMIT <= 0) {
			return;
		}

		// who they are
		$client_ip = Request::get_client_ip();

		// if they're whitelisted, let them straight through
		global $RATELIMIT_WHITELIST;
		if (isset($RATELIMIT_WHITELIST) && in_array($client_ip, $RATELIMIT_WHITELIST)) {
			return;
		}

		// the current time, to the nearest wall-clock minute
		$now = (time() - date('s'));

		/* ----- FIVE MINUTE COOLDOWN JERKS ----- */

		// If the current client is in the five-minute-cooldown bin,
		// check to see if their time has expired yet.
		$bancache = new DataCache('request-rate-banlist', array());
		$banstore = $bancache->data();
		if (isset($banstore[$client_ip])) {
			$timeout = $banstore[$client_ip];
			if ($timeout >= $now) {
				// They didn't wait the full five minutes!
				#$banstore[$client_ip] = ($now + RateLimiter::COOLDOWN_PERIOD);
				#$bancache->data($banstore);

				header('HTTP/1.1 429 Too Many Requests', TRUE, 429);
				// always tell them the max time; no skin off our nose if they only
				// had two seconds before their cooldown wears off.
				header('Retry-After: '.RateLimiter::COOLDOWN_PERIOD);
				if (defined('DEBUG') && DEBUG) {
					// the actual time and date the cooldown wears off
					header('X-CoolDown-Time: '.$timeout);
					header('X-CoolDown-Date: '.gmdate('Y-m-d\TH:i:s',$timeout));
				}
				// required response entity, describing the issue
				header('Content-Type: text/html; charset=ISO-8859-1');
				echo '<!DOCTYPE html><html><head><title>Too Many Requests</title></head><body><h1>429 Too Many Requests</h1><p>Too many requests this minute.  Try again in '.RateLimiter::COOLDOWN_TEXT.'.</p></body></html>';
				exit;
			} else {
				// They chilled out; let's let them back in.
				unset($banstore[$client_ip]);
				$bancache->data($banstore);
			}
		}

		// If the cooldown bin is getting full, do a mark-and-sweep to
		// see if we can reduce it some.
		// This is only run if the current request isn't already
		// blocked for being to spammy.
		if (count($banstore) >= RateLimiter::CACHE_SIZE_LIMIT) {
			// the store is too full; sweep the entries and see
			// if any can be dropped (i.e. we haven't heard from
			// them yet this minute)
			$ips_to_drop = array();
			foreach ($store as $ipaddress=>$timeout) {
				if ($timeout < $now) {
					$ips_to_drop[] = $ipaddress;
				}
			}
			if ($ips_to_drop) {
				foreach ($ips_to_drop as $ipaddress) {
					unset($banstore[$ipaddress]);
				}
				$bancache->data($banstore);
			}
		}

		/* ----- REGULAR PEOPLE ----- */

		$cache = new DataCache('request-rate-throttle', array());
		$store = $cache->data();

		// Check to see if the current client has made any
		// requests this minute, and if they have, make sure
		// they haven't made too many.
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
				$banstore[$client_ip] = ($now + RateLimiter::COOLDOWN_PERIOD);
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

