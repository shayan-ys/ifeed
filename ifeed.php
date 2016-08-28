<?php
/*
Plugin Name: iFeed
Description: Iterating feed for WordPress
Author: Shayan Ys
Author URI: http://www.shayanys.com/
Company: BE360
Company URI: http://www.be360.ir/
License: GPLv2 or later

Copyright 2016  ShayanYs  (email : shayan.yousefian1372@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/
DEFINE( 'IFEED_FULLNAME', 'Iterating WP RSS Feed' );
DEFINE( 'IFEED_VERSION', '1.1.0' );

/** WP-admin **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_list_class.php' );
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_sp_class.php' );

add_action( 'plugins_loaded', function () {
	SP_Ifeed::get_instance();
});

/** handle save retrieve or create tables in Database **/
include_once( plugin_dir_path( __FILE__ ) . '/includes/ifeed_db.php' );
if(!function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: function not found, include function: "ifeed_save_options_db"') );}

/** html and php codes needed for edit/create page of ifeeds **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_edit_page.php' );

/** ajax handler for post loader action **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_ajax_post_loader.php' );
if( !function_exists('ifeed_ajax_post_loader') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_ajax_post_loader"') );} else {
	add_action( 'wp_ajax_ifeed_load_posts', 'ifeed_ajax_post_loader' );
}
/** WP-admin END **/

/** RSS iFeed **/
add_action('init', 'ifeed_RSS');
function ifeed_RSS(){ add_feed('ifeed', 'ifeed_RSS_func'); }
function ifeed_RSS_func(){
	if ( $overridden_template = locate_template('rss-ifeed.php') ) {
		// locate_template() returns path to file
		// if either the child theme or the parent theme have overridden the template
		load_template( $overridden_template );
	} else {
		// If neither the child nor parent theme have overridden the template,
		// we load the template from the 'includes' sub-directory of the directory this file is in
		load_template( dirname( __FILE__ ) . '/includes/rss-ifeed.php' );
	}
}
/** RSS iFeed END **/

if( !function_exists('ifeed_detect_page_refresh') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_detect_page_refresh"') );} else {
	add_action('template_redirect', 'ifeed_detect_page_refresh');
}
function ifeed_detect_page_refresh() {
    $queried_object = get_queried_object();
	$message = '';
    if($queried_object->post_name == "ifeed-refresh") { // ifeed-refresh
		if( $_GET['key'] != '123' || current_user_can('manage_options') ) {
			include_once( plugin_dir_path( __FILE__ ) . 'ifeed_refresher.php' );
			if( !function_exists('ifeed_refresher') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_refresher"') );} else {
				$message = ifeed_refresher();
			}
		} else {
			wp_redirect( site_url() );
		}
		// if( !current_user_can('edit_posts') )
		// if( !current_user_can('manage_options') )
		// if( in_array('administrator',  wp_get_current_user()->roles) )
    } else {
        // for normal pages nothing would happens here.
        return;
    }
	add_filter('the_content',
	function($content) use ($message) {
		return $message;
	});
}

?>