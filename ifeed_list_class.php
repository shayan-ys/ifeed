<?php
if ( !class_exists( 'WP_List_Table_ifeed' ) ) {
	include_once( plugin_dir_path( __FILE__ ) . '/includes/class-wp-list-table-ifeed.php' );
	if(!class_exists( 'WP_List_Table_ifeed' )) {wp_die( __('iFeed-error: class not found: "WP_List_Table_ifeed"') );}
}

if ( class_exists( 'Ifeed_List' ) ) {wp_die( __('iFeed-error: duplicate class found: "Ifeed_List"') );}else{
	class Ifeed_List extends WP_List_Table_ifeed {

		/** Class constructor */
		public function __construct() {

			parent::__construct( [
				'singular' => __( 'Ifeed', 'sp' ), //singular name of the listed records
				'plural'   => __( 'Ifeeds', 'sp' ), //plural name of the listed records
				'ajax'     => false //should this table support ajax?

			] );
		}
		
		/**
		* Retrieve ifeed’s data from the database
		*
		* @param int $per_page
		* @param int $page_number
		*
		* @return mixed
		*/
		public static function get_ifeeds( $per_page = 5, $page_number = 1 ) {

			global $wpdb;

			$sql = "SELECT * FROM {$wpdb->prefix}ifeed";

			if ( ! empty( $_REQUEST['orderby'] ) ) {
				$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
				$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
			}

			$sql .= " LIMIT $per_page";

			$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

			$result = $wpdb->get_results( $sql, 'ARRAY_A' );

			return $result;
		}
		
		/**
		* Delete an ifeed record.
		*
		* @param int $id ifeed ID
		*/
		public static function delete_ifeed( $id ) {
			global $wpdb;

			$wpdb->delete(
				"{$wpdb->prefix}ifeed",
				[ 'id' => $id ],
				[ '%d' ]
			);
		}
		
		/**
		* Returns the count of records in the database.
		*
		* @return null|string
		*/
		public static function record_count() {
			global $wpdb;

			$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ifeed";

			return $wpdb->get_var( $sql );
		}
		
		/** Text displayed when no ifeed data is available */
		public function no_items() {
			_e( 'No ifeeds avaliable.', 'sp' );
		}
		
		/**
		* Method for name column
		*
		* @param array $item an array of DB data
		*
		* @return string
		*/
		function column_name( $item, $column_name ) {

			// create a nonce
			$delete_nonce = wp_create_nonce( 'sp_delete_ifeed' );
			
			if($column_name=='slug') {
				$title = '<strong>' . $item['slug'] . '</strong>';
			} else {
				$item['slug'] = ucfirst($item['slug']);
				$title = '<strong>' . sprintf( '<a href="?page=%s&action=%s&ifeed=%s">'. $item['slug'] .'</a>', esc_attr( $_REQUEST['page']."-edit" ), 'edit', absint( $item['id'] ) ) . '</strong>';
			}
			

			$actions = [
				'edit' => sprintf( '<a href="?page=%s&action=%s&ifeed=%s">Edit</a>', esc_attr( $_REQUEST['page']."-edit" ), 'edit', absint( $item['id'] ) ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&ifeed=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce ),
				'link' => sprintf( '<a href="%s">Preview</a>', esc_url(get_bloginfo('rss2_url')."ifeed?title=".$item['slug']) )
			];
			
			$preview_text = $title;
			if($column_name!='slug') $preview_text .= $this->row_actions( $actions );
			return $preview_text;
		}
		
		function get_last_run($item) {
			if( !isset($item['log_posts']) ) return __("Unknown");
			$log_posts = json_decode($item['log_posts'], true);
			if( !is_array($log_posts) || count($log_posts)<1 ) return __("Never");
			$last_log = end($log_posts);
			if( !isset($last_log['added_time']) ) return __("Unknown");
			return $last_log['added_time'];
		}
		
		function get_last_modified_by($item) {
			if( !isset($item['log_posts']) ) return __("Unknown");
			$log_posts = json_decode($item['log_posts'], true);
			if( !is_array($log_posts) || count($log_posts)<1 ) return __("Never Changed");
			$last_log = end($log_posts);
			if( !isset($last_log['last_modified_agent']) ) return __("Unknown");
			$last_agent = json_decode($last_log['last_modified_agent'] ,true);
			if( !isset($last_agent['user_id']) ) return __("Unknown");
			$last_user = get_userdata( intval($last_agent['user_id']) );
			if( !$last_user ) return __("Unknown");
			return $last_user->display_name;
		}
		
		function get_next_run_auto($item) {
			$now = new DateTime(null, new DateTimeZone('Asia/Tehran'));
			$curr_hour = (int)$now->format('H');
			$curr_day_hour = $now->format('Y-m-d H');
			
			$log_posts = (isset($item['log_posts']) && $item['log_posts']!=null)? json_decode($item['log_posts'], true) : array();
			$duplicated_in_hour = false;
			foreach($log_posts as $log_post) {
				if( isset($log_post['promissed_exec_time']) && date( "Y-m-d H", strtotime($log_post['promissed_exec_time']) ) == $curr_day_hour ) {
					$duplicated_in_hour = "duplicated_in:".$log_post['promissed_exec_time'];
				}
			}			
			
			$hours_set = false;
			if( !isset($item['query']) ) return __("No query");

			$query = json_decode($item['query'], true);
			if( !isset($query['hours_set']) ) return __("Nothing");

			$hours_set = json_decode(stripslashes($query['hours_set']), true);
			if( !is_array($hours_set) ) return __("No hours set");
			
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
			
			unset($query['hours_set']);
			
			if(isset($item['exact_query']) && strlen($item['exact_query'])>10 && function_exists('ifeed_get_by_query_db')) {
				$item['exact_query'] = substr_replace( $item['exact_query'], "LIMIT ".$item['offset'].", 1;", strpos($item['exact_query'], "LIMIT") );
				$posts = ifeed_get_by_query_db(stripslashes($item['exact_query']));
				$count = ifeed_get_by_query_db("SELECT FOUND_ROWS();");
				if(is_array($count)) {
					$count = reset($count);
					if(is_array($count))
						$count = reset($count);
						if(is_array($count))
							$count = reset($count);
				}
				$count = intval($count) - intval($item['offset']);
				// return "query: ".stripslashes($item['exact_query'])."<hr/>";
				$next_post = reset($posts);
				if( isset($next_post['ID']) ) {
					$next_post_id = $next_post['ID'];
					$execution_string = "Unknown";
					if($hours_set!==false) {
						$execution_string = $ifeed_execution_date->format('Y-m-d') .' '. $hours_set[$ifeed_execution_hour_index].':00';
					}
					
					$post_array = array(
						'id' => $next_post_id,
						'title' => get_the_title($next_post_id),
						'permalink' => get_the_permalink($next_post_id),
						'thumbnail' => get_the_post_thumbnail($next_post_id,'thumbnail'),
						'exec_time' => $execution_string,
						'count' => $count,
						'offset' => intval($item['offset'])
					);
					return $post_array;
				}
			}
			
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
			
			if(isset($query['post__not_in']) && strlen($query['post__not_in'])>0) {
				$posts_not_array_string = preg_replace('/\s+/', '', $query['post__not_in']);
				$query['post__not_in'] = explode(',', $posts_not_array_string);
				$query['ignore_sticky_posts'] = 1;
				$query['caller_get_posts'] = 1;
			}
			
			$posts = null;
			try{
				// wp_reset_query();
				$posts = new WP_Query($query);
				// var_dump($query);
				// var_dump($posts);
			} catch(Exception $e) { echo "Exception:". $e->getMessage();}
			$index=0;
			if ( strlen(serialize($posts))>0 &&  $posts->have_posts() ) :
				$count = $posts->found_posts;
				while ( $posts->have_posts() ) : $posts->the_post();

					// if( in_array(get_the_ID(), $query['post__not_in'] ) ) continue;
					$execution_string = "Unknown";
					if($hours_set!==false) {
						$execution_string = $ifeed_execution_date->format('Y-m-d') .' '. $hours_set[$ifeed_execution_hour_index].':00';
					}
					
					$post_array = array(
						'id' => get_the_ID(),
						'title' => get_the_title(),
						'permalink' => get_the_permalink(),
						'thumbnail' => get_the_post_thumbnail(null,'thumbnail'),
						'exec_time' => $execution_string,
						'count' => $count
					);
					break;

				endwhile;
				wp_reset_query();
				return $post_array;
			else :
				return "Empty Post";
			endif;			
		}
		
		function get_next_run_manual($item) {
			if(!isset($item['query']) || strlen($item['query'])<1) return "Nothing";
			$manual_posts = json_decode($item['query'], true);
			if(count($manual_posts)<1) return __("Nothing");
			$count = count($manual_posts);
			$manual_post_next = reset($manual_posts);
			if(!isset($manual_post_next['exec_time'])) return __("Unknown");
			
			$post_array = array(
				'id' => $manual_post_next['post_id'],
				'title' => get_the_title($manual_post_next['post_id']),
				'permalink' => get_the_permalink($manual_post_next['post_id']),
				'thumbnail' => get_the_post_thumbnail($manual_post_next['post_id'],'thumbnail'),
				'exec_time' => $manual_post_next['exec_time'],
				'count' => $count
			);
			return $post_array;
		}
		
		/**
		* Render a column when no column specific method exists.
		*
		* @param array $item
		* @param string $column_name
		*
		* @return mixed
		*/
		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
			case 'slug':
				return $this->column_name($item,$column_name);
			case 'name':
				return $this->column_name($item,$column_name);
			case 'utm':
			case 'description':
				return $item[ $column_name ];
			case 'manual':
				return ($item[ $column_name ]==1)? "Manual" : "Auto";
			case 'active':
				return ($item[ $column_name ]==1)? "<span class='ifeed-list-active'>1</span>" : "<span class='ifeed-list-deactive'>0</span>";
			case 'last_modified_by':
				return $this->get_last_modified_by($item);
			case 'last_run':
				return $this->get_last_run($item);
			case 'next_run':
			{
				if( intval($item['manual'])==1 ) {
					$post = $this->get_next_run_manual($item);
					if( is_array($post) && isset($post['exec_time']) ) { return $post['exec_time']; } else return $post;
				} else {
					$post = $this->get_next_run_auto($item);
					if( is_array($post) && isset($post['exec_time']) ) { return $post['exec_time']; } else return $post;
				}
			}			
			case 'next_post_id':
			{
				if( intval($item['manual'])==1 ) {
					$post = $this->get_next_run_manual($item);
					if( is_array($post) && isset($post['id']) ) { return $post['id']; } else return $post;
				} else {
					$post = $this->get_next_run_auto($item);
					if( is_array($post) && isset($post['id']) ) { return $post['id']; } else return $post;
				}
			}
			case 'next_post':
			{
				if( intval($item['manual'])==1 ) {
					$post = $this->get_next_run_manual($item);
					if( is_array($post) && isset($post['permalink']) ) { return "<a href='".$post['permalink']."' title='".$post['title']."'>".$post['title']."</a>"; } else return $post;
				} else {
					$post = $this->get_next_run_auto($item);
					if( is_array($post) && isset($post['permalink']) ) { return "<a href='".$post['permalink']."' title='".$post['title']."'>".$post['title']."</a>"; } else return $post;
				}
			}
			case 'queue_size':
			{
				if( intval($item['manual'])==1 ) {
					$post = $this->get_next_run_manual($item);
					if( is_array($post) && isset($post['count']) ) { return $post['count']; } else return $post;
				} else {
					$post = $this->get_next_run_auto($item);
					if( is_array($post) && isset($post['count']) ) { return $post['count']; } else return $post;
				}
			}
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
			}
		}
		
		/**
		* Render the bulk edit checkbox
		*
		* @param array $item
		*
		* @return string
		*/
		function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
			);
		}
		
		/**
		*  Associative array of columns
		*
		* @return array
		*/
		function get_columns() {
			$columns = [
				'cb'			=> '<input type="checkbox" />',
				'name'			=> __( 'Name', 'sp' ),
				'slug'			=> __( 'Slug', 'sp' ),
				'description'	=> __( 'Description', 'sp' ),
				'last_modified_by'	=> __( 'Last Modified By', 'sp' ),
				'last_run'		=> __( 'Last Run', 'sp' ),
				'next_run'		=> __( 'Next Run', 'sp' ),
				'next_post_id'	=> __( 'Next Post ID', 'sp' ),
				'next_post'		=> __( 'Next Post', 'sp' ),
				'queue_size'	=> __( 'Queue Size', 'sp' ),
				'manual'		=> __( 'Auto/Manual', 'sp' ),
				'active'		=> __( 'Active', 'sp' )
			];

			return $columns;
		}
		
		/**
		* Columns to make sortable.
		*
		* @return array
		*/
		public function get_sortable_columns() {
			$sortable_columns = array(
				'slug' => array( 'slug', true ),
				'manual' => array( 'manual', false ),
				'active' => array( 'active', false )
			);

			return $sortable_columns;
		}
		
		/**
		* Returns an associative array containing the bulk action
		*
		* @return array
		*/
		public function get_bulk_actions() {
			$actions = [
				'bulk-delete' => 'Delete'
			];

			return $actions;
		}
		
		/**
		* Handles data query and filter, sorting, and pagination.
		*/
		public function prepare_items() {

			$this->_column_headers = $this->get_column_info();

			/** Process bulk action */
			$this->process_bulk_action();

			$per_page     = $this->get_items_per_page( 'ifeeds_per_page', 5 );
			$current_page = $this->get_pagenum();
			$total_items  = self::record_count();

			$this->set_pagination_args( [
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'    => $per_page //WE have to determine how many items to show on a page
			] );


			$this->items = self::get_ifeeds( $per_page, $current_page );
		}
		
		public function process_bulk_action() {

			//Detect when a bulk action is being triggered...
			if ( 'delete' === $this->current_action() ) {

				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'sp_delete_ifeed' ) ) {
					die( 'Not allowed, security error' );
				}
				else {
					self::delete_ifeed( absint( $_GET['ifeed'] ) );

					wp_redirect( esc_url( add_query_arg() ) );
					exit;
				}

			}

			// If the delete bulk action is triggered
			if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
				|| ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
			) {

				$delete_ids = esc_sql( $_POST['bulk-delete'] );

				// loop over the array of record IDs and delete them
				foreach ( $delete_ids as $id ) {
					self::delete_ifeed( $id );
				}

				wp_redirect( esc_url( add_query_arg() ) );
				exit;
			}
		}	
	}
}