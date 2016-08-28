<?php
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

		$hours_set = false;
		if(isset($_POST['hours_set']) && $_POST['hours_set']!="") {
			$hours_set = json_decode(stripslashes($_POST['hours_set']), true);
			if(is_array($hours_set)) {
				sort($hours_set);
				$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
				$curr_hour = (int)$now->format('H');
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
		
		if(isset($query['post__not_in']) && $query['post__not_in']=="sticky" ) {
			$query['post__not_in'] = get_option( 'sticky_posts' );
			$query['ignore_sticky_posts'] = 1;
		}
		
		$query['caller_get_posts'] = 1;
		
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