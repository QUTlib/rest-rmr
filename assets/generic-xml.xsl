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
<!--
  Modified by QUT Library eServices, 2012.
  
  @author Matthew Kerwin <matthew.kerwin@qut.edu.au>
  @date 2012-11-23
  @todo Add entity escaping to more attribute values (and PIs?); make entity escaping work on other special chars
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">
  <xsl:output method="xml" omit-xml-declaration="yes" indent="no" encoding="UTF-8" media-type="application/xhtml+xml" cdata-section-elements="style" doctype-system="about:legacy-compat"/>
  <xsl:strip-space elements="*"/>
  <xsl:template match="/">
    <html lang="en">
      <head>
        <title>XML Document</title>
        <style type="text/css">
          <![CDATA[
body{background:#fff;color:#000;font-size:10pt;margin:0;padding:0.5em}

#header{padding:0.5em;background:#eef;border:2px inset #d8d8ff;margin-bottom:0.5em}
#header h1{font-size:18pt;font-weight:bold;padding:0;margin:0}
#header p{padding:0;margin:0.5em 0}
#header p:last-child{margin-bottom:0}

#content{font-family:monospace;padding:0.5em;border:2px inset #d8d8d8}
#content pre{margin:0;padding:0}

.node{margin-left:2em}
#content>.node{margin-left:0}

.element{color:/*#881280*/#909}
.comment{color:/*#236E25*/#090;font-style:italic}
.pi{color:/*#236E25*/#090}
.xml{color:#960}
.text{color:#000}
span.text{color:#000;background:#f8f8f8;outline:1px solid #ccc}

.tag-name{/*font-weight:bold*/}
.attribute-name{color:/*#994500*/#960}
.attribute-value{color:/*#1A1AA6*/#00c}
]]>
        </style>
      </head>
      <body>
        <div id="header">
          <h1>XML Document</h1>
          <p>Styled with an XSLT Stylesheet by Matthew Kerwin and QUT Library eServices.</p>
        </div>
        <div id="content">
          <xsl:apply-templates match="*"/>
        </div>
      </body>
    </html>
  </xsl:template>

  <!-- Element -->
  <xsl:template match="*[name()]" priority="2">
    <div class="element node">
      <xsl:text>&lt;</xsl:text>
      <span class="tag-name"><xsl:value-of select="name()"/></span>
      <xsl:apply-templates select="@*"/>
      <xsl:choose>
        <xsl:when test="count(node()) > 0">
          <xsl:text>&gt;</xsl:text>
          <xsl:choose>
            <xsl:when test="count(node()) = count(text())">
              <span class="text">
                <xsl:call-template name="quote-entities">
                  <xsl:with-param name="text" select="."/>
                </xsl:call-template>
              </span>
            </xsl:when>
            <xsl:otherwise>
              <xsl:apply-templates select="node()"/>
            </xsl:otherwise>
          </xsl:choose>
          <xsl:text>&lt;/</xsl:text>
          <xsl:value-of select="name()"/>
          <xsl:text>&gt;</xsl:text>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>/&gt;</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <!-- Comment -->
  <xsl:template match="comment()" priority="3">
    <div class="comment node">
      <xsl:choose>
        <xsl:when test="contains(., '&#xA;')">
          <pre>
            <xsl:text>&lt;!--</xsl:text>
            <xsl:call-template name="ensure-whitespace">
              <xsl:with-param name="text">
                <xsl:value-of select="."/>
              </xsl:with-param>
            </xsl:call-template>
            <xsl:text>--&gt;</xsl:text>
          </pre>
        </xsl:when>
        <xsl:otherwise>
          <xsl:text>&lt;!--</xsl:text>
          <xsl:call-template name="ensure-whitespace">
            <xsl:with-param name="text">
              <xsl:value-of select="."/>
            </xsl:with-param>
          </xsl:call-template>
          <xsl:text>--&gt;</xsl:text>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <!-- Text -->
  <!-- Only invoked when the node has siblings; see Element -->
  <xsl:template match="text()" priority="3">
    <div class="text node">
      <xsl:call-template name="quote-entities">
        <xsl:with-param name="text" select="."/>
      </xsl:call-template>
    </div>
  </xsl:template>

  <!-- Processing Instruction -->
  <xsl:template match="processing-instruction()" priority="3">
    <div class="pi node">
      <xsl:text>&lt;?</xsl:text>
      <xsl:value-of select="name()"/>
      <xsl:if test=". != ''">
        <xsl:text>&#x20;</xsl:text>
        <xsl:value-of select="."/>
      </xsl:if>
      <xsl:text>?&gt;</xsl:text>
    </div>
  </xsl:template>

  <!-- ?? Unknown node that has a name ?? -->
  <xsl:template match="node()[name()]">
    <div class="xml node">
      <xsl:text>&lt;</xsl:text>
      <xsl:value-of select="name()"/>
      <xsl:apply-templates select="@*"/>
      <xsl:text>&gt;</xsl:text>
      <xsl:apply-templates select="node()"/>
      <xsl:text>&lt;</xsl:text>
      <xsl:text>/</xsl:text><xsl:value-of select="name()"/>
      <xsl:text>&gt;</xsl:text>
    </div>
  </xsl:template>

  <!-- Attribute -->
  <!-- Called by Element -->
  <xsl:template match="@*">
    <span class="attr">
      <xsl:text> </xsl:text>
      <span class="attribute-name"><xsl:value-of select="name()"/></span>
      <xsl:text>="</xsl:text>
      <span class="attribute-value"><xsl:value-of select="."/></span>
      <xsl:text>"</xsl:text>
    </span>
  </xsl:template>

  <!--
    Ensures $text is pre- and post-fixed by whitespace.
    If the first and/or last character is not TAB, CR, LF, or SPACE,
    adds an appropriate SPACE character (0x20).
  -->
  <xsl:template name="ensure-whitespace">
    <xsl:param name="text"/>
    <xsl:if test="not(contains('&#x9;&#xA;&#xD;&#x20;',substring($text,1,1)))">
      <xsl:text>&#x20;</xsl:text>
    </xsl:if>
    <xsl:value-of select="$text"/>
    <xsl:variable name="n" select="string-length($text)"/>
    <xsl:if test="not(contains('&#x9;&#xA;&#xD;&#x20;',substring($text,$n,1)))">
      <xsl:text>&#x20;</xsl:text>
    </xsl:if>
  </xsl:template>

  <!--
    Replaces < & > with &lt; &amp; &gt;
  -->
  <xsl:template name="quote-entities">
    <xsl:param name="text"/>
    <!-- boolean: does $text contain '$entity'? -->
    <xsl:variable name="has_amp" select="contains($text,'&amp;')"/>
    <xsl:variable name="has_lt" select="contains($text,'&lt;')"/>
    <xsl:variable name="has_gt" select="contains($text,'&gt;')"/>
    <!-- string: substring before '$entity' (or '') -->
    <xsl:variable name="b4_amp" select="substring-before($text,'&amp;')"/>
    <xsl:variable name="b4_lt" select="substring-before($text,'&lt;')"/>
    <xsl:variable name="b4_gt" select="substring-before($text,'&gt;')"/>
    <!-- int: length of ^^substring -->
    <xsl:variable name="pos_amp" select="string-length($b4_amp)"/>
    <xsl:variable name="pos_lt" select="string-length($b4_lt)"/>
    <xsl:variable name="pos_gt" select="string-length($b4_gt)"/>
    <!-- find the smallest non-zero length var -->
    <xsl:choose>
      <!-- '&amp;' exists and is first -->
      <xsl:when test="$has_amp and not($has_lt and $pos_amp &gt; $pos_lt) and not($has_gt and $pos_amp &gt; $pos_gt)">
        <xsl:value-of select="$b4_amp"/>
        <xsl:text>&amp;amp;</xsl:text>
        <xsl:call-template name="quote-entities">
          <xsl:with-param name="text" select="substring-after($text,'&amp;')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- '&lt;' exists and is first -->
      <xsl:when test="$has_lt and not($has_amp and $pos_lt &gt; $pos_amp) and not($has_gt and $pos_lt &gt; $pos_gt)">
        <xsl:value-of select="$b4_lt"/>
        <xsl:text>&amp;lt;</xsl:text>
        <xsl:call-template name="quote-entities">
          <xsl:with-param name="text" select="substring-after($text,'&lt;')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- '&gt;' exists and is first -->
      <xsl:when test="$has_gt and not($has_amp and $pos_gt &gt; $pos_lt) and not($has_lt and $pos_gt &gt; $pos_amp)">
        <xsl:value-of select="$b4_gt"/>
        <xsl:text>&amp;gt;</xsl:text>
        <xsl:call-template name="quote-entities">
          <xsl:with-param name="text" select="substring-after($text,'&gt;')"/>
        </xsl:call-template>
      </xsl:when>
      <!-- no entities exist -->
      <xsl:otherwise>
        <xsl:value-of select="$text"/>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
