<?php
/* ------------------------------
 *      XMLSitemapFeed CLASS
 * ------------------------------ */

class XMLSitemapFeed {

	/**
	* Plugin variables
	*/
	
	// Pretty permalinks base name
	public $base_name = 'sitemap';

	// Pretty permalinks extension
	public $extension = 'xml';
	
	// Database options prefix
	private $prefix = 'xmlsf_';
	
	// Flushed flag
	private $yes_mother = false;

	private $defaults = array();
	
	// Global values used for priority and changefreq calculation
	private $firstdate;
	private $lastmodified;
	private $postmodified = array();
						
	private function build_defaults() 
	{
		// sitemaps
		if ( '1' == get_option('blog_public') )
			$this->defaults['sitemaps'] = array(
					'sitemap' => XMLSF_NAME
					);
		else
			$this->defaults['sitemaps'] = array();

		// post_types
		$this->defaults['post_types'] = array();
		foreach ( get_post_types(array('public'=>true),'names') as $name ) {
			$this->defaults['post_types'][$name] = array(
								'name' => $name,
								'active' => '',
								'priority' => '0.5',
								'dynamic_priority' => '',
								);
		}		

		if ( defined('XMLSF_POST_TYPE') && XMLSF_POST_TYPE != 'any' )
			$active_arr = array_map('trim',explode(',',XMLSF_POST_TYPE));
		else 
			$active_arr = array('post','page');
			
		foreach ( $active_arr as $name )
			if ( isset($this->defaults['post_types'][$name]) )
				$this->defaults['post_types'][$name]['active'] = '1';
		
		if ( isset($this->defaults['post_types']['post']) ) {
			if (wp_count_posts('post')->publish > 500)
				$this->defaults['post_types']['post']['archive'] = 'yearly';
			else
				$this->defaults['post_types']['post']['archive'] = '';
			//$this->defaults['post_types']['post']['tags'] => array('news','image','video');
			$this->defaults['post_types']['post']['priority'] = '0.7';
			$this->defaults['post_types']['post']['dynamic_priority'] = '1';
		}

		if ( isset($this->defaults['post_types']['page']) ) {
			//$this->defaults['post_types']['page']['tags'] => array('image','video');
			$this->defaults['post_types']['page']['priority'] = '0.3';
		}

		// taxonomies
		$this->defaults['taxonomies'] = array();// by default do not include any taxonomies

		// ping search engines
		$this->defaults['ping'] = array(
					'google' => array (
						'active' => '1',
						'uri' => 'http://www.google.com/webmasters/tools/ping?sitemap=',
						),
					'bing' => array (
						'active' => '1',
						'uri' => 'http://www.bing.com/ping?sitemap=',
						),
					);

		$this->defaults['pings'] = array(); // for storing last ping timestamps and status

		// robots
		$this->defaults['robots'] = "Disallow: /xmlrpc.php\nDisallow: /wp-\nDisallow: /trackback/\nDisallow: ?wptheme=\nDisallow: ?comments=\nDisallow: ?replytocom\nDisallow: /comment-page-\nDisallow: /?s=\nDisallow: /wp-content/\nAllow: /wp-content/uploads/\n";
	}


	public function defaults($key = false) 
	{
		if (empty($this->defaults))
			$this->build_defaults();

		if (!$key) 
			$return = $this->defaults;
		else
			$return = $this->defaults[$key];
			
		return apply_filters( 'xmlsf_defaults', $return, $key );
	}
	
	public function get_option($option) 
	{
		return get_option($this->prefix.$option, $this->defaults($option));
	}
		
	public function get_sitemaps() 
	{		
		$return = $this->get_option('sitemaps');
		
		// make sure it's an array we are returning
		return (is_array($return)) ? (array)$return : array();
	}
		
	public function get_ping() 
	{		
		$return = $this->get_option('ping');
		
		// make sure it's an array we are returning
		return (!empty($return)) ? (array)$return : array();
	}
		
