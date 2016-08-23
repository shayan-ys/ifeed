var query_presenter = jQuery(".ifeed-preview tbody");

jQuery(document).ready(function($){
	ifeed_update_preview();
	$(".ifeed-post-generator").on("click", '[data-action="clear-query"]', function(){
		ifeed_switchto_manual_query();
		query_presenter.html(" ");
	});
	$(".ifeed-post-generator").on("click", '[data-action="reload-query"]', function(){
		// load posts via query generated
		ifeed_update_preview();
	});
	$(".ifeed-post-generator").on("click", '[data-action="add-post-query"]', function(){
		var security = jQuery('#security').val();
		
		if( ifeed_if_null(ajax_ifeed_load_posts_object) || ifeed_if_null(security) ) return;
		ifeed_switchto_manual_query();
		query_presenter.addClass("loading");
		
		jQuery.ajax({
			type: 'POST',
			url: ajax_ifeed_load_posts_object.ajaxurl,
			data: {
				'action': 'ifeed_load_posts',
				'security': security
			},
			success: function (data) {
				query_presenter.append(data);
				query_presenter.removeClass("loading");
			}
		});
	});
	$(".ifeed-post-generator").on("click", '[data-action="remove-post-query"]', function(){
		ifeed_switchto_manual_query();
		$(this).closest("tr").remove();
	});
	$(".ifeed-post-generator").on("change", '[data-name="exec-time"]', function(){
		var input_id = $(this).attr("data-id");
		var changed_value = $(this).val();
		ifeed_switchto_manual_query();
		
		query_presenter.addClass("loading");
		ifeed_sort_by_date();
		query_presenter.find('[data-id="'+input_id+'"]').val(changed_value);
		query_presenter.removeClass("loading");
	});
	$(".ifeed-post-generator").on("change", '.post-id-wrapper input', function(){
		// update single post row
		var query = {};
		var security = jQuery('#security').val();
		var td_id_wrapper = $(this).closest("td");
		query.post_type = "post";
		query.post_status = "publish";
		query.p = $(this).val();
		query = JSON.stringify(query);
		
		if( ifeed_if_null(ajax_ifeed_load_posts_object) || ifeed_if_null(security) || ifeed_if_null(query) ) return;
		ifeed_switchto_manual_query();
		td_id_wrapper.closest("tr").addClass("loading");
		
		jQuery.ajax({
			type: 'POST',
			url: ajax_ifeed_load_posts_object.ajaxurl,
			data: {
				'action': 'ifeed_load_posts',
				'security': security,
				'query': query
			},
			success: function (data) {
				td_id_wrapper.closest("tr").removeClass("loading");
				td_id_wrapper.closest("tr").html( data );
			}
		});	
	});
	
	$(".ifeed-post-generator").on("change", '[name="ifeed-query-manual"]', function(){
		if( ! $(this).prop('checked') )
			$(".ifeed-post-generator").find('[data-action="reload-query"]').click();
	});
});

function ifeed_switchto_manual_query() {
	jQuery(".ifeed-post-generator").find('[name="ifeed-query-manual"]').prop('checked', true);
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
	query.posts_per_page = 10;
	query.post_type = "post";
	query.post_status = "publish";
	ifeed_load_posts( JSON.stringify(query) );
}

function ifeed_hours_set() {
	var string = jQuery(".ifeed-post-generator").find('[data-name="hours-array"]').val();
	if( ifeed_if_null(string) ) return;
	var hours_array = string.split(',');
	for(var i = 0; i < hours_array.length; i++) {
		hours_array[i] = parseInt(hours_array[i], 10);
	}
	return hours_array;
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
			query_presenter.removeClass("loading");
			query_presenter.html( data );
		}
	});	
}