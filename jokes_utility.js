$(document).ready(function(){
	
	$('#jokes_table').hide();
	
	$("#clear-result-area").click(function(){
		$('#result').html("");
		$('#jokes_table').hide();
	});
	
	function print_joke_list(joke_list){
		
		$('#jokes_table').show();
		$("#jokes_table td").parent().remove();
		for(var i = 0; i < joke_list.length; i++) {
			var joke_data = joke_list[i];
			var table_row = "<tr><td>";
			table_row = table_row.concat(joke_data.PostKey, "</td><td>", joke_data.Title , "</td><td>" , joke_data.Content);
			table_row = table_row.concat("</td><td>", joke_data.name, "</td><td>");
			table_row = table_row.concat(joke_data.created_date, "</td><td>", joke_data.Status, "</td></tr>");
			
			$('#jokes_table tr:last').after(table_row);
			
		}
	}
	
	$("#new-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"joke_list", status:"New", secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			
			$('#jokes_table').show();
			$("#jokes_table td").parent().remove();
			
			$("#jokes_table th").eq(0).text( "Title" );
			$("#jokes_table th").eq(1).text( "Content" );
			$("#jokes_table th").eq(2).text( "Action" );
			$("#jokes_table th").eq(3).text( "" );
			$("#jokes_table th").eq(4).text( "" );
			$("#jokes_table th").eq(5).text( "" );
			
			for(var i = 0; i < result.length; i++) {
				var joke_data = result[i];
				var post_key = joke_data.PostKey;
				var table_row = "<tr>";
				table_row = table_row.concat("<td>", joke_data.Title , "</td><td>" , joke_data.Content, "</td>");
				
				//table_row = table_row.concat("<td>", joke_data.not_joke, " | ", joke_data.duplicate_joke, " | ");
				//table_row = table_row.concat(joke_data.ad_content, " | ", joke_data.like_joke, " | ", joke_data.bad_joke);
				
				table_row = table_row.concat("<td>");
											
				var joke_button = '<div class="btn-group">\
				<button type="button" class="btn btn-danger reject-nonsense" id="' + post_key + '">Non Sense</button>\
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
				<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>\
				<ul class="dropdown-menu">\
					<li><a class="reject-bad" id="' + post_key + '">Pathetic Post</a></li>\
					<li><a class="reject-duplicate" id="' + post_key + '">Duplicate Post</a></li>\
					<li><a class="reject-advertising" id="' + post_key + '">Advertising Post</a></li>\
					<li><a class="other-language" id="' + post_key + '">Other Language</a></li>\
				</ul>\
				</div>';
				table_row = table_row.concat(joke_button);
								
				var joke_button = '<div class="btn-group">\
				<button type="button" class="btn btn-success start-voting" id="' + post_key + '">Start Voting</button>\
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
				<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>\
				<ul class="dropdown-menu">\
					<li><a class="approve-post" id="' + post_key + '">Approve Post</a></li>\
				</ul>\
				</div>';
				table_row = table_row.concat(joke_button);
				
				var joke_button = '<div class="btn-group">\
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">\
				Move Post To: <span class="caret"></span></button>\
				<ul class="dropdown-menu">\
					<li><a class="move_post" id="' + 'hs-' + post_key + '">Shayari</a></li>\
					<li><a class="move_post" id="' + 'dj-' + post_key + '">Desi Jokes</a></li>\
					<li><a class="move_post" id="' + 'hj-' + post_key + '">Hindi Jokes</a></li>\
					<li><a class="move_post" id="' + 'ss-' + post_key + '">Swag Status</a></li>\
				</ul>\
				</div>';
				table_row = table_row.concat(joke_button);
				
				table_row = table_row.concat("</td></tr>");
				
				$('#jokes_table tr:last').after(table_row);
				
			}
			
		});
		
	});
	
	$(document).on('click', '.reject-nonsense', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Reject","nonsense");

	});
	
	$(document).on('click', '.reject-bad', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Reject","bad");

	});
	
	$(document).on('click', '.reject-duplicate', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Reject","duplicate");

	});
	
	$(document).on('click', '.reject-advertising', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Reject","advertising");
		
	});
	
	$(document).on('click', '.other-language', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Reject","other-language");
		
	});
	
	$(document).on('click', '.start-voting', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().index();
		complete_post_action(row_index,post_key,"Accept","good");

	});
	
	$(document).on('click', '.approve-post', function(){
		
		var post_key = $(this).attr("id");
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"ApprovePost","good");

	});
	
	$(document).on('click', '.move_post', function(){
		
		var id = $(this).attr("id").split('-');
		var post_key = id[1];
		var app_id = id[0];
		var row_index = $(this).parent().parent().parent().parent().parent().index();
		complete_post_action(row_index,post_key,"MovePost",app_id);
	});
	
	$("#approved-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"joke_list", status:"Approved", secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			print_joke_list(result);
		});
		
	});
	
	$("#rejected-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"joke_list", status:"Rejected", secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			print_joke_list(result);
		});
		
	});
	
	$("#pending-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"joke_list", status:"Voting", secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			print_joke_list(result);
		});
		
	});
	
	$("#pending-by-moderator").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		var moderator_id = $( "#moderator-id" ).val();
		
		$.post("jokes_utility.php", {code:"pending_by_moderator", moderator_id:moderator_id, secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			print_joke_list(result);
		});
		
	});
	
	$("#show-joke").click(function(){

		$('#jokes_table').hide();
		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		var post_key = $( "#joke-key" ).val();
		if(post_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"post_detail", secret_key:secret_key, post_key:post_key},
		function(data,status){
			
			var result = jQuery.parseJSON(data);		
			var output = "<b>Title</b> : ";
			output = output.concat(result.Title,"<br><b>Content</b> : ",result.Content);
			$('#result').html(output);
		});
		
	});
	
	$("#notify-admin").click(function(){

		$('#jokes_table').hide();
		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		var post_key = $( "#joke-key" ).val();
		if(post_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"notify_admin", secret_key:secret_key, post_key:post_key},
		function(data,status){
			
			var result = jQuery.parseJSON(data);		
			var output = "";
			output = output.concat("<b>", result.message,"</b>");
			$('#result').html(output);
		});
		
	});
	
	$("#approve-joke").click(function(){

		$('#jokes_table').hide();
		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		var post_key = $( "#joke-key" ).val();
		if(post_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"approve_joke", secret_key:secret_key, post_key:post_key},
		function(data,status){
			
			var result = jQuery.parseJSON(data);		
			var output = "";
			output = output.concat("<b>", result.message,"</b>");
			$('#result').html(output);
		});
		
	});
	
	$("#reject-joke").click(function(){

		$('#jokes_table').hide();
		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		var post_key = $( "#joke-key" ).val();
		if(post_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"reject_joke", secret_key:secret_key, post_key:post_key},
		function(data,status){
			
			var result = jQuery.parseJSON(data);		
			var output = "";
			output = output.concat("<b>", result.message,"</b>");
			$('#result').html(output);
		});
		
	});
	
	$("#delete-rejected-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"delete_rejected_jokes", secret_key:secret_key},
		function(data,status){		
			var result = jQuery.parseJSON(data);
			$('#result').html(result);
		});
		
	});
	
	$("#validate-jokes").click(function(){

		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}
		
		$.post("jokes_utility.php", {code:"validate_jokes", secret_key:secret_key},
		function(data,status){		
			//var result = jQuery.parseJSON(data);
			$('#result').html(data);
		});
		
	});
	
	function complete_post_action(row_index,post_key,action,reason){
		
		var secret_key = $( "#secret-key" ).val();
		if(secret_key == ""){
			return;
		}

		$.post("jokes_utility.php", {	code:"complete_post_action",
										secret_key:secret_key,
										post_key:post_key,
										action:action,
										reason:reason
									},
		function(data,status){
			
			if(action == "MovePost"){
				var x = document.getElementById("snackbar");
				x.className = "show";
				setTimeout(function(){ x.className = x.className.replace("show", ""); }, 3000);
			}
			else{
				var result = jQuery.parseJSON(data);
				var html_text = "<td>Result:</td><td>" + result + "</td>";
				$("#jokes_table tr:eq("+row_index+")").html(html_text);
			}
						
		});
	}
	
});
