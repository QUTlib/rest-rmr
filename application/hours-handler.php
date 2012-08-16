<?php

require_once('branchdb.inc.php');

class Hours {

	function get_branch_model($request) {
		$branch_name = $request->param('branch_name');
		$now = date('H')*3600+date('i')*60+date('s');
		$db = new BranchDB();

		if ($branch_name) {
			$branches = $db->getBranchByName($branch_name, array('branch_id','name','description','address','phone','url','notes'));
		} else {
			$branches = $db->getBranches(array('branch_id','name','description','address','phone','url','notes'));
		}

		$list = new BranchList();
		foreach ($branches as $branch) {
			// ----- aliases
			$aliases = $db->getBranchAliases($branch['branch_id']);

			$aka = array();
			if ($aliases) {
				foreach ($aliases as $alias) {
					$aka[] = $alias['alias'];
				}
			}
			$branch['aliases'] = $aka;

			// ----- status
			$is_tbd = true;
			$is_open = false;
			$periods = $db->getBranchStatus($branch['branch_id']);
			if ($periods) {
				$period = reset($periods);
				if ($period['is_tbd'] == 0) {
					$is_tbd = false;
				}
				if ($period['is_closed'] == 0) {
					// could still be closed; check the time
					if ($now >= $period['open_time'] && $now <= $period['close_time']) {
						$is_open = $period['nice_close_time'];
					}
				}
			}
			if ($is_open) {
				$branch['status'] = 'open';
				$branch['closing_time'] = $is_open;
			} else {
				$branch['status'] = 'closed';
			}
			if ($is_tbd) {
				$branch['tbd'] = true;
			}

			$list->add($branch);
		}

		return $list;
	}

	function get_status_model($request) {
		$branch_name = $request->query_parameter('branch_name');
		$now = date('H')*3600+date('i')*60+date('s');
		$db = new BranchDB();

		$list = new BranchList();
		if ($branch_name) {
			$branches = $db->getBranchByName($branch_name, array('branch_id','name','url'));
		} else {
			$branches = $db->getBranches(array('branch_id','name','url'));
		}
		foreach ($branches as $branch) {
			$branch_data = array('branch_id'=>$branch['branch_id'], 'name'=>$branch['name'], 'url'=>$branch['url']);

			$is_tbd = true;
			$is_open = false;

			$periods = $db->getBranchStatus($branch['branch_id']);

			if ($periods) {
				$period = reset($periods);

				if ($period['is_tbd'] == 0) {
					$is_tbd = false;
				}
				if ($period['is_closed'] == 0) {
					// could still be closed; check the time
					if ($now >= $period['open_time'] && $now <= $period['close_time']) {
						$is_open = $period['nice_close_time'];
					}
				}
			}

			if ($is_open) {
				$branch_data['status'] = 'open';
				$branch_data['closing_time'] = $is_open;
			} else {
				$branch_data['status'] = 'closed';
			}
			if ($is_tbd) {
				$branch_data['tbd'] = true;
			}

			$list->add($branch_data);
		}

		return $list;
	}

	function branch_xsl($request) {
		return new RawXMLDoc(<<<XML
<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:hrs="http://www.library.qut.edu.au/hours/" xmlns="http://www.w3.org/1999/xhtml">
  <xsl:output method="html" omit-xml-declaration="yes" indent="yes" encoding="ISO-8859-1" media-type="text/html" cdata-section-elements="style"/>
  <xsl:template match="/">
    <html>
      <head>
        <title>Branches</title>
        <style type="text/css">
h2 {
	clear:both;
}
a:link {
	color:#00f;
	text-decoration:none;
}
a:visited {
	color:#90c;
}
a:hover {
	text-decoration:underline;
}
.description {
	margin:1em;
}
.notes {
    font-size:90%;
	font-style:italic;
}
.aliases {
	font-size:70%;
	font-family: "MS Sans Serif",sans-serif;
	list-style-type:none;
	padding-left:5px;
}
.aliases li {
	margin-left:0;
	padding-left:0;
}
.aliases li:before {
	content:' ) ';
}
.url {
	font-family:sans-serif;
	font-size:80%;
	padding-left:3px;
}
.contact {
	border:2px inset #eee;
	padding:3px;
	font-size:90%;
}
.contact dt {
	font-weight:bold;
	float:left;
	clear:both;
	padding-left:5px;
	min-width:5em;
}
.contact dt:after {
	content: ': ';
}
.contact dd {
	margin-left:6em;
}
.status {
	float:right;
	margin-right:1em;
    width:6em;
    padding:3px;
    border:2px outset #ccc;
    text-align:center;
    font-weight:bold;
	font-family:sans-serif;
	font-size:10pt;
}
.status.open {
    background:#0d3;
}
.status.closed {
    background:#fc0;
}
.status .tbd {
    font-size:80%;
    font-style:italic;
    font-weight:normal;
}
.status .until {
    font-size:85%;
}
        </style>
      </head>
      <body>
        <h1>Branches</h1>
        <xsl:apply-templates select="//hrs:branch"/>
      </body>
    </html>
  </xsl:template>
  <xsl:template match="hrs:branch">
    <h2 id="branch-{@branch-id}"><xsl:value-of select="@name"/></h2>
    <xsl:apply-templates select="hrs:status"/>
    <xsl:apply-templates select="hrs:aliases"/>
    <p class="description"><xsl:value-of select="hrs:description"/></p>
    <xsl:if test="@url != ''">
      <p class="url"><a href="{@url}"><xsl:value-of select="@url"/></a></p>
    </xsl:if>
    <xsl:apply-templates select="hrs:contact"/>
    <p class="notes"><xsl:value-of select="hrs:notes"/></p>
  </xsl:template>
  <xsl:template match="hrs:status">
    <xsl:choose>
      <xsl:when test="@open = 'open'">
        <div class="status open">Open<div class="until"><xsl:value-of select="hrs:close/@time"/></div>
          <xsl:if test="@tbd = 'tbd'">
            <div class="tbd">* to be finalised</div>
          </xsl:if>
        </div>
      </xsl:when>
      <xsl:otherwise>
        <div class="status closed">Closed
          <xsl:if test="@tbd = 'tbd'">
            <div class="tbd">* to be finalised</div>
          </xsl:if>
        </div>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="hrs:aliases">
    <ul class="aliases">
      <xsl:for-each select="hrs:alias">
        <li><a href="http://dev-demo12.library.qut.edu.au/pub/branches/{.}"><xsl:value-of select="."/></a></li>
      </xsl:for-each>
    </ul>
  </xsl:template>
  <xsl:template match="hrs:contact">
    <dl class="contact">
      <xsl:if test="hrs:address != ''">
        <dt>Address</dt>
        <dd><xsl:value-of select="hrs:address"/></dd>
      </xsl:if>
      <xsl:if test="hrs:phone != ''">
        <dt>Phone</dt>
        <dd><xsl:value-of select="hrs:phone"/></dd>
      </xsl:if>
    </dl>
  </xsl:template>
</xsl:stylesheet>
XML
);
	}

}

class BranchList {
	public $branches = array();
	public function add($branch) {
		$this->branches[] = $branch;
	}
}

