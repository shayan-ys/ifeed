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
		offset INT DEFAULT 0,
		agent TEXT NOT NULL,
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
			'%d'
		);
		
		$query_type = "insert";
		
		if( isset($vals['ifeed_id']) && intval($vals['ifeed_id'])>0 ) {
			$ifeed_ID = intval($vals['ifeed_id']);
			$query_type = "update";
		} else {
			// new record and not update
			$query_vars['offset'] = intval(0);
			$query_format[] = '%d';
		}
		
		var_dump($vals['offset']);
		// die();
		
		if( isset($vals['offset']) && $vals['offset']!=null && $vals['offset']!="" && $vals['offset']!==false ) {
			$query_vars['offset'] = intval($vals['offset']);
			$query_format[] = '%d';
		}
		
		if($query_type=="insert") {
			$wpdb->replace($table_name, $query_vars, $query_format);
			return $wpdb->insert_id;
		} elseif($query_type=="update") {
			$wpdb->update(
				$table_name, 
				$query_vars,
				array('id' => $ifeed_ID),
				$query_format,
				array('%d')
			);
			return $ifeed_ID;
		}
		// echo $wpdb->last_query;
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
	$query = "SELECT * FROM ".$table_name." WHERE `".$key."` = \"".$value."\"";
	try {
		$result = $wpdb->get_row( $query, ARRAY_A);
	} catch (Exception $e) {wp_die( __('iFeed-error: DB error for query "'.$query.'" exception="'.$e->getMessage().'" ') );}
	return $result;
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


/** Author blog: http://www.wpbeginner.com/wp-themes/how-to-add-user-browser-and-os-classes-in-wordpress-body-class/ **/
function mv_browser_body_class($classes) {
	global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;
	if($is_lynx) $classes[] = 'lynx';
	elseif($is_gecko) $classes[] = 'gecko';
	elseif($is_opera) $classes[] = 'opera';
	elseif($is_NS4) $classes[] = 'ns4';
	elseif($is_safari) $classes[] = 'safari';
	elseif($is_chrome) $classes[] = 'chrome';
	elseif($is_IE) {
		$classes[] = 'ie';
		if(preg_match('/MSIE ([0-9]+)([a-zA-Z0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $browser_version))
		$classes[] = 'ie'.$browser_version[1];
	} else $classes[] = 'unknown';
	if($is_iphone) $classes[] = 'iphone';
	if ( stristr( $_SERVER['HTTP_USER_AGENT'],"mac") ) {
		$classes[] = 'osx';
	} elseif ( stristr( $_SERVER['HTTP_USER_AGENT'],"linux") ) {
		$classes[] = 'linux';
	} elseif ( stristr( $_SERVER['HTTP_USER_AGENT'],"windows") ) {
		$classes[] = 'windows';
	}
	return $classes;
}
?>