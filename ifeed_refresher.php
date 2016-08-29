<?php
if(function_exists('ifeed_refresher')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_refresher"') );} else {
	function ifeed_refresher() {
		if( !function_exists('ifeed_get_options_db') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_get_options_db"') );} else {
			$ifeeds = ifeed_get_options_db(array('active'=>1));
			echo "hello world";
			echo "<pre style='direction:ltr!important; text-align:left!important;'>";
			foreach($ifeeds as $key=>$ifeed) {
				echo "active ifeed_id=".$ifeed['id']. "<br />";
				if( intval($ifeed['manual'])==1 ) {
					// is manual
					$manual_posts = json_decode($ifeed['query'], true);
					if(count($manual_posts)<1) continue; return null;
					$online_posts = (isset($ifeed['online_posts']) && $ifeed['online_posts']!=null)? json_decode($ifeed['online_posts'], true) : array();
					$log_posts = (isset($ifeed['log_posts']) && $ifeed['log_posts']!=null)? json_decode($ifeed['log_posts'], true) : array();
					$manual_post_next = reset($manual_posts);
					$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
					$curr_day_hour = $now->format('Y-m-d H:00');
					echo $curr_day_hour;
					if( isset($manual_post_next['exec_time']) && $curr_day_hour == $manual_post_next['exec_time'] ) {
						// now it's the time!
						echo "<br />now its the time!";
						array_shift($manual_posts);
						// pop from beginning of online_posts
						array_shift($online_posts);						
						// add at end of online_posts
						array_push($online_posts, intval($manual_post_next['post_id']));
						// log this addition to ifeed
						array_push($log_posts, array(
							'post_id' => $manual_post_next['post_id'],
							'promissed_exec_time' => $manual_post_next['exec_time'],
							'added_time' => $now->format("Y-m-d H:i:s"),
							'last_modified_agent' => stripslashes($ifeed['agent'])
						));
						echo "<br />added: ".$manual_post_next['post_id'];
						if( !function_exists('ifeed_update_options_db') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_update_options_db"') );} else {
							ifeed_update_options_db($ifeed['id'], array(
								'query' => json_encode($manual_posts),
								'online_posts' => json_encode($online_posts),
								'log_posts' => json_encode($log_posts)
							), array(
								'%s',
								'%s',
								'%s'
							));
							echo "<hr />saved in DB: ";
							echo "<br />query: ";
							var_dump($manual_posts);
							echo "<br />online_posts: ";
							var_dump($online_posts);
							echo "<br />log_posts: ";
							var_dump($log_posts);
						}
					}
				} else {
					// auto query
					$query = json_decode($ifeed['query'], true);
					$hours_set = json_decode($query['hours_set']);
					unset($query['hours_set']);
					$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
					$curr_hour = (int)$now->format('H');
					$curr_day_hour = $now->format('Y-m-d H:00');
					
					$log_posts = (isset($ifeed['log_posts']) && $ifeed['log_posts']!=null)? json_decode($ifeed['log_posts'], true) : array();
					$last_log_post = end($log_posts);
					// var_dump($query);
					if( in_array($curr_hour, $hours_set) &&
						(!isset($last_log_post['promissed_exec_time']) || $last_log_post['promissed_exec_time'] != $curr_day_hour) ) {
						// it might be the time, also not equal to last run saved in log
						
						$online_posts = (isset($ifeed['online_posts']) && $ifeed['online_posts']!=null)? json_decode($ifeed['online_posts'], true) : array();
						
						$query['offset'] = intval($ifeed['offset']);	
						
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
						
						if(isset($query['post__not_in']) && $query['post__not_in'] !== false) {
							$query['post__not_in'] = json_decode($query['post__not_in'], true);
						}
						
						$posts = null;
						$next_post_id = false;
						try{
							$posts = new WP_Query($query);
							// var_dump($query);
							// var_dump($posts);
						} catch(Exception $e) {echo "Exception:". $e->getMessage(); die();}
						if ( strlen(serialize($posts))>0 &&  $posts->have_posts() ) :
							while ( $posts->have_posts() ) : $posts->the_post();
								$next_post_id = get_the_ID();
								break;
							endwhile;
						else :
							die("empty_post");
						endif;
						
						if($next_post_id !== false) {
							// pop from beginning of online_posts
							array_shift($online_posts);						
							// add at end of online_posts
							array_push($online_posts, intval($next_post_id));
							// log this addition to ifeed
							array_push($log_posts, array(
								'post_id' => $next_post_id,
								'promissed_exec_time' => $curr_day_hour,
								'added_time' => $now->format("Y-m-d H:i:s"),
								'last_modified_agent' => stripslashes($ifeed['agent'])
							));
							echo "<br />added: ".$next_post_id;
							if( !function_exists('ifeed_update_options_db') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_update_options_db"') );} else {
								ifeed_update_options_db($ifeed['id'], array(
									'online_posts' => json_encode($online_posts),
									'log_posts' => json_encode($log_posts),
									'offset' => ($query['offset']+1)
								), array(
									'%s',
									'%s',
									'%d'
								));
								echo "<hr />saved in DB: ";
								echo "<br />query: ";
								var_dump($query);
								echo "<br />online_posts: ";
								var_dump($online_posts);
								echo "<br />log_posts: ";
								var_dump($log_posts);
							}							
						}
						
					}
				}
			}
			echo "</pre>";
			die();
		}
		return $output;
	}
}
?>