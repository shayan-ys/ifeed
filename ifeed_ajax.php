<?php
if(function_exists('ifeed_ajax_go_online')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_ajax_go_online"') );} else {
	function ifeed_ajax_go_online() {
		ob_clean();
		if(!isset($_POST) || !isset($_POST['security']) ) die("empty_security");
		// First check the nonce, if it fails the function will break
		check_ajax_referer('ajax-ifeed-panel-nonce', 'security');
		
		$post_id = intval($_POST['postid']);
		$ifeed_ID = intval($_POST['ifeed_id']);
		$ifeed = array();
		if( !function_exists('ifeed_get_options_db') ) {die( __('iFeed-error: function not found, include function: "ifeed_get_options_db"') );} else {
			$ifeed = ifeed_get_options_db($ifeed_ID);
		}
		if( count($ifeed)<1 ) die("empty_ifeed");
		$online_posts = (isset($ifeed['online_posts'])&&count(json_decode($ifeed['online_posts'],true))>0)? json_decode($ifeed['online_posts'],true) : array();
		$log_posts = (isset($ifeed['log_posts'])&&count(json_decode($ifeed['log_posts'],true))>0)? json_decode($ifeed['log_posts'],true) : array();
		$log_posts_limit = 10;
		if( count($log_posts)>$log_posts_limit ) {
			$log_posts_to_file = array_slice($log_posts,0,-$log_posts_limit); // return first log_posts items before last 10 in the array
			$log_posts = array_slice($log_posts,-$log_posts_limit);  // returns 10 items from the end of log_posts
			file_put_contents(plugin_dir_path( __FILE__ ) . '/log_posts.txt', "{'ifeed-id', '".$ifeed['id']."'}\n".json_encode($log_posts_to_file)."\n", FILE_APPEND | LOCK_EX);
		}
		if( in_array($post_id, $online_posts) ) {
			// duplicate post, exact same post is online
			die("update failed");
		}
		if( count($online_posts) >= $ifeed['visible_size'] ) {
			$current_count = count($online_posts);
			for($i=0; $i< $current_count-$ifeed['visible_size']+1; $i++ ) {
				// pop from beginning of online_posts
				array_shift($online_posts);
			}
		}
		// add at end of online_posts
		array_push($online_posts, intval($post_id));
		
		global $current_user;
		$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
		$agent = json_encode(array(
			'user_id' => get_current_user_id(),
			'user_display_name' => $current_user->display_name,
			'http_user_agent' => $_SERVER['HTTP_USER_AGENT']
		));
		// log this addition to ifeed
		array_push($log_posts, array(
			'post_id' => $post_id,
			'promissed_exec_time' => $now->format("Y-m-d H:i:s"),
			'added_time' => $now->format("Y-m-d H:i:s"),
			'last_modified_agent' => stripslashes($agent)
		));
		if( !function_exists('ifeed_update_options_db') ) {die( __('iFeed-error: function not found, include function: "ifeed_update_options_db"') );} else {
			$result = ifeed_update_options_db($ifeed_ID, array(
				'online_posts' => json_encode($online_posts),
				'log_posts' => json_encode($log_posts)
			), array(
				'%s',
				'%s'
			));
			if($result) {
				$post = null;
				try{
					$query = array();
					$query['p'] = $post_id;
					$query['post_type'] = array('post', 'page');
					$post = new WP_Query($query);
				} catch(Exception $e) { echo "failed, Exception:". $e->getMessage();}
				if ( strlen(serialize($post))>0 &&  $post->have_posts() ) :
					while ( $post->have_posts() ) : $post->the_post();
						?>
						<tr>
						<?php
						$execution_string = $now->format("Y-m-d H:i:s");
						ifeed_print_post_row(get_the_ID(), get_the_title(), get_the_permalink(), gmdate("Y-m-d", get_the_time('U')), get_the_post_thumbnail(null,'thumbnail'), count($log_posts)-1, $execution_string, false, false);
						?>
						<td>
						<?php echo get_post_meta( $post_id, '_count-views_day-'.date('Ymd'), true ); ?>
						</td>
						</tr>
						<?php
					endwhile;
				endif;
				die();
			} else {
				die("update failed");
			}
		}
	}
}
if(function_exists('ifeed_ajax_post_loader')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_ajax_post_loader"') );} else {
	function ifeed_ajax_post_loader() {
		ob_clean();
		if(!isset($_POST) || !isset($_POST['security']) ) die("empty_security");
		// First check the nonce, if it fails the function will break
		check_ajax_referer('ajax-ifeed-panel-nonce', 'security');
		
		if(!isset($_POST['query']) || $_POST['query']==null || $_POST['query']=="") {
			$placeholder = "<img src=". plugins_url( 'assets/default-placeholder.jpg', __FILE__ ) ." />";
			$index = (isset($_POST['data_id']))? $_POST['data_id'] : rand();
			ifeed_print_post_row("", "", "", "", $placeholder, $index, "Unknown");
			die();
		}

		$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
		$curr_hour = (int)$now->format('H');
		$curr_day_hour = $now->format('Y-m-d H');
		
		if( isset($_POST['ifeed_id']) && strlen($_POST['ifeed_id'])>0 ) {
			if(function_exists('ifeed_get_options_db')) {
				$ifeed = ifeed_get_options_db($_POST['ifeed_id']);
				$log_posts = (isset($ifeed['log_posts']) && $ifeed['log_posts']!=null)? json_decode($ifeed['log_posts'], true) : array();
				$duplicated_in_hour = false;
				foreach($log_posts as $log_post) {
					if( isset($log_post['promissed_exec_time']) && date( "Y-m-d H", strtotime($log_post['promissed_exec_time']) ) == $curr_day_hour ) {
						$duplicated_in_hour = "duplicated_in:".$log_post['promissed_exec_time'];
					}
				}
			}
		}
		
		$hours_set = false;
		if(isset($_POST['hours_set']) && $_POST['hours_set']!="") {
			$hours_set = json_decode(stripslashes($_POST['hours_set']), true);
			if(is_array($hours_set)) {
				sort($hours_set);
				$ifeed_execution_hour_index = false;
				for( $i=0; $i<count($hours_set); $i++ ) {
					if(( $duplicated_in_hour==false && $curr_hour == $hours_set[$i] ) ||
					($curr_hour < $hours_set[$i])) {
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
		
		if(isset($query['tag__not_in']) && $query['tag__not_in']!="") {
			$tags_not_array_string = $query['tag__not_in'];
			$tags_not_array_string = preg_replace('/\s+/', '', $tags_not_array_string);
			$tags_not_array = explode(",", $tags_not_array_string);
			$query['tag__not_in'] = $tags_not_array;
			// foreach($tags_not_array as $tag_not_slug) {
				// if(!function_exists('get_term_by')) break;
				// var_dump($tag_not_slug);
				// $tag_not_obj = get_term_by('slug', $tag_not_slug);
				// var_dump($tag_not_obj);
				// // $query['tag__not_in'][] = $tag_not_obj->term_id;
			// }
		}
		
		// if(isset($query['post__not_in']) && $query['post__not_in']=="sticky" ) {
			// $query['post__not_in'] = get_option( 'sticky_posts' );
			// $query['ignore_sticky_posts'] = 1;
		// }
		if(isset($query['post__not_in']) && strlen($query['post__not_in'])>0) {
			$posts_not_array_string = preg_replace('/\s+/', '', $query['post__not_in']);
			$query['post__not_in'] = explode(',', $posts_not_array_string);
			$query['ignore_sticky_posts'] = 1;
			$query['caller_get_posts'] = 1;
		}
		
		$query['post_type'] = array('post', 'page');
		
		$posts = null;
		try{
			// wp_reset_query();
			$posts = new WP_Query($query);
			// var_dump($query);
			// var_dump($posts);
		} catch(Exception $e) { echo "Exception:". $e->getMessage();}
		$index=0;
		if ( strlen(serialize($posts))>0 &&  $posts->have_posts() ) :
			while ( $posts->have_posts() ) : $posts->the_post();

				// if( in_array(get_the_ID(), $query['post__not_in'] ) ) continue;
				$execution_string = "Unknown";
				if($hours_set!==false) {
					$execution_string = $ifeed_execution_date->format('Y-m-d') .' '. $hours_set[$ifeed_execution_hour_index].':00';

					$ifeed_execution_hour_index++;
					if( $ifeed_execution_hour_index == count($hours_set) ) {
						$ifeed_execution_hour_index=0;
						$ifeed_execution_date->modify('+1 day');
					}
				}
				$index = (isset($_POST['data_id']))? $_POST['data_id'] : $index;
				ifeed_print_post_row(get_the_ID(), get_the_title(), get_the_permalink(), gmdate("Y-m-d", get_the_time('U')), get_the_post_thumbnail(null,'thumbnail'), $index, $execution_string, (!isset($query['p'])));
				$index++;
			endwhile;
			echo "<input type='hidden' name='exact_query' value=\"".$posts->request."\"/>";
			wp_reset_query();
		else :
			die("empty_post");
		endif;
		die();
	}
}


if(function_exists('ifeed_print_post_row')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_print_post_row"') );} else {
	function ifeed_print_post_row($id, $title, $permalink, $created_time, $thumbnail, $index, $execution_string, $with_tr=true, $actions=true) {
		if($with_tr) : ?>
		<tr>
			<?php endif; ?>
			<?php if($actions) { ?>
			<td class="post-id-wrapper"><input type="text" value="<?php echo $id ?>" /></td>
			<?php } else { ?>
			<td class="post-id-wrapper"><?php echo $id ?></td>
			<?php } ?>
			<td class='title-wrapper'><a title="<?php echo $title ?>" href="<?php echo $permalink ?>"><?php echo $title ?></a></td>
			<td><?php echo $created_time; ?></td>
			<td class='img-wrapper'><?php echo $thumbnail ?></td>
			<?php if($actions) { ?>
			<td class='execution-time-wrapper'><input type="text" data-name="exec-time" data-id="<?php echo $index; ?>" value="<?php echo $execution_string; ?>" /></td>
			<?php } else { ?>
			<td class='execution-time-wrapper'><?php echo $execution_string; ?></td>
			<?php } ?>
			<?php if($actions) { ?>
			<td class='remove'><button type="button" data-action="remove-post-query" class="button-secondary"><?php _e("X"); ?></button></td>
			<?php } ?>
			<?php if( $with_tr ) : ?>
		</tr>
		<?php
		endif;
	}
}
?>