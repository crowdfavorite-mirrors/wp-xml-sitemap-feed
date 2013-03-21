<?php
/**
 * XML Sitemap Feed Template for displaying an XML Sitemap feed.
 *
 * @package XML Sitemap Feed plugin for WordPress
 */

status_header('200'); // force header('HTTP/1.1 200 OK') even for sites without posts
header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?>
<?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="' . date('Y-m-d\TH:i:s+00:00') . '" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status301.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="' . XMLSF_VERSION . '" -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
';

global $xmlsf;
foreach ( $xmlsf->get_home_urls() as $url ) {
?>
	<url>
		<loc><?php echo esc_url( $url ); ?></loc>
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', get_lastdate( 'gmt' ), false); ?></lastmod>
		<changefreq>hourly</changefreq>
		<priority>1.0</priority>
	</url>
<?php
}
?>
</urlset>
