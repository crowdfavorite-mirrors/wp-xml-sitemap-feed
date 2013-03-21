<?php
/* ------------------------------
 *      XMLSitemapFeed CLASS
 * ------------------------------ */

class XMLSitemapFeed {

	/**
	* Plugin variables
	*/
	
	public $base_name = 'sitemap';

	public $extension = 'xml';
	
	private $yes_mother = false;

	private $defaults = array();
						
	private function build_defaults() {

		// sitemaps
		if ( '1' == get_option('blog_public') ) 
			$this->defaults['sitemaps'] = array(
					'sitemap' => XMLSF_NAME,
					);
		else
			$this->defaults['sitemaps'] = array();

		// post_types
		if ( defined('XMLSF_POST_TYPE') && XMLSF_POST_TYPE != 'any' ) {
			$this->defaults['post_types'] = array_map('trim',explode(',',XMLSF_POST_TYPE));
		} else {
			$this->defaults['post_types'] = array(
							'post' => array(
								'active' => '1',
								'name' => 'post',
								//'tags' => array('news','image','video'),
								//'split_by' => 'year'
								),
							'page' => array(
								'active' => '1',
								'name' => 'page',
								//'tags' => array('image','video')
								)
							);
			foreach ( get_post_types(array('public'=>true,'_builtin'=>false),'names') as $custom ) 
				$this->defaults['post_types'][$custom] = array(
								'active' => '1',
								'name' => $custom,
								//'tags' => array('image','video')
							);			
					
		}

		// taxonomies
		$this->defaults['taxonomies'] = array();

		// robots
		$this->defaults['robots'] = '';
	}


	public function defaults($key = false) {

		if (empty($this->defaults))
			$this->build_defaults();

		if (!$key) 
			return apply_filters( 'xmlsf_defaults', $this->defaults, false );
		else
			return apply_filters( 'xmlsf_defaults', $this->defaults[$key], $key );

	}
	
	public function get_sitemaps() {		

		if (empty($this->defaults))
			$this->build_defaults();

		$return = get_option('xmlsf_sitemaps', $this->defaults['sitemaps']);

		return (empty($return)) ? array() : $return;
	}
		
	public function get_post_types() {		

		if (empty($this->defaults))
			$this->build_defaults();

		$return = get_option('xmlsf_post_types', $this->defaults['post_types']);

		return (empty($return)) ? array() : $return;
	}
		
	public function get_taxonomies() {

		if (empty($this->defaults))
			$this->build_defaults();

		$return = get_option('xmlsf_taxonomies', $this->defaults['taxonomies']);

		return (empty($return)) ? array() : $return;
	}

	public function get_robots() {

		if (empty($this->defaults))
			$this->build_defaults();

		return get_option('xmlsf_robots', $this->defaults['robots']);
	}

	public function get_do_tags( $type = 'post' ) {
	
		// just return empty array for now...	
		return array();
		
		$sitemaps = get_option('xmlsf_sitemaps', $this->defaults('sitemaps'));
		$return = get_option('xmlsf_post_types', $this->defaults('post_types'));

		// unset news tags if news sitemap is switched off
		if ( isset($return[$type]['tags']['news']) && !isset($sitemaps['sitemap-news']) )
			unset($return[$type]['tags']['news']);

		return $return[$type]['tags'];
	}
	
		
	/**
	* MULTI-LANGUAGE PLUGIN FUNCTIONS
	*/

	public function get_languages() {
		/* Only Polylang compatibility for now, rest is rudimentary */
		global $polylang;
		if ( isset($polylang) ) {
			$langs = array();
			foreach ($polylang->get_languages_list() as $term)
		    		$langs[] = $term->slug;
		    	
			return $langs;
		}
		
		global $q_config;
		if (isset($q_config)) 
			return $q_config['enabled_languages'];
			// or return only current language in the array??

		return array();
	}

	public function get_home_urls() {
		$urls = array();
		
		global $polylang,$q_config;

		if ( isset($polylang) )			
			foreach ($polylang->get_languages_list() as $term)
		    		$urls[] = $polylang->get_home_url($term);
		elseif ( isset($q_config) ) 
			foreach($q_config['enabled_languages'] as $language)
				$urls[] = qtrans_convertURL($url,$language);
		else
			$urls[] = esc_url( trailingslashit( home_url()) );
		
		return $urls;
	}

	/**
	* MULTI-LANGUAGE PLUGIN FILTERS
	*/

	// Polylang
	public function polylang($input) {
		global $polylang;
		$options = get_option('polylang');

		if (is_array($input)) { // got an array? return one!
			if ('1' == $options['force_lang'] )
				foreach ( $input as $url )
					foreach($polylang->get_languages_list() as $language)
						$return[] = $polylang->add_language_to_link($url,$language);
			else
				foreach ( $input as $url )
					foreach($polylang->get_languages_list() as $language)
						$return[] = add_query_arg('lang', $language->slug, $url);
		} else { // not an array? do nothing, Polylang does all the work :)
			$return = $input;
		}

		return $return;
	}

