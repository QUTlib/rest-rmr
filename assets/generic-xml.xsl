<?xml version="1.0" encoding="utf-8"?>
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
ul{list-style-type:none}
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
      <span class="value"><xsl:value-of select="."/></span>
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
