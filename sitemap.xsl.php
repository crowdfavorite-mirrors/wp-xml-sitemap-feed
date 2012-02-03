<?php
/* -------------------------------------
    XML Sitemap Feed Styleheet Template
   ------------------------------------- */

header('Content-Type: text/xsl; charset=utf-8', true);

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?><xsl:stylesheet version="2.0" xmlns:html="http://www.w3.org/TR/REC-html40" xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/><xsl:template match="/"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>XML Sitemap Feed</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><style type="text/css">body{font-family:"Lucida Grande","Lucida Sans Unicode",Tahoma,Verdana,sans-serif;font-size:13px}#header,#footer{padding:2px;margin:10px;font-size:8pt;color:gray}a{color:black}td{font-size:11px}th{text-align:left;padding-right:30px;font-size:11px}tr.high{background-color:whitesmoke}#footer img{vertical-align:text-bottom}</style></head><body><h1>XML Sitemap Feed</h1><div id="header">This is an XML Sitemap to aid search engines like <a href="http://www.google.com">Google</a>, <a href="http://www.bing.com/">Bing</a>, <a href="http://www.yahoo.com">Yahoo!</a> and <a href="http://www.ask.com">Ask</a> indexing your site better. Read more about XML sitemaps on <a href="http://sitemaps.org">Sitemaps.org</a>.</div><div id="content"><table cellpadding="5"><tr style="border-bottom:1px black solid;"><th>#</th><th>URL</th><th>Priority</th><th>Change Frequency</th><th>Last Changed</th></tr><xsl:variable name="lower" select="'abcdefghijklmnopqrstuvwxyz'"/><xsl:variable name="upper" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'"/><xsl:for-each select="sitemap:urlset/sitemap:url"><tr><xsl:if test="position() mod 2 != 1"><xsl:attribute  name="class">high</xsl:attribute></xsl:if><td><xsl:value-of select="position()"/></td><td><xsl:variable name="itemURL"><xsl:value-of select="sitemap:loc"/></xsl:variable><a href="{$itemURL}"><xsl:value-of select="sitemap:loc"/></a></td><td><xsl:value-of select="concat(sitemap:priority*100,'%')"/></td><td><xsl:value-of select="concat(translate(substring(sitemap:changefreq, 1, 1),concat($lower, $upper),concat($upper, $lower)),substring(sitemap:changefreq, 2))"/></td><td><xsl:value-of select="concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))"/></td></tr></xsl:for-each></table></div><div id="footer"><img src="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']); ?>/sitemapxml.gif" alt="XML Sitemap" title="XML Sitemap" /> generated by <a href="http://4visions.nl/en/wordpress-plugins/xml-sitemap-feed/" title="XML Sitemap Feed plugin for WordPress">XML &amp; Google News Sitemap Feed <?php echo (is_numeric($_GET['v'])) ? $_GET['v'] : ''; ?></a> running on <a href="http://wordpress.org/">WordPress</a>.</div></body></html></xsl:template></xsl:stylesheet>
