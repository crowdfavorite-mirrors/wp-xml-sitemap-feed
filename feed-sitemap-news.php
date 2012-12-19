<?php
/**
 * Google News Sitemap Feed Template
 *
 * @package XML Sitemap Feed plugin for WordPress
 */

if ( !have_posts() ) :

	// No posts? Temporary redirect to the main xml sitemap.
	wp_redirect( 'sitemap.xml' ); // default 302... maybe 304 not modified is better?
	exit;
	
	// ALTERNATIVE:
	// No posts? Then go and get at least one last post to prevent GWT validation error.
	// Remove the filtering functions
	remove_filter( 'posts_where', array( 'XMLSitemapFeed', 'filter_news_where' ), 10, 1 );
	remove_filter( 'post_limits', array( 'XMLSitemapFeed', 'filter_limits' ) );

	// Perform the alternative query
	query_posts( 'posts_per_page=1' );

	global $wp_query;
	$wp_query->is_404 = false;	// force is_404() condition to false when on site without posts
	$wp_query->is_feed = true;	// force is_feed() condition to true so WP Super Cache includes
				// the sitemap in its feeds cache

endif; 
 
status_header('200'); // force header('HTTP/1.1 200 OK') for sites without posts
header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

echo '<?xml version="1.0" encoding="'.get_bloginfo('charset').'"?><?xml-stylesheet type="text/xsl" href="' . plugins_url('',__FILE__) . '/sitemap-news.xsl.php?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="'.date('Y-m-d\TH:i:s+00:00').'" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://4visions.nl/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="'.XMLSF_VERSION.'" -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">';

$maxURLS = 1000;	// maximum number of URLs allowed in a news sitemap.

// editing below here is not advised!

// prepare counter to limit the number of URLs to the absolute max of $maxURLS
$counter = 1;

// loop away!
while ( have_posts() && $counter < $maxURLS ) : the_post();

	// check if we are not dealing with an external URL :: Thanks, Francois Deschenes :)
	if(!preg_match('/^' . preg_quote(home_url(), '/') . '/i', get_permalink())) continue;

	// get the article tags
	$keys_arr = get_the_tags();

	// TODO : include categories too ??

	?>
<url><loc><?php echo esc_url( get_permalink() ) ?></loc><news:news><news:publication><news:name><?php if(defined('XMLSF_GOOGLE_NEWS_NAME')) echo strip_tags(XMLSF_GOOGLE_NEWS_NAME); else echo strip_tags(get_bloginfo('name')); ?></news:name><news:language><?php echo reset(explode('-', get_bloginfo_rss('language'))); /*bloginfo_rss('language') returns impropper format*/ ?></news:language></news:publication><news:publication_date><?php echo mysql2date('Y-m-d\TH:i:s+00:00', $post->post_date_gmt, false); ?></news:publication_date><news:title><?php echo strip_tags(get_the_title(get_the_ID())); ?></news:title><news:keywords><?php $comma = 0; if ($keys_arr) foreach($keys_arr as $key) { if ( $comma == 1 ) { echo ', '; } echo $key->name; $comma = 1; } ?></news:keywords><news:genres>Blog</news:genres></news:news></url><?php 

	$counter++;

endwhile; 

// Now what if there are no posts less than 48 hours old? We get an urlset without url nodes...
// ... resulting in an error by Google Webmaster Tools :(
// But what can we do? Nothing, I suppose.

//else :

//	echo '<url><loc></loc><news:news><news:publication><news:name>';
//	bloginfo('name');
//	echo '</news:name><news:language>' . get_option('rss_language') . '</news:language></news:publication><news:publication_date></news:publication_date><news:title></news:title><news:keywords></news:keywords><news:genres>Blog</news:genres></news:news></url>';

	// TODO see what we can do for :
	//<news:access>Subscription</news:access> (for now always leave off)
	// and
	//<news:genres>Blog</news:genres> (for now leave as Blog)
	// http://www.google.com/support/news_pub/bin/answer.py?answer=93992
	
	// lees over indienen:
	// http://www.google.com/support/news_pub/bin/answer.py?hl=nl&answer=74289

?></urlset>