	// qTranslate
	public function qtranslate($input) {
		global $q_config;

		if (is_array($input)) // got an array? return one!
			foreach ( $input as $url )
				foreach($q_config['enabled_languages'] as $language)
					$return[] = qtrans_convertURL($url,$language);
		else // not an array? just convert the string.
			$return = qtrans_convertURL($input);

		return $return;
	}


	/**
	* ROBOTSTXT 
	*/

	// add sitemap location in robots.txt generated by WP
	public function robots($output) {
			
		$sitemaps = get_option('xmlsf_sitemaps', $this->defaults('sitemaps'));

		echo "\n# XML & Google News Sitemap Feeds - version ".XMLSF_VERSION." (http://status301.net/wordpress-plugins/xml-sitemap-feed/)";

		if (!empty($sitemaps))
			foreach ( $sitemaps as $pretty )
				echo "\nSitemap: " . trailingslashit(get_bloginfo('url')) . $pretty;
		else
			echo "\n# Warning: XML Sitemaps are disabled. Please see your sites XML Sitemap and Privacy settings.";
		
		echo "\n\n";
	}
	
	// add robots.txt rules
	public function robots_txt($output) {
		return $output . get_option('xmlsf_robots') ;
	}
	
	/**
	* DE-ACTIVATION
	*/

