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
		
		wp_register_style('ifeed-style', plugins_url( 'assets/ifeed-style.css', __FILE__ ), null, '1.0.0', 'all' );
		wp_enqueue_style('ifeed-style');
		
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
						<th><?php _e("Automatic Query"); ?></th>
						<th><?php _e("Preview / Manual"); ?></th>
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
									<th scope="row"><?php _e("Time Schedule"); ?></th>
									<td><fieldset><p> 
										<input type="text" data-name="hours-array" size="60" placeholder="Hours set, e.g. 10,13,19" value="" />
										<br/><em><?php _e("Hours set you want ifeed to be changed, delimited by ',' (10,13,19)"); ?></em>
									</fieldset></td></p>
								</tr>								
								<tr valign="top">
									<th scope="row"><?php _e("Time Published After"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="time_offset" size="20" placeholder="Select Date and Time offset posts should be fetched after" value="" />
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
								<!--<tr valign="top">
									<th scope="row"><?php _e("Offset increment amount"); ?></th>
									<td><fieldset><p> 
										<input type="number" data-name="offset-inc" placeholder="Default=1" value="" />
										<em><?php _e("Offset increment amount on each step"); ?></em>
									</fieldset></td></p>
								</tr>-->
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
								<!-- <tr valign="top">
									<th scope="row"><?php _e("Number of posts"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="posts_per_page" placeholder="Number of posts" value="5" />
									</fieldset></td></p>
								</tr> -->
								<tr valign="top">
									<th scope="row"><?php _e("Exclude posts in"); ?></th>
									<td><fieldset><p>
										<select class="ifeed-query-builder" data-name="post__not_in">
											<option value=""><?php _e("ignore"); ?></option>
											<option value="get_option('sticky_posts')"><?php _e("Sticky Posts"); ?></option>
										</select>
									</fieldset></td></p>
								</tr>
							</tbody>
						</table>
					</td><td class="right ifeed-post-viewer">
						<div class="ifeed-preview">
							<h2><label><input type="checkbox" name="ifeed-query-manual"><span><?php _e("Manual select posts"); ?></span></label><em>&nbsp; (will inhibit automatic query)</em></h2>
							<em><b>Note:</b> Every change on box below (change in post-id or clearing the list) will tick the "Manual select posts".</em>
							<br/><br/>
							<button type="button" data-action="clear-query" class="button ifeed-button-danger"><?php _e("clear list"); ?></button>
							<button type="button" data-action="reload-query" class="button-secondary"><?php _e("reload"); ?></button>
							<table>
								<thead><tr>
									<th><?php _e("post ID"); ?></th>
									<th><?php _e("Title"); ?></th>
									<th><?php _e("Created Date"); ?></th>
									<th><?php _e("Image"); ?></th>
									<th><?php _e("iFeed Exec Time"); ?></th>
									<th><?php _e("Actions"); ?></th>
								</tr></thead>							
								<tbody>
								</tbody>
							</table>
							<button type="button" data-action="add-post-query" class="button-primary"><?php _e("add post"); ?></button>
						</div>
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
		if(!isset($_POST) || !isset($_POST['security']) ) die("empty1");
		// First check the nonce, if it fails the function will break
		check_ajax_referer('ajax-ifeed-panel-nonce', 'security');
		
		if(!isset($_POST['query']) || $_POST['query']==null || $_POST['query']=="") {
			?>
			<tr>
				<td class="post-id-wrapper"><input type="text" value="" /></td>
				<td class='title-wrapper'></td>
				<td></td>
				<td class='img-wrapper'><img src="<?php echo plugins_url( 'assets/default-placeholder.jpg', __FILE__ ); ?>" /></td>
				<td class='remove'><button type="button" data-action="remove-post-query" class="button-secondary"><?php _e("X"); ?></button></td>
			</tr>
			<?php
			die();
		}
		
		$hours_set = false;
		if(isset($_POST['hours_set']) && $_POST['hours_set']!="") {
			$hours_set = json_decode(stripslashes($_POST['hours_set']), true);
			if(is_array($hours_set)) {
				sort($hours_set);
				$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
				$curr_hour = (int)$now->format('h');
				$ifeed_execution_hour_index = false;
				for( $i=0; $i<count($hours_set); $i++ ) {
					if($curr_hour < $hours_set[$i]) {
						$ifeed_execution_hour_index = $i;
						break;
					}
				}
				if($ifeed_execution_hour_index===false) {
					$ifeed_execution_date = new DateTime('tomorrow', new DateTimeZone('Asia/Tehran'));
					$ifeed_execution_hour_index = 0;
				} else {
					$ifeed_execution_date = new DateTime(null, new DateTimeZone('Asia/Tehran'));
				}
			}
		}
		
		$query = json_decode(stripslashes($_POST['query']), true);
		if( isset($query['time_offset']) ) {
			$query['date_query'] = array('after' => $query['time_offset']);
			unset($query['time_offset']);
		}
		
		$posts = null;
		try{
			$posts = new WP_Query($query);
			// var_dump($posts);
		} catch(Exception $e) { echo "Exception:". $e->getMessage();}
		$index=0;
		if ( strlen(serialize($posts))>0 &&  $posts->have_posts() ) :
			while ( $posts->have_posts() ) : $posts->the_post();
				if( !isset($query['p']) ) :
				?>
				<tr>
					<?php endif; ?>
					<td class="post-id-wrapper"><input type="text" value="<?php the_ID(); ?>" /></td>
					<td class='title-wrapper'><a title="<?php the_title(); ?>" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
					<td><?php echo gmdate("Y-m-d", get_the_time('U')); ?></td>
					<td class='img-wrapper'><?php the_post_thumbnail('thumbnail'); ?></td>
					<?php if($hours_set!==false) {
					?>
					<td class='execution-time-wrapper'><input type="text" data-name="exec-time" data-id="<?php echo $index; ?>" value="<?php echo $ifeed_execution_date->format('Y-m-d') .' '. $hours_set[$ifeed_execution_hour_index].':00'; ?>" /></td>
					<?php 
						$ifeed_execution_hour_index++;
						if( $ifeed_execution_hour_index == count($hours_set) ) {
							$ifeed_execution_hour_index=0;
							$ifeed_execution_date->modify('+1 day');
						}
					} else { /* if hours_set = false */
					?>
					<td class='execution-time-wrapper'>Unknown time</td>
					<?php } /* endif hours_set != false */ ?>
					<td class='remove'><button type="button" data-action="remove-post-query" class="button-secondary"><?php _e("X"); ?></button></td>
					<?php if( !isset($query['p']) ) : ?>
				</tr>
				<?php endif;
				$index++;
			endwhile;
		else :
			die("empty2");
		endif;
		die();
	}
}

if(function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_save_options_db"') );} else {
	function ifeed_save_options_db() {
		
	}
}
/** WP-admin **/

?>