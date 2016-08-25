<?php
if ( !class_exists( 'WP_List_Table_ifeed' ) ) {
	include_once( plugin_dir_path( __FILE__ ) . 'class-wp-list-table-ifeed.php' );
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
				'delete' => sprintf( '<a href="?page=%s&action=%s&ifeed=%s&_wpnonce=%s">Delete</a>', esc_attr( $_REQUEST['page'] ), 'delete', absint( $item['id'] ), $delete_nonce )
			];
			
			$preview_text = $title;
			if($column_name!='slug') $preview_text .= $this->row_actions( $actions );
			return $preview_text;
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
			case 'manual':
			case 'active':
				return $item[ $column_name ];
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