	public function get_pings() 
	{		
		$return = $this->get_option('pings');
		
		// make sure it's an array we are returning
		return (!empty($return)) ? (array)$return : array();
	}
		
	public function get_post_types() 
	{		
		$return = $this->get_option('post_types');

		// make sure it's an array we are returning
		return (!empty($return)) ? (array)$return : array();
	}

	public function have_post_types() 
	{		
		$post_types = $this->get_option('post_types');
		$return = array();
		

		foreach ( $post_types as $type => $values ) {
			if(!empty($values['active'])) {
				$count = wp_count_posts( $values['name'] );
				if ($count->publish > 0) {
					$values['count'] = $count->publish;
				
					$return[$type] = $values;				
				}					
			}
		}

		// make sure it's an array we are returning
		return (!empty($return)) ? (array)$return : array();
	}
		
	public function get_taxonomies() 
	{
		$return = $this->get_option('taxonomies');

		// make sure it's an array we are returning
		return (!empty($return)) ? (array)$return : array();
	}

	public function get_archives($post_type = 'post', $type = '') 
	{
		global $wpdb;
		$return = array();
		if ( 'monthly' == $type ) {
			$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts WHERE post_type = '$post_type' AND post_status = 'publish' GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date DESC";
			$key = md5($query);
			$cache = wp_cache_get( 'xmlsf_get_archives' , 'general');
			if ( !isset( $cache[ $key ] ) ) {
				$arcresults = $wpdb->get_results($query);
				$cache[ $key ] = $arcresults;
				wp_cache_set( 'xmlsf_get_archives', $cache, 'general' );
			} else {
				$arcresults = $cache[ $key ];
			}
			if ( $arcresults ) {
				foreach ( (array) $arcresults as $arcresult ) {
					$return[$arcresult->year.$arcresult->month] = $this->get_index_url( 'posttype', $post_type, $arcresult->year . $arcresult->month );
				}
			}
		} elseif ('yearly' == $type) {
			$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts WHERE post_type = '$post_type' AND post_status = 'publish' GROUP BY YEAR(post_date) ORDER BY post_date DESC";
			$key = md5($query);
			$cache = wp_cache_get( 'xmlsf_get_archives' , 'general');
			if ( !isset( $cache[ $key ] ) ) {
				$arcresults = $wpdb->get_results($query);
				$cache[ $key ] = $arcresults;
				wp_cache_set( 'xmlsf_get_archives', $cache, 'general' );
			} else {
				$arcresults = $cache[ $key ];
			}
			if ($arcresults) {
				foreach ( (array) $arcresults as $arcresult) {
					$return[$arcresult->year] = $this->get_index_url( 'posttype', $post_type, $arcresult->year );
				}
			}
		} else {
			$return[0] = $this->get_index_url('posttype', $post_type); // $sitemap = 'home', $type = false, $param = false
		}
		return $return;
	}

	public function get_robots() 
	{
		return ( $robots = $this->get_option('robots') ) ? $robots : '';
	}

	public function get_do_tags( $type = 'post' ) 
	{
		$return = $this->get_option('post_types');

		// make sure it's an array we are returning
		return (!empty($return[$type]['tags'])) ? (array)$return[$type]['tags'] : array();
	}
	
		
	/**
	* TEMPLATE FUNCTIONS
	*/
	
	public function postmodified() 
	{
		global $post;

		if ( empty($this->postmodified[$post->ID]) ) {
			$postmodified = get_post_modified_time( 'Y-m-d H:i:s', true, $post->ID );
			$options = $this->get_option('post_types');

			if( !empty($options[$post->post_type]['update_lastmod_on_comments']) )
				$lastcomment = get_comments( array(
							'status' => 'approve',
							'number' => 1,
							'post_id' => $post->ID,
							) );

			if ( isset($lastcomment[0]->comment_date_gmt) )
				if ( mysql2date( 'U', $lastcomment[0]->comment_date_gmt ) > mysql2date( 'U', $postmodified ) )
					$postmodified = $lastcomment[0]->comment_date_gmt;
		
			$this->postmodified[$post->ID] = $postmodified;
		}
		
		return $this->postmodified[$post->ID];
	}

