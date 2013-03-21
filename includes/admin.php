<?php
/* ------------------------------
 *      XMLSF Admin CLASS
 * ------------------------------ */
 
 class XMLSF_Admin extends XMLSitemapFeed {

	/**
	* SETTINGS
	*/

	// add our FancyBox Media Settings Section on Settings > Media admin page
	// TODO get a donation button in there and refer to support forum !
	public function privacy_settings_section() {
		echo '<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ravanhagen%40gmail%2ecom&item_name=XML%20Sitemap%20Feeds&item_number='.XMLSF_VERSION.'&no_shipping=0&tax=0&charset=UTF%2d8&currency_code=EUR" title="'.__('Donate to keep the free XML Sitemap Feeds plugin development & support going!','easy-fancybox').'"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" style="border:none;float:right;margin:5px 0 0 10px" alt="'.__('Donate to keep the free XML Sitemap Feeds plugin development & support going!','easy-fancybox').'" width="92" height="26" /></a>'.__('These settings control the XML Sitemap generation.','xml-sitemap-feed').' '.sprintf(__('XML Sitemaps are disabled if you have set the option %s (above) to %s.','xml-sitemap-feed'),'<strong>'.__('Site Visibility').'</strong>','<strong>'.__('Discourage search engines from indexing this site').'</strong>').'</p>
    <script type="text/javascript">
        jQuery( document ).ready( function() {
            jQuery( "input[name=\'blog_public\']" ).on( \'change\', function() {
			jQuery("#xmlsf_sitemaps input").each(function() {
			  var $this = jQuery(this);
			  $this.attr("disabled") ? $this.removeAttr("disabled") : $this.attr("disabled", "disabled");
			});
            });
            jQuery( "#xmlsf_sitemaps_index" ).on( \'change\', function() {
			jQuery("#xmlsf_post_types input,#xmlsf_taxonomies input").each(function() {
			  var $this = jQuery(this);
			  $this.attr("disabled") ? $this.removeAttr("disabled") : $this.attr("disabled", "disabled");
			});
            });
        });
    </script>';
	}
	
	public function sitemaps_settings_field() {
		$options = parent::get_sitemaps();
		$disabled = ('1' == get_option('blog_public')) ? false : true;

		echo '<div id="xmlsf_sitemaps">
			<label><input type="checkbox" name="xmlsf_sitemaps[sitemap]" id="xmlsf_sitemaps_index" value="'.XMLSF_NAME.'" '.checked(XMLSF_NAME, $options['sitemap'], false).' '.disabled($disabled, true, false).' /> '.__('Regular XML Sitemaps','xml-sitemap-feed').'</label>';
		if (isset($options['sitemap']))
			echo '<span class="description"> - <a href="'.trailingslashit(get_bloginfo('url')). ( ('' == get_option('permalink_structure')) ? '?feed=sitemap' : $options['sitemap'] ) .'" target="_blank">'.__('View').'</a></span>';
		//<a href="#">'.__('Settings').'</a> | <a href="#">'.__('Advanced').'</a> | <a href="#">'.__('Advanced Settings').'</a> | ...
		//__('Note: if you do not include any post or taxonomy types below, the sitemap will only contain your sites root url.','xml-sitemap-feed')
		echo '<br />
			<label><input type="checkbox" name="xmlsf_sitemaps[sitemap-news]" id="xmlsf_sitemaps_news" value="'.XMLSF_NEWS_NAME.'" '.checked(XMLSF_NEWS_NAME, $options['sitemap-news'], false).' '.disabled($disabled, true, false).' /> '.__('Google News Sitemap','xml-sitemap-feed').'</label>';
		if (isset($options['sitemap-news']))
			echo '<span class="description"> - <a href="'.trailingslashit(get_bloginfo('url')). ( ('' == get_option('permalink_structure')) ? '?feed=sitemap-news' : $options['sitemap-news'] ) .'" target="_blank">'.__('View').'</a></span>';
		echo '
		</div>';
	}

	public function post_types_settings_field() {
		$options = parent::get_post_types();
		$sitemaps = parent::get_sitemaps();
		$disabled = (isset($sitemaps['sitemap'])) ? false : true;

		echo '<div id="xmlsf_post_types">
			';
		foreach ( get_post_types(array('public'=>true),'objects') as $post_type ) {
			$count = wp_count_posts( $post_type->name );
				
			echo '
				<label><input type="checkbox" name="xmlsf_post_types['.
				$post_type->name.'][active]" id="xmlsf_post_types_'.
				$post_type->name.'" value="1"'.
				checked( !empty($options[$post_type->name]["active"]), true, false).
				disabled($disabled, true, false).' /> '.
				$post_type->label.'</label> ('.
				$count->publish.')';
			
			echo '
				<input type="hidden" name="xmlsf_post_types['.
				$post_type->name.'][name]" value="'.
				$post_type->name.'" />';
/* Find a better way...			
			if ( !empty($options[$post_type->name]["tags"]) )
				foreach ( (array)$options[$post_type->name]["tags"] as $tag )
					echo '
						<input type="hidden" name="xmlsf_post_types['.
						$post_type->name.'][tags][]" value="'.$tag.'" />';
			else
				echo '
					<input type="hidden" name="xmlsf_post_types['.
					$post_type->name.'][tags][]" value="image" />
					<input type="hidden" name="xmlsf_post_types['.
					$post_type->name.'][tags][]" value="video" />';
				
			echo ( !empty( $options[$post_type->name]["split_by"] ) ) ? '
				<input type="hidden" name="xmlsf_post_types['.
				$post_type->name.'][split_by]" value="'.
				$options[$post_type->name]["split_by"].'" />' : '';*/
			echo '
				<br />';
		}
		echo '
		</div>';
	}

	public function taxonomies_settings_field() {
		$options = parent::get_taxonomies();
		$sitemaps = parent::get_sitemaps();
		$disabled = (isset($sitemaps['sitemap'])) ? false : true;

		echo '<div id="xmlsf_taxonomies">
			';
		foreach ( get_taxonomies(array('public'=>true),'objects') as $taxonomy ) {
			$count = wp_count_terms( $taxonomy->name );
			echo '
				<label><input type="checkbox" name="xmlsf_taxonomies['.
				$taxonomy->name.']" id="xmlsf_taxonomies_'.
				$taxonomy->name.'" value="'.
				$taxonomy->name.'"'.
				checked(in_array($taxonomy->name,$options), true, false).
				disabled($disabled, true, false).' /> '.
				$taxonomy->label.'</label> ('.
				$count.')<br />';
		}
		echo '
		</div>';
	}

	public function robots_settings_field() {
		echo '<label for="xmlsf_robots">'.sprintf(__('Rules to append to %s generated by WordPress.','xml-sitemap-feed'),'<a href="'.trailingslashit(get_bloginfo('url')).'robots.txt" target="_blank">robots.txt</a>').'</label><br /><textarea name="xmlsf_robots" id="xmlsf_robots" class="large-text"'.disabled($disabled, true, false).' cols="50" rows="5" />'.esc_attr( parent::get_robots() ).'</textarea><p class="description"'.__('Warning: Only set rules here when you know what you are doing, otherwise you might break access to your site.<br />Note: These rules will not have effect when you are using a static robots.txt file.','xml-sitemap-feed').'</p>';
	}

	//sanitize callback functions

	public function sanitize_robots_settings($new) {
		return trim(strip_tags($new));
	}
	
	public function sanitize_sitemaps_settings($new) {
		$old = parent::get_sitemaps();
		if ($old != $new) // when sitemaps are added or removed, set transient to flush rewrite rules after updating
			set_transient('xmlsf_flush_rewrite_rules','');
		return $new;
	}
	
	public function sanitize_post_types_settings($new) {
		$old = parent::get_post_types();
		if ($old != $new) // when post types are added or removed, set transient to flush rewrite rules after updating
			set_transient('xmlsf_flush_rewrite_rules','');
		return $new;
	}

	public function sanitize_taxonomies_settings($new) {
		$old = parent::get_taxonomies();
		if ($old != $new) // when taxonomy types are added or removed, set transient to flush rewrite rules after updating
			set_transient('xmlsf_flush_rewrite_rules','');
		return $new;
	}

	public function sanitize_pings_settings($new) {
		return $new;
	}
	
	// do we need intval some day soon ? maybe for priority calc settings ? or remove ...
	public function intval($setting = '') {
		if ($setting == '')
			return '';
	
		if (substr($setting, -1) == '%') {
			$val = intval(substr($setting, 0, -1));
			$prc = '%';
		} else {
			$val = intval($setting);
		}
	
		return ( $val != 0 ) ? $val.$prc : 0;
	}
		

	/**
	* CONSTRUCTOR
	*/

	function XMLSitemapFeed() {
		//constructor in php4
		$this->__construct(); // just call the php5 one.
	}
	
	function __construct() {
		
		// SETTINGS
		add_settings_section('xmlsf_main_section', __('XML Sitemaps','xml-sitemap-feed'), array($this,'privacy_settings_section'), 'reading');
		// sitemaps
		register_setting('reading', 'xmlsf_sitemaps', array($this,'sanitize_sitemaps_settings') );
		add_settings_field('xmlsf_sitemaps', __('Enable XML sitemaps','xml-sitemap-feed'), array($this,'sitemaps_settings_field'), 'reading', 'xmlsf_main_section');
		// post_types
		register_setting('reading', 'xmlsf_post_types', array($this,'sanitize_post_types_settings') );
		add_settings_field('xmlsf_post_types', __('Include post types','xml-sitemap-feed'), array($this,'post_types_settings_field'), 'reading', 'xmlsf_main_section');
		// taxonomies
		register_setting('reading', 'xmlsf_taxonomies', array($this,'sanitize_taxonomies_settings') );
		add_settings_field('xmlsf_taxonomies', __('Include taxonomies','xml-sitemap-feed'), array($this,'taxonomies_settings_field'), 'reading', 'xmlsf_main_section');
		// pings
		//register_setting('privacy', 'xmlsf_pings', array($this,'sanitize_pings_settings') );
		
		//robots only when permalinks are set
		if(''!=get_option('permalink_structure')) {
			register_setting('reading', 'xmlsf_robots', array($this,'sanitize_robots_settings') );
			add_settings_field('xmlsf_robots', __('Additional robots.txt rules','xml-sitemap-feed'), array($this,'robots_settings_field'), 'reading', 'xmlsf_main_section');
		}
	
		//add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'easy_fancybox_add_action_link');
	}

}

/* ----------------------
*      INSTANTIATE
* ---------------------- */

if ( class_exists('XMLSitemapFeed') )
	$xmlsf_admin = new XMLSF_Admin();

