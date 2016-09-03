<?php
/*
Plugin Name: iFeed
Description: Iterating feed for WordPress
Version: 1.1.0
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
/** handle save retrieve or create tables in Database **/
include_once( plugin_dir_path( __FILE__ ) . '/includes/ifeed_db.php' );
if(!function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: function not found, include function: "ifeed_save_options_db"') );}


/** Activation function **/
function ifeed_activate() {
    // Activation code here...
	global $user_ID;
	$page['post_type']    = 'page';
	$page['post_content'] = 'iFeed Refresher script';
	$page['post_parent']  = 0;
	$page['post_author']  = $user_ID;
	$page['post_status']  = 'publish';
	$page['post_title']   = 'ifeed-refresh';
	if(get_page_by_title($page['post_title'])!=null) {
		$page['post_title'] = 'ifeed-refresher';
		$suffix=1;
		$page_title = $page['post_title'];
		while(get_page_by_title($page['post_title'])!=null) {
			$page['post_title'] = $page_title.$suffix;
			$suffix++;
		}
	}
	
	$page = apply_filters('yourplugin_add_new_page', $page, 'teams');
	$pageid = wp_insert_post ($page);
	if ($pageid != 0) { update_option('ifeed_refresher_page_id', $pageid); update_option('ifeed_refresher_page_key', wp_generate_password(12,false)); }
	else {}
}
register_activation_hook( __FILE__, 'ifeed_activate');
/** Activation function END **/
/** Deactivation function **/
function ifeed_deactivate() {
    $refresh_pageid = get_option('ifeed_refresher_page_id');
	if( intval($refresh_pageid)>0 )
		wp_delete_post($refresh_pageid,true);
	delete_option('ifeed_refresher_page_id');
	delete_option('ifeed_refresher_page_key');
}
register_deactivation_hook( __FILE__, 'ifeed_deactivate');
/** Deactivation function END **/
/** Unistallation function **/
register_uninstall_hook( __FILE__, 'ifeed_uninstall' );
function ifeed_uninstall() {
    $refresh_pageid = get_option('ifeed_refresher_page_id');
	if( intval($refresh_pageid)>0 )
		wp_delete_post($refresh_pageid,true);
	if( function_exists('ifeed_delete_table_db') )
		ifeed_delete_table_db();
}
/** Unistallation function END **/

/** WP-admin **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_list_class.php' );
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_sp_class.php' );

add_action( 'plugins_loaded', function () {
	SP_Ifeed::get_instance();
});

/** html and php codes needed for edit/create page of ifeeds **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_edit_page.php' );

/** ajax handler for post loader action **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_ajax.php' );
if( !function_exists('ifeed_ajax_post_loader') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_ajax_post_loader"') );} else {
	add_action( 'wp_ajax_ifeed_load_posts', 'ifeed_ajax_post_loader' );
}
if( !function_exists('ifeed_ajax_go_online') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_ajax_go_online"') );} else {
	add_action( 'wp_ajax_ifeed_go_online', 'ifeed_ajax_go_online' );
}
/** WP-admin END **/

/** Plugins page **/
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'ifeed_add_action_links' );
function ifeed_add_action_links ( $links ) {
	$mylinks = array(
		'<a href="' . admin_url( 'options-general.php?page=ifeed-settings' ) . '">Settings</a>',
	);
	return array_merge( $mylinks, $links );
}
add_filter( 'plugin_row_meta', 'ifeed_add_meta_links', 10, 2 );
function ifeed_add_meta_links( $links, $file ) {
	$plugin = plugin_basename(__FILE__);
	// create link
	if ( $file == $plugin ) {
		return array_merge(
			$links,
			array( __('Company').': <a href="http://www.be360.ir">BE360</a>' , __('Project').': <a href="http://www.chetor.com">Chetor.com</a>' )
		);
	}
	return $links;
}
/** Plugins page END **/

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
    if($queried_object->ID == get_option('ifeed_refresher_page_id')) { // ifeed-refresh
		if( $_GET['key'] == get_option('ifeed_refresher_page_key') || current_user_can('manage_options') ) {
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