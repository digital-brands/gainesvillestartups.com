<?php
/*
Plugin Name: Fundraising
Plugin URI: http://premium.wpmudev.org/project/fundraising/
Description: Create a fundraising page for any purpose or project.  Check http://premium.wpmudev.org/project/fundraising/ for installation guides and instructions.
Version: 1.0.0
Text Domain: wdf
Author: (Cole) Incsub
Author URI: http://premium.wpmudev.org/
WDP ID: 259

Copyright 2009-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
/*
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );
	function wdp_un_check() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) )
			echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	}
}
 */
/* --------------------------------------------------------------------- */


define ('WDF_PLUGIN_SELF_DIRNAME', basename(dirname(__FILE__)), true);

//Setup proper paths/URLs and load text domains
if (is_multisite() && defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'mu-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WPMU_PLUGIN_DIR, true);
	define ('WDF_PLUGIN_URL', WPMU_PLUGIN_URL, true);
	$textdomain_handler = 'load_muplugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'subfolder-plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	define ('WDF_PLUGIN_URL', WP_PLUGIN_URL . '/' . WDF_PLUGIN_SELF_DIRNAME, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else if (defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/' . basename(__FILE__))) {
	define ('WDF_PLUGIN_LOCATION', 'plugins', true);
	define ('WDF_PLUGIN_BASE_DIR', WP_PLUGIN_DIR, true);
	define ('WDF_PLUGIN_URL', WP_PLUGIN_URL, true);
	$textdomain_handler = 'load_plugin_textdomain';
} else {
	// No textdomain is loaded because we can't determine the plugin location.
	// No point in trying to add textdomain to string and/or localizing it.
	wp_die(__('There was an issue determining where the Donations plugin is installed. Please reinstall.'));
}
$textdomain_handler('wdf', false, WDF_PLUGIN_SELF_DIRNAME . '/languages/');

class WDF {
	function WDF() {
		$this->_vars();
		$this->_construct();
	}
	function _vars() {
		$this->version = '1.0.0';
		$this->defaults = array(
			'currency' => 'USD',
			'slug' => 'fundraisers',
			'first_time' => 1,
			'curr_symbol_position' => 1,
			'curr_decimal' => 1,
			'default_email' => 'Thank You %FIRSTNAME% %LASTNAME%,
Your donation of %DONATIONTOTAL% has been recieved and is greatly appreciated.

Thanks for your support,

Cheers from	'.get_bloginfo('name')
);
		
		// Setup Additional Data Structure
		require_once(WDF_PLUGIN_BASE_DIR . '/lib/wdf_data.php');
	}
	function _construct() {
		
		//load sitewide features if Network Installation
		if (is_multisite()) {
		  //require_once(WDF_PLUGIN_BASE_DIR . '/lib/class.wdf_admin_ms.php');
		  //new WDF_MS();
		}
		
		$settings = maybe_unserialize(get_option('wdf_settings'));
		if(!is_array($settings) ) {
			update_option('wdf_settings',$this->defaults);
			$settings = $this->defaults;
		}
		
		if($settings['first_time'] === '1') {
			add_action('network_admin_notices', 'wdp_un_check', 5 );
		}

		add_action('init', array(&$this,'_init'),1);
		add_action('init', array(&$this, 'flush_rewrite'), 999 );
		add_action('wp_insert_post', array(&$this,'wp_insert_post') );
		
		// Include Widgets
		add_action('widgets_init', array(&$this,'register_widgets') );
		add_action('wp_ajax_nopriv_wdf_process_ipn', array(&$this, 'process_ipn'));
		// Load styles and scripts to be used across is_admin and !is_admin
		wp_register_style( 'jquery-ui-base', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/themes/base/jquery-ui.css', null, '1.8.16', 'screen' );
		
		if(is_admin()) {
			
			
			// Add Admin Only Actions
			if($settings['first_time'] == '1')
				add_action( 'admin_init', array(&$this,'tutorial') );
				
			add_action( 'add_meta_boxes_funder', array(&$this,'add_meta_boxes') );
			add_action( 'add_meta_boxes_donation', array(&$this,'add_meta_boxes') );
			add_action( 'admin_menu', array(&$this,'admin_menu') );
			add_action( 'admin_enqueue_scripts', array(&$this,'admin_enqueue_scripts') );
			add_action( 'admin_enqueue_styles', array(&$this,'admin_enqueue_styles') );
			add_action( 'manage_funder_posts_custom_column', array(&$this,'column_display') );
			add_action( 'manage_donation_posts_custom_column', array(&$this,'column_display') );
			add_action( 'media_buttons', array(&$this,'media_buttons'), 30 );
			add_action( 'media_upload_fundraising', array(&$this,'media_fundraising'));
			add_action( 'media_upload_donate_button', array(&$this,'media_donate_button'));
			// Add Admin Only Filters		
			add_filter( 'manage_edit-funder_columns', array(&$this,'edit_columns') );
			add_filter( 'manage_edit-donation_columns', array(&$this,'edit_columns') );
			add_filter( 'media_upload_tabs', array(&$this,'media_upload_tabs') );
						
			//Register Styles and Scripts For The Admin Area
			wp_register_script( 'wdf-post', WDF_PLUGIN_URL . '/js/wdf-post.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-edit', WDF_PLUGIN_URL . '/js/wdf-edit.js', array('jquery'), $this->version, false );
			wp_register_script( 'wdf-media', WDF_PLUGIN_URL . '/js/wdf-media.js', array('jquery'), $this->version, true );
			wp_register_style( 'wdf-admin', WDF_PLUGIN_URL . '/css/wdf-admin.css', null, $this->version, null );
			
		} else {
			
			
			//Not the admin area so lets load up our front-end actions and filters
			wp_register_style('wdf-style-wdf_basic', WDF_PLUGIN_URL . '/styles/wdf-basic.css',null,$this->version);
			wp_register_style('wdf-style-wdf_minimal', WDF_PLUGIN_URL . '/styles/wdf-minimal.css',null,$this->version);
			wp_register_style('wdf-style-wdf_dark', WDF_PLUGIN_URL . '/styles/wdf-dark.css',null,$this->version);
			wp_register_style('wdf-style-wdf_note', WDF_PLUGIN_URL . '/styles/wdf-note.css',null,$this->version);
			wp_register_style('wdf-thickbox', WDF_PLUGIN_URL . '/css/wdf-thickbox.css',null,$this->version);
			wp_register_script('wdf-base', WDF_PLUGIN_URL . '/js/wdf-base.js', array('jquery'), $this->version, false );
			
			add_action( 'template_redirect', array(&$this,'template_redirect'));
			add_action( 'wp', array(&$this, 'handle_donations'));
			if($settings['inject_menu'] == 'yes') {
				add_filter( 'wp_list_pages', array(&$this, 'filter_nav_menu'), 10, 2 );
			}
		}
	}
	function register_widgets() {
		
		if(!class_exists('WDF_Simple_Donation')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.simple_donation.php');
			register_widget('WDF_Simple_Donation');
		}
		if(!class_exists('WDF_Recent_Fundraisers')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.recent_fundraisers.php');
			register_widget('WDF_Recent_Fundraisers');
		}
		if(!class_exists('WDF_Featured_Fundraisers')) {
			require_once(WDF_PLUGIN_BASE_DIR.'/lib/widgets/widget.featured_fundraisers.php');
			register_widget('WDF_Featured_Fundraisers');
		}
	}
	function tutorial() {
		
		require_once( WDF_PLUGIN_BASE_DIR . '/lib/external/pointers_tutorial.php' );
		
		$tutorial = new Pointer_Tutorial('wdf_tutorial', true, false);
		
		if(isset($_POST['wdf_restart_tutorial']))
			$tutorial->restart();
		
		$tutorial->set_textdomain = 'wdf';
		
		$tutorial->add_style('');
		
		$tutorial->set_capability = 'manage_options';
		
		$tutorial->add_step(admin_url('admin.php?page=wdf'), 'funder_page_wdf', '#icon-wdf-admin', __('Getting Started Is Easy', 'wdf'), array(
				'content'  => '<p>' . esc_js( __('Follow these tutorial steps to get your Fundraising project up and running quickly.', 'wdf') ) . '</p>',
				'position' => array( 'edge' => 'top', 'align' => 'left' ),
			));
		$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_currency', __('Choose your currency', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Choose your preferred currency for your incoming donations.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
		));
		if(!get_option('permalink_structure')) {
			$tutorial->add_step(admin_url('options-permalink.php'), 'options-permalink.php', '#permalink_structure', __('Turn On Permalinks', 'wdf'), array(
				'content'  => '<p>' . esc_js( __('Permalinks must been enabled and configured before your donation page can be seen publicly.', 'wdf') ) . '</p>',
				'position' => array( 'edge' => 'top', 'align' => 'left' ),
			));
		}
		$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_slug', __('Choose your permalink slug', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Choose a base url for all your donations.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'top', 'align' => 'left' ),
		));
		$tutorial->add_step(admin_url('edit.php?post_type=funder&page=wdf_settings'), 'funder_page_wdf_settings', '#wdf_settings_paypal_email', __('Insert Your PayPal Email', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Insert a personal or business PayPal email address. ', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
		));
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#titlediv', __('Give Your Fundraiser A Title', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Enter a title that best describes your fundraiser.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'top', 'align' => 'left' ), 'post_type' => 'funder',
		));
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_goals', __('Set A Goal?', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('If you set a goal for your fundraiser your sites visitors will be able to see how close you are to your goal.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'bottom', 'align' => 'bottom' ), 'post_type' => 'funder',
		));
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_levels_table', __('Recommend Donation Levels', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('You can recommend donation levels to your visitors, provide a title, short description, and dollar amount for each level you create.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'bottom', 'align' => 'right' ), 'post_type' => 'funder',
		));	
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_messages', __('Create Thank You Messages and Emails', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Send the user back to a specific url, any post or page ID, or enter a custom thank you message customizable with shortcodes.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'bottom', 'align' => 'right' ), 'post_type' => 'funder',
		));	
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#wdf_style', __('Choose A Style', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Choose a style that best fits your site, or apply no styles and use your own custom css.', 'wdf') ) . '</p>',
			'position' => array( 'edge' => 'right', 'align' => 'left' ), 'post_type' => 'funder',
		));
		$tutorial->add_step(admin_url('post-new.php?post_type=funder'), 'post-new.php', '#submitdiv', __('Publish or Save As Draft', 'wdf'), array(
			'content'  => '<p>' . esc_js( __('Publish your fundraiser, or save it as a draft.  Now start fundraising!  You can use the fundraiser url or insert the fundraising shortcodes directly into any page or post.', 'wdf')) . '</p>',
			'position' => array( 'edge' => 'right', 'align' => 'left' ), 'post_type' => 'funder',
		));
		$tutorial->initialize();
	}
	
	function _init() {
		$settings = get_option('wdf_settings');
		//Funder Custom Post Type Arguments
		$funder_args = array(
			'labels' => array(
				'name' => __('Fundraisers','wdf'),
				'singular_name' => __('Fundraiser','wdf'),
				'add_new' => __('New Fundraiser'),
				'add_new_item' => __('Add New Fundraiser','wdf'),
				'edit_item' => __('Edit Fundraiser','wdf'),
				'new_item' => __('New Fundraiser','wdf'),
				'all_items' => __('All Fundraisers','wdf'),
				'view_item' => __('View Fundraiser','wdf'),
				'search_items' => __('Search Fundraisers','wdf'),
				'not_found' =>  __('No Fundraisers found','wdf'),
				'not_found_in_trash' => __('No Fundraisers found in Trash','wdf'), 
				'parent_item_colon' => '',
				'menu_name' => 'Fundraising'
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true, 
			'show_in_menu'       => true, 
			'query_var'          => true,
			'rewrite'            => array(
				'slug' => ($settings['slug'] ? $settings['slug'] : 'fundraisers'),
				'with_front' => false,
				'feeds'      => false
			),
			'capability_type'    => 'post',
            'has_archive'        => true,
            'taxonomies'         => array( 'category', 'post_tag' ), 
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => WDF_PLUGIN_URL . '/img/sm_ico.png',
			'supports'           => array('title','thumbnail','editor','excerpt')
		);
		//Donation Custom Post Type arguments
		$donation_args = array(
			'labels' => array(
				'name' => __('Donations','wdf'),
				'singular_name' => __('Donation','wdf'),
				'add_new' => __('New Donation'),
				'add_new_item' => __('Add New Donation','wdf'),
				'edit_item' => __('Edit Donation','wdf'),
				'new_item' => __('New Donation','wdf'),
				'all_items' => __('All Donations','wdf'),
				'view_item' => __('View Donations','wdf'),
				'search_items' => __('Search Donations','wdf'),
				'not_found' =>  __('No Donations found','wdf'),
				'not_found_in_trash' => __('No Donations found in Trash','wdf'), 
				'parent_item_colon' => '',
				'menu_name' => 'Donations'
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true, 
			'show_in_menu'       => false, 
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'post',
			'has_archive'        => false, 
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array('')
		);
		
		//Register Post Type
		register_post_type( 'funder', $funder_args );
		register_post_type( 'donation', $donation_args );
		
		$complete_args = array(
			'label'       => __('Complete', 'wdf'),
			'label_count' => array( __('Complete <span class="count">(%s)</span>', 'wdf'), __('Complete <span class="count">(%s)</span>', 'wdf') ),
			'post_type'   => 'donation',
			'public'      => false,
			'show_in_admin_status_list' => true,
			'show_in_admin_all_list' => true
		);
		register_post_status('donation_complete', $complete_args);
	}
	
	function flush_rewrite() {
		global $wp_rewrite;
		$wp_rewrite->flush_rules();
	}
	
	function filter_nav_menu($list, $args = array()) {
    	$settings = get_option('wdf_settings');
		$list = $list . '<li class="page_item'. ((get_query_var('post_type') == 'funder') ? ' current_page_item' : '') . '"><a href="' . home_url($settings['slug'].'/') . '" title="' . __('Fundraisers', 'mp') . '">' . __('Fundraisers', 'mp') . '</a></li>';
		return $list;
	}
	
	function views_list($views) {
		global $wp_query;
		unset($views['publish']);
		unset($views['all']);
		$avail_post_stati = wp_edit_posts_query();
		$num_posts = wp_count_posts( 'donation', 'readable' );
		$argvs = array('post_type' => 'donation');
		foreach ( get_post_stati($argvs, 'objects') as $status ) {
			$class = '';
			$status_name = $status->name;
			if ( !in_array( $status_name, $avail_post_stati ) )
				continue;
			if ( empty( $num_posts->$status_name ) )
				continue;
			if ( isset($_GET['post_status']) && $status_name == $_GET['post_status'] )
				$class = ' class="current"';
			$views[$status_name] = "<li><a href='edit.php?post_type=donation&amp;post_status=$status_name'$class>" . sprintf( _n( $status->label_count[0], $status->label_count[1], $num_posts->$status_name ), number_format_i18n( $num_posts->$status_name ) ) . '</a>';
		}
		
		return $views;
    }
	
	function template_redirect() {
		global $wp_query;
		if ($wp_query->is_single && $wp_query->query_vars['post_type'] == 'funder') {
			add_filter('the_content', array(&$this,'donation_content'), 99 );
		}
	}
	
	function front_scripts($id = null) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script('wdf-base');
		wp_enqueue_script('thickbox');
		wp_enqueue_style('jquery-ui-base');
		wp_enqueue_style('wdf-thickbox');
		if(!empty($id) && $style = get_post_meta($id,'wdf_style',true))
			wp_enqueue_style('wdf-style-'.$style);
		
	}
	
	function donation_content($content) {
		if ( !in_the_loop() )
		  return $content;

		global $post;
		$atts = array(
			'context' => ''
		);
		if(is_single())
			$content .= show_fundraiser_page(false, $post->ID);
		else
			return $content;
		
		return $content;
	}
	
	function handle_donations() {
		
		
		
		if(isset($_POST['funder_id']) && isset($_POST['send_nonce'])) {
				
				require_once(WDF_PLUGIN_BASE_DIR . '/gateways/paypal-standard.php');
				
				$funder = get_post($_POST['funder_id']);
				if(!$funder)
					return;
				if (!wp_verify_nonce($_POST['send_nonce'], 'send_nonce_'.$funder->ID) )
						return;	
				global $wdf_send_obj;
				$wdf_send_obj = $funder;
				$wdf_send_obj->send_amount = intval($_POST['wdf_send_amount']);
				$wdf_send_obj->recurring = $_POST['recurring'];
				
				if(isset($_POST['wdf_send_donation']) && intval($wdf_send_obj->send_amount) != 0) {
					global $wdf_gateway;
					$wdf_gateway->process();
				}
		} else if($_POST['txn_type'] == 'web_accept' || $_POST['txn_type'] == 'subscr_signup' ) {
			
			require_once(WDF_PLUGIN_BASE_DIR . '/gateways/paypal-standard.php');
			global $wdf_gateway;
			$wdf_gateway->process_ipn();
			
		}
	}
	function process_ipn() {
		require_once(WDF_PLUGIN_BASE_DIR . '/gateways/paypal-standard.php');
		global $wdf_gateway;
		$wdf_gateway->process_ipn();
	}
	function create_thank_you($funder_id,$trans) {
		
		$msg = get_post_meta($funder_id,'wdf_thanks_custom',true);
				
		$search = array('%DONATIONTOTAL%','%FIRSTNAME%','%LASTNAME%');
		$replace = array($this->format_currency('',$trans['gross']),$trans['first_name'], $trans['last_name']);

    	//replace
    	$msg = str_replace($search, $replace, $msg);
		$style = get_post_meta($funder_id,'wdf_style',true);
		
		$this->front_scripts($funder_id);
		$func = 'echo "<div id=\'wdf_thank_you\' class=\''.$style.'\'><div class=\'fade\'></div><div class=\'wdf_ty_content\'><a class=\'close\'>'.__('Close','wdf').'</a><h1>'.apply_filters('wdf_thankyou_title',__('Thank You!','wdf')).'</h1><p>' . $msg . '</p></div></div><script type=\"text/javascript\">jQuery(document).ready( function($) { $(\"#wdf_thank_you .close\").click( function() { $(\"#wdf_thank_you\").fadeOut(500); }); });</script>";';
		
		add_action('wp_footer',  create_function('',$func),50);
		
		$send_email = get_post_meta($funder_id,'wdf_send_email',true);
		if($send_email) {
			
			//remove any other filters
			remove_all_filters( 'wp_mail_from' );
			remove_all_filters( 'wp_mail_from_name' );
	
			//add our own filters
			//add_filter( 'wp_mail_from_name', create_function('', 'return get_bloginfo("name");') );
			//add_filter( 'wp_mail_from', create_function('', 'return get_option("admin_email")') );
			$msg = get_post_meta($funder_id,'wdf_email_msg', true);
			$search = array('%DONATIONTOTAL%','%FIRSTNAME%','%LASTNAME%');
			$replace = array($this->format_currency('',$trans['gross']),$trans['first_name'],$trans['last_name']);
			
			$subject = get_post_meta($funder_id,'wdf_email_subject',true);
			$msg = str_replace($search, $replace, $msg);
			
			if($subject && $msg && $trans['payer_email']) {
				wp_mail($trans['payer_email'],$subject,$msg);
			}
		}
	}	
	function update_donation($post_title = false,$funder_id = false,$status = false,$transaction = false) {
		if( !$post_title || !$funder_id || !$status || !$transaction )
			return;
		
		global $wpdb;
		//Check to see if we have created this donation yet
		$search = false;
		$search = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_title = '" . $post_title . "'" );
		$donation = array();
		if(!empty($search) &&  $search != false) {
			$donation['ID'] = $search;
		}
		$donation['post_title'] = $post_title;
		$donation['post_name'] = $post_title;
		$donation['post_status'] = $status;
		$donation['post_parent'] = $funder_id;
		$donation['post_type'] = 'donation';
		$id = wp_insert_post($donation);
		foreach($transaction as $k => $v) {
			if(!is_array($v))
				$transaction[$k] = esc_attr($v);
			else
				$transaction[$k] = $v;
				
		}
		update_post_meta($id, 'wdf_transaction', $transaction);
		update_post_meta($id,'wdf_native', '1');
	}
	
	function add_meta_boxes() {
		global $post, $wp_meta_boxes, $typenow;
		
		if($typenow == 'funder') {
			//If this fundraiser has raised funds then show a progress meta-box
			if(count($this->get_donation_list($post->ID)) > 0)
				add_meta_box( 'wdf_progress', __('Fundraiser Progress','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
				
			add_meta_box( 'wdf_options', __('Fundraiser Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'side', 'high');
			add_meta_box( 'wdf_goals', __('Set Your Fundraising Goals','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			add_meta_box( 'wdf_levels', __('Create Suggested Donation Levels','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			
			add_meta_box( 'wdf_messages', __('Thank You Message Settings','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
			if($donations = $this->get_donation_list($post->ID))
				add_meta_box( 'wdf_activity', __('Donation Activity','wdf'), array(&$this,'meta_box_display'), 'funder', 'normal', 'high');
		} elseif($typenow == 'donation') {
			add_meta_box( 'wdf_donation_info', __('Donation Information','wdf'), array(&$this,'meta_box_display'), 'donation', 'normal', 'high');
			
		}
	}
	function meta_box_display($post,$data) {
		//Setup tooltips for all metaboxes
		if (!class_exists('WpmuDev_HelpTooltips')) require_once WDF_PLUGIN_BASE_DIR . '/lib/external/class_wd_help_tooltips.php';
			$tips = new WpmuDev_HelpTooltips();
			$tips->set_icon_url(WDF_PLUGIN_URL.'/img/information.png');
		
		// Setup $meta for all metaboxes
		$meta = get_post_custom($post->ID);
		
		//pull out the meta_box id and pass it through a switch instead of using individual functions
		switch($data['id']) {
			
			///////////////////////////
			// DONATION INFO METABOX //
			///////////////////////////
			case 'wdf_donation_info' : 
				
				$trans = $this->get_transaction($post->ID);
				
				
				if(!$meta['wdf_native'][0] == '1') : ?>
					<input type="hidden" name="post_title" value="Manual Payment" />
					<?php $donations = get_posts(array('post_type' => 'funder', 'numberposts' => -1, 'post_status' => 'publish')); ?>
					<p>
						<?php if(!$donations) : ?>
							<label><?php echo __('You have not made any fundraiser yet.','wdf') ?></label>
						<?php else : ?>
							<label><?php echo __('Choose The Fundraiser','wdf') ?></label>
							<select name="post_parent">
							<?php foreach($donations as $donation) : ?>
								<option <?php selected($post->post_parent,$donation->ID); ?> value="<?php echo $donation->ID ?>"><?php echo $donation->post_title; ?></option>
							<?php endforeach; ?>
							</select>
						<?php endif; ?>
					</p>
					<p><label>First & Last Name:<input type="text" name="wdf[transaction][name]" value="<?php echo $trans['first_name'] . ' ' . $trans['last_name']; ?>" /></label></p>
					<p><label>Email Address:<input type="text" name="wdf[transaction][payer_email]" value="<?php echo $trans['payer_email']; ?>" /></label></p>
					<p><label>Donation Amount:<input type="text" name="wdf[transaction][gross]" value="<?php echo $trans['gross']; ?>" /></label></p>
					<p><label>Payment Source:
						<select name="wdf[transaction][gateway]">
							<option value="paypal" <?php selected($trans['gateway'],'paypal'); ?>><?php echo $this->get_gateway_label('paypal'); ?></option>
							<option value="check" <?php selected($trans['gateway'],'check'); ?>><?php echo $this->get_gateway_label('check'); ?></option>
							<option value="cash" <?php selected($trans['gateway'],'cash'); ?>><?php echo $this->get_gateway_label('cash'); ?></option>
						</select>
						</label>
					</p>
				<?php else : ?>
					<?php $parent = get_post($post->post_parent); ?>
					<?php if($parent) : ?>
						<h3>Fundraiser:</h3><p><a href="<?php echo get_edit_post_link($parent->ID); ?>"><?php echo $parent->post_title; ?></a></p>
					<?php else : ?> 
						<?php $donations = get_posts(array('post_type' => 'funder', 'numberposts' => -1, 'post_status' => 'publish')); ?>
						<p>
							<?php if(!$donations) : ?>
								<label><?php echo __('You have not made any fundraiser yet.','wdf') ?></label>
							<?php else : ?>
								<label><?php echo __('Not attached to any fundraiser please choose one','wdf') ?></label>
								<select name="post_parent">
								<?php foreach($donations as $donation) : ?>
									<option value="<?php echo $donation->ID ?>"><?php echo $donation->post_title; ?></option>
								<?php endforeach; ?>
								</select>
							<?php endif; ?>
						</p>
					<?php endif; ?>
						<?php $trans = $this->get_transaction(); ?>
						<h3>From:</h3><p><label><strong><?php echo __('Name:','wdf'); ?> </strong></label><?php echo $trans['first_name'] . ' ' . $trans['last_name']; ?></p><p><label><strong><?php echo __('Email:','wdf'); ?> </strong></label><?php echo $trans['payer_email']; ?></p>
						<h3>Amount Donated:</h3>
						<?php if($trans['recurring'] == 1) :?>
							<p><?php echo $this->format_currency('',$trans['gross']); ?> every <?php echo $trans['cycle']; ?></p>
						<?php else: ?>
							<p><?php echo $this->format_currency('',$trans['gross']); ?></p>
						<?php endif; ?>
						<h3>Payment Source:</h3><p><?php echo $this->get_gateway_label($trans['gateway']); ?></p>
						<h3>Transaction ID:</h3><p><?php echo $post->post_title; ?></p>
				<?php endif; ?>
			<?php break;

			/////////////////////
			// FUNDER PROGRESS //
			/////////////////////
			case 'wdf_progress' : 

				if($this->has_goal($post->ID)) {
					echo $this->prepare_progress_bar($post->ID,null,null,'admin_metabox',true);
				} else {
					echo  '<label>Amount Raised So Far</label><br /><span class="wdf_bignum">' . $this->format_currency('',$this->get_amount_raised($post->ID)) . '</span>';
				} 
				break;
			
			////////////////////////////
			// FUNDER OPTIONS METABOX //
			////////////////////////////
			case 'wdf_options' : 
				global $pagenow;
				$settings = get_option('wdf_settings');
			?>
				<div id="wdf_style">
					<p>
						<label><?php echo __('Choose a display style','wdf'); ?>
						<select name="wdf[style]">
							<option <?php selected($meta['wdf_style'][0],'wdf_basic'); ?> value="wdf_basic"><?php echo __('Basic','wdf'); ?></option>
							<option <?php selected($meta['wdf_style'][0],'wdf_minimal'); ?> value="wdf_minimal"><?php echo __('Minimal','wdf'); ?></option>
							<option <?php selected($meta['wdf_style'][0],'wdf_dark'); ?> value="wdf_dark"><?php echo __('Dark','wdf'); ?></option>
							<option <?php selected($meta['wdf_style'][0],'wdf_note'); ?> value="wdf_note"><?php echo __('Note','wdf'); ?></option>
							<option <?php selected($meta['wdf_style'][0],'wdf_custom'); ?> value="custom"><?php echo __('None (Custom CSS)','wdf'); ?></option>
						</select></label>
					</p>
				</div>
				<p><label><?php echo __('Allow Recurring Donations','wdf') ?><br />
						<select name="wdf[recurring]" rel="wdf_recurring" class="wdf_toggle">
							<option value="yes" <?php selected($meta['wdf_recurring'][0],'yes'); ?>>Yes</option>
							<option value="no" <?php selected($meta['wdf_recurring'][0],'no'); ?>>No</option>
						</select>
					</label>
				</p>
				<?php /*?><p>
					<label><?php echo __('Override Default PayPal Email Address','wdf'); ?><br />
						<input type="text" class="widefat" name="wdf[paypal_email_override]" value="<?php echo $meta['wdf_paypal_email_override'][0]; ?>" />
					</label>
				</p><?php */?>
				<?php $cycles = maybe_unserialize($meta['wdf_recurring_cycle'][0]); ?>
				<?php /*?><p rel="wdf_recurring" <?php echo ($meta['wdf_recurring'][0] == 'yes'? '' : 'style="display:none;"') ?>>
					<input type="hidden" name="wdf[recurring_cycle][d]" value="" />
					<input type="hidden" name="wdf[recurring_cycle][w]" value="" />
					<input type="hidden" name="wdf[recurring_cycle][m]" value="" />
					<input type="hidden" name="wdf[recurring_cycle][y]" value="" />
					<label><input type="checkbox" name="wdf[recurring_cycle][d]" value="1" <?php echo checked($cycles['d'],'1'); ?> />Daily</label><br />
					<label><input type="checkbox" name="wdf[recurring_cycle][w]" value="1" <?php echo checked($cycles['w'],'1'); ?> />Weekley</label><br />
					<label><input type="checkbox" name="wdf[recurring_cycle][m]" value="1" <?php echo checked($cycles['m'],'1'); ?> />Monthly</label><br />
					<label><input type="checkbox" name="wdf[recurring_cycle][y]" value="1" <?php echo checked($cycles['y'],'1'); ?> />Yearly</label>
				</p><?php */?>
			<?php break;
			
			////////////////////
			// GOALS METABOX //
			////////////////////
			case 'wdf_goals' : ?>
				<?php $settings = get_option('wdf_settings'); ?>
				<p id="wdf_type">
					<label><?php echo __('Display a public goal for this fundraiser?','wdf'); ?>
					<select class="wdf_toggle" rel="wdf_has_goal" name="wdf[has_goal]">
						<option <?php selected($meta['wdf_has_goal'][0],'0'); ?> value="0">No</option>
						<option <?php selected($meta['wdf_has_goal'][0],'1'); ?> value="1">Yes</option>
					</select></label>
                	
				</p>
				<div rel="wdf_has_goal" <?php echo ($meta['wdf_has_goal'][0] == '1' ? '' : 'style="display:none"') ?>>
				<input type="hidden" name="wdf[show_progress]" value="0" />
				<p><label><input type="checkbox" name="wdf[show_progress]" value="1" <?php checked($meta['wdf_show_progress'][0],'1'); ?> /> <?php echo __('Show Progress Bar','wdf') ?></label></p>
				<table class="widefat">
					<thead>
						<tr>
							<th class="wdf_goal_start_date"><?php echo __('Start Date','wdf') ?></th>
							<th class="wdf_goal_end_date"><?php echo __('End Date','wdf') ?></th>
							<th class="wdf_goal_amount" align="right"><?php echo __('Goal Amount','wdf') ?></th>
						</tr>
					</thead>
					<tbody>
							<tr>
								<td class="wdf_goal_start_date">
									<input style="background-image: url(<?php echo admin_url('images/date-button.gif'); ?>);" type="text" name="wdf[goal_start]" class="wdf_biginput" value="<?php echo $meta['wdf_goal_start'][0]; ?>" />
								</td>
								<td class="wdf_goal_end_date">
									<input style="background-image: url(<?php echo admin_url('images/date-button.gif'); ?>);" type="text" name="wdf[goal_end]" class="wdf_biginput" value="<?php echo $meta['wdf_goal_end'][0]; ?>" />
								</td>
								<td class="wdf_goal_amount">
									<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
									<input type="text" name="wdf[goal_amount]" class="wdf_input_switch active wdf_biginput wdf_bignum" value="<?php echo $this->filter_price($this->format_currency(' ',$meta['wdf_goal_amount'][0])); ?>" />
									<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
								</td>
							</tr>
						<?php //endif; ?>
					</tbody>
				</table>
				</div>
				
			<?php break;
		
			////////////////////
			// LEVELS METABOX //
			////////////////////
			case 'wdf_levels' : ?>
				<?php $settings = get_option('wdf_settings'); ?>
				<table id="wdf_levels_table" class="widefat">
                	<thead>
                    	<tr>
							<th class="wdf_level_amount"><?php echo __('Choose Amount','wdf'); ?></th>
                        	<th class="wdf_level_title"><?php echo __('Optional Title','wdf'); ?></th>
                            <th class="wdf_level_description"><?php echo __('Optional Description','wdf'); ?></th>
							<th class="wdf_level_reward" align="right"><?php echo __('Add A Reward','wdf'); ?></th>
							<th class="delete" align="right"></th>
                        </tr>
                    </thead>
                    <tbody>
						<?php 
						if(isset($meta['wdf_levels']) && is_array($meta['wdf_levels'])) :
						$level_count = count($meta['wdf_levels']);
						$i = 1;
						//var_export($meta);
						foreach($meta['wdf_levels'] as $level) :
							$level = maybe_unserialize($level);
							foreach($level as $index => $data) : ?>
								<tr class="wdf_level <?php echo ($level_count == $i ? 'last' : ''); ?>">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" name="wdf[levels][<?php echo $index ?>][amount]" value="<?php echo $this->filter_price($this->format_currency(' ',$data['amount'])); ?>" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?></td>
									<td class="wdf_level_title"><input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" name="wdf[levels][<?php echo $index ?>][title]" value="<?php echo $data['title'] ?>" /></td>
									<td class="wdf_level_description"><textarea class="wdf_input_switch active " name="wdf[levels][<?php echo $index ?>][description]"><?php echo $data['description'] ?></textarea></td>
									<td class="wdf_level_reward"><input class="wdf_check_switch" type="checkbox" name="wdf[levels][<?php echo $index ?>][reward]" value="1" <?php echo checked($data['reward'],'1'); ?> /></td>
									<td class="delete"><a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span>Delete</a></td>
								</tr>
								<tr class="wdf_reward_options">
									<td colspan="5">
										<div class="wdf_reward_toggle" <?php echo ($data['reward'] == 1 ? '' : 'style="display:none"'); ?>>
											<p><label><?php echo __('Describe Your Reward','wdf') ?><input type="text" name="wdf[levels][<?php echo $index ?>][reward_description]" value="<?php echo $data['reward_description'] ?>" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
							<?php $i++; endforeach; endforeach; ?>
							<?php else : ?>
								<tr class="wdf_level last">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input class="wdf_input_switch wdf_biginput wdf_bignum" type="text" name="wdf[levels][0][amount]" value="" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
									</td>
									<td class="wdf_level_title"><input class="wdf_input_switch wdf_biginput wdf_bignum" type="text" name="wdf[levels][0][title]" value="" /></td>
									<td class="wdf_level_description"><textarea class="wdf_input_switch" name="wdf[levels][0][description]"><?php //echo __('Add a description for this level','wdf'); ?></textarea></td>
									<td class="wdf_level_reward"><input class="wdf_check_switch" type="checkbox" name="wdf[levels][0][reward]" value="1" /></td>
									<td class="delete"><a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span>Delete</a></td>
								</tr>
								<tr class="wdf_reward_options">
									<td colspan="5">
										<div class="wdf_reward_toggle" style="display:none">
											<p><label><?php echo __('Describe Your Reward','wdf') ?><input type="text" name="wdf[levels][0][reward_description]" value="<?php echo $data['reward_description'] ?>" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
								<tr rel="wdf_level_template" style="display:none">
									<td class="wdf_level_amount">
										<?php echo ($settings['curr_symbol_position'] == 1 || $settings['curr_symbol_position'] == 2 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
										<input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" rel="wdf[levels][][amount]" value="" />
										<?php echo ($settings['curr_symbol_position'] == 3 || $settings['curr_symbol_position'] == 4 ? '<span class="wdf_bignum wdf_disabled">'.$this->format_currency().'</span>' : ''); ?>
									</td>
									<td class="wdf_level_title"><input class="wdf_input_switch active wdf_biginput wdf_bignum" type="text" rel="wdf[levels][][title]" value="" /></td>
									<td class="wdf_level_description"><textarea class="wdf_input_switch active" rel="wdf[levels][][description]"></textarea></td>
									<td class="wdf_level_reward"><input class="wdf_check_switch" type="checkbox" rel="wdf[levels][][reward]" value="1" /></td>
									<td class="delete"><a href="#"><span style="background-image: url(<?php echo admin_url('images/xit.gif'); ?>);" class="wdf_ico_del"></span>Delete</a></td>
								</tr>
								<tr rel="wdf_level_template" class="wdf_reward_options" style="display:none">
									<td colspan="5">
										<div class="wdf_reward_toggle" style="display:none">
											<p><label><?php echo __('Describe Your Reward','wdf') ?><input type="text" rel="wdf[levels][][reward_description]" value="" class="widefat" /></label></p>
										</div>
									</td>
								</tr>
							<tr><td colspan="5" align="right"><a href="#" id="wdf_add_level">Add A Level</a></td></tr>
							
						
                    </tbody>
                </table>
			<?php break;
			
			//////////////////////
			// ACTIVITY METABOX //
			//////////////////////
			case 'wdf_activity' : ?>
				<?php $donations = $this->get_donation_list($post->ID); ?>
				<table class="widefat">
						<thead>
							<tr>
								<th>From:</th>
								<th>Date:</th>
								<th>Transaction Type:</th>
								<th>Amount:</th>
								<th class="wdf_actvity_edit"><br /></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($donations as $donation) : ?>
							<?php $meta = get_post_custom($donation->ID); ?>
							<?php $trans = maybe_unserialize($meta['wdf_transaction'][0]); ?>
							<tr class="wdf_actvity_level">
								<td><label><?php echo $trans['first_name'].' '.$trans['last_name']; ?></label><br /><a href="mailto:<?php echo $trans['payer_email']; ?>"><?php echo $trans['payer_email']; ?></a></</td>
								<td><?php echo get_post_modified_time('F d Y', null, $donation->ID) ?></td>
								<td><?php echo $this->get_gateway_label($trans['gateway']); ?></td>
								<td><?php echo $this->format_currency('',$trans['gross']); ?></td>
								<td><a class="hidden" href="<?php echo get_edit_post_link($donation->ID); ?>">View Details</a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
            <?php break;
			
			//////////////////////
			// MESSAGES METABOX //
			//////////////////////
			case 'wdf_messages' : 
				$settings = get_option('wdf_settings');
			?>
            <p>
				<label id="wdf_thanks_type"><?php echo __('Thank You Message','wdf'); ?>
				<select class="wdf_toggle" rel="wdf_thanks_type" name="wdf[thanks_type]">
					<option <?php selected($meta['wdf_thanks_type'][0],'custom'); ?> value="custom"><?php echo __('Custom Thank You Message','wdf'); ?></option>
					<option <?php selected($meta['wdf_thanks_type'][0],'post'); ?> value="post"><?php echo __('Use A Post or Page ID','wdf'); ?></option>
					<option <?php selected($meta['wdf_thanks_type'][0],'url'); ?> value="url"><?php echo __('Use A Custom URL','wdf'); ?></option>
				</select></label>
				<p <?php echo ($meta['wdf_thanks_type'][0] == 'custom' || $pagenow == 'post-new.php' ? 'style="display: block;"' : ''); ?> rel="wdf_thanks_type" class="wdf_thanks_custom">
					<label><?php echo __('Text or HTML Allowed','wdf'); ?><?php echo $tips->add_tip('Provide a custom thank you message for users.  You can use the following codes to display specific information from the donation: %DONATIONTOTAL% %FIRSTNAME% %LASTNAME%'); ?></label><br />
					<textarea id="wdf_thanks_custom" name="wdf[thanks_custom]"><?php echo $meta['wdf_thanks_custom'][0]; ?></textarea>
				</p>
				<p <?php echo ($meta['wdf_thanks_type'][0] == 'post' ? 'style="display: block;"' : 'style="display: none;"'); ?> rel="wdf_thanks_type" class="wdf_thanks_post">
					<?php do_action('wdf_error_thanks_post');?>
					<label><?php echo __('Insert A Post or Page ID','wdf'); ?><input type="text" name="wdf[thanks_post]" value="<?php echo $meta['wdf_thanks_post'][0]; ?>" /></label>
				</p>
				<p <?php echo ($meta['wdf_thanks_type'][0] == 'url' ? 'style="display: block;"' : 'style="display: none;"'); ?> rel="wdf_thanks_type" class="wdf_thanks_url">
					<label><?php echo __('Insert A Custom URL','wdf'); ?><input type="text" name="wdf[thanks_url]" value="<?php echo $meta['wdf_thanks_url'][0]; ?>" /></label>
				</p>
			</p>
			<p>
				<h3><?php echo __('Send A Confirmation Email After Donation?','wdf'); ?></h3>
				<select class="wdf_toggle" rel="wdf_send_email" name="wdf[send_email]" id="wdf_send_email">
					<option value="0" <?php echo selected($meta['wdf_send_email'][0],0); ?>>No</option>
					<option value="1" <?php echo selected($meta['wdf_send_email'][0],1); ?>>Yes</option>
				</select>
			</p>
			<div <?php echo ($meta['wdf_send_email'] == 1 ? '' : 'style="display: none;"');?> rel="wdf_send_email">
				<label><?php echo __('Create a custom email message or use the default one.','wdf'); ?></label><?php $tips->add_tip('The email will come from your Administrator email <strong>'.get_bloginfo('admin_email').'</strong>')?><br />
				<p><label><?php echo __('Email Subject','wdf'); ?></label><br />
				<input class="regular-text" type="text" name="wdf[email_subject]" value="<?php echo (isset($meta['email_subject'][0]) ? $meta['email_subject'][0] : __('Thank you for your Donation', 'wdf')); ?>" /></p>
				<p><textarea id="wdf_email_msg" name="wdf[email_msg]">
					<?php echo (isset($meta['wdf_email_msg'][0]) ? $meta['wdf_email_msg'][0] : $settings['default_email']); ?>
				</textarea></p>
			</div>
            <?php break;
		}	
	}
	function admin_menu() {		
		add_submenu_page( 'edit.php?post_type=funder', 'Getting Started', 'Getting Started', 'manage_options', 'wdf', array(&$this,'admin_display') );
		add_submenu_page( 'edit.php?post_type=funder', 'Donations', 'Donations', 'manage_options', 'wdf_donations', array(&$this,'admin_display') );		
		add_submenu_page( 'edit.php?post_type=funder', 'Donations Settings', 'Settings', 'manage_options', 'wdf_settings', array(&$this,'admin_display') );
		
		//Some quick fixes for the menu
		global $submenu, $menu;
		$submenu['edit.php?post_type=funder'][5][0] = 'Fundraisers';
		$submenu['edit.php?post_type=funder'][10][0] = 'Add New';
		$submenu['edit.php?post_type=funder'][4] = $submenu['edit.php?post_type=funder'][11];
		unset($submenu['edit.php?post_type=funder'][11]);
		$submenu['edit.php?post_type=funder'][12][2] = 'edit.php?post_type=donation';
		ksort($submenu['edit.php?post_type=funder']);		
	}
	
	function admin_display(){
		$content = '';
		if(!current_user_can('manage_options'))
			wp_die(__('You are not allowed to view this page.','wdf'));
			
		switch($_GET['page']) {
			case 'wdf_settings' : 
            	
				// Save our settings if needed then print the page.
				if(isset($_POST['wdf_settings']) && is_array($_POST['wdf_settings'])) {
					$this->save_settings($_POST['wdf_settings']);
				}
				$settings = get_option('wdf_settings');
								
				if (!class_exists('WpmuDev_HelpTooltips')) require_once WDF_PLUGIN_BASE_DIR . '/lib/external/class_wd_help_tooltips.php';
					$tips = new WpmuDev_HelpTooltips();
					$tips->set_icon_url(WDF_PLUGIN_URL.'/img/information.png');?>
			
				<div class="wrap">
					<div id="icon-wdf-admin" class="icon32"><br></div>
						<h2><?php echo __('Donations Settings','wdf') ?></h2>
						<?php do_action('wdf_msg_general');?>
						<form action="" method="post" id="wdf_settings_<?php echo $active_tab ?>">
							<input type="hidden" name="wdf_nonce" value="<?php echo wp_create_nonce('_wdf_settings_nonce');?>" />
							<?php do_action('wdf_error_wdf_nonce');	?>					
							<table class="form-table">
								<tbody>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[currency]"><?php echo __('Set Your Currency','wdf'); ?></label>
										</th>
										<td>
											<select id="wdf_settings_currency" name="wdf_settings[currency]">
												<?php
												foreach ($this->currencies as $key => $value) {
												  ?><option value="<?php echo $key; ?>"<?php selected($settings['currency'], $key); ?>><?php echo esc_attr($value[0]) . ' - ' . $this->format_currency($key); ?></option><?php
												}
												?>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php _e('Currency Symbol Position', 'wdf') ?></th>
										<td>
										<label><input value="1" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 1); ?>>
											<?php echo $this->format_currency($settings['currency']); ?>100</label><br />
											<label><input value="2" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 2); ?>>
											<?php echo $this->format_currency($settings['currency']); ?> 100</label><br />
											<label><input value="3" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 3); ?>>
											100<?php echo $this->format_currency($settings['currency']); ?></label><br />
											<label><input value="4" name="wdf_settings[curr_symbol_position]" type="radio"<?php checked($settings['curr_symbol_position'], 4); ?>>
											100 <?php echo $this->format_currency($settings['currency']); ?></label>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row"><?php _e('Show Decimal in Prices', 'wdf') ?></th>
										<td>
										<label><input value="1" name="wdf_settings[curr_decimal]" type="radio"<?php checked( ( ($settings['curr_decimal'] !== 0) ? 1 : 0 ), 1); ?>>
												<?php echo __('Yes', 'wdf') ?></label>
												<label><input value="0" name="wdf_settings[curr_decimal]" type="radio"<?php checked($settings['curr_decimal'], 0); ?>>
												<?php echo __('No', 'wdf') ?></label>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[inject_menu]"><?php echo __('Add fundraiser directory to your page list menu?','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[inject_menu]">
												<option value="no" <?php selected($settings['inject_menu'],'no') ?>>No</option>
												<option value="yes" <?php selected($settings['inject_menu'],'yes') ?>>Yes</option>
											</select>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[slug]">Donations Page Slug<?php //echo $tips->add_tip(__('Do not include any trailing slashes or special characters.', 'wdf')); ?></label>
										</th>
										<td>
											<?php if(get_option('permalink_structure')) : ?>
												<span class="special"><?php echo home_url(); ?>/</span><input id="wdf_settings_slug" type="text" name="wdf_settings[slug]" value="<?php echo esc_attr($settings['slug']); ?>" />
											<?php else : ?>
												<div class="error below-h2"><p>You Need To Setup Your Permalink Structure Before Setting Your Donations Slug</p></div>
											<?php endif; ?>
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[paypal_email]"><?php echo __('PayPal Email Address:','wdf'); ?></label>
										</th>
										<td>
											<input class="regular-text" type="text" id="wdf_settings_paypal_email" name="wdf_settings[paypal_email]" value="<?php echo esc_attr($settings['paypal_email']); ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[paypal_image_url]"><?php echo __('PayPal Checkout Header Image ','wdf'); ?></label><?php echo $tips->add_tip('PayPal allows you to use a custom header images during the purchase process.  PayPal recommends using an image from a secure https:// link, but this is not required.'); ?>
										</th>
										<td>
											<input class="regular-text" type="text" name="wdf_settings[paypal_image_url]" value="<?php echo $settings['paypal_image_url']; ?>" />
										</td>
									</tr>
									<tr valign="top">
										<th scope="row">
											<label for="wdf_settings[paypal_sb]"><?php echo __('Use PayPal Sandbox?','wdf'); ?></label>
										</th>
										<td>
											<select name="wdf_settings[paypal_sb]" id="wdf_settings_paypal_sb">
												<option value="no" <?php selected($settings['paypal_sb'],'no'); ?>>No</option>
												<option value="yes" <?php selected($settings['paypal_sb'],'yes'); ?>>Yes</option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
					<input type="submit" value="Save Changes" class="button-primary" name="save_settings" />
					</form>
						
				</div>
                <?php
				break;
			default : ?>
				<div id="wdf_dashboard" class="wrap">
					<div id="icon-wdf-admin" class="icon32"><br></div>
                	<h2><?php echo __('Getting Started Guide','wdf'); ?></h2>
					<p><?php echo __('The Fundraising plugin can help you fund your important projects.','wdf') ?></p> 
                    <div class="metabox-holder">
						<div class="postbox">
                        	<h3 class="hndle"><span>First Time Setup Guide</span></h3>
                            <div class="inside">
								<div class="updated below-h2">
									<p>Welcome to the Donations Plugin.  Follow the steps below to start taking donations.</p>
								</div>
								<ol id="wdf_steps">
									<li>Configure your general, payment and email settings.<a href="<?php echo admin_url('edit.php?post_type=funder&page=wdf_settings'); ?>" class="button wdf_goto_step">Configure Settings</a></li>
									<li>Create your first fundraiser, set a goal, and choose a display style.<a href="<?php echo admin_url('post-new.php?post_type=funder'); ?>" class="button wdf_goto_step">Create A Fundraiser</a></li>
									<li>Insert a widget to handle simple donations.<a href="<?php echo admin_url('widgets.php'); ?>" class="button wdf_goto_step">Add A Widget</a></li>
									<li>Add a fundraising shortcode to an existing post or page<code>[funraiser id=""][donate_button paypal_email="" button_type="default"]</code></li>
								</ol>
                            </div>
                        </div>
						<div class="postbox">
							<h3 class="hndle"><span><?php echo __('Need Help?','wdf'); ?></span></h3>
							<div class="inside">
								<form action="<?php echo admin_url('edit.php?post_type=funder&page=wdf'); ?>" method="post">
									<label>Restart the getting started walkthrough?</label>
									<input type="submit" name="wdf_restart_tutorial" class="button" value="Restart the Tutorial" />
								</form>
							</div>
						</div>
                    </div>
                </div><!-- #wdf_dashboard -->
                <?php
				break;
		}
	}
	function save_settings($new) {
		$die = false;
		$settings = get_option('wdf_settings');
		if(isset($_POST['wdf_nonce'])) {
			$nonce = $_POST['wdf_nonce'];
		}
		if (!wp_verify_nonce($nonce,'_wdf_settings_nonce') ) {
			$this->create_error('Security Check Failed.  Whatchu doing??', 'wdf_nonce');
			$die = true;
		}
		
		foreach($new as $k => $v) {
			if($k == 'slug') {
				$new[$k] = sanitize_title($v,__('donations','wdf'));
			} else if($k == 'paypal_email') {
				$new[$k] = is_email($v);
			} else {
				$new[$k] = esc_attr($v);
			}
		}
		
		// If no die flags have been triggered then lets merge our settings array together and save.
		if(!$die) {
			$settings = array_merge($settings,$new);
			update_option('wdf_settings',$settings);
			$this->create_msg('Settings Saved', 'general');			
		}			
	}
	
	function create_error($msg, $context) {
		$classes = 'error';
		$content = 'echo "<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_action('wdf_error_' . $context, create_function('', $content));
		$this->settings_error = true;
	}
	
	function create_msg($msg, $context) {
		$classes = 'updated below-h2';
		$content = 'echo "<div class=\"'.$classes.'\"><p>' . $msg . '</p></div>";';
		add_action('wdf_msg_' . $context, create_function('', $content));
		$this->settings_msg = true;
	}
	
	function wp_insert_post() {
		global $post;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
		if(!current_user_can('edit_post') && !isset($_POST['wdf']))
			return;
			
		if(isset($_POST['wdf']['levels']) && count($_POST['wdf']['levels']) < 2 && $_POST['wdf']['levels'][0]['amount'] == '')
			$_POST['wdf']['levels'] = '';
		
		if ( 'funder' == $_POST['post_type'] ) {
			foreach($_POST['wdf'] as $key => $value) {
					if($value != '') {
						if($key == 'goal') {
							$value = $this->filter_price($value);
						} else if($key == 'levels') {
							foreach($value as $level => $data) {
								$value[$level]['amount'] = $this->filter_price($data['amount']);
								$value[$level]['description'] = esc_textarea($data['description']);
								$value[$level]['title'] = esc_attr($data['title']);
							}
						} else if($key == 'recurring_cycle') {
							
						} else if($key == 'thanks_url') {
							$value = esc_attr($value);
						} else if($key == 'thanks_custom') {
							$value = esc_html($value);
						} else if($key == 'thanks_post') {
							$value = absint($value);
							$is_page = get_page($value);
							$is_post = get_post($value);
							if($is_page == false && $is_post == false) {
								$this->create_error('You must supply a valid post or page ID','thanks_post');
								$value = '';
							}
						} else {
							$value = esc_attr($value);
						}
						update_post_meta($post->ID,'wdf_'.$key,$value);
					} else {
						delete_post_meta($post->ID,'wdf_'.$key);
					}
			}
		} elseif ( 'donation' == $_POST['post_type'] && isset($_POST['wdf'])) {
			if(isset($_POST['wdf']['transaction'])) {
				foreach($_POST['wdf']['transaction'] as $k => $v) {
					$_POST['wdf']['transaction'][$k] = esc_attr($v);
				}
				if(isset($_POST['wdf']['transaction']['name'])) {
					$name = explode(' ',$_POST['wdf']['transaction']['name'],2);
					$_POST['wdf']['transaction']['first_name'] = $name[0];
					$_POST['wdf']['transaction']['last_name'] = $name[1];
				}
				if(isset($_POST['wdf']['transaction']['gross']))
					$_POST['wdf']['transaction']['gross'] = $this->filter_price($_POST['wdf']['transaction']['gross']);
				update_post_meta($post->ID,'wdf_transaction',$_POST['wdf']['transaction']);
			}	
		}
	}
	
	function admin_enqueue_scripts($hook) {
		global $typenow, $pagenow;

		if($typenow == 'funder' || $pagenow == 'admin.php') {
			if($typenow == 'funder' || $_GET['page'] == 'wdf' || $_GET['page'] == 'wdf_settings')
				wp_enqueue_style('wdf-admin');
			if( $hook === 'post.php' || $hook === 'post-new.php') {
				wp_enqueue_style('jquery-ui-base');
				wp_enqueue_script('jquery-ui-progressbar');
				wp_enqueue_style('wdf-admin');
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('wdf-post');
			} elseif( $hook == 'edit.php') {
				wp_enqueue_style('jquery-ui-base');
				wp_enqueue_script('jquery-ui-progressbar');
				wp_enqueue_script('wdf-edit');
			}
		} elseif( $typenow == 'donation' ) {
			wp_enqueue_style('wdf-admin');
			if($hook = 'edit.php') {
				wp_enqueue_script('wdf-edit');
			}
		} elseif( $typenow != 'funder' && $typenow != 'donation') {
			if($hook == 'post.php' || $hook == 'post-new.php') {
				//Media Button Includes For Other Post Types
				wp_enqueue_style('colorpicker');
				wp_enqueue_style('wdf-base');
				wp_enqueue_script('wdf-media');
			}
		}
		
		if($hook = 'edit.php')
			wp_localize_script('wdf-edit', 'WDF', array( 'hook' => $hook, 'typenow' => $typenow) );
	}
	
	function edit_columns($columns) {
		global $typenow;
		if($typenow == 'funder') {
			$move_title = $columns['title'];
			unset($columns['title']);
			$columns['funder_thumb'] = '';
			$columns['funder_raised'] = 'Raised';
			$columns['title'] = $move_title;
			$columns['funder_donations'] = 'Donations';
			unset($columns['date']);
		} elseif($typenow == 'donation') {
			$columns['donation_amount'] = 'Amount';
			$columns['donation_recurring'] = 'Recurring Donation';
			$columns['donation_funder'] = 'Fundraiser';
			$columns['donation_from'] = 'From';
			$columns['donation_method'] = 'Method';
			$columns['title'] = 'Transaction ID';
			$title_move = $columns['title'];
			unset($columns['title']);
			$move_date = $columns['date'];
			unset($columns['date']);
			$columns['title'] = $title_move;
			$columns['date'] = $move_date;
		}
		return $columns;
	}
	
	function column_display($name) {
		global $post;
		switch($name) {
			case 'donation_recurring' :
				$trans = $this->get_transaction();
				if($trans['cycle'])
					echo $trans['cycle'];
				break;
			case 'donation_funder' :
				$parent = get_post($post->post_parent);
				echo $parent->post_title;
				break;
			case 'funder_donations' :
				$donations = $this->get_donation_list($post->ID);
				echo count($donations);
				break;
			case 'funder_thumb' :
				if(has_post_thumbnail($post->ID))
					echo '<a href="'.get_edit_post_link($post->ID).'">'.get_the_post_thumbnail($post->ID, array(45,45)).'</a>';
				break;
			case 'donation_amount' :
				$trans = $this->get_transaction();
				//var_export($trans);
				//die();
				echo '<a href="'.get_edit_post_link($post->ID).'">'.$this->format_currency('',$trans['gross']).'</a>';
				break;
			case 'donation_from' :
				$trans = $this->get_transaction();
				echo $trans['payer_email'];
				break;
			case 'donation_method' : 
				$trans = $this->get_transaction();
				echo $this->get_gateway_label($trans['gateway']);
				break;
			case 'funder_raised' :
				$has_goal = get_post_meta($post->ID,'wdf_has_goal',true);
				$goal = get_post_meta($post->ID,'wdf_goal_amount',true);
				$total = $this->get_amount_raised($post->ID);
				// If The Type is goal display the raise amount with the goal total
				if($has_goal == '1') {
					$classes = ($total >= $goal ? 'class="wdf_complete"' : '');
					echo '<div '.$classes.'>'.$this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</div>';
					if($bar = $this->prepare_progress_bar(null,$total,$goal,'column',false)) {
						echo $bar;
					}
				} else {
						echo $this->format_currency('',$total);
				}
				break;
		}
	}
	function media_upload_tabs($tabs) {
		
		if($_GET['tab'] == 'fundraising' || $_GET['tab'] == 'donate_button') {
			$tabs = array();
			$tabs['fundraising'] = 'Fundraising Form';
			$tabs['donate_button'] = 'Donate Button';
		}
	
		return $tabs;
	}
	function media_fundraising() {
		wp_iframe(array(&$this, 'media_fundraiser_iframe'));
	}
	function media_donate_button() {
		wp_iframe(array(&$this, 'media_donate_button_iframe'));
	}
	function media_buttons($context) {
		global $typenow, $pagenow, $post;
		if($typenow != 'funder' && $typenow != 'donation' && $context == 'content' && $pagenow != 'index.php') {
			echo '<a title="Insert Funraising Shortcodes" class="thickbox add_media" id="add_wdf" href="'.admin_url('media-upload.php?post_id='.$post->ID).'&tab=fundraising&TB_iframe=1&wdf=1"><img onclick="return false;" alt="Insert Funraising Shortcodes" src="'.WDF_PLUGIN_URL.'/img/sm_ico.png"></a>';
		}
	}
	function media_donate_button_iframe () {
		$settings = get_option('wdf_settings');
		media_upload_header(); ?>
		<form class="wdf_media_cont" id="media_donate_button">
		<h3 class="media-title">Add A Donation Button</h3>
		<p>
			<label>Donation Item Title<br /><input type="text" name="title" class="regular-text" /></label>
		</p>
		<p>
			<label><?php echo __('Donation Amount (blank = choice)','wdf') ?><br /><input type="text" id="donation_amount" value="" /></label>
		</p>
		<p>
			<label>Button Type</label><br/>
			<label><input onchange="input_switch(); return false;" type="radio" value="default" name="button_type" /> Default PayPal Button</label><br />
			<label><input type="radio" name="button_type" value="custom" /> Custom Button</label>
		</p>
		<p>
			<?php /*?><label><?php echo __('Override PayPal Email Address','wdf') ?></label><br />
				<label class="code"><?php echo $settings['paypal_email']; ?></label><br />
				<input class="regular-text" type="text" name="paypal_email" value="" />
			</label><?php */?>
		</p>
		<p>
			<label><?php echo __('Choose a display style','wdf'); ?>
			<select name="style">
				<option value="wdf_default"><?php echo __('Basic','wdf'); ?></option>
				<option value="wdf_dark"><?php echo __('Dark','wdf'); ?></option>
				<option value="wdf_minimal"><?php echo __('Minimal','wdf'); ?></option>
				<option value="wdf_note"><?php echo __('Note','wdf'); ?></option>
				<option value="custom"><?php echo __('None (Custom CSS)','wdf'); ?></option>
			</select></label>
		</p>
		<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;">Insert Form</a></p>
		</form>
		<?php
	}
	function media_fundraiser_iframe() {
			$content = '';
			
			$args = array(
				'post_type' => 'funder',
				'numberposts' => -1,
				'post_status' => 'publish'
			);
			$funders = get_posts($args);
			media_upload_header();?>
			<form class="wdf_media_cont" id="media_fundraising">
			<h3 class="media-title">Add A Fundraising Form</h3>
			<p><select id="wdf_funder_select" name="id">
			<option value="0"> ---------------------------- </option>
			<?php foreach($funders as $funder) { ?>
				<option value="<?php echo $funder->ID; ?>"><?php echo $funder->post_title ?></option>
			<?php } ?>
			</select></p>
			
			<p><a class="button" href="#" id="wdf_add_shortcode" onclick="window.parent.wdf_inject_shortcode(); return false;">Insert Form</a></p>
			</form><?php
			
	}

	function format_currency($currency = '', $amount = false) {
		
		$settings = get_option('wdf_settings');
	
		if (!$currency)
			$currency = $settings['currency'];
	
		// get the currency symbol
		$symbol = $this->currencies[$currency][1];
		// if many symbols are found, rebuild the full symbol
		$symbols = explode(', ', $symbol);
		if (is_array($symbols)) {
			$symbol = "";
			foreach ($symbols as $temp) {
				$symbol .= '&#x'.$temp.';';
			}
		} else {
			$symbol = '&#x'.$symbol.';';
		}
	
		//check decimal option
		if ( $settings['curr_decimal'] === '0' ) {
			$decimal_place = 0;
			$zero = '0';
		} else {
			$decimal_place = 2;
			$zero = '0.00';
		}
	
		//format currency amount according to preference
		if ($amount) {
		  if ($settings['curr_symbol_position'] == 1 || !$settings['curr_symbol_position'])
			return $symbol . number_format_i18n($amount, $decimal_place);
		  else if ($settings['curr_symbol_position'] == 2)
			return $symbol . ' ' . number_format_i18n($amount, $decimal_place);
		  else if ($settings['curr_symbol_position'] == 3)
			return number_format_i18n($amount, $decimal_place) . $symbol;
		  else if ($settings['curr_symbol_position'] == 4)
			return number_format_i18n($amount, $decimal_place) . ' ' . $symbol;
	
		} else if ($amount === false) {
		  return $symbol;
		} else {
		  if ($settings['curr_symbol_position'] == 1 || !$settings['curr_symbol_position'])
			return $symbol . $zero;
		  else if ($settings['curr_symbol_position'] == 2)
			return $symbol . ' ' . $zero;
		  else if ($settings['curr_symbol_position'] == 3)
			return $zero . $symbol;
		  else if ($settings['curr_symbol_position'] == 4)
			return $zero . ' ' . $symbol;
		}
	}
	function is_new_install() {
		return true;
	}
	function prepare_progress_bar($post_id = '', $total = false, $goal = false, $context = 'general', $echo = false) {
		$content = '';
		if(!empty($post_id)) {
			$goal = get_post_meta($post_id,'wdf_goal_amount',true);
			$total = $this->get_amount_raised($post_id);
		}
		//if($total == false || $goal == false ) {
			//return false;
		//} else {
			$classes = ($total >= $goal ? 'wdf_complete' : '');
			if($context == 'admin_metabox') {
				$content .= '<h1 class="'.$classes.'">' . $this->format_currency('',$total) . ' / ' . $this->format_currency('',$goal) . '</h1>';
			} elseif($context == 'general') {
				
			}
			$content .= '<div rel="'.$post_id.'" class="wdf_goal_progress '.$classes.' not-seen '.$context.'" total="'.$total.'" goal="'.$goal.'"></div>';
		//}
		if($echo) {echo $content;} else {return $content;}
	}
	
	function get_donation_list($post_id = false) {
		global $post;
		$post_id = ($post_id != false ? $post_id : $post->ID);
		$args = array(
			'post_parent' => $post_id,
			'numberposts' => -1,
			'post_type' => 'donation',
			'post_status' => array('publish')
		);
		return get_posts($args);
	}
	
	function get_amount_raised($post_id = false) {
		global $post;
		$post_id = ($post_id != false ? $post_id : $post->ID);
		$donations = $this->get_donation_list($post_id);
		$totals = 0;
		if($donations) {
			foreach($donations as $donation) {
				$trans = maybe_unserialize(get_post_meta($donation->ID,'wdf_transaction',true));
				if($trans['gross']) {
					$totals = $totals + intval($trans['gross']);
				}
			}
		} else {
			$totals = '0';
		}
		return $totals;
	}
	
	function get_gateway_label($name) {
		switch($name) {
			case 'paypal' :
				return 'PayPal';
				break;
			case 'cash' :
				return 'Cash';
				break;
			case 'check' :
				return 'Check';
				break;
			default :
				$name = apply_filters('wdf_gateway_label',$name);
				return $name;
				break;
		}
	}
	
	function get_transaction($post_id = false) {
		global $post;
		if(!$post_id)
			$post_id = $post->ID;
			
		return maybe_unserialize(get_post_meta($post_id,'wdf_transaction',true));
			
	}
	
	function filter_price($price) {
		 $price = round(preg_replace("/[^0-9.]/", "", $price), 2);return ($price) ? $price : 0;
	}
	
	function has_goal($post_id = false) {
		global $post;
		$post_id = ($post_id ? $post_id : $post->ID);
		
		if(get_post_meta($post_id, 'wdf_has_goal', true) == '1')
			return true;
		else 
			return false;
	}
	
}
global $wdf;
$wdf = new WDF();
require_once( WDF_PLUGIN_BASE_DIR . '/lib/template-functions.php');
