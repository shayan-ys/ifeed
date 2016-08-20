<?php
/*
Plugin Name: iFeed
Description: Iterating feed for WordPress
Author: shayanys
Author URI: http://www.shayanys.com/
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

/** WP-admin **/
if(function_exists('ifeed_custom_admin_menu') ) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_custom_admin_menu"') );} else {
	function ifeed_custom_admin_menu() {
		add_options_page(
			'iFeed panel',
			'iFeed',
			'manage_options',
			'ifeed-settings',
			'ifeed_options_page'
		);
	}
}
add_action( 'admin_menu', 'ifeed_custom_admin_menu' );

if(function_exists('ifeed_options_page')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_options_page"') );} else {
	function ifeed_options_page() {

		//blocking direct access to plugin PHP files
		defined('ABSPATH') or die('Direct access to this script is blocked.');
		//must check that the user has the required capability 
		if (!current_user_can('manage_options') && !current_user_can('ifeed_create'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		
		if( isset($_POST) && count($_POST)>0 ) var_dump($_POST);
		?>
		<div class="wrap">
			<h2>iFeed Plugin</h2>
			<form name="form1" method="post" action="">
				<input type="hidden" name="security" value="Y">

				<p><?php _e("Favorite Color:", 'menu-test' ); ?> 
					<input type="text" name="color" value="" size="20">
				</p>
				
				<hr />

				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>

			</form>
		</div>
		<?php
	}
}
/** WP-admin **/

?>