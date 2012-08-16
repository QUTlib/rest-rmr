<?php

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

