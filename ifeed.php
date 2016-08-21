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
if(function_exists('ifeed_custom_admin_menu') ) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_custom_admin_menu"') );} else {
	function ifeed_custom_admin_menu() {
		add_options_page(
			'iFeed panel',
			'iFeed',
			'manage_options',
			'ifeed-settings',
			'ifeed_options_page'
		);
		add_submenu_page(
			null,
			null,
			null,
			'manage_options',
			'ifeed-settings-edit',
			'ifeed_options_page_edit'
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
			<h1>
			iFeed Plugin
			<a class="page-title-action" href="?page=ifeed-settings-edit">Add New</a>
			</h1>
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
if(function_exists('ifeed_options_page_edit')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_options_page_edit"') );} else {
	function ifeed_options_page_edit() {

		//blocking direct access to plugin PHP files
		defined('ABSPATH') or die('Direct access to this script is blocked.');
		//must check that the user has the required capability 
		if (!current_user_can('manage_options') && !current_user_can('ifeed_create'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		echo plugins_url( 'assets/ifeed-script.js', __FILE__ );
		
		wp_register_script('ifeed-script', plugins_url( 'assets/ifeed-script.js', __FILE__ ), array('jquery'), '1.0.0', true );
		wp_enqueue_script('ifeed-script');
		wp_localize_script('ifeed-script', 'ajax_ifeed_load_posts_object', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'loadingmessage' => __('loading..')
		));
		
		// wp_register_style( 'topic-style', get_template_directory_uri() . '-child/topic-layout/assets/topic-style.css' );
		// wp_enqueue_style('topic-style');
		
		if( isset($_POST) && count($_POST)>0 ) var_dump($_POST);
		?>
		
		<div class="wrap">
			<h1><?php _e("iFeed Plugin Create"); ?></h1>
			<form name="ifeed-settings" method="post" action="">
				<?php wp_nonce_field('ajax-ifeed-panel-nonce', 'security'); ?>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed Slug:"); ?></th>
							<td><fieldset><p> 
								<input type="text" name="ifeed-slug" size="20" placeholder="Unique name for iFeed" value=""/>
								&nbsp;<em><?php _e("Unique name for ifeed, also will be used for calling ifeed by URL"); ?></em>
							</fieldset></td></p>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed UTM:"); ?></th>
							<td><fieldset><p> 
								<input type="text" name="ifeed-utm" size="50" placeholder="UTM source and campaign for URLs" value="" />
								&nbsp;<em><?php _e("UTM url query you want to append to posts (leave it blank for no UTM)"); ?></em>
							</fieldset></td></p>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed Description:"); ?></th>
							<td><fieldset><p> 
									<textarea name="ifeed-desc" rows="3" placeholder="Describe what this iFeed is"></textarea>
							</fieldset></td></p>
						</tr>
						<tr valign="top">
							<th scope="row"></th>
							<td><fieldset><p></p></fieldset></td>
						</tr>						
					</tbody>
				</table>
				
				<hr />

				<table class="form-table ifeed-post-generator">
					<thead><tr>
						<th><?php _e("Automatic"); ?></th>
						<th><?php _e("Manual"); ?></th>
					</tr></thead>
					<tbody>
					<tr><td class="left ifeed-query-builder">
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e("Tag"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder"  data-name="tag" size="20" placeholder="Filter posts by a specific tag" value="" />
										<em><?php _e("Leave blank to fetch all tags"); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Category"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="cat" size="20" placeholder="Filter posts by a specific category" value="" />
										<em><?php _e("Leave blank to fetch all categories"); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Time Published"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="from-time" size="20" placeholder="Select Date and Time offset posts should be fetched after" value="" />
										<em><?php _e("Leave blank to ignore time offset"); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Offset"); ?></th>
									<td><fieldset><p> 
										<input type="number" class="ifeed-query-builder" data-name="offset" placeholder="Offset on selected posts" value="" />
										<em><?php _e("Leave blank to fetch from first post"); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Order By"); ?></th>
									<td><fieldset><p>
										<input type="text" class="ifeed-query-builder" data-name="orderby" placeholder="Order By" value="post_date" />
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Ascending or Decending"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="order" placeholder="Direction of sort" value="ASC" />
									</fieldset></td></p>
								</tr>									
								<tr valign="top">
									<th scope="row"><?php _e("Number of posts"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="posts_per_page" placeholder="Number of posts" value="5" />
									</fieldset></td></p>
								</tr>								
								<button class="reload-query" type="button">reload</button>
							</tbody>
						</table>
					</td><td class="right ifeed-post-viewer">
						<div class="query-viewer"></div>
					</td></tr>
					</tbody>
				</table>
				
				<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
				</p>

			</form>
		</div>
		<?php
	}
}
if(function_exists('ajax_ifeed_load_posts')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ajax_ifeed_load_posts"') );} else {
	add_action( 'wp_ajax_ifeed_load_posts', 'ajax_ifeed_load_posts' );
	function ajax_ifeed_load_posts() {
		ob_clean();
		if(!isset($_POST) || !isset($_POST['query']) || !isset($_POST['security']) ) die("empty1");
		// First check the nonce, if it fails the function will break
		check_ajax_referer('ajax-ifeed-panel-nonce', 'security');
		$query = json_decode(stripslashes($_POST['query']), true);
		
		$posts = null;
		try{
			$posts = new WP_Query($query);
		} catch(Exception $e) { echo "Exception:". $e->getMessage();}
		
		if ( $post!=null && $posts->have_posts() ) :
			while ( $posts->have_posts() ) : $posts->the_post();
				the_title();
				echo ", ";
			endwhile;
		else :
			die("empty2");
		endif;
		// var_dump($query);
		die();
	}
}

if(function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_save_options_db"') );} else {
	function ifeed_save_options_db() {
		
	}
}
/** WP-admin **/

?>