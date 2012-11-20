<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet href="/assets/generic-xml.xsl" type="text/xsl"?>
<!--
  XSL Stylesheet Copyright 2012 Matthew Kerwin.

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">
  <xsl:output method="html" omit-xml-declaration="yes" indent="yes" encoding="utf-8" media-type="text/html" cdata-section-elements="style"/>
  <xsl:strip-space elements="*"/>
  <xsl:template match="/">
    <xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html&gt;</xsl:text>
    <html lang="en">
      <head>
        <title>XML Document</title>
        <style type="text/css">
body{font-family:sans-serif;background:#fff;color:#000;font-size:12pt}
pre{margin:0}
ul{list-style-type:none}
body>ul{margin-left:0;padding-left:0}
.node{color:#000;font-size:12pt}
.node .name{font-weight:bold}
.elem.node > .name::before{content:'&lt;'}
.elem.node > .name::after{content:'&gt;'}
.node .value{font-family:monospace}
.node li.value{list-style-type:none}
.node li.value::before{content:'"'}
.node li.value::after{content:'"'}
.pi.node{color:#090}
.pi.node::before{content:'&lt;?';font-weight:bold}
.pi.node::after{content:'?&gt;';font-weight:bold}
.pi.node .value::before{content:' '}
.pi.node .value::after{content:' '}
.xml.node{color:#960}
.attr{color:#00f;font-size:10pt}
.attr .name{font-weight:normal}
.attr .value::before{content:' = "'}
.attr .value::after{content:'"'}
.comment.node .value{color:#090;font-style:italic}
.comment.node .value::before{content:'&lt;!-- '}
.comment.node .value::after{content:' --&gt;'}
        </style>
      </head>
      <body>
        <ul>
          <xsl:apply-templates match="*"/>
        </ul>
      </body>
    </html>
  </xsl:template>
  <xsl:template match="*[name()]" priority="2">
    <li class="elem node">
      <span class="name"><xsl:value-of select="name()"/></span>
      <ul>
        <xsl:apply-templates select="@* | node()"/>
      </ul>
    </li>
  </xsl:template>
  <xsl:template match="comment()" priority="3">
    <li class="comment node">
      <xsl:choose>
        <xsl:when test="contains(., '&#xA;')">
          <pre class="value"><xsl:text>&#xA;</xsl:text><xsl:value-of select="."/></pre>
        </xsl:when>
        <xsl:otherwise>
          <span class="value"><xsl:value-of select="."/></span>
        </xsl:otherwise>
      </xsl:choose>
    </li>
  </xsl:template>
  <xsl:template match="text()" priority="3">
    <li class="value">
      <xsl:value-of select="."/>
    </li>
  </xsl:template>
  <xsl:template match="processing-instruction()" priority="3">
    <li class="pi node">
      <span class="name"><xsl:value-of select="name()"/></span>
      <span class="value"><xsl:value-of select="."/></span>
    </li>
  </xsl:template>
  <xsl:template match="node()[name()]">
    <!-- unknown nodes that have a name -->
    <li class="xml node">
      <span class="name"><xsl:value-of select="name()"/></span>
    </li>
  </xsl:template>
  <xsl:template match="@*">
    <li class="attr">
      <span class="name"><xsl:value-of select="name()"/></span>
      <span class="value"><xsl:value-of select="."/></span>
    </li>
  </xsl:template>
</xsl:stylesheet>
