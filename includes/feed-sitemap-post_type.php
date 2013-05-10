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
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';

global $xmlsf;
$post_type = get_query_var('post_type');

foreach ( $xmlsf->do_tags($post_type) as $tag => $setting )
	$$tag = $setting;

echo !empty($news) ? '
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" ' : '';
echo !empty($image) ? '
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" ' : '';
echo '
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
echo !empty($news) ? '
		http://www.google.com/schemas/sitemap-news/0.9 
		http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd' : '';
echo !empty($image) ? '
		http://www.google.com/schemas/sitemap-image/1.1 
		http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd' : '';
echo '">
';

// get site language for default language
// bloginfo_rss('language') returns improper format so
// we explode on hyphen and use only first part. 
// TODO this workaround breaks (simplified) chinese :(
$language = reset(explode('-', get_bloginfo_rss('language')));
if ( empty($language) )
	$language = 'en';

// any ID's we need to exclude?
$excluded = $xmlsf->get_excluded($post_type);

// loop away!
if ( have_posts() ) :
    while ( have_posts() ) : 
	the_post();

	// check if we are not dealing with an external URL :: Thanks to Francois Deschenes :)
	// or if page is in the exclusion list (like front pages)
	// or if post meta says "exclude"
	$exclude = get_post_meta( $post->ID, '_xmlsf_exclude', true );
	if ( !empty($exclude) || !preg_match('/^' . preg_quote(home_url(), '/') . '/i', get_permalink()) || in_array($post->ID, $excluded) )
		continue;

// TODO news, image & video tags
?>
	<url>
		<loc><?php the_permalink_rss(); ?></loc>
		<lastmod><?php echo $xmlsf->get_lastmod(); ?></lastmod>
		<changefreq><?php echo $xmlsf->get_changefreq(); ?></changefreq>
	 	<priority><?php echo $xmlsf->get_priority(); ?></priority>
<?php
if ( !empty($news) && $post->post_date > date('Y-m-d H:i:s', strtotime('-49 hours') ) ) : ?>
		<news:news>
			<news:publication>
				<news:name><?php 
					if(defined('XMLSF_GOOGLE_NEWS_NAME')) 
						echo apply_filters('the_title_rss', XMLSF_GOOGLE_NEWS_NAME); 
					else 
						echo bloginfo_rss('name'); ?></news:name>
				<news:language><?php 
					$lang = reset(get_the_terms($post->ID,'language'));
					echo (is_object($lang)) ? $lang->slug : $language;  ?></news:language>
			</news:publication>
			<news:publication_date><?php 
				echo mysql2date('Y-m-d\TH:i:s+00:00', $post->post_date_gmt, false); ?></news:publication_date>
			<news:title><?php the_title_rss() ?></news:title>
			<news:keywords><?php 
				$do_comma = false; 
				$keys_arr = get_the_category(); 
				foreach($keys_arr as $key) { 
					echo ( $do_comma ) ? ', ' : '' ; 
					echo apply_filters('the_title_rss', $key->name); 
					$do_comma = true; 
				} ?></news:keywords>
<?php 
// TODO: create the new taxonomy "Google News Genre" with some genres preset
		if ( taxonomy_exists('gn_genre') && get_the_terms($post->ID,'gn_genre') ) { 
?>
			<news:genres><?php 
				$do_comma = false; 
				foreach(get_the_terms($post->ID,'gn_genre') as $key) { 
					echo ( $do_comma ) ? ', ' : '' ; 
					echo apply_filters('the_title_rss', $key->name); 
					$do_comma = true; 
				} ?></news:genres>
		<?php
		}
		?>
		</news:news>
<?php
endif;
if ( !empty($image) && $xmlsf->get_images() ) : 
	foreach ( $xmlsf->get_images() as $loc ) { 
?>
		<image:image>
			<image:loc><?php echo $loc; ?></image:loc>
		</image:image>
<?php 
	} 
endif;
?>
 	</url>
<?php 
    endwhile; 
endif; 
?></urlset>
<?php $xmlsf->_e_usage(); ?>
