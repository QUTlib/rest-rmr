<?php

class BranchXMLRepresenter extends Representer {

	public function list_types() {
		return array(
			'application/branch+xml' => 1.0,
			'application/xml' => 0.9,
		);
	}

	public function can_do_model($model) {
		return is_a($model, 'BranchList');
	}

	public function preference_for_type($t) {
		switch ($t['option']) {
		case 'application/branch+xml':
			return 1.0;
		case 'application/xml':
			return 0.9;
		case '*/*':
			return 0.001;
		default:
			return 0.0;
		}
	}

	public function represent($m, $t, $response) {
		if ($t['option'] == '*/*') {
			$response->content_type('application/xml');
		} else {
			$response->content_type($t['option']);
		}
		$response->body( $this->xml_branch_encode($m) );
	}

	protected function xml_branch_encode($list) {
		$result = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n";
		$result .= "<?xml-stylesheet href=\"/pub/hours/branches.xsl\" type=\"text/xsl\"?>\n";
		$result .= "<branches xmlns=\"http://www.library.qut.edu.au/hours/\">\n";
		foreach ($list->branches as $branch) {
			$id   = $branch['branch_id'];
			$name = htmlspecialchars($branch['name']);
			$url  = htmlspecialchars($branch['url']);
			$result .= "  <branch branch-id=\"$id\" name=\"$name\" url=\"$url\">\n";

			$status = $branch['status'];
			if (isset($branch['tbd']) && $branch['tbd']) {
				$tbd = " tbd=\"tbd\"";
			} else {
				$tbd = '';
			}
			if (isset($branch['closing_time'])) {
				$closing_time = htmlspecialchars($branch['closing_time']);
				$result .= "    <status$tbd open=\"$status\">\n";
				$result .= "      <close time=\"$closing_time\"/>\n";
				$result .= "    </status>\n";
			} else {
				$result .= "    <status$tbd open=\"$status\"/>\n";
			}

			if (isset($branch['description'])) {
				$desc = htmlspecialchars($branch['description']);
				$result .= "    <description>$desc</description>\n";
			}

			if (isset($branch['address']) || isset($branch['phone'])) {
				$addr = nl2br(htmlspecialchars($branch['address']));
				$phone= htmlspecialchars($branch['phone']);
				$result .= "    <contact>\n";
				if (isset($branch['address'])) {
					$addr = nl2br(htmlspecialchars($branch['address']));
					$result .= "      <address>$addr</address>\n";
				}
				if (isset($branch['phone'])) {
					$phone= htmlspecialchars($branch['phone']);
					$result .= "      <phone>$phone</phone>\n";
				}
				$result .= "    </contact>\n";
			}

			if (isset($branch['notes'])) {
				$notes= htmlspecialchars($branch['notes']);
				$result .= "    <notes>$notes</notes>\n";
			}

			if (isset($branch['aliases'])) {
				$result .= "    <aliases>\n";
				foreach ($branch['aliases'] as $alias) {
					$alias = htmlspecialchars($alias);
					$result .= "      <alias>$alias</alias>\n";
				}
				$result .= "    </aliases>\n";
			}

			$result .= "  </branch>\n";
		}
		$result .= "</branches>\n";
		return $result;
	}

}

// --- IMPORTANT: REMEMBER THIS BIT!

Application::register_representer( new BranchXMLRepresenter() );

// ----------------------------------------------------------------------

