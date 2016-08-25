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
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_db.php' );
if(!function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: function not found, include function: "ifeed_save_options_db"') );}

/** html and php codes needed for edit/create page of ifeeds **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_edit_page.php' );

/** ajax handler for post loader action **/
include_once( plugin_dir_path( __FILE__ ) . 'ifeed_ajax_post_loader.php' );
if( !function_exists('ifeed_ajax_post_loader') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_ajax_post_loader"') );} else {
	add_action( 'wp_ajax_ifeed_load_posts', 'ifeed_ajax_post_loader' );
}
/** WP-admin **/

?>