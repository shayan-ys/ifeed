<?php
if(function_exists('ifeed_refresher')) {die( __('iFeed-error: Duplicate function name, remove function: "ifeed_refresher"') );} else {
	function ifeed_refresher() {
		if( !function_exists('ifeed_get_options_db') ) {die( __('iFeed-error: function not found, include function: "ifeed_get_options_db"') );} else {
			$ifeeds = ifeed_get_options_db(array('active'=>1));
			echo "Welcom to iFeed Refresher script (don't worry no one else can see this script)";
			$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
			// $now->setDate( $now->format("Y"), "09", "01" );
			// $now->setTime( "16", $now->format("i"), $now->format("s") );
			echo "<hr />Current time is: ". $now->format("Y-m-d H:i:s")."<br />";
			echo "<pre style='direction:ltr!important; text-align:left!important;'>";
			foreach($ifeeds as $key=>$ifeed) {
				echo "<hr /><hr />active ifeed_id=".$ifeed['id'];
				if( intval($ifeed['manual'])==1 ) {
					// is manual.
					echo " is manual";
					$manual_posts = json_decode($ifeed['query'], true);
					if(count($manual_posts)<1) {continue; die("continue command didn't work");}
					$online_posts = (isset($ifeed['online_posts']) && $ifeed['online_posts']!=null)? json_decode($ifeed['online_posts'], true) : array();
					$log_posts = (isset($ifeed['log_posts']) && $ifeed['log_posts']!=null)? json_decode($ifeed['log_posts'], true) : array();
					$manual_post_next = reset($manual_posts);
					if(isset($manual_post_next['exec_time'])) { $manual_post_next['exec_time'] = date("Y-m-d H:00", strtotime($manual_post_next['exec_time'])); }
					$curr_day_hour = $now->format('Y-m-d H:00');
					// echo $curr_day_hour;
					if( isset($manual_post_next['exec_time']) && $curr_day_hour == $manual_post_next['exec_time'] ) {
						// now it's the time!
						echo "<br />now its the time!";
						if( in_array($manual_post_next['post_id'], $online_posts) ) {
							// duplicate post, exact same post is online
							echo "<br/>duplicate post, exact same post is online";
							continue; die("continue command didn't work");
						}
						// pop from beginning of manual_posts queue
						array_shift($manual_posts);
						if( count($online_posts) >= $ifeed['visible_size'] ) {
							$current_count = count($online_posts);
							for($i=0; $i< $current_count-$ifeed['visible_size']+1; $i++ ) {
								// pop from beginning of online_posts
								array_shift($online_posts);
							}
						}
						
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
						if( !function_exists('ifeed_update_options_db') ) {die( __('iFeed-error: function not found, include function: "ifeed_update_options_db"') );} else {
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
					echo " is auto<br/>";
					$query = json_decode($ifeed['query'], true);
					$offset = $ifeed['offset'];
					$hours_set = json_decode($query['hours_set']);
					unset($query['hours_set']);
					$curr_hour = (int)$now->format('H');
					$curr_day_hour = $now->format('Y-m-d H:00');
					
					$log_posts = (isset($ifeed['log_posts']) && $ifeed['log_posts']!=null)? json_decode($ifeed['log_posts'], true) : array();
					$duplicated_in_hour = false;
					foreach($log_posts as $log_post) {
						if( isset($log_post['promissed_exec_time']) && date( "Y-m-d H:00", strtotime($log_post['promissed_exec_time']) ) == $curr_day_hour )
							$duplicated_in_hour = "duplicated_in:".$log_post['promissed_exec_time'];
					}
					
					// var_dump($query);
					if( in_array($curr_hour, $hours_set) && $duplicated_in_hour==false ) {
						// it might be the time, also not equal to last run saved in log
						
						$online_posts = (isset($ifeed['online_posts']) && $ifeed['online_posts']!=null)? json_decode($ifeed['online_posts'], true) : array();
						
						$posts = null;
						$next_post_id = false;
						try{
							if(isset($ifeed['exact_query']) && strlen($ifeed['exact_query'])>10) {
								$ifeed['exact_query'] = substr_replace( $ifeed['exact_query'], "LIMIT ".$offset.", 1;", strpos($ifeed['exact_query'], "LIMIT") );
								$posts = ifeed_get_by_query_db(stripslashes($ifeed['exact_query']));
								echo "<br/>query: ".stripslashes($ifeed['exact_query'])."<hr/>";
								$next_post = reset($posts);
								if( isset($next_post['ID']) )
									$next_post_id = $next_post['ID'];
								echo "next_post_id from exact_query=". $next_post_id;
							}
								
							if($next_post_id==false) {
								$posts = new WP_Query($query);
								if ( strlen(serialize($posts))>0 &&  $posts->have_posts() ) :
									while ( $posts->have_posts() ) : $posts->the_post();
										$next_post_id = get_the_ID();
										echo "next_post_id from WP_Query=".$next_post_id;
										break;
									endwhile;
								else :
									continue; die("empty_post");
								endif;								
							}
						} catch(Exception $e) {echo "Exception:". $e->getMessage(); die();}
						
						if($next_post_id !== false) {
							
							if( in_array($next_post_id, $online_posts) ) {
								// duplicate post, exact same post is online
								echo "<br/>duplicate post, exact same post is online";
								continue; die("continue command didn't work");
							}
							if( count($online_posts) >= $ifeed['visible_size'] ) {
								$current_count = count($online_posts);
								for($i=0; $i< $current_count-$ifeed['visible_size']+1; $i++ ) {
									// pop from beginning of online_posts
									array_shift($online_posts);
								}
							}
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
									'offset' => ($offset+1)
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
					else if( $duplicated_in_hour!==false ) { echo $duplicated_in_hour."<br />"; }
				}
			}
			echo "</pre>";
			die();
		}
		return $output;
	}
}
?>