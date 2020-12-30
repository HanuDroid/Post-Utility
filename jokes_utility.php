<?php

require_once( 'jokes_utility_functions.php' );

$secret_key = $_POST['secret_key'];
$code = $_POST["code"];
$config = getConfig();

if($secret_key != $config["secret_key"]){
	die("Wrong secret key. Please try again");
}

// Create DB Connection
createDBConnection();

$results = array();

if($code == "joke_list"){
	$status = $_POST["status"];
	$results = get_joke_list_by_status($status);
	if($status == "New"){
		// For new Jokes, show other lists
		$results = prepare_new_jokes_list($results);
	}
}

if($code == "post_detail"){
	$post_key = $_POST["post_key"];
	$results = get_post_details($post_key);
}

if($code == "notify_admin"){
	$post_key = $_POST["post_key"];
	$results = notify_admin_about_new_post($post_key);
}

if($code == "approve_joke"){
	$post_key = $_POST["post_key"];
	$results = approve_joke($post_key);
}

if($code == "reject_joke"){
	$post_key = $_POST["post_key"];
	$results = reject_joke($post_key);
}

if($code == "pending_by_moderator"){
	$moderator_id = $_POST["moderator_id"];
	$results = get_joke_list_for_pending_votes($moderator_id);
}

if($code == "recycle_old_post"){
	$post_tag = $_POST["post_tag"];
	$post_id = $_POST["post_id"];
	$results = recycle_old_post($post_tag,$post_id);
}

if($code == "add_tag_to_post"){
	$post_tag = $_POST["post_tag"];
	$post_id = $_POST["post_id"];
	$results = add_tag_to_post($post_tag,$post_id);
}

if($code == "delete_rejected_jokes"){
	
	$jokes_data = get_joke_list_by_status("Rejected");
	
	foreach($jokes_data as $joke_data){
		delete_joke($joke_data['PostKey']);
	}
	
	$results[] = count($jokes_data) . " Jokes deleted.";
}

if($code == "validate_jokes"){
	
	$jokes_data = get_joke_list_by_status("New");

	foreach($jokes_data as $joke){
		
		$post_key = $joke['PostKey'];
		$title = $joke['Title'];
		$content = $joke['Content'];
		
		$result = validate_joke($title, $content);
		
		$action = $result['Action'];
		$reason = $result['Reason'];
		
		if( $action == "Reject" ) {
			
			// Check if it is duplicate
			if( $reason == "Duplicate" ) {
				complete_post_action($post_key,"Reject","duplicate");
				echo "<br>Post Key: " . $post_key . " was duplicate joke";
			}
			
			// Check if it is Whats app advertising
			else if( $reason == "WhatsApp" ) {
				complete_post_action($post_key,"Reject","advertising");
				echo "<br>Post Key: " . $post_key . " was WhatsApp ad joke";
			}
			
			// Check if it is you tube
			else if( $reason == "Youtube" ) {
				complete_post_action($post_key,"Reject","advertising");
				echo "<br>Post Key: " . $post_key . " contained some You tube link";
			}
			
			// Check if it is App Link
			else if( $reason == "PlayStore" ) {
				complete_post_action($post_key,"Reject","advertising");
				echo "<br>Post Key: " . $post_key . " contained some URL link";
			}
			
			// Check if it is some URL
			else if( $reason == "URL" ) {
				complete_post_action($post_key,"Reject","advertising");
				echo "<br>Post Key: " . $post_key . " contained some URL link";
			}
			
			// Check if it is Joke is meaning ful
			else if( $reason == "NonSense" ) {
				complete_post_action($post_key,"Reject","nonsense");
				echo "<hr />";
				echo "<br>Post Key: " . $post_key . " was Nonsense joke. Word count: " . $content_words;
				echo "<br> String length: " . $content_length;
				echo "<br>Title : " . $title . "<br>Content: " . $content . "<br>";
				var_dump(str_word_count($content,1));
				echo "<hr />";
			}
			
			else if( $reason == "Pathetic" ) {
				complete_post_action($post_key,"Reject","bad");
				echo "<hr />";
				echo "<br>Post Key: " . $post_key . " was Pathetic joke. Word count: " . $content_words;
				echo "<br> String length: " . $content_length;
				echo "<br>Title : " . $title . "<br>Content: " . $content . "<br>";
				var_dump(str_word_count($content,1));
				echo "<hr />";
			}
			else{
				
			}
			
		}
		else{
			//echo "<br>Post Key: " . $post_key . " seems reasonable joke";
		}
		
	}
	
}

if($code == "complete_post_action"){
	$post_key = $_POST["post_key"];
	$action = $_POST["action"];
	$reason = $_POST["reason"];
	$results = complete_post_action($post_key,$action,$reason);
}

echo json_encode($results);

?>