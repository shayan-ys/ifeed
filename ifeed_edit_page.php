<?php
if(function_exists('ifeed_options_page_edit')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_options_page_edit"') );} else {
	function ifeed_options_page_edit() {

		//blocking direct access to plugin PHP files
		defined('ABSPATH') or die('Direct access to this script is blocked.');
		//must check that the user has the required capability 
		if (!current_user_can('manage_options') && !current_user_can('ifeed_edit'))
			wp_die( __('You do not have sufficient permissions to access this page.') );
		
		wp_register_script('ifeed-script', plugins_url( 'assets/ifeed-script.js', __FILE__ ), array('jquery'), '1.0.0', true );
		wp_enqueue_script('ifeed-script');
		wp_localize_script('ifeed-script', 'ajax_ifeed_load_posts_object', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'loadingmessage' => __('loading..')
		));
		
		wp_register_style('ifeed-style', plugins_url( 'assets/ifeed-style.css', __FILE__ ), null, '1.0.0', 'all' );
		wp_enqueue_style('ifeed-style');
	
		$ifeed_ID = "";
		$vals = array();
		$input_errors = array();

		
		if( isset($_POST) && count($_POST)>0 ) {
			$vals = $_POST;
			if( !isset($vals['ifeed-slug']) || $vals['ifeed-slug']==null || $vals['ifeed-slug']=="" ) {
				$input_errors['ifeed-slug'] = "empty";
			} else {
				$result = false;
				if(!function_exists('ifeed_get_value_db')) {wp_die( __('iFeed-error: function not found: "ifeed_get_value_db"') );} else {
					$result = ifeed_get_value_db("slug", $vals['ifeed-slug']);
				}
				if($result===false || 
					(is_array($result) && count($result)>0 && (!isset($result['id']) || (isset($_GET) && isset($_GET['ifeed']) && isset($result['id']) && $result['id']!=$_GET['ifeed']))  ) 
				)
					$input_errors['ifeed-slug'] = "duplicated";
			}
			if( isset($vals['ifeed-auto-query']) )
				$vals['ifeed-auto-query'] = stripslashes($vals['ifeed-auto-query']);
			if( isset($vals['ifeed-manual-posts']) )
				$vals['ifeed-manual-posts'] = stripslashes($vals['ifeed-manual-posts']);
			if( isset($vals['ifeed-auto-query']['hours_set']) )
				$vals['ifeed-auto-query']['hours_set'] = stripslashes($vals['ifeed-auto-query']['hours_set']);
			if( isset($vals['active']) )
				$vals['ifeed-active'] = 1;
			elseif ( isset($vals['draft']) )
				$vals['ifeed-active'] = 0;
			
			if( count($input_errors)==0 ) {
				if( function_exists('ifeed_save_options_db')) {
					$ifeed_ID = ifeed_save_options_db($vals);
					if(!isset($_GET) || !isset($_GET['ifeed']) || $_GET['ifeed'] != $ifeed_ID) {
						$params = array_merge($_GET, array("ifeed" => $ifeed_ID));
						$actual_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
						$actual_link=strtok($actual_link,'?');
						$new_url = $actual_link."?". http_build_query($params);
						wp_redirect($new_url);
						echo "Manual redirect to <a id='redirect_url' href='".$new_url."'>".$vals['ifeed-slug']."</a> edit page";
					}
				} else {
					// saved failed function ifeed_save_options_db doesn't exists !
					wp_die( __('iFeed-error: Save ifeed failed, function doesn\'t exists: "ifeed_save_options_db"') );
				}
			} else {
				// input error
			}
		}
		
		if( isset($_GET) && isset($_GET['ifeed']) ) {
			if( !isset($vals) || count($valse)<1 ) {
				$vals = ifeed_get_options_db($_GET['ifeed']);
				$ifeed_ID = isset($vals['id'])? $vals['id'] : "";
			}
		}
		
		if(isset($vals['ifeed-auto-query'])) {
			$vals['ifeed-auto-query'] = json_decode($vals['ifeed-auto-query'], true);
			$vals['ifeed-auto-query']['hours_set'] = implode("," ,  json_decode($vals['ifeed-auto-query']['hours_set'], true)  );
		}
		if(isset($vals['ifeed-manual-posts'])) {
			$vals['ifeed-manual-posts'] = json_decode($vals['ifeed-manual-posts'], true);
		}
		?>
		
		<div class="wrap">
			<h1>
			<?php if($ifeed_ID=="") _e("iFeed Plugin Create"); else _e("iFeed Plugin Edit"); ?>
			<a class="page-title-action" href="?page=ifeed-settings">iFeeds List</a>
			<?php if($ifeed_ID!="") { ?><a class="page-title-action" href="?page=ifeed-settings-edit">Add New</a><?php } ?>
			</h1>
			<form name="ifeed-settings" method="post" action="">
				<?php wp_nonce_field('ajax-ifeed-panel-nonce', 'security'); ?>
				<input type="hidden" name="ifeed_id" value="<?php echo $ifeed_ID; ?>" />
				<?php echo (count($input_errors)>0)? '<p class="error">You have some errors in your input shown by red border.</p>' : ""; ?>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed Slug:"); ?></th>
							<td><fieldset><p> 
								<input type="text" name="ifeed-slug" class="<?php echo (isset($input_errors['ifeed-slug']))? 'error '.$input_errors['ifeed-slug'] : ''; ?>" <?php echo ($ifeed_ID=="")? "" : 'readonly="readonly"'; ?> size="20" placeholder="Unique name for iFeed" value="<?php echo $vals['ifeed-slug']; ?>"/>
								&nbsp;<em><?php _e("Unique name for ifeed, also will be used for calling ifeed by URL"); ?></em>
								<?php if($ifeed_ID!="") { $ifeed_url = get_bloginfo('rss2_url')."ifeed?title=".$vals['ifeed-slug']; ?>
								<br />
								<a href="<?php echo $ifeed_url ?>"><?php echo $ifeed_url ?></a>
								<?php } ?>
							</fieldset></td></p>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed UTM:"); ?></th>
							<td><fieldset><p> 
								<input type="text" name="ifeed-utm" size="50" placeholder="UTM source and campaign for URLs" value="<?php echo $vals['ifeed-utm']; ?>" />
								&nbsp;<em><?php _e("UTM url query you want to append to posts (leave it blank for no UTM)"); ?></em>
							</fieldset></td></p>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e("iFeed Description:"); ?></th>
							<td><fieldset><p> 
								<textarea name="ifeed-desc" rows="3" placeholder="Describe what this iFeed is"><?php echo $vals['ifeed-desc']; ?></textarea>
							</fieldset></td></p>
						</tr>					
					</tbody>
				</table>
				<hr />
				<table class="form-table ifeed-post-generator">
					<thead><tr>
						<th><?php _e("Automatic Query"); ?></th>
						<th><?php _e("Preview"); ?></th>
					</tr></thead>
					<tbody>
					<tr><td class="left ifeed-query-builder">
						<table class="form-table">
							<tbody>
								<tr valign="top">
									<th scope="row"><?php _e("Tags"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder"  data-name="tag" size="20" placeholder="Filter posts by tags" value="<?php echo $vals['ifeed-auto-query']['tag'] ?>" />
										<em><?php _e("Leave blank to fetch all tags by <b>slug</b>"); ?></em>
										<br /><em><?php _e("Use ',' to display posts that have 'either' of these tags"); ?></em>
										<br /><em><?php _e("Use '+' to display posts that have 'all' of these tags"); ?></em>
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Excluded Tags"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder"  data-name="tag__not_in" size="20" placeholder="Filter posts not having these tags" value="<?php echo $vals['ifeed-auto-query']['tag__not_in'] ?>" />
										<em><?php _e("(Only use <b>tag_id</b> ',' delimited or leave blank to fetch all tags"); ?></em>
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>								
								<tr valign="top">
									<th scope="row"><?php _e("Category"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="cat" size="20" placeholder="Filter posts by a specific category" value="<?php echo $vals['ifeed-auto-query']['cat'] ?>" />
										<em><?php _e("Leave blank to fetch all categories by <b>cat_id</b>"); ?></em>
										<br /><em><?php _e("Use ',' to display posts that have 'either' of these categories"); ?></em>
										<br /><em><?php _e("Use '+' to display posts that have 'all' of these categories"); ?></em>
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Time Schedule"); ?></th>
									<td><fieldset><p> 
										<input type="text" data-name="hours-array" size="60" placeholder="Hours set, e.g. 10,13,19" value="<?php echo $vals['ifeed-auto-query']['hours_set'] ?>" />
										<br/><em><?php _e("Hours set you want ifeed to be changed, delimited by ',' (e.g. 10,13,19)"); ?></em>
									</fieldset></td></p>
								</tr>								
								<tr valign="top">
									<th scope="row"><?php _e("Time Published After"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="time_offset" size="20" placeholder="for e.g. 2016-04-22" value="<?php echo $vals['ifeed-auto-query']['time_offset'] ?>" />
										<em><?php _e("Select Date and Time offset posts should be fetched after, Leave blank to ignore time offset"); ?></em>
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Offset"); ?></th>
									<td><fieldset><p>
										<button type="button" data-action="reset-offset" class="button-primary" >Reset offset</button>
										<input type="number" class="ifeed-query-builder" data-name="offset" placeholder="Offset on selected posts" value="<?php echo intval($vals['offset']) ?>" style="display:none;" />
										<input type="hidden" name="offset" value="" />
										<em><?php _e("Be careful if you make this offset 0 it will iterate from beginning of your query"); ?></em>
										<br /><p><?php _e("Current offset is"); ?>&nbsp;<span data-action="offset-value"><?php echo intval($vals['offset']) ?></span>
										<label><input type="checkbox" data-action="custom-offset" />&nbsp;Custom offset value (use with caution)</label></p>
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
										<input type="text" class="ifeed-query-builder" data-name="orderby" placeholder="Order By" value="<?php echo ($vals['ifeed-auto-query']['orderby']!="")? $vals['ifeed-auto-query']['orderby'] : "post_date" ?>" />
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>
								<tr valign="top">
									<th scope="row"><?php _e("Ascending or Decending"); ?></th>
									<td><fieldset><p> 
										<input type="text" class="ifeed-query-builder" data-name="order" placeholder="Direction of sort" value="<?php echo ($vals['ifeed-auto-query']['order']!="")? $vals['ifeed-auto-query']['order'] : "ASC" ?>" />
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
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
										<select data-action="excluding-options">
											<option value=""><?php _e("ignore"); ?></option>
											<option value="<?php echo implode(',', get_option('sticky_posts')); ?>" ><?php _e("Sticky Posts"); ?></option>
										</select>
										<br /><input type="text" placeholder="Excluded posts ',' delimited" value="<?php echo $vals['ifeed-auto-query']['post__not_in']; ?>" data-name="post__not_in" class="ifeed-query-builder" />
										<br /><em><b><?php _e("Caution"); ?></b>:&nbsp;<?php _e("changing this value will reset the offset."); ?></em>
									</fieldset></td></p>
								</tr>
							</tbody>
						</table>
					</td><td class="right">
						<div class="ifeed-preview ifeed-log-viewer">
							<h2><?php _e("Log (previously aired posts)"); ?></h2>
							<em class='caution'><b><?php _e("Note"); ?>:</b>&nbsp;<?php _e("Last element of below table is showing the post that is live on rss-ifeed right now, others shows previous posts were in ifeed."); ?></em>
							<table>
								<thead><tr>
									<th><?php _e("post ID"); ?></th>
									<th><?php _e("Title"); ?></th>
									<th><?php _e("Created Date"); ?></th>
									<th><?php _e("Image"); ?></th>
									<th><?php _e("Executed Time"); ?></th>
									<th><?php _e("View Counts <br/>(daily)"); ?></th>
								</tr></thead>
								<?php
								$log_posts_limit = 5;
								?>
								<tbody data-limit="<?php echo $log_posts_limit; ?>">
								<?php
								if(isset($vals['log_posts']))
									$vals['log_posts'] = json_decode($vals['log_posts'], true);
								if( is_array($vals['log_posts']) && count($vals['log_posts'])>0 ) {
									array_slice($vals['log_posts'], $log_posts_limit);
									foreach( $vals['log_posts'] as $index=>$log_post ) {
										$post = null;
										try{
											$post = new WP_Query(array('p'=> $log_post['post_id'] ));
										} catch(Exception $e) { echo "Exception:". $e->getMessage();}
										if ( strlen(serialize($post))>0 &&  $post->have_posts() ) :
											while ( $post->have_posts() ) : $post->the_post();
												?>
												<tr>
												<?php
												$execution_string = (isset($log_post['added_time']))? $log_post['added_time'] : "Unknown";
												ifeed_print_post_row(get_the_ID(), get_the_title(), get_the_permalink(), gmdate("Y-m-d", get_the_time('U')), get_the_post_thumbnail(null,'thumbnail'), $index, $execution_string, false, false);
												?>
												<td>
												<?php echo get_post_meta( $log_post['post_id'], '_count-views_day-'.date('Ymd'), true ); ?>
												</td>
												</tr>
												<?php
											endwhile;
										endif;
									}
								}
								?>
								</tbody>
							</table>
						</div>						
						<div class="ifeed-preview ifeed-post-viewer">
							<br /><br /><br />
							<h2><?php _e("Query Result Preview / Manual"); ?></h2>
							<h2><label><input type="checkbox" name="ifeed-query-manual" <?php echo (isset($vals['ifeed-query-manual'])&&$vals['ifeed-query-manual']!==null)? 'checked="checked"' : ""; ?>><span><?php _e("Manual select posts"); ?></span></label><em>&nbsp; (will inhibit automatic query)</em></h2>
							<em class='caution'><b><?php _e("Note"); ?>:</b>&nbsp;<?php _e("Every change on box below (change in post-id or clearing the list) will tick the \"Manual select posts\"."); ?></em>
							<br/><br/>
							<button type="button" data-action="clear-query" class="button ifeed-button-danger"><?php _e("clear list"); ?></button>
							<button type="button" data-action="reload-query" class="button-secondary"><?php _e("reload from auto"); ?></button>
							&nbsp;<label for="reload-query-size" class="reload-query-size"><?php _e("Load Count"); ?>:</label><select data-action="reload-query-size" id="reload-query-size" class="reload-query-size">
								<option value="5">5</option>
								<option value="10" selected="selected" >10</option>
								<option value="15">15</option>
								<option value="25">25</option>
								<option value="50">50</option>
							</select>
							<?php if($ifeed_ID!="") { ?>
								&nbsp;<label for="online-now-postid" class="online-now"><?php _e("Go Online Now"); ?></label>
								<input type="text" data-name="online-now-postid" id="online-now-postid" class="online-now" placeholder="Post ID" value="" />
								<button type="button" data-action="online-post-now" class="button-primary online-now" onclick="ifeed_go_online(<?php echo $ifeed_ID; ?>)" ><?php _e("Online Now"); ?></button>
							<?php } ?>
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
								<?php
								if( isset($vals['ifeed-manual-posts']) && is_array($vals['ifeed-manual-posts']) && count($vals['ifeed-manual-posts'])>0 ) {
									foreach( $vals['ifeed-manual-posts'] as $index=>$manual_post ) {
										$post = null;
										try{
											$post = new WP_Query(array('p'=> $manual_post['post_id'] ));
											// var_dump($posts);
										} catch(Exception $e) { echo "Exception:". $e->getMessage();}
										if ( strlen(serialize($post))>0 &&  $post->have_posts() ) :
											while ( $post->have_posts() ) : $post->the_post();

												$execution_string = (isset($manual_post['exec_time']))? $manual_post['exec_time'] : "Unknown";
												ifeed_print_post_row(get_the_ID(), get_the_title(), get_the_permalink(), gmdate("Y-m-d", get_the_time('U')), get_the_post_thumbnail(null,'thumbnail'), $index, $execution_string, (!isset($query['p'])));
											endwhile;
										endif;
									}
								}
								?>
								</tbody>
							</table>
							<button type="button" data-action="add-post-query" class="button-primary"><?php _e("add post"); ?></button>
						</div>
					</td></tr>
					</tbody>
				</table>
				
				<input type="hidden" name="ifeed-auto-query" value='<?php echo $_POST['ifeed-auto-query'] ?>' />
				<input type="hidden" name="ifeed-manual-posts" value='<?php echo $vals['ifeed-manual-posts'] ?>' />
				
				<p class="submit">
					<input type="submit" name="active" class="button-primary" value="<?php _e("Save & Active"); ?>" />
					<input type="submit" name="draft" class="button-secondary" value="<?php if($ifeed_ID=="") _e("Draft"); else _e("Save & Deactive"); ?>" />
				</p>

			</form>
		</div>
		<?php
	}
}
?>