<?php
function ifeed_create_table_db($table_name) {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	
	$sql = "CREATE TABLE " . $table_name . " (
		id INT NOT NULL AUTO_INCREMENT, 
		slug TEXT NOT NULL,
		utm TEXT,
		description TEXT,
		manual INT NOT NULL,
		query TEXT NOT NULL,
		exact_query TEXT,
		offset INT DEFAULT 0,
		online_posts TEXT,
		log_posts TEXT,
		agent TEXT NOT NULL,
		active INT NOT NULL,
		PRIMARY KEY  (id)
	) ". $charset_collate .";";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function ifeed_delete_table_db() {
	global $wpdb;
	$table_name = $wpdb->prefix.'ifeed';
	$sql = "DROP TABLE IF_EXISTS $table_name;";
	return $wpdb->query($sql);
}

/** Function to handle save on DB get array of posted values,
parse here then store. If DB table not found it will create **/
if(function_exists('ifeed_save_options_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_save_options_db"') );} else {
	function ifeed_save_options_db($vals) {
		global $wpdb, $current_user;
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
			'exact_query' => (isset($vals['exact_query']) && $vals['exact_query']!=null && $vals['exact_query']!="" && $vals['exact_query']!=false)? $vals['exact_query'] : "",
			'agent' => json_encode(array(
				'user_id' => get_current_user_id(),
				'user_display_name' => $current_user->display_name,
				'http_user_agent' => $_SERVER['HTTP_USER_AGENT']
			)),
			'active' => $vals['ifeed-active']
		);
			
		$query_format = array(
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%d'
		);
		
		$query_type = "insert";
		
		if( isset($vals['ifeed_id']) && intval($vals['ifeed_id'])>0 ) {
			$ifeed_ID = intval($vals['ifeed_id']);
			$query_type = "update";
			if( isset($vals['offset']) && $vals['offset']!=null && $vals['offset']!="" && $vals['offset']!==false ) {
				$query_vars['offset'] = intval($vals['offset']);
				$query_format[] = '%d';
			}
		} else {
			// new record and not update
			$query_vars['offset'] = intval(0);
			$query_format[] = '%d';
		}
		
		if($query_type=="insert") {
			$wpdb->replace($table_name, $query_vars, $query_format);
			return $wpdb->insert_id;
		} elseif($query_type=="update") {
			if( !function_exists('ifeed_update_options_db') ) {wp_die( __('iFeed-error: function not found, include function: "ifeed_update_options_db"') );} else {
				$result = ifeed_update_options_db($ifeed_ID, $query_vars, $query_format);
				if($result)
					return $ifeed_ID;
			}
			return false;
		}
		// echo $wpdb->last_query;
	}
}
if(function_exists('ifeed_get_by_query_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_get_by_query_db"') );} else {
	function ifeed_get_by_query_db($query, $output=ARRAY_A) {
		global $wpdb, $current_user;
		$table_name = $wpdb->prefix.'ifeed';

		try {
			$results = $wpdb->get_results( $query, $output );
			return $results;
		} catch(Exception $e) {return false;}
	}
}
if(function_exists('ifeed_update_options_db')) {wp_die( __('iFeed-error: Duplicate function name, remove function: "ifeed_update_options_db"') );} else {
	function ifeed_update_options_db($ifeed_ID, $query_vars, $query_format) {
		global $wpdb, $current_user;
		$table_name = $wpdb->prefix.'ifeed';
		
		try{
			$wpdb->update(
				$table_name, 
				$query_vars,
				array('id' => $ifeed_ID),
				$query_format,
				array('%d')
			);
			return true;
		} catch(Exception $e) {return false;}
	}
}

function ifeed_get_value_db($key, $value) {
	if($key==null || $key=="" || $value===null)
		return false;
	
	global $wpdb;
	$table_name = $wpdb->prefix.'ifeed';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		if(!function_exists('ifeed_create_table_db')) {wp_die( __('iFeed-error: function not found: "ifeed_create_table_db"') );} else {
			ifeed_create_table_db($table_name);
		}
	}
	$result = false;
	$table_key = "id";
	if($key==="slug") $table_key = "slug";
	$query = $wpdb->prepare( 
			"SELECT * FROM ".$table_name." WHERE `".$table_key."` = \"%s\"", 
			$value 
	);
	try {
		$result = $wpdb->get_row( $query, ARRAY_A);
		// if($result==null || $result=="") throw new Exception("empty result");
	} catch (Exception $e) {wp_die( __('iFeed-error: DB error for query "'.$query.'" exception="'.$e->getMessage().'" ') );}
	return $result;
}

function ifeed_get_options_db($ifeed_id, $output=ARRAY_A) {

	global $wpdb;
	$table_name = $wpdb->prefix.'ifeed';
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		if(!function_exists('ifeed_create_table_db')) {wp_die( __('iFeed-error: function not found: "ifeed_create_table_db"') );} else {
			ifeed_create_table_db($table_name);
		}
	}
	$result = null;
	$query_by_id = "SELECT * FROM ".$table_name." WHERE id = ".$ifeed_id;
	$query_all = "SELECT * FROM ".$table_name;
	try {
		if($ifeed_id!=null && $ifeed_id!="" && intval($ifeed_id)>=0 && !is_array($ifeed_id) ) {
			$result = $wpdb->get_row( $query_by_id, ARRAY_A);
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
		} elseif(is_array($ifeed_id)) {
			$ifeed_custom_q = $ifeed_id;
			$result = $wpdb->get_results($query_all." WHERE `".current(array_keys($ifeed_custom_q))."` = '".current($ifeed_custom_q)."'"
				, $output);
		} else {
			$result = $wpdb->get_results($query_all, $output);
		}
		
	} catch (Exception $e) {wp_die( __('iFeed-error: DB error for query "'.$query.'" exception="'.$e->getMessage().'" ') );}
	return $result;
}
?>