	public function get_lastmod() 
	{
		return mysql2date('Y-m-d\TH:i:s+00:00', $this->postmodified(), false);

	}

	public function get_changefreq() 
	{
		$lastactivityage = ( gmdate('U') - mysql2date( 'U', $this->postmodified() ) ); // post age
	 	
	 	if ( ($lastactivityage/86400) < 1 ) { // last activity less than 1 day old 
	 		$changefreq = 'hourly';
	 	} else if ( ($lastactivityage/86400) < 7 ) { // last activity less than 1 week old 
	 		$changefreq = 'daily';
	 	} else if ( ($lastactivityage/86400) < 30 ) { // last activity less than one month old 
	 		$changefreq = 'weekly';
	 	} else if ( ($lastactivityage/86400) < 365 ) { // last activity less than 1 year old 
	 		$changefreq = 'monthly';
	 	} else {
	 		$changefreq = 'yearly'; // over a year old...
	 	} 

	 	return $changefreq;
	}

	public function get_priority() 
	{
		global $post;
		$options = $this->get_option('post_types');
		$defaults = $this->defaults('post_types');
		$priority_meta = get_metadata('post', $post->ID, '_xmlsf_priority' , true);
		
		if ( !empty($priority_meta) ) {
		
			$priority = $priority_meta;
			
		} elseif ( !empty($options[$post->post_type]['dynamic_priority']) ) {
		
			$post_modified = mysql2date('U',$post->post_modified_gmt);
		
			if ( empty($this->lastmodified) )
				$this->lastmodified = mysql2date('U',get_lastmodified('GMT',$post->post_type)); 
				// last posts or page modified date in Unix seconds 
				// uses get_lastmodified() function defined in xml-sitemap/hacks.php !
			
			if ( empty($this->firstdate) )
				$this->firstdate = mysql2date('U',get_firstdate('GMT',$post->post_type)); 
				// uses get_firstdate() function defined in xml-sitemap/hacks.php !
			
			if ( isset($options[$post->post_type]['priority']) )
				$priority_value = $options[$post->post_type]['priority'];
			else
				$priority_value = $defaults[$post->post_type]['priority'];
		
			// reduce by age
			if ( is_sticky($post->ID) )
				$priority = $priority_value;
			else
				$priority = ( $this->lastmodified > $this->firstdate ) ? $priority_value - $priority_value * ( $this->lastmodified - $post_modified ) / ( $this->lastmodified - $this->firstdate ) : $priority_value;
		
			if ( $post->comment_count > 0 )
				$priority = $priority + 0.1 + ( 0.9 - $priority ) * $post->comment_count / wp_count_comments($post->post_type)->approved;

			// and a final trim for cases where we end up above 1 (sticky posts with many comments)
			if ($priority > 1) 
				$priority = 1;

		} else {
		
			$priority = ( isset($options[$post->post_type]['priority']) && is_numeric($options[$post->post_type]['priority']) ) ? $options[$post->post_type]['priority'] : $defaults[$post->post_type]['priority'];
		
		}
		
		return number_format($priority,1);
	}

	public function get_home_urls() 
	{
		$urls = array();
		
		global $polylang,$q_config;

		if ( isset($polylang) )			
			foreach ($polylang->get_languages_list() as $term)
		    		$urls[] = $polylang->get_home_url($term);
		else
			$urls[] = home_url();
		
		return $urls;
	}

	public function get_excluded($post_type) 
	{
		global $polylang;
		$exclude = array();
		
		if ( $post_type == 'page' && $id = get_option('page_on_front') ) {
			$exclude[] = $id;
			if ( isset($polylang) )
				$exclude = $polylang->get_translations('post', $id);
		}
		
		return $exclude;
	}

