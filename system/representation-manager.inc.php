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


class RepresentationManager {

	private static $list = array();

### DEBUG
public static function dump() {
	print_r(self::$list);
}
public static function htmldump() {
	echo <<<HTML
<!doctype html>
<html lang="en">
<head>
<title>Debug: Representers</title>
<style type="text/css">
table,tr,th,td{vertical-align:top;border-collapse:collapse;}
tr:nth-child(even){background-color:#d8d8d8}
tr.big,tr.big th,tr.big td{border-top:1px solid black;}
table{border-bottom:1px solid black;}
tr.top th{background-color:#fff;}
th,td{padding:0 1em;}
</style>
</head>
<body>
<h1><a href="/debug/">Debug</a>: Representers</h1>
<table>
<tr class="top"><th>Class</th><th>QS</th><th>Type</th></tr>
HTML;
	foreach (self::$list as $r) {
		$a = $r->list_types();
		echo '<tr class="big">
<th>' . htmlspecialchars(get_class($r)) . '</th>
';
		$first = true;
		foreach ($a as $t=>$q) {
			if ($first)
				$first = false;
			else
				echo '<tr><td></td>';
			echo '<td>'.sprintf('%0.3f', intval($q*1000)/1000.0)."</td>\n";
			echo '<td><code>' . htmlspecialchars($t)."</code></td>\n";
		echo "</tr>\n";
		}
	}
	echo <<<HTML
</table>
<p>(Order is significant)</p>
</body>
</html>
HTML;
}
### /DEBUG

	public static function add($rep) {
		if (!($rep instanceof Representer)) {
			throw new Exception("not a Representer (" . get_class($rep) .")");
		}
		self::$list[] = $rep;
	}

	public static function represent($model, $request) {
		$accepted_types = $request->content_types();
		if (!$accepted_types) {
			// if the client didn't specify anything, we have
			// to assume they will accept everything.
			$accepted_types = array(
				1000 => array(
					array('option'=>'*/*','raw'=>'*/*' ),
				)
			);
		}

		$reps = array();
		$best_rep = NULL;
		$best_type = NULL;
		$best_qvalue = 0;
		foreach (self::$list as $rep) {
			if ($rep->can_do_model($model)) {
				$reps[] = $rep;
				$array = $rep->pick_best($accepted_types);
				if ($array && $array['weight'] > $best_qvalue) {
					$best_rep = $rep;
					$best_type = $array['type'];
					$best_qvalue = $array['weight'];
				}
			}
		}

		if ($best_rep) {
			$response = new Response($request->http_version());
			$best_rep->represent($model, $best_type, $response);
		} else {
			// urgh.. build up a nice response
			$response = self::generate406( $request->uri(), $reps );
		}
		return $response;
	}

	protected static function generate406($uri, $reps) {
		$array = array();
		foreach ($reps as $rep) {
			foreach ($rep->list_types() as $type => $qs) {
				if (!isset($array[$type]) || $array[$type] < $qs) {
					$array[$type] = $qs;
				}
			}
		}

		$response = new Response(NULL, 406);
		if (count($array) > 0) {
			$alts = array();
			$html = array();
			foreach ($array as $type => $qs) {
				// NOTE: we're not playing fair according to RFC2295 because we're
				// putting fragment identifiers in the variant URIs.  For some reason
				// they insist on these silly 'neighboring variant's
				$alts[] = sprintf('{"%s#%s" %0.3f {type %s}}', $uri, $type, $qs, $type);
				$html[] = sprintf('<li><code>%s</code> [%0.3f]</li>', htmlspecialchars($type), $qs);
			}
			$alts = implode(', ', $alts);
			$html = implode('', $html);
			$response->header('Vary', 'negotiate, accept')
				->header('TCN', 'list')
				->header('Alternates', $alts)
				->content_type('text/html; charset=iso-8859-1')
				->body( Response::generate_html('Not Acceptable', <<<HTML
    <p>The resource you requested could not be delivered in an acceptable format.</p>
    <p>Supported formats are:</p>
    <ul>$html</ul>
HTML
			));
		}

		return $response;
	}

	private function __construct() {}
	private function __clone() {}

}

