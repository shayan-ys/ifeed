var query_presenter = jQuery(".ifeed-preview tbody");

jQuery(document).ready(function($){
	
	if( ! jQuery(".ifeed-post-generator").find('[name="ifeed-query-manual"]').prop('checked') )
		ifeed_update_preview();
	
	if( $("#redirect_url").length ) {
		window.location.href = $("#redirect_url").attr("href");
	}
	
	$(".ifeed-post-generator").find('[data-action="custom-offset"]').prop('checked', false);
	
	$(".ifeed-post-generator").on("change", '[data-name="offset"]', function(){
		var this_val = $(this).val();
		$(".ifeed-post-generator").find('[name="offset"]').val(this_val);
		$(".ifeed-post-generator").find('[data-action="offset-value"]').text(this_val);
		ifeed_update_preview();
	});
	
	$(".ifeed-post-generator").on("change", '[data-action="custom-offset"]', function(){
		if( $(this).prop('checked') ) {
			$(this).closest("td").find('[data-action="reset-offset"]').hide();
			$(this).closest("td").find('[data-name="offset"]').show();
		} else {
			$(this).closest("td").find('[data-action="reset-offset"]').show();
			$(this).closest("td").find('[data-name="offset"]').hide();
		}
	});
	
	$(".ifeed-post-generator").on("click", '[data-action="reset-offset"]', function(){
		ifeed_reset_offset();
		ifeed_update_preview();
	});
	
	$(".ifeed-post-generator").on("click", '[data-action="clear-query"]', function(){
		query_presenter.html(" ");
		ifeed_switchto_manual_query();
	});
	$(".ifeed-query-builder").on("change", 'input,select', function(){
		var data_name = $(this).attr("data-name");
		switch(data_name) {
			case "offset": return;
			case "tag":
			case "tag__not_in":
			case "cat":
			case "time_offset":
			case "orderby":
			case "order":
			case "post__not_in": {ifeed_reset_offset(); break;}
			default: break;
		}
		ifeed_update_preview();
	});
	$(".ifeed-post-generator").on("click", '[data-action="reload-query"]', function(){
		// load posts via query generated
		ifeed_update_preview();
	});
	$(".ifeed-post-generator").on("click", '[data-action="add-post-query"]', function(){
		var security = jQuery('#security').val();
		
		if( ifeed_if_null(ajax_ifeed_load_posts_object) || ifeed_if_null(security) ) return;
		query_presenter.addClass("loading");

		var data_id_max = 0;
		query_presenter.find('[data-name="exec-time"]').each(function(i){
			if( $(this).attr("data-id") > data_id_max ) data_id_max = $(this).attr("data-id");
		});
		data_id_max++;
		
		jQuery.ajax({
			type: 'POST',
			url: ajax_ifeed_load_posts_object.ajaxurl,
			data: {
				'action': 'ifeed_load_posts',
				'security': security,
				'data_id': data_id_max
			},
			success: function (data) {
				query_presenter.append(data);
				query_presenter.removeClass("loading");
				ifeed_switchto_manual_query();
			}
		});
	});
	$(".ifeed-post-generator").on("click", '[data-action="remove-post-query"]', function(){
		$(this).closest("tr").remove();
		ifeed_switchto_manual_query();
	});
	$(".ifeed-post-generator").on("change", '[data-name="exec-time"]', function(){
		var input_id = $(this).attr("data-id");
		var changed_value = $(this).val();
		duplicated_data = false;
		query_presenter.find('[data-name="exec-time"]').each(function(i){
			if( $(this).attr("data-id") != input_id && $(this).val() == changed_value )
				duplicated_data = true;
		});
		if( Date.parse( changed_value ) < $.now() || duplicated_data ) {
			$(this).addClass("error");
			return;
		}
		$(this).removeClass("error");
			
		query_presenter.addClass("loading");
		ifeed_sort_by_date();
		query_presenter.find('[data-id="'+input_id+'"]').val(changed_value);
		query_presenter.removeClass("loading");
		
		ifeed_switchto_manual_query();
	});
	$(".ifeed-post-generator").on("change", '.post-id-wrapper input', function(){
		// update single post row
		var query = {};
		var security = jQuery('#security').val();
		var td_id_wrapper = $(this).closest("td");
		var tr_wrapper = td_id_wrapper.closest("tr");
		var data_id = tr_wrapper.find('[data-name="exec-time"]').attr("data-id");
		var old_exec_time = tr_wrapper.find('[data-name="exec-time"]').val();
		query.post_type = "post";
		query.post_status = "publish";
		query.p = $(this).val();
		query = JSON.stringify(query);
		
		if( ifeed_if_null(ajax_ifeed_load_posts_object) || ifeed_if_null(security) || ifeed_if_null(query) ) return;
		tr_wrapper.addClass("loading");
		
		jQuery.ajax({
			type: 'POST',
			url: ajax_ifeed_load_posts_object.ajaxurl,
			data: {
				'action': 'ifeed_load_posts',
				'security': security,
				'query': query,
				'data_id': data_id
			},
			success: function (data) {
				if( data=="empty_security" ) {
					alert("security error!");
					return;
				}
				tr_wrapper.removeClass("loading");
				if( data=="empty_post" ) {
					td_id_wrapper.find("input").addClass("error");
					return;
				}
				tr_wrapper.html( data );
				tr_wrapper.find('[data-name="exec-time"]').val(old_exec_time);
				ifeed_switchto_manual_query();
			}
		});	
	});
	
	$(".ifeed-post-generator").on("change", '[name="ifeed-query-manual"]', function(){
		if( $(this).prop('checked') ) 
			ifeed_switchto_manual_query();
		else
			$(".ifeed-post-generator").find('[data-action="reload-query"]').click();
	});
});