	public function get_index_url( $sitemap = 'home', $type = false, $param = false ) 
	{
		$root =  esc_url( trailingslashit(home_url()) );		
		$name = $this->base_name.'-'.$sitemap;
				
		if ( $type )
			$name .= '-'.$type;			

		if ( '' == get_option('permalink_structure') || '1' != get_option('blog_public')) {
			$name = '?feed='.$name;
			$name .= $param ? '&m='.$param : '';
		} else {
			$name .= $param ? '.'.$param : '';
			$name .= '.'.$this->extension;
		}
		
		return $root . $name;
	}
	

	/**
	* ROBOTSTXT 
	*/

	// add sitemap location in robots.txt generated by WP
	public function robots($output) 
	{		
		echo "\n# XML Sitemap & Google News Feeds version ".XMLSF_VERSION." - http://status301.net/wordpress-plugins/xml-sitemap-feed/";

		if ( '1' != get_option('blog_public') ) {
			echo "\n# XML Sitemaps are disabled. Please see Site Visibility on Settings > Reading.";
		} else {
			foreach ( $this->get_sitemaps() as $pretty ) 
				echo "\nSitemap: " . trailingslashit(get_bloginfo('url')) . $pretty;

			if ( empty($pretty) )
				echo "\n# No XML Sitemaps are enabled. Please see XML Sitemaps on Settings > Reading.";
		}
		echo "\n\n";
	}
	
	// add robots.txt rules
	public function robots_txt($output) 
	{
		return $output . $this->get_option('robots') ;
	}
	
	/**
	* REWRITES
	*/

	/**
	 * Remove the trailing slash from permalinks that have an extension,
	 * such as /sitemap.xml (thanks to Permalink Editor plugin for WordPress)
	 *
	 * @param string $request
	 */
	 
	public function trailingslash($request) 
	{
		if (pathinfo($request, PATHINFO_EXTENSION)) {
			return untrailingslashit($request);
		}
		return $request; // trailingslashit($request);
	}

	/**
	 * Add sitemap rewrite rules
	 *
	 * @param string $wp_rewrite
	 */
	 
