<?php
/**
 * Google News Sitemap Feed Template
 *
 * @package XML Sitemap Feed plugin for WordPress
 */
global $xmlsf;
status_header('200'); // force header('HTTP/1.1 200 OK') for sites without posts
header('Content-Type: text/xml; charset=' . get_bloginfo('charset'), true);

echo '<?xml version="1.0" encoding="'.get_bloginfo('charset').'"?>
<?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap-news.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="'.date('Y-m-d\TH:i:s+00:00').'" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status301.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="'.XMLSF_VERSION.'" -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd 
		http://www.google.com/schemas/sitemap-news/0.9 
		http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd">
';

// get site language for default language
// bloginfo_rss('language') returns improper format so
// we explode on hyphen and use only first part. 
// TODO this workaround breaks (simplified) chinese :(
$language = reset(explode('-', get_bloginfo_rss('language')));
if ( empty($language) )
	$language = 'en';

// loop away!
if ( have_posts() ) : 
    while ( have_posts() ) : 
	the_post();

	// check if we are not dealing with an external URL :: Thanks, Francois Deschenes :)
	if(!preg_match('/^' . preg_quote(home_url(), '/') . '/i', get_permalink())) continue;

	$thispostmodified_gmt = $post->post_modified_gmt; // post GMT timestamp
	$thispostmodified = mysql2date('U',$thispostmodified_gmt); // post Unix timestamp
	$lastcomment = array();

	if ($post->comment_count && $post->comment_count > 0) {
		$lastcomment = get_comments( array(
						'status' => 'approve',
						'$number' => 1,
						'post_id' => $post->ID,
						) );
		$lastcommentsdate = mysql2date('U',$lastcomment[0]->comment_date_gmt); // last comment timestamp
		if ( $lastcommentsdate > $thispostmodified ) {
			$thispostmodified = $lastcommentsdate; // replace post with comment Unix timestamp
			$thispostmodified_gmt = $lastcomment[0]->comment_date_gmt; // and replace modified GMT timestamp
		}
	}
	$lastactivityage = (gmdate('U') - $thispostmodified); // post age

	// get the article keywords from categories and tags
	$keys_arr = get_the_category(); 
	if (get_the_tags()) 
		$keys_arr = array_merge($keys_arr,get_the_tags());

	?>
	<url>
		<loc><?php the_permalink_rss() ?></loc>
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
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', $thispostmodified_gmt, false); ?></lastmod>
		<changefreq><?php
		 	if(($lastactivityage/86400) < 1) { // last activity less than 1 day old 
		 		echo 'hourly';
		 	} else { 
		 		echo 'daily';	
		 	} ?></changefreq>
		<priority>1.0</priority>
	</url>
<?php 
    endwhile;
else :
// TODO replace link to home with the last post even if it's older than 2 days...

	$lastmodified_gmt = get_lastmodified('GMT'); // last posts or page modified date
?>
	<url>
		<loc><?php 
			// hook for filter 'xml_sitemap_url' provides a string here and MUST get a string returned
			$url = apply_filters( 'xml_sitemap_url', trailingslashit(home_url()) );
			if ( is_string($url) ) 
				echo esc_url( $url ); 
			else 
				echo esc_url( trailingslashit(home_url()) ); ?></loc>
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', $lastmodified_gmt, false); ?></lastmod>
		<changefreq>daily</changefreq>
		<priority>1.0</priority>
	</url>
<?php
endif; 

	// TODO see what we can do for :
	//<news:access>Subscription</news:access> (for now always leave off)
	// and
	//<news:genres>Blog</news:genres> (for now leave up to external taxonomy plugin to set up 'gn_genre')
	// http://www.google.com/support/news_pub/bin/answer.py?answer=93992
	
	// Submit:
	// http://www.google.com/support/news_pub/bin/answer.py?hl=nl&answer=74289

?></urlset>
<?php $xmlsf->_e_usage(); ?>
