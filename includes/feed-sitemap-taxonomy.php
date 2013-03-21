<?php
/**
 * Google News Sitemap Feed Template
 *
 * @package XML Sitemap Feed plugin for WordPress
 */

status_header('200'); // force header('HTTP/1.1 200 OK') for sites without posts
// TODO test if we can do without it
header('Content-Type: text/xml; charset=' . get_bloginfo('charset', 'UTF-8'), true);

echo '<?xml version="1.0" encoding="'.get_bloginfo('charset', 'UTF-8').'"?>
<?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="'.date('Y-m-d\TH:i:s+00:00').'" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status310.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="'.XMLSF_VERSION.'" -->

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
';

// PRESETS are changable -- please read comments:

$max_priority = 0.7;	// Maximum priority value for any URL in the sitemap; set to any other value between 0 and 1.
$min_priority = 0.2;	// Minimum priority value for any URL in the sitemap; set to any other value between 0 and 1.
			// NOTE: Changing these values will influence each URL's priority. Priority values are taken by 
			// search engines to represent RELATIVE priority within the site domain. Forcing all URLs
			// to a priority of above 0.5 or even fixing them all to 1.0 - for example - is useless.

$level_weight = 0.1;	// TODO Makes a sub-term gain or loose priority for each level; set to any other value between 0 and 1.

$taxonomy = get_query_var('taxonomy');
$lang = get_query_var('lang');
echo "<!-- taxonomy: $taxonomy -->";
$tax_obj = get_taxonomy($taxonomy);
foreach ( $tax_obj->object_type as $post_type) {
	echo "<!-- taxonomy post type: $post_type -->
";
	$_post_count = wp_count_posts($post_type);
	$postcount += $_post_count->publish;
}

//$_terms_count = wp_count_terms(get_query_var('taxonomy'));
//$average_count = $_post_count->publish / $_terms_count;

// Polylang solution on http://wordpress.org/support/topic/query-all-language-terms?replies=6#post-3415389
//global $xmlsf;

$terms = get_terms( $taxonomy, array(
					'orderby' => 'count',
					'order' => 'DESC',
					'lang' => $lang,
					'hierachical' => 0,
					'pad_counts' => true, // count child term post count too...
					'number' => 50000 ) );

if ( $terms ) : 

    foreach ( $terms as $term ) : 
    
    // calculate priority based on number of posts
    // or maybe take child taxonomy terms into account.?

	$priority = $min_priority + ( $term->count / ( $postcount / 2 ) );
	$priority = ($priority > $max_priority) ? $max_priority : $priority;
	
	// get the latest post in this taxonomy item, to use its post_date as lastmod
	$posts = get_posts ( array(
		 	'numberposts' => 1, 
			'no_found_rows' => true, 
			'update_post_meta_cache' => false, 
			'update_post_term_cache' => false, 
			'update_cache' => false,
			'tax_query' => array(
					array(
						'taxonomy' => $term->taxonomy,
						'field' => 'slug',
						'terms' => $term->slug
					)
				)
			)
		);
	?>
	<url>
		<loc><?php echo get_term_link( $term ); ?></loc>
	 	<priority><?php echo number_format($priority,1) ?></priority>
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', $posts[0]->post_date_gmt, false); ?></lastmod>
		<changefreq><?php
			$lastactivityage = (gmdate('U') - mysql2date('U', $posts[0]->post_date_gmt));
		 	if(($lastactivityage/86400) < 1) { // last activity less than 1 day old 
		 		echo 'hourly';
		 	} else if(($lastactivityage/86400) < 7) { // last activity less than 1 week old 
		 		echo 'daily';
		 	} else if(($lastactivityage/86400) < 30) { // last activity between 1 week and one month old 
		 		echo 'weekly';
		 	} else if(($lastactivityage/86400) < 365) { // last activity between 1 month and 1 year old 
		 		echo 'monthly';
		 	} else {
		 		echo 'yearly';
		 	} ?></changefreq>
	</url>
<?php 
    endforeach;
else : 
?>
	<url>
		<loc><?php echo esc_url( trailingslashit(home_url()) ); ?></loc>
	</url>
<?php
endif; 

?></urlset>
