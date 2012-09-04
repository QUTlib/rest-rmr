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

class TemplateEngine {

	/** Items that can be replaced using %%ITEM%% patterns. */
	private $items = array(
		'BASEURL'    => '',
		'TITLE_PREFIX'    => SITENAME,
		'TITLE_SEPARATOR' => ' | ',
		'_DOCTITLE'  => '',
		'_PAGETITLE' => '',
		'CONTENT'    => '',
		'CSS'        => '',
		# Magic:
		'DOCTITLE'  => SITENAME,
		'PAGETITLE' => SITENAME,
	);

	private $default_filename = NULL;

	private static $fallback_filename = NULL;

	/**
	 * Creates a new template engine, associated with a request.
	 */
	public function __construct($default_filename=NULL) {
		if (is_null(TemplateEngine::$fallback_filename))
			TemplateEngine::$fallback_filename = APPDIR.'/default-template.thtml';
		$this->default_filename = $default_filename;
	}

	/**
	 * Get or set the BASEURL.
	 */
	public function baseurl($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('BASEURL');
		} else {
			return $this->set('BASEURL', $value);
		}
	}

	/**
	 * Get or set the prefix for both DOCTITLE and PAGETITLE.
	 */
	public function title_prefix($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('TITLE_PREFIX');
		} else {
			return $this->set('TITLE_PREFIX', $value, TRUE);
		}
	}

	/**
	 * Get or set the separator that joins PREFIX to DOCTITLE or PAGETITLE.
	 */
	public function title_separator($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('TITLE_SEPARATOR');
		} else {
			return $this->set('TITLE_SEPARATOR', $value, TRUE);
		}
	}

	/**
	 * Get or set the document title (from the HTML HEAD).
	 */
	public function doctitle($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('_DOCTITLE');
		} else {
			return $this->set('_DOCTITLE', $value, TRUE);
		}
	}

	/**
	 * Get or set the page title (in the print header).
	 */
	public function pagetitle($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('_PAGETITLE');
		} else {
			return $this->set('_PAGETITLE', $value, TRUE);
		}
	}

	/**
	 * Sets both PAGETITLE and DOCTITLE at the same time.
	 */
	public function set_title($value) {
		$this->set('_DOCTITLE', $value);
		return $this->set('_PAGETITLE', $value, TRUE);
	}

	/**
	 * Get or set the actual doctitle (get: TITLE_PREFIX, TITLE_SEPARATOR, DOCTITLE; set: override).
	 */
	public function full_doctitle($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('DOCTITLE');
		} else {
			return $this->set('DOCTITLE', $value);
		}
	}

	/**
	 * Get the actual pagetitle (get: TITLE_PREFIX, TITLE_SEPARATOR, PAGETITLE; set: override).
	 */
	public function full_pagetitle($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('PAGETITLE');
		} else {
			return $this->set('DOCTITLE', $value);
		}
	}

	/**
	 * Get or set the page content.
	 */
	public function content($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('CONTENT');
		} else {
			return $this->set('CONTENT', $value);
		}
	}

	/**
	 * Append some text to the page content.
	 */
	public function append($content) {
		$this->items['CONTENT'] .= $content;
		return $this;
	}

	/**
	 * Get or set the custom stylesheet.
	 */
	public function css($value=NULL) {
		if (func_num_args() < 1) {
			return $this->get('CSS');
		} else {
			return $this->set('CSS', $value);
		}
	}

	/**
	 * Append some css to the page's custom stylesheet.
	 */
	public function append_css($css) {
		$this->items['CSS'] .= $css;
		return $this;
	}

	/** Get one of the templatable items. NULL if undefined. */
	public function get($prop) {
		if (isset($this->items[$prop]))
			return $this->items[$prop];
		return NULL;
	}

	/** Set one of the templatable items. */
	public function set($prop,$value,$recalculate_title=FALSE) {
		$this->items[$prop] = $value;
		if ($recalculate_title) {
			$pfx = $this->get('TITLE_PREFIX');
			$sep = $this->get('TITLE_SEPARATOR');
			foreach (array('DOCTITLE', 'PAGETITLE') as $key) {
				if ($sfx = $this->get("_$key")) {
					$this->set($key, $pfx.$sep.$sfx);
				} else {
					$this->set($key, $pfx);
				}
			}
		}
		return $this;
	}

	/** Get one of the templatable items from a local list. NULL if undefined. */
	public function get_local($items,$prop) {
		if (isset($items[$prop]))
			return $items[$prop];
		return NULL;
	}

	/** Set one of the templatable items in a local list. */
	public function set_local(&$items,$prop,$value,$recalculate_title=FALSE) {
		$items[$prop] = $value;
		if ($recalculate_title) {
			$pfx = $this->get_local($items, 'TITLE_PREFIX');
			$sep = $this->get_local($items, 'TITLE_SEPARATOR');
			foreach (array('DOCTITLE', 'PAGETITLE') as $key) {
				if ($sfx = $this->get_local($items, "_$key")) {
					$this->set_local($items, $key, $pfx.$sep.$sfx);
				} else {
					$this->set_local($items, $key, $pfx);
				}
			}
		}
		return $this;
	}

	/**
	 * Run template substitutions on a string, and return the
	 * final product.
	 *
	 * @see #execFile
	 */
	public function exec($string, $items=NULL) {
		// first extract page-local variables
		if (is_null($items)) $items = $this->items;
		$pattern = '/^\s*%set ([^=]+)=(.*)(\r\n|\r|\n)+/';
		while ($string && preg_match($pattern, $string, $m)) {
			$var = $m[1];
			$val = $m[2];
			switch ($var) {
			#case 'DOCTITLE':
			#case 'PAGETITLE':
			case 'TITLE_PREFIX':
			case 'TITLE_SEPARATOR':
				$this->set_local($items,$var,$val, TRUE);
				break;
			default:
				$this->set_local($items,$var,$val);
			}
			$string = substr($string, strlen($m[0]));
		}
		// then substitute away!
		$result = '';
		$pattern = $this->regex($items);
		while ($string && preg_match($pattern, $string, $m)) {
			$result .= $m[1];
			if ($key = $m[2]) {
				$result .= $items[$key];
			} elseif ($file = $m[3]) {
				$result .= $this->execFile($file, $items);
			} elseif ($method = $m[4]) {
				$result .= $this->invoke($method, $items);
#			} else {
#				// FIXME
#				$result .= '<i>'.$m[5].'</i>';
			}
			$string = substr($string, strlen($m[0]));
		}
		$result .= $string;
		return $result;
	}

	/**
	 * Construct the regular expression used to search for
	 *  %%PROPERTY%% and %<file>% type tags.
	 */
	protected function regex($items) {

		# %%PROPERTY%%
		$property_keys = array();
		foreach ($items as $key=>$val) {
			$property_keys[] = preg_quote($key);
		}
		$property_regexp = '%('.implode('|',$property_keys).')%';

		# %<some-file.mhtml>%
		$file_regexp = '<([^>]+)>';

		# %?METHOD:param:param?%
		$method_regexp = '\?([^?]+)\?';

#		return "/^(.*)%(?:$property_regexp|$file_regexp|$method_regexp|%([^%]+)%)%/Us";
		return "/^(.*)%(?:$property_regexp|$file_regexp|$method_regexp)%/Us";
	}

	/**
	 * Get the contents of a template file.
	 */
	public function load($filename,$fatal=FALSE) {
		$files = array(
			$filename,
			APPDIR.'/'.$filename,
			$filename.'.thtml',
			APPDIR.'/'.$filename.'.thtml',
		);

		foreach ($files as $fullname) {
			if (file_exists($fullname)) {
				return file_get_contents($fullname);
			}
		}

		if ($fatal) {
			throw new Exception('failed to load "'.$filename.'" : no such file');
		} else {
			error_log('failed to load "'.$filename.'" : no such file');
			return '';
		}
	}

	/**
	 * Tries as hard as it can to get a filename, given either a String or NULL.
	 *
	 * $f -> $this->default_filename -> TemplateEngine::$fallback_filename -> err
	 *
	 */
	protected function resolve_filename($f) {
		// if they gave us something real-looking, use it
		if (!is_null($f)) {
			return $f;
		}
		// if we were given a filename in the constructor, use that
		if (!is_null($this->default_filename)) {
			return $this->default_filename;
		}
		// if the default-template file exists, use that
		if (file_exists(TemplateEngine::$fallback_filename) && is_readable(TemplateEngine::$fallback_filename)) {
			return TemplateEngine::$fallback_filename;
		}
		// I give up
		throw new Exception("no filename given");
	}

	/**
	 * Get the contents of a template file, run the template
	 * substitutions on it, and return the final product.
	 *
	 * @see #exec
	 * @see #load
	 */
	public function execFile($filename=NULL, $items=NULL) {
		$filename = $this->resolve_filename($filename);
		$doc = $this->load($filename);
		return $this->exec($doc, $items);
	}

	/**
	 * Returns TRUE iff a call to execFile wouldn't die because of bad file stuff.
	 */
	public function canExec($filename=NULL) {
		try {
			$filename = $this->resolve_filename($filename);
			$this->load($filename, TRUE);
			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	/**
	 * Tries as hard as it can to get a sitemap filename, given either a String or NULL.
	 *
	 * $f -> dir($this->default_filename).$f -> APPDIR.$f
	 *
	 */
	protected function find_sitemap($f=NULL) {
		if (is_null($f)) $f = 'sitemap.inf';

		$filenames = array();
		$filenames[] = $f;
		if (isset($this->default_filename) && ($dir = dirname($this->default_filename)) && ($dir != '.'))
			$filenames[] = rtrim($dir, '/').'/'.$f;
		$filenames[] = APPDIR.'/'.$f;

		foreach ($filenames as $filename) {
			if (file_exists($filename) && is_readable($filename)) {
				return $filename;
			}
		}
if(defined('DEBUG')&&DEBUG)
		throw new Exception("file not found: ".implode(', ',$filenames));
else
		throw new Exception("file not found: '$f'");
	}

	/**
	 * Locates, opens, and parses a sitemap file.  Returns the parsed content, as an array.
	 */
	public function load_sitemap($f=NULL) {
		$raw = parse_ini_file($this->find_sitemap($f), TRUE);

		$sitemap = array();
		foreach ($raw as $base=>$pages) {
			$base = rtrim($base, '/');
			if (!$base) $base = '';

			$map = array();
			foreach ($pages as $pattern=>$breadcrumb) {
				$parts = array();
				foreach (explode('*', $base.$pattern) as $p) {
					$parts[] = preg_quote($p, '#');
				}
				$regex = '#^' . implode('([^/]+)', $parts) . '$#';
				$map[$regex] = $breadcrumb;
			}
			$sitemap[$base] = $map;
		}
		return $sitemap;
	}

	/**
	 * Generates an array of ( $path=>'crumb' ) pairs for each directory in the
	 * current request's URI.
	 */
	public function breadcrumbs($f=NULL) {
		$sitemap = $this->load_sitemap($f);

		$url = Request::uri();
		$url = str_replace('//', '/', $url);

		// FIND AND REMOVE THE COMMON BASE (if any)
		// This finds the longest matching 'base' part.
		$base = NULL;
		$map  = NULL;
		foreach ($sitemap as $b=>$m) {
			if (!$b || substr($url,0,strlen($b)) == $b) {
				if (is_null($base) || strlen($b) > strlen($base)) {
					$base = $b;
					$map = $m;
				}
			}
		}
		if (is_null($map)) return NULL;
		if ($base) $url = substr($url, strlen($base));

		// WORK OUT HOW/WHEN TO APPEND SLASHES
		if (substr($url,-1)=='/'){
			$url=rtrim($url,'/');
			$isdir=true;
		}else{
			$isdir=false;
		}

		// FOR EACH DIRECTORY IN THE URL, ADD DIRPATH=>CRUMB TO AN ARRAY
		$parts = explode('/',$url);
		$n = count($parts) - 1;
		$array = array();
		$accum = $base;
		foreach ($parts as $i=>$p) {
			$accum .= $p . (($i==$n&&!$isdir) ? '' : '/');
			$got = false;
			foreach ($map as $pattern=>$crumb) {
				if (preg_match($pattern, $accum)) {
					$array[$accum] = preg_replace($pattern, $crumb, $accum);
					continue 2;
				}
			}
			$array[$accum] = $p;
		}
		return $array;
	}

	/**
	 * Commands are of the form:
	 *
	 *   COMMAND
	 *   COMMAND:param
	 *   COMMAND:param1:param2:...
	 *
	 * FIXME !!?
	 */
	public function invoke($command, $items=NULL) {
		if (is_null($items)) $items = $this->items;
		$args = explode(':', $command);
		$cmd = 't_'.array_shift($args);
		array_unshift($args, $items);
		return call_user_func_array( array($this, $cmd), $args );
	}

	// FIXME ??
	protected function t_LASTMODIFIED($items) {
		return date('M j, Y');
	}

	// prints the current date
	// if long is given and not 'short', uses a long format
	protected function t_NOW($items, $long=FALSE) {
		if ($long && strtolower($long) != 'short') {
			return date('c');
		} else {
			return date('M j, Y');
		}
	}

	protected function t_BENCHMARK($items) {
		return sprintf('%0.3f', elapsed());
	}

	protected function t_SELECTED($items, $page) {
		$page = ltrim($page, '/');

		$regex = '/^';
		if (substr($page, -1) == '*') {
			$page = substr($page, 0, -1);
			$regex .= preg_quote($page,'/' );
			$regex .= '/';
		} else {
			$regex .= preg_quote($page,'/');
			$regex .= '$/';
		}

		if (preg_match($regex, Request::get_page())) {
			return 'selected';
		} else {
			return '';
		}
	}

	protected function t_BREADCRUMBS($items) {
		$filename = $this->get_local($items, 'SITEMAP'); // may be NULL
		$sitemap = $this->breadcrumbs($filename);

		$s = '';
		$s .= '<div id="breadcrumb" class="breadcrumb"><span class="bold">location:</span> ';
		$s .= '<ul id="breadcrumb-list">';
		#$s .= '<li><a href="'.$base.'/">home</a></li>';

		foreach ($sitemap as $path=>$crumb) {
			$s .= '<li><a href="'.$path.'">'.$crumb.'</a></li>';
		}

		$s .= '</ul>';
		$s .= '</div><div class="clear"></div>';
		return $s;
	}

}

