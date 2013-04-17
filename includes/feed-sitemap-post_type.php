<?php
/**
 * XML Sitemap Feed Template for displaying an XML Sitemap feed.
 *
 * @package XML Sitemap Feed plugin for WordPress
 */

status_header('200'); // force header('HTTP/1.1 200 OK') even for sites without posts
header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

global $xmlsf;
$post_type = get_query_var('post_type');
foreach ( $xmlsf->get_do_tags($post_type) as $tag )
	$$tag = true;

echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?>
<?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="' . date('Y-m-d\TH:i:s+00:00') . '" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status301.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="' . XMLSF_VERSION . '" -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
echo !empty($news) ? '
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" ' : '';
echo '
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
echo !empty($news) ? '
		http://www.google.com/schemas/sitemap-news/0.9 
		http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd' : '';
echo '">
';

// any ID's we need to exclude?
$excluded = $xmlsf->get_excluded($post_type);

// loop away!
if ( have_posts() ) :
    while ( have_posts() ) : 
	the_post();

	// check if we are not dealing with an external URL :: Thanks to Francois Deschenes :)
	// or if page is in the exclusion list (like front pages)
	if ( !preg_match('/^' . preg_quote(home_url(), '/') . '/i', get_permalink()) || in_array($post->ID, $excluded) )
		continue;
// TODO news, image & video tags
?>
	<url>
		<loc><?php the_permalink_rss(); ?></loc>
		<lastmod><?php echo $xmlsf->get_lastmod(); ?></lastmod>
		<changefreq><?php echo $xmlsf->get_changefreq(); ?></changefreq>
	 	<priority><?php echo $xmlsf->get_priority(); ?></priority>
 	</url>
<?php 
    endwhile; 
endif; 
?></urlset>
<?php $xmlsf->_e_usage(); ?>