	public function rewrite_rules($wp_rewrite) 
	{
		$xmlsf_rules = array();
		$sitemaps = $this->get_sitemaps();

		foreach ( $sitemaps as $name => $pretty )
			$xmlsf_rules[ preg_quote($pretty) . '$' ] = $wp_rewrite->index . '?feed=' . $name;

		if (!empty($sitemaps['sitemap'])) {
			// home urls
			$xmlsf_rules[ $this->base_name . '-home\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-home';
		
			// add rules for post types (can be split by month or year)
			foreach ( $this->get_post_types() as $post_type )
				if ( isset($post_type['active']) && '1' == $post_type['active'] )
					$xmlsf_rules[ $this->base_name . '-posttype-' . $post_type['name'] . '\.([0-9]+)?\.?' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-posttype-' . $post_type['name'] . '&m=$matches[1]';
		
			// add rules for taxonomies
			foreach ( $this->get_taxonomies() as $taxonomy )
				$xmlsf_rules[ $this->base_name . '-taxonomy-' . $taxonomy . '\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-taxonomy-' . $taxonomy; //&taxonomy=

		}
		
		$wp_rewrite->rules = $xmlsf_rules + $wp_rewrite->rules;
	}
	
	/**
	* REQUEST FILTER
	*/

	public function filter_request( $request ) 
	{
		if ( isset($request['feed']) && strpos($request['feed'],'sitemap') == 0 ) {

			if ( $request['feed'] == 'sitemap' ) {
				// setup actions and filters
				add_action('do_feed_sitemap', array($this, 'load_template_index'), 10, 1);

				return $request;
			}

			if ( $request['feed'] == 'sitemap-news' ) {
				// disable caching
				define( 'DONOTCACHEPAGE', 1 ); // wp super cache -- or does super cache always clear feeds after new posts??
				// TODO w3tc
				
				// setup actions and filters
				add_action('do_feed_sitemap-news', array($this, 'load_template_news'), 10, 1);
				add_filter('post_limits', array($this, 'filter_news_limits') );
				add_filter('posts_where', array($this, 'filter_news_where'), 10, 1);

				// modify request parameters
				$types_arr = explode(',',XMLSF_NEWS_POST_TYPE);
				$request['post_type'] = (in_array('any',$types_arr)) ? 'any' : $types_arr;

				$request['no_found_rows'] = true;
				$request['update_post_meta_cache'] = false;
				//$request['update_post_term_cache'] = false; // << TODO test: can we disable or do we need this for terms?

				return $request;
			}

			if ( $request['feed'] == 'sitemap-home' ) {
				// setup actions and filters
				add_action('do_feed_sitemap-home', array($this, 'load_template_base'), 10, 1);

				return $request;
			}

			if ( strpos($request['feed'],'sitemap-posttype') == 0 ) {
				foreach ( $this->get_post_types() as $post_type ) {
					if ( $request['feed'] == 'sitemap-posttype-'.$post_type['name'] ) {
						// setup actions and filters
						add_action('do_feed_sitemap-posttype-'.$post_type['name'], array($this, 'load_template'), 10, 1);
						add_filter( 'post_limits', array($this, 'filter_limits') );

						// modify request parameters
						$request['post_type'] = $post_type['name'];
						$request['orderby'] = 'modified';
						$request['lang'] = '';
						$request['no_found_rows'] = true;
						$request['update_post_meta_cache'] = false;
						$request['update_post_term_cache'] = false;

						return $request;
					}
				}
			}

			if ( strpos($request['feed'],'sitemap-taxonomy') == 0 ) {
				foreach ( $this->get_taxonomies() as $taxonomy ) {
					if ( $request['feed'] == 'sitemap-taxonomy-'.$taxonomy ) {
						// setup actions and filters
						add_action('do_feed_sitemap-taxonomy-'.$taxonomy, array($this, 'load_template_taxonomy'), 10, 1);

						// modify request parameters
						$request['taxonomy'] = $taxonomy;
						$request['lang'] = '';
						$request['no_found_rows'] = true;
						$request['update_post_meta_cache'] = false;
						$request['update_post_term_cache'] = false;
						$request['post_status'] = 'publish';

						return $request;
					}
				}
			}
		}

		return $request;
	}

	/**
	* FEED TEMPLATES
	*/

	// set up the sitemap index template
	public function load_template_index() 
	{
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap.php' );
	}

	// set up the sitemap home page(s) template
	public function load_template_base() 
	{
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-home.php' );
	}

	// set up the post types sitemap template
	public function load_template() 
	{
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-post_type.php' );
	}

	// set up the taxonomy sitemap template
	public function load_template_taxonomy() 
	{
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-taxonomy.php' );
	}

	// set up the news sitemap template
	public function load_template_news() 
	{
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-news.php' );
	}

	/**
	* LIMITS
	*/

	// override default feed limit
	public function filter_limits( $limits ) 
	{
		return 'LIMIT 0, 50000';
	}

	// override default feed limit for taxonomy sitemaps
	public function filter_limits_taxonomy( $limits ) 
	{
		return 'LIMIT 0, 1';
	}

	// override default feed limit for GN
	public function filter_news_limits( $limits ) 
	{
		return 'LIMIT 0, 1000';
	}

	// Create a new filtering function that will add a where clause to the query,
	// used for the Google News Sitemap
	public function filter_news_where( $where = '' ) 
	{
		// only posts from the last 2 days
		return $where . " AND post_date > '" . date('Y-m-d H:i:s', strtotime('-49 hours')) . "'";
	}
		

	/**
	* PINGING
	*/

	public function ping($uri, $timeout = 3) 
	{
		$options = array();
		$options['timeout'] = $timeout;

		$response = wp_remote_request( $uri, $options );

		if ( '200' == wp_remote_retrieve_response_code($response) )
			$succes = true;
		else
			$succes = false;	

		return $succes;
	}

	public function do_pings($post_ID) 
	{		
		$sitemaps = $this->get_sitemaps();
		foreach ($this->get_ping() as $se => $data) {
			if(empty($data['active']) || '1' != $data['active']) continue;
				
			foreach ( $sitemaps as $pretty ) {
				if ( $this->ping( $data['uri'].urlencode(trailingslashit(get_bloginfo('url')) . $pretty) ) ) {
					$pings = $this->get_pings();
					$pings[$se][$pretty] = mysql2date('Y-m-d H:i:s', 'now', false);
					update_option($this->prefix.'pings',$pings);
				}
			}
		}

		return $post_ID;
	}

	/**
	* DE-ACTIVATION
	*/

	public function clear_settings() 
	{
		delete_option('xmlsf_version');
		foreach ( $this->defaults() as $option => $settings )
			delete_option('xmlsf_'.$option);

		remove_action('generate_rewrite_rules', array($this, 'rewrite_rules') );
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}

	/**
	* INITIALISATION
	*/

	public function plugins_loaded() 
	{
		// TEXT DOMAIN
		
		if ( is_admin() ) // text domain on plugins_loaded even if it is for admin only
			load_plugin_textdomain('xml-sitemap-feed', false, dirname(dirname(plugin_basename( __FILE__ ))) . '/languages' );

		if (get_option('xmlsf_version') != XMLSF_VERSION) {
			// rewrite rules not available on plugins_loaded 
			// and don't flush rules from init as Polylang chokes on that
			// just remove the rules and let WP renew them when ready...
			delete_option('rewrite_rules');

			$this->yes_mother = true;

			update_option('xmlsf_version', XMLSF_VERSION);
		}
		
	}

	private function flush_rules($hard = false) 
	{		
		if ($this->yes_mother)
			return;

		global $wp_rewrite;
		// don't need hard flush by default
		$wp_rewrite->flush_rules($hard); 

		$this->yes_mother = true;
	}
	
	public function admin_init() 
	{
		// UPGRADE RULES after plugin upgrade (is this needed since we do this on plugins_loaded too?)
		if (get_option('xmlsf_version') != XMLSF_VERSION) {
			$this->flush_rules();
			update_option('xmlsf_version', XMLSF_VERSION);
		}

		// CATCH TRANSIENT for reset
		if (delete_transient('xmlsf_clear_settings'))
			$this->clear_settings();
		
		// CATCH TRANSIENT for flushing rewrite rules after the sitemaps setting has changed
		if (delete_transient('xmlsf_flush_rewrite_rules'))
			$this->flush_rules();
		
		// Include the admin class file
		include_once( XMLSF_PLUGIN_DIR . '/includes/admin.php' );

	}

	// for debugging
	public function _e_usage() 
	{
		if (defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<!-- Queries executed '.get_num_queries();
			if(function_exists('memory_get_peak_usage'))
				echo ' | Peak memory usage '.round(memory_get_peak_usage()/1024/1024,2).'M';
			echo ' -->';
		}
	}

	/**
	* CONSTRUCTOR
	*/

	function __construct() 
	{	
		// REQUEST main filtering function
		add_filter('request', array($this, 'filter_request'), 1 );
		
		// TEXT DOMAIN, LANGUAGE PLUGIN FILTERS ...
		add_action('plugins_loaded', array($this,'plugins_loaded'), 11 );

		// REWRITES
		add_action('generate_rewrite_rules', array($this, 'rewrite_rules') );
		add_filter('user_trailingslashit', array($this, 'trailingslash') );
		
		// REGISTER SETTINGS, SETTINGS FIELDS, UPGRADE checks...
		add_action('admin_init', array($this,'admin_init'));
		
		// ROBOTSTXT
		add_action('do_robotstxt', array($this, 'robots'), 0 );
		add_filter('robots_txt', array($this, 'robots_txt'), 0 );
		
		// PINGING
		add_action('publish_post', array($this, 'do_pings'));

		// DE-ACTIVATION
		register_deactivation_hook( XMLSF_PLUGIN_DIR . '/xml-sitemap.php', array($this, 'clear_settings') );
	}

}