function ifeed_reset_offset() {
	jQuery(".ifeed-post-generator").find('[data-name="offset"]').val("0");
	jQuery(".ifeed-post-generator").find('[data-name="offset"]').trigger('change');
}

function ifeed_switchto_manual_query() {
	jQuery('[name="ifeed-settings"]').find('[name="ifeed-manual-posts"]').val( ifeed_stringify_manual_posts() );
	jQuery(".ifeed-post-generator").find('[name="ifeed-query-manual"]').prop('checked', true);
}

function ifeed_switchto_auto_query() {
	jQuery(".ifeed-post-generator").find('[name="ifeed-query-manual"]').prop('checked', false);
}

function ifeed_stringify_manual_posts() {
	var manual_posts = [];
	query_presenter.find("tr").each(function(index){
		var tr = {};
		tr.post_id = jQuery(this).find(".post-id-wrapper input").val();
		tr.exec_time = jQuery(this).find('[data-name="exec-time"]').val();
		manual_posts.push(tr);
	});
	return JSON.stringify( manual_posts );
}

function ifeed_sort_by_date() {
	var posts_timestamps = [];
	query_presenter.find("tr").each(function(i){
		var timestring = jQuery(this).find('[data-name="exec-time"]').val();
		var timestamp = Date.parse( timestring );
		posts_timestamps.push( timestamp );
		jQuery(this).attr("data-timestamp", timestamp);
	});
	posts_timestamps.sort();
	
	var sorted_html = "";
	for(i=0; i<posts_timestamps.length; i++) {
		var tr_selector = query_presenter.find('[data-timestamp="'+ posts_timestamps[i] +'"]');
		tr_selector.removeAttr("data-timestamp");
		sorted_html += tr_selector[0].outerHTML;
	}
	
	query_presenter.html(sorted_html);
}

function ifeed_update_preview() {
	var query = ifeed_query_creator();
	query.post_type = "post";
	query.post_status = "publish";
	jQuery('[name="ifeed-settings"]').find('[name="ifeed-auto-query"]').val(JSON.stringify( jQuery.extend({hours_set:JSON.stringify(ifeed_hours_set())}, query) ));
	query.posts_per_page = 10;
	ifeed_load_posts( JSON.stringify(query) );
	ifeed_switchto_auto_query();
}

function ifeed_hours_set() {
	var hours_string = jQuery(".ifeed-post-generator").find('[data-name="hours-array"]').val();
	return ifeed_string_to_int_array(hours_string);
}

function ifeed_string_to_int_array( string ) {
	if( ifeed_if_null(string) ) return;
	var output_array = string.split(',');
	for(var i = 0; i < output_array.length; i++) {
		output_array[i] = parseInt(output_array[i], 10);
	}
	return output_array;	
}

function ifeed_query_creator() {
	var query = {};
	jQuery(".ifeed-post-generator").find(".ifeed-query-builder").each(function(i){
		var key = jQuery(this).attr("data-name");
		var value = jQuery(this).val();
		if( ifeed_if_null(key) || ifeed_if_null(value) ) return;
		query[key] = value;
	});
	return query;
	// return JSON.stringify(query);	
}

function ifeed_if_null( variable ) {
	return (typeof variable=='undefined' || variable=='' || variable.length<1);
}

function ifeed_load_posts(query) {
	var security = jQuery('#security').val();
	if( ifeed_if_null(ajax_ifeed_load_posts_object) || ifeed_if_null(security) || ifeed_if_null(query) ) return;
	query_presenter.addClass("loading");
	
	jQuery.ajax({
		type: 'POST',
		url: ajax_ifeed_load_posts_object.ajaxurl,
		data: {
			'action': 'ifeed_load_posts',
			'security': security,
			'query': query,
			'hours_set': JSON.stringify(ifeed_hours_set())
		},
		success: function (data) {
			if( data=="empty_security" ) {
				alert("security error!");
				return;
			}
			if( data=="empty_post" ) data="";
			
			query_presenter.removeClass("loading");
			query_presenter.html( data );
		}
	});	
}