<?php
function ifeed_create_table_db($table_name) {
	
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE " . $table_name . " (
		id INT NOT NULL AUTO_INCREMENT, 
		slug TEXT NOT NULL,
		utm TEXT,
		description TEXT,
		manual INT NOT NULL,
		query TEXT NOT NULL,
		active INT NOT NULL,
		PRIMARY KEY  (id)
	) ". $charset_collate .";";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}


/** Function to handle save on DB get array of posted values,
parse here then store. If DB table not found it will create **/
if(function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_save_options_db"') );} else {
	function ifeed_save_options_db($vals) {
		global $wpdb;
		$table_name = $wpdb->prefix.'ifeed';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			//table not in database. Create new table
			if(!function_exists('ifeed_create_table_db')) {wp_die( __('iFeed-error: function not found: "ifeed_create_table_db"') );} else {
				ifeed_create_table_db($table_name);
			}
		} else {
		}
		
		$is_manual = (isset($vals['ifeed-query-manual']))? 1 : 0;
		
		$query_vars = array(
			'slug' => $vals['ifeed-slug'],
			'utm' => $vals['ifeed-utm'],
			'description' => $vals['ifeed-desc'],
			'manual' => $is_manual,
			'query' => ($is_manual===1)? $vals['ifeed-manual-posts'] : $vals['ifeed-auto-query'],
			'active' => $vals['ifeed-active']
		);
			
		$query_format = array(
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%d'
		);
		
		if( isset($vals['ifeed_id']) && intval($vals['ifeed_id'])>0 ) {
			$query_vars['id'] = intval($vals['ifeed_id']);
			$query_format[] = '%d';
		}
		
		$wpdb->replace($table_name, $query_vars, $query_format);
		return $wpdb->insert_id;
	}
}


function ifeed_get_options_db($ifeed_id) {
	if($ifeed_id==null || $ifeed_id=="" || intval($ifeed_id)<0)
		$ifeed_id = 0;
	global $wpdb;
	$table_name = $wpdb->prefix.'ifeed';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		if(!function_exists('ifeed_create_table_db')) {wp_die( __('iFeed-error: function not found: "ifeed_create_table_db"') );} else {
			ifeed_create_table_db($table_name);
		}
		return null;
	}
	$result = null;
	$query = "SELECT * FROM ".$table_name." WHERE id = ".$ifeed_id;
	try {
		$result = $wpdb->get_row( $query, ARRAY_A);
		/** parse data **/
		$result['ifeed-slug'] = isset($result['slug'])? $result['slug'] : "";
		$result['ifeed-utm'] = isset($result['utm'])? $result['utm'] : "";
		$result['ifeed-desc'] = isset($result['description'])? $result['description'] : "";
		$result['ifeed-active'] = isset($result['active'])? $result['active'] : "";
		if(isset($result['manual']) && $result['manual']=="1") {
			$result['ifeed-query-manual'] = "1";
			$result['ifeed-manual-posts'] = isset($result['query'])? $result['query'] : "";
		} else {
			$result['ifeed-auto-query'] = isset($result['query'])? $result['query'] : "";
		}
		
	} catch (Exception $e) {wp_die( __('iFeed-error: DB error for query "'.$query.'" exception="'.$e->getMessage().'" ') );}
	return $result;
}
?>