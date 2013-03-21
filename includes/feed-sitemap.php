<?php
/**
 * Google News Sitemap Feed Template
 *
 * @package XML Sitemap Feed plugin for WordPress
 */

status_header('200'); // force header('HTTP/1.1 200 OK') for sites without posts
header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

echo '<?xml version="1.0" encoding="'.get_bloginfo('charset').'"?><?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap-index.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="'.date('Y-m-d\TH:i:s+00:00').'" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status301.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="'.XMLSF_VERSION.'" -->
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd">
';

global $xmlsf;
?>
<!-- home page(s) -->
	<sitemap>
		<loc><?php 
			// hook for filter 'xml_sitemap_url' provides a string here and MUST get a string returned
			$url = apply_filters( 'xml_sitemap_url', trailingslashit(home_url()) );
			if ( is_string($url) ) 
				echo esc_url( $url ); 
			else 
				echo esc_url( trailingslashit(home_url()) );		
			if (''==get_option('permalink_structure'))
				echo '?feed='.$xmlsf->base_name.'-home';
			else
				echo $xmlsf->base_name.'-home.'.$xmlsf->extension; ?></loc>
	</sitemap>
<!-- post types -->
<?php
// add rules for custom public post types
foreach ( $xmlsf->get_post_types() as $post_type ) {
	$count = wp_count_posts( $post_type['name'] );
	if ( $count->publish > 0 && isset($post_type['active']) ) {
?>
	<sitemap>
		<loc><?php 
			// hook for filter 'xml_sitemap_url' provides a string here and MUST get a string returned
			$url = apply_filters( 'xml_sitemap_url', trailingslashit(home_url()) );
			if ( is_string($url) ) 
				echo esc_url( $url ); 
			else 
				echo esc_url( trailingslashit(home_url()) );		
			if (''==get_option('permalink_structure'))
				echo '?feed='.$xmlsf->base_name.'-posttype_'.$post_type['name'];
			else
				echo $xmlsf->base_name.'-posttype-'.$post_type['name'].'.'.$xmlsf->extension; ?></loc>
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', get_lastdate( 'gmt', $post_type['name'] ), false); ?></lastmod>
	</sitemap>
<?php 
	}
}
?>
<!-- taxonomy types -->
<?php
	// add rules for custom public post taxonomies
foreach ( $xmlsf->get_taxonomies() as $taxonomy ) {
	if ( wp_count_terms( $taxonomy ) > 0 ) {
?>
	<sitemap>
		<loc><?php 
			// hook for filter 'xml_sitemap_url' provides a string here and MUST get a string returned
			$url = apply_filters( 'xml_sitemap_url', trailingslashit(home_url()) );
			if ( is_string($url) ) 
				echo esc_url( $url ); 
			else 
				echo esc_url( trailingslashit(home_url()) );
			if (''==get_option('permalink_structure'))
				echo '?feed='.$xmlsf->base_name.'-taxonomy&amp;taxonomy='.$taxonomy;
			else
				echo $xmlsf->base_name.'-taxonomy-'.$taxonomy.'.'.$xmlsf->extension; ?></loc>
	</sitemap>
<?php 
// TODO add lastmod ?
	}
}

?></sitemapindex>
