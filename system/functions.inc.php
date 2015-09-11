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
 * Adds the given path(s) to PHP's include_path.
 *
 * @param String... $path Paths/directories to add.
 * @return The old include_path on success, or FALSE on failure.
 * @emits E_USER_WARNING if a given path is not an actual directory.
 */
function add_include_path($path) {
	$paths = explode(PATH_SEPARATOR, get_include_path());
	foreach (func_get_args() as $path) {
		if (!file_exists($path) OR (file_exists($path) && filetype($path) !== 'dir')) {
			trigger_error("Include path '{$path}' does not exist", E_USER_WARNING);
			continue;
		}
		if (array_search($path, $paths) === false)
			array_push($paths, $path);
	}
	return set_include_path(implode(PATH_SEPARATOR, $paths));
}

/**
 * Removes the give path(s) from PHP's include_path.
 *
 * @param String... $path Paths/directories to remove.
 * @return The old include_path on success, or FALSE on failure.
 * @emits E_USER_NOTICE if removing a directory would leave the include_path empty.
 */
function remove_include_path($path) {
	$paths = explode(PATH_SEPARATOR, get_include_path());
	foreach (func_get_args() as $path) {
		if (($k = array_search($path, $paths)) !== false)
			if (count($paths) > 1)
				unset($paths[$k]);
			else
				trigger_error("Include path '{$path}' can not be removed because it is the only", E_USER_NOTICE);
	}
	return set_include_path(implode(PATH_SEPARATOR, $paths));
}

/**
 * How long has elapsed since the request started, in seconds.
 */
function elapsed() {
	return microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT'];
}

/**
 * How long has elapsed since the request started, in whole-number seconds.
 */
function elapsed_grainy() {
	return time() - $_SERVER['REQUEST_TIME'];
}

/**
 * Gets a HTTP-date for the given time (or 'now'), in RFC822/RFC1123 format.
 */
function httpdate($timestamp=NULL) {
	if (func_num_args() > 0) {
		return gmdate('D, d M Y H:i:s T', $timestamp);
	} else {
		return gmdate('D, d M Y H:i:s T');
	}
}

/**
 * Gets the last modified timestamp of all included source files.
 * If $datamtime is given, it is taken into account as well.
 */
function calculate_last_modified($datamtime=NULL) {
	$incls = get_included_files();
	$incls = array_filter($incls, 'is_file');
	$mod_times = array_map('filemtime', $incls);
	if (func_num_args() > 0) {
		$mod_times[] = $datamtime;
	}
	return max($mod_times);
}

/**
 * Presents a readable version of a value.
 * Equivalent to Ruby's Object#inspect
 */
function inspect($v) {
	$str = var_export($v, TRUE);
	$str = preg_replace('/\s+/', '', $str);
	return $str;
}

/**
 * Like json_encode() but non-crap.
 */
function json_encode_v2($v, $as_object=false) {
	// convert objects to arrays, with different open and close tokens
	if (is_object($v)) {
		if (method_exists($v,'jsonSerialize')) {
			$w = $v->jsonSerialize();
			if ($w != $v) {
				// FIXME: this can still result in an infinite loop
				return json_encode_v2($w, true);
			} else {
				$v = $w;
			}
		} else {
			$a = array(); foreach ($v as $k=>$e) $a[$k] = $e;
			$v = $a;
		}
		$as_object = true;
	}
	// detect object-style arrays
	elseif (is_array($v)) {
		// extract and sort the keys
		$keys = array_keys($v);
		sort($keys);
		// check that they're all in-order integers
		$exp = 0;
		foreach ($keys as $k) {
			if ($k != $exp) {
				$as_object = true;
				break;
			}
			$exp ++;
		}
	}
	// special-handle arrays (and objects), fallback for everything else
	if (is_array($v)) {
		$s = ($as_object ? '{' : '[');
		$j = '';
		foreach ($v as $k=>$e) {
			$s .= $j;
			if ($as_object) {
				#$s .= var_export($k,1).":";
				$s .= "\"$k\":";
			}
			$s .= json_encode_v2($e);
			$j = ',';
		}
		$s .= ($as_object ? '}' : ']');
		return $s;
	} else {
		return json_encode($v);
	}
}