	public function deactivate() {
		global $wp_rewrite;
		remove_action('generate_rewrite_rules', array($this, 'rewrite_rules') );
		$wp_rewrite->flush_rules();
		delete_option('xmlsf_version');
		foreach ( $this->defaults() as $option => $settings )
			delete_option('xmlsf_'.$option);
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
	 
	public function trailingslash($request) {
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
	 
	public function rewrite_rules($wp_rewrite) {

		$xmlsf_rules = array();
		$sitemaps = $this->get_sitemaps();

		foreach ( $sitemaps as $name => $pretty )
			$xmlsf_rules[ preg_quote($pretty) . '$' ] = $wp_rewrite->index . '?feed=' . $name;

		if (!empty($sitemaps['sitemap'])) {
			// home urls
			$xmlsf_rules[ $this->base_name . '-home\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-home';
			// all urls (still works but redundant)
			//$xmlsf_rules[ $this->base_name . '-posttype-any\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-any';
			// rule catch posts split by category (unsupported)
			//$xmlsf_rules[ $this->base_name . '\.([a-z0-9_-]+)?\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap_post&category_name=$matches[1]';			
		
			// add rules for post types (can be split by month or year)
			foreach ( $this->get_post_types() as $post_type ) {
				$xmlsf_rules[ $this->base_name . '-posttype-' . $post_type['name'] . '\.([0-9]+)?\.?' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-posttype_' . $post_type['name'] . '&m=$matches[1]';
			}
		
			// add rules for taxonomies
			foreach ( $this->get_taxonomies() as $taxonomy ) {
				$xmlsf_rules[ $this->base_name . '-taxonomy-' . $taxonomy . '\.' . $this->extension . '$' ] = $wp_rewrite->index . '?feed=sitemap-taxonomy&taxonomy=' . $taxonomy;
			}
		}
		
		$wp_rewrite->rules = $xmlsf_rules + $wp_rewrite->rules;
	}
	
	/**
	* REQUEST FILTER
	*/

	public function filter_request( $request ) {
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
					if ( $request['feed'] == 'sitemap-posttype_'.$post_type['name'] ) {
						// setup actions and filters
						add_action('do_feed_sitemap-posttype_'.$post_type['name'], array($this, 'load_template'), 10, 1);
						add_filter( 'post_limits', array($this, 'filter_limits') );

						// modify request parameters
						$request['post_type'] = $post_type['name'];
						$request['orderby'] = 'modified';
						//$request['lang'] = implode( ',', $this->get_languages() );
							// TODO test this with qTranslate !!
						$request['no_found_rows'] = true;
						$request['update_post_meta_cache'] = false;
						$request['update_post_term_cache'] = false;

						return $request;
					}
				}
			}

			if ( $request['feed'] == 'sitemap-taxonomy' ) {
				// setup actions and filters
				add_action('do_feed_sitemap-taxonomy', array($this, 'load_template_taxonomy'), 10, 1);
//				add_filter( 'post_limits', array( $this, 'filter_limits_taxonomy' ) );

				// modify request parameters
				$request['lang'] = implode( ',', $this->get_languages() );
					// TODO test this with qTranslate !!

				$request['no_found_rows'] = true;
				$request['update_post_meta_cache'] = false;
				$request['update_post_term_cache'] = false;
				$request['post_status'] = 'publish';

				return $request;
			}

/* Still works, but redundant
			if ( $request['feed'] == 'sitemap-any' ) {
				// setup actions and filters
				add_action('do_feed_sitemap-any', array($this, 'load_template'), 10, 1);
				add_filter('post_limits', array($this, 'filter_limits'));

				// modify request parameters
				$request['post_type'] = 'any';
				$request['orderby'] = 'modified';

				$request['no_found_rows'] = true;
				$request['update_post_meta_cache'] = false;
				$request['update_post_term_cache'] = false;

				return $request;
			}*/

		}

		return $request;
	}

	/**
	* FEED TEMPLATES
	*/

	// set up the sitemap index template
	public function load_template_index() {
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap.php' );
	}

	// set up the sitemap home page(s) template
	public function load_template_base() {
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-home.php' );
	}

	// set up the post types sitemap template
	public function load_template() {
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-post_type.php' );
	}

	// set up the taxonomy sitemap template
	public function load_template_taxonomy() {
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-taxonomy.php' );
	}

	// set up the news sitemap template
	public function load_template_news() {
		load_template( XMLSF_PLUGIN_DIR . '/includes/feed-sitemap-news.php' );
	}

	/**
	* LIMITES
	*/

	// override default feed limit
	public function filter_limits( $limits ) {
		return 'LIMIT 0, 50000';
	}

	// override default feed limit for taxonomy sitemaps
	public function filter_limits_taxonomy( $limits ) {
		return 'LIMIT 0, 1';
	}

	// override default feed limit for GN
	public function filter_news_limits( $limits ) {
		return 'LIMIT 0, 1000';
	}

	// Create a new filtering function that will add a where clause to the query,
	// used for the Google News Sitemap
	public function filter_news_where( $where = '' ) {
		// only posts from the last 2 days
		return $where . " AND post_date > '" . date('Y-m-d H:i:s', strtotime('-49 hours')) . "'";
	}
		
	/**
	* INITIALISATION
	*/

	public function plugins_loaded() {

		// TEXT DOMAIN
		
		if ( is_admin() ) // text domain on plugins_loaded even if it is for admin only
			load_plugin_textdomain('xml-sitemap-feed', false, dirname(dirname(plugin_basename( __FILE__ ))) . '/languages' );
		
		// LANGUAGE PLUGINS

		// check for Polylang and add filter
		global $polylang;
		if (isset($polylang))
			add_filter('xml_sitemap_url', array($this, 'polylang'), 99);

		// check for qTranslate and add filter
		elseif (defined('QT_LANGUAGE'))
			add_filter('xml_sitemap_url', array($this, 'qtranslate'), 99);

		// some upgrade stuffs, to be removed next version
		if (delete_option('xml-sitemap-feed-version')) { 
			delete_option('XMLSitemapFeed_option1');
			delete_option('XMLSitemapFeed_option2');

		}
		if (get_option('XMLSitemapFeed_robots')) { 
			update_option('xmlsf_robots', get_option('XMLSitemapFeed_robots'));
			delete_option('XMLSitemapFeed_robots');
		}		

		if (get_option('xmlsf_version') != XMLSF_VERSION) {
			// rewrite rules not available on plugins_loaded 
			// don't flush rules from init as Polylang chokes on that
			// just remove the rules and let WP renew them when ready...
			delete_option('rewrite_rules');

			$this->yes_mother = true;

			update_option('xmlsf_version', XMLSF_VERSION);
		}
		
	}

	private function flush_rules($hard = false) {
		// don't need hard flush by default
		
		if ($this->yes_mother)
			return;

		global $wp_rewrite;
		$wp_rewrite->flush_rules($hard); 

		$this->yes_mother = true;
	}
	
	public function admin_init() {

		// UPGRADE RULES after plugin upgrade
		// CATCH TRANSIENT for flushing rewrite rules after the sitemaps setting has changed
		if (get_option('xmlsf_version') != XMLSF_VERSION) {
			$this->flush_rules();
			update_option('xmlsf_version', XMLSF_VERSION);
		} else
		
		if (delete_transient('xmlsf_flush_rewrite_rules')) {
			$this->flush_rules();
		}
		
		// Include the admin class file
		include_once( XMLSF_PLUGIN_DIR . '/includes/admin.php' );

	}

	/**
	* CONSTRUCTOR
	*/

	function XMLSitemapFeed() {
		//constructor in php4
		$this->__construct(); // just call the php5 one.
	}
	
	function __construct() {
		
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

		// DE-ACTIVATION
		register_deactivation_hook( XMLSF_PLUGIN_DIR . '/xml-sitemap.php', array($this, 'deactivate') );
	}

}
