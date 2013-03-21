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
foreach ( (array)$xmlsf->get_do_tags($post_type) as $tag )
	$$tag = true;

echo '<?xml version="1.0" encoding="' . get_bloginfo('charset') . '"?>
<?xml-stylesheet type="text/xsl" href="' . plugins_url('xsl/sitemap.xsl.php',__FILE__) . '?ver=' . XMLSF_VERSION . '"?>
<!-- generated-on="' . date('Y-m-d\TH:i:s+00:00') . '" -->
<!-- generator="XML & Google News Sitemap Feed plugin for WordPress" -->
<!-- generator-url="http://status301.net/wordpress-plugins/xml-sitemap-feed/" -->
<!-- generator-version="' . XMLSF_VERSION . '" -->
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
echo $do_news ? '
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" ' : '';
echo '
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd ';
echo $do_news ? '
		http://www.google.com/schemas/sitemap-news/0.9 
		http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd ' : '';
echo '">
';

// PRESETS are changable -- please read comments:

$max_priority = 0.9;	// Maximum priority value for any URL in the sitemap; set to any other value between 0 and 1.
$min_priority = 0;	// Minimum priority value for any URL in the sitemap; set to any other value between 0 and 1.
			// NOTE: Changing these values will influence each URL's priority. Priority values are taken by 
			// search engines to represent RELATIVE priority within the site domain. Forcing all URLs
			// to a priority of above 0.5 or even fixing them all to 1.0 - for example - is useless.
$frontpage_priority = 1.0;	// Your front page priority, usually the same as max priority but if you have any reason
				// to change it, please be my guest; set to any other value between 0 and 1.

$level_weight = 0.1;	// Makes a sub-page gain or loose priority for each level; set to any other value between 0 and 1.
$month_weight = -0.1;	// Fall-back value normally ignored by automatic priority calculation, which
			// makes a post loose 10% of priority monthly; set to any other value between 0 and 1.
$firstcomment_bonus = 0.1;

// EDITING below here is NOT ADVISED!

// setup site variables
$_post_count = wp_count_posts('post');
$_page_count = wp_count_posts('page');
$_totalcommentcount = wp_count_comments();

$lastmodified_gmt = get_lastmodified('GMT'); // last posts or page modified date
$lastmodified = mysql2date('U',$lastmodified_gmt); // last posts or page modified date in Unix seconds
$firstdate = mysql2date('U',get_firstdate('GMT')); // uses new get_firstdate() function defined in xml-sitemap/hacks.php !

// calculated presets
if ($_totalcommentcount->approved > 0) {
	$average_commentcount = $_totalcommentcount->approved/($_post_count->publish + $_page_count->publish);
	//$comment_weight =  $average_commentcount / $_totalcommentcount->approved;
} else {
	//$comment_weight = 0;
	$average_commentcount = 0;
}

if ( $_post_count->publish > $_page_count->publish ) { // emphasis on posts (blog)
	$blogbonus = 0.4;
	$sitebonus = 0;
} elseif ( $_post_count->publish==0 ) { // only pages (you're kidding... really?? old style site)
	$blogbonus = 0;
	$sitebonus = 0.4;
} else { // emphasis on pages (site)
	$blogbonus = 0;
	$sitebonus = 0.2;
}

if ( $lastmodified > $firstdate ) // valid blog age found ?
	$age_weight = ($blogbonus - $min_priority) / ($firstdate - $lastmodified); // calculate relative age weight
else
	$age_weight = $month_weight / 2629744 ; // if not ? malus per month (that's a month in seconds)

$exclude = array();
if ( $post_type == 'page' ) {
	$exclude[] = get_option('page_on_front');
	if ( !empty($exclude) && function_exists('pll_get_post') )
		foreach ( $xmlsf->get_languages() as $lang ) 
			$exclude[] = pll_get_post( $exclude[0], $lang );
// TODO : find a better way to exclude all Polylang language front pages : is_front_page() in any language !
}

// loop away!
if ( have_posts() ) :
    while ( have_posts() ) : 
	the_post();

	// check if we are not dealing with an external URL :: Thanks to Francois Deschenes :)
	// or if page is in the exclusion list (like front pages)
	if ( !preg_match('/^' . preg_quote(home_url(), '/') . '/i', get_permalink()) || ( $post_type == 'page' && in_array($post->ID, $exclude) ) )
		continue;
	
	$thispostmodified_gmt = $post->post_modified_gmt; // post GMT timestamp
	$thispostmodified = mysql2date('U',$thispostmodified_gmt); // post Unix timestamp
	$lastcomment = array();
	$priority = 0;

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
		
		if ($_totalcommentcount->approved > 0)
			$priority = ( $post->comment_count / ( ( $_totalcommentcount->approved / 2 ) - $average_commentcount ) ) + $firstcomment_bonus;
		else
			$priority = ( $min_priority + $max_priority ) / 2 ;
		
	}

	if($post->post_type == "page") {
	
		if (!isset($post->ancestors)) { 
			// could use get_post_ancestors($post) but that creates an 
			// extra db query per sub-page so better do it ourselves... 
			$page_obj = $post;
			$ancestors = array();
			while($page_obj->post_parent!=0) {
				$page_obj = get_page($page_obj->post_parent);
				$ancestors[] = $page_obj->ID;
			}
		} else {
			$ancestors = $post->ancestors;
		}

		$priority += (count($ancestors) * $level_weight) + $sitebonus;
	} else {
		if(is_sticky($post->ID))
			$priority = $max_priority;
		else
			$priority += (($lastmodified - $thispostmodified) * $age_weight) + $blogbonus;
	}


	$lastactivityage = (gmdate('U') - $thispostmodified); // post age
	
	// trim priority
	$priority = ($priority > $max_priority) ? $max_priority : $priority;
	$priority = ($priority < $min_priority) ? $min_priority : $priority; 
?>
	<url>
		<loc><?php the_permalink_rss(); ?></loc>
<?php 
// Google News tags 
if ( $news && $post->post_date > date('Y-m-d H:i:s', strtotime('-49 hours') ) ) { ?>
		<news:news>
			<news:publication>
				<news:name><?php 
					echo ( defined('XMLSF_GOOGLE_NEWS_TITLE') ) ? apply_filters('the_title_rss', XMLSF_GOOGLE_NEWS_TITLE) : bloginfo_rss('name'); ?></news:name>
				<news:language><?php 
					$lang = reset(get_the_terms($post->ID,'language'));
					// bloginfo_rss('language') returns improper format
					// so using explode but that breaks chinese :°(
					echo ( is_object($lang) ) ? $lang->slug : reset(explode('-', get_bloginfo_rss('language')));  ?></news:language>
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
// and lastly, set the priority to news priority level
$priority = $max_priority;
} ?>
		<lastmod><?php echo mysql2date('Y-m-d\TH:i:s+00:00', $thispostmodified_gmt, false); ?></lastmod>
		<changefreq><?php
		 	if(($lastactivityage/86400) < 1) { // last activity less than 1 day old 
		 		echo 'hourly';
		 	} else if(($lastactivityage/86400) < 7) { // last activity less than 1 week old 
		 		echo 'daily';
		 	} else if(($lastactivityage/86400) < 30) { // last activity between 1 week and one month old 
		 		echo 'weekly';
		 	} else if(($lastactivityage/86400) < 365) { // last activity between 1 month and 1 year old 
		 		echo 'monthly';
		 	} else {
		 		echo 'yearly'; // never
		 	} ?></changefreq>
	 	<priority><?php echo number_format($priority,1) ?></priority>
 	</url>
<?php 
    endwhile; 
endif; 
?></urlset>
