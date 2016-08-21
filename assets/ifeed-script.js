jQuery(document).ready(function($){
	$(".ifeed-post-generator").on("click", ".reload-query", function(){
		// alert("lets reload query");
		var query = {};
		$(".ifeed-post-generator").find(".ifeed-query-builder").each(function(i){
			var key = $(this).attr("data-name");
			var value = $(this).val();
			if(typeof key=='undefined' || key=='' || key.length<1 || typeof value=='undefined' || value=='' || value.length<1) return;
			query[key] = value;
		});
		query = JSON.stringify(query);
		ifeed_load_posts($, query);
	});
});

function ifeed_load_posts($, query) {
	var security = $('#security').val();
	$.ajax({
		type: 'POST',
		url: ajax_ifeed_load_posts_object.ajaxurl,
		data: {
			'action': 'ifeed_load_posts',
			'security': security,
			'query': query
		},
		success: function (data) {
			alert(data);
		}
	});	
}