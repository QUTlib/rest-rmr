<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet href="/assets/generic-xml.xsl" type="text/xsl"?>
<xsl:stylesheet version="1.0" xmlns:p="urn:ietf:rfc:7807" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml">
  <xsl:output method="xml" omit-xml-declaration="yes" indent="no" encoding="UTF-8" media-type="application/xhtml+xml" cdata-section-elements="style" doctype-system="about:legacy-compat"/>
  <xsl:strip-space elements="*"/>
  <xsl:template match="/">
    <html xml:lang="en">
      <head>
        <title>HTTP Problem</title>
        <style type="text/css">
          <![CDATA[
body{background:#fff;color:#000;font:12pt serif;margin:0;padding:0.5em}

#header{padding:0.5em;margin-bottom:0.5em;border-bottom:1px solid #ccc}
#header h1{font-size:20pt;font-weight:bold;padding:0;margin:0;color:#23c}
#header p{font-size:10pt;padding:0;margin:0.5em 0;color:#333;font-style:italic}
#header p:last-child{margin-bottom:0}

#content{padding:0.5em}

.title{font-size:14pt}
.type{font:10pt sans-serif}

table{margin-top:1.5em;font:10pt sans-serif}

table,tr,th,td{border:2px solid #ccc;border-collapse:collapse}
th,td{padding:0.2em 0.5em}

th{background:#ccc}
]]>
        </style>
      </head>
      <body>
        <div id="header">
          <h1>HTTP Problem</h1>
          <p>Styled with an XSLT Stylesheet by Matthew Kerwin and QUT Library eServices.</p>
        </div>
        <div id="content">
          <xsl:apply-templates/>
        </div>
      </body>
    </html>
  </xsl:template>
  <xsl:template match="p:problem" priority="1">
    <div>
      <div class="title"><xsl:value-of select="p:title"/></div>
      <div class="type">(<xsl:value-of select="p:type"/>)</div>
    </div>
    <table>
      <tr>
        <th>Field</th>
        <th>Value</th>
      </tr>
      <xsl:apply-templates/>
    </table>
  </xsl:template>
  <xsl:template match="p:detail" priority="1">
    <tr>
      <td>Detail</td>
      <td><xsl:value-of select="."/></td>
    </tr>
  </xsl:template>
  <xsl:template match="p:instance" priority="1">
    <tr>
      <td>Problem Instance</td>
      <td><a href="{.}"><xsl:value-of select="."/></a></td>
    </tr>
  </xsl:template>
  <xsl:template match="p:status" priority="1">
    <tr>
      <td>HTTP Status</td>
      <td>
        <xsl:value-of select="."/>
        <xsl:choose>
          <!-- http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml -->
          <xsl:when test=". = '100'"> Continue</xsl:when>
          <xsl:when test=". = '101'"> Switching Protocols</xsl:when>
          <xsl:when test=". = '102'"> Processing</xsl:when>
          <xsl:when test=". = '200'"> OK</xsl:when>
          <xsl:when test=". = '201'"> Created</xsl:when>
          <xsl:when test=". = '202'"> Accepted</xsl:when>
          <xsl:when test=". = '203'"> Non-Authoritative Information</xsl:when>
          <xsl:when test=". = '204'"> No Content</xsl:when>
          <xsl:when test=". = '205'"> Reset Content</xsl:when>
          <xsl:when test=". = '206'"> Partial Content</xsl:when>
          <xsl:when test=". = '207'"> Multi-Status</xsl:when>
          <xsl:when test=". = '208'"> Already Reported</xsl:when>
          <xsl:when test=". = '300'"> Multiple Choices</xsl:when>
          <xsl:when test=". = '301'"> Moved Permanently</xsl:when>
          <xsl:when test=". = '302'"> Moved Temporarily</xsl:when>
          <xsl:when test=". = '303'"> See Other</xsl:when>
          <xsl:when test=". = '304'"> Not Modified</xsl:when>
          <xsl:when test=". = '305'"> Use Proxy</xsl:when>
          <xsl:when test=". = '307'"> Temporary Redirect</xsl:when>
          <xsl:when test=". = '308'"> Permanent Redirect</xsl:when>
          <xsl:when test=". = '400'"> Bad Request</xsl:when>
          <xsl:when test=". = '401'"> Unauthorised</xsl:when>
          <xsl:when test=". = '402'"> Payment Required</xsl:when>
          <xsl:when test=". = '403'"> Forbidden</xsl:when>
          <xsl:when test=". = '404'"> Not Found</xsl:when>
          <xsl:when test=". = '405'"> Method Not Allowed</xsl:when>
          <xsl:when test=". = '406'"> Not Acceptable</xsl:when>
          <xsl:when test=". = '407'"> Proxy Authentication Required</xsl:when>
          <xsl:when test=". = '408'"> Request Time-out</xsl:when>
          <xsl:when test=". = '409'"> Conflict</xsl:when>
          <xsl:when test=". = '410'"> Gone</xsl:when>
          <xsl:when test=". = '411'"> Length Required</xsl:when>
          <xsl:when test=". = '412'"> Precondition Failed</xsl:when>
          <xsl:when test=". = '413'"> Request Entity Too Large</xsl:when>
          <xsl:when test=". = '414'"> Request-URI Too Large</xsl:when>
          <xsl:when test=". = '415'"> Unsupported Media Type</xsl:when>
          <xsl:when test=". = '416'"> Requested range not satisfiable</xsl:when>
          <xsl:when test=". = '417'"> Expectation Failed</xsl:when>
          <xsl:when test=". = '418'"> I'm a teapot</xsl:when><!-- not IANA -->
          <xsl:when test=". = '422'"> Unprocessable Entity</xsl:when>
          <xsl:when test=". = '423'"> Locked</xsl:when>
          <xsl:when test=". = '424'"> Failed Dependency</xsl:when>
          <xsl:when test=". = '426'"> Upgrade Required</xsl:when>
          <xsl:when test=". = '428'"> Precondition Required</xsl:when>
          <xsl:when test=". = '429'"> Too Many Requests</xsl:when>
          <xsl:when test=". = '431'"> Request Header Fields Too Large</xsl:when>
          <xsl:when test=". = '500'"> Internal Server Error</xsl:when>
          <xsl:when test=". = '501'"> Not Implemented</xsl:when>
          <xsl:when test=". = '502'"> Bad Gateway</xsl:when>
          <xsl:when test=". = '503'"> Service Unavailable</xsl:when>
          <xsl:when test=". = '504'"> Gateway Time-out</xsl:when>
          <xsl:when test=". = '505'"> HTTP Version not supported</xsl:when>
          <xsl:when test=". = '506'"> Variant Also Negotiates</xsl:when><!-- Experimental - TCN [RFC 2295] -->
          <xsl:when test=". = '507'"> Insufficient Storage</xsl:when>
          <xsl:when test=". = '508'"> Loop Detected</xsl:when>
          <xsl:when test=". = '510'"> Not Extended</xsl:when>
          <xsl:when test=". = '511'"> Network Authentication Required</xsl:when>
        </xsl:choose>
      </td>
    </tr>
  </xsl:template>
  <xsl:template match="*[name()]" priority="0">
    <xsl:choose>
      <xsl:when test="name() = 'title'" />
      <xsl:when test="name() = 'type'" />
      <xsl:otherwise>
        <tr>
          <td><xsl:value-of select="name()"/></td>
          <td><xsl:value-of select="."/></td>
        </tr>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>
