<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once "vendor/autoload.php";

require_once( (dirname(dirname(__FILE__))) . '/wp-config.php' );

global $wpdb;
global $db;
global $gcm_url;

$gcm_url = "https://apps.ayansh.com/HanuGCM/";

function createDBConnection(){
	
	global $db;
	$host = DB_HOST;
	$user = DB_USER;
	$pass = DB_PASSWORD;
	$database = DB_NAME;
	
	$db = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $user, $pass);

}

function getConfig(){

	$str = file_get_contents('config.json');
	return json_decode($str, true);
}

function get_joke_list_by_status($post_status){

	global $db;
	global $wpdb;
	
	$result = array();
	$table = $wpdb->prefix.'post_temp';
	
	$query = "SELECT * FROM $table WHERE Status = '$post_status' ORDER BY created_date DESC";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$result[] = $row;
		}
	}
	
	$stmt = null;
	//echo $query;
	return $result;
}

function get_joke_list_for_pending_votes($moderatorId){
	
	global $db;
	global $wpdb;
	
	$result = array();
	$post_temp = $wpdb->prefix.'post_temp';
	$votes = $wpdb->prefix.'votes';
	
	$query = "SELECT p.*, v.ModeratorId, v.Vote from $post_temp as p ".
			"LEFT OUTER JOIN $votes as v on p.PostId = v.PostId and v.ModeratorId = $moderatorId ".
			"WHERE p.Status = 'Voting' AND v.Vote IS NULL";
	
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$result[] = $row;
		}
	}
	
	$stmt = null;
	return $result;	
}

function validate_joke($title, $content){
	
	$result = array();
	
	$content_words = str_word_count($content);
	$content_length = mb_strlen($content, 'UTF-8');
	
	// Check if it is duplicate
	if( strpos( $content, "via ~ ayansh.com/" ) !== false ) {
		$result['Action'] = "Reject";
		$result['Reason'] = "Duplicate";
    }
	
	// Check if it is Whats app advertising
	else if( strpos( $title, "WhatsApp Messenger" ) !== false || strpos( $title, "WhatsApp मैसेंजर" ) !== false ) {
		$result['Action'] = "Reject";
		$result['Reason'] = "WhatsApp";
    }
	
	// Check if it is you tube
	else if( strpos( $content, "http" ) !== false && strpos( $content, "youtu" ) !== false ) {
		$result['Action'] = "Reject";
		$result['Reason'] = "Youtube";
    }
	
	// Check if it is App Link
	else if( strpos( $content, "https://play.google.com" ) !== false ) {
		$result['Action'] = "Reject";
		$result['Reason'] = "PlayStore";
    }
	
	// Check if it is some URL
	else if( strpos( $content, "http://" ) !== false || strpos( $content, "https://" ) !== false ) {
		$result['Action'] = "Reject";
		$result['Reason'] = "URL";
    }
	
	// Check if it is Joke is meaning ful
	else if($content_words >= 1 && $content_words <= 3 && $content_length <= 20) {
		$result['Action'] = "Reject";
		$result['Reason'] = "NonSense";
    }
	
	else if($content_words >= 1 && $content_words <= 10 && $content_length <= 40) {
		$result['Action'] = "Reject";
		$result['Reason'] = "Pathetic";
    }
	
	else{
		$result['Action'] = "OK";
	}
	
	return $result;
}

function get_post_details($post_key){
	
	global $db;
	global $wpdb;
	
	$result = array();
	$table = $wpdb->prefix.'post_temp';
	$query = "SELECT * FROM $table WHERE PostKey = '$post_key'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	$stmt = null;
	//echo $query;
	return $result;
}

function complete_post_action($post_key,$action,$reason){
	
	$admin_info = get_admin_info();
	$key = $admin_info['key'];
	
	$base_url = get_option("siteurl") . "/Post-Utility/moderate_joke.php?key=".$key;
	$url = $base_url . "&post_key=".$post_key;
		
	if($action == "Reject"){
		
		if($reason == "nonsense"){
			$post_url = $url."&vote=boo&reason=nonsense";
		}
		
		if($reason == "bad"){
			$post_url = $url."&vote=boo&reason=bad";
		}
		
		if($reason == "duplicate"){
			$post_url = $url."&vote=boo&reason=duplicate";
		}
		
		if($reason == "other-language"){
			$post_url = $url."&vote=boo&reason=lang";
		}
		
		if($reason == "advertising"){
			$post_url = $url."&vote=boo&reason=advertising";
		}
		
	}
	else if($action == "Accept"){
		$post_url = $url."&vote=yay";
	}
	else if($action == "ApprovePost"){
		return approve_joke($post_key);
		return;
	}
	else if($action == "MovePost"){
		
		// $reson contains app code
		return move_post_to_another_blog($post_key,$reason);
		return;
		
	}
	
	//echo $post_url;
	$ch = curl_init( $post_url );
	//curl_setopt( $ch, CURLOPT_POST, 0);
	curl_setopt( $ch, CURLOPT_HTTPGET, 1);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	// Close connection
	curl_close($ch);
	return $response;
}

function move_post_to_another_blog($post_key, $app_code){
	
	if($app_code == "hj"){
		$base_url = "https://apps.ayansh.com/Hindi-Jokes/";
	}
	else if($app_code == "dj"){
		$base_url = "https://hanu-droid.varunverma.org/Applications/DesiJokes/";
	}
	else if($app_code == "hs"){
		$base_url = "https://apps.ayansh.com/Hindi-Shayari/";
	}
	else if($app_code == "ss"){
		$base_url = "https://apps.ayansh.com/Swag-Status/";
	}
	
	$post_url = $base_url . "wp-content/plugins/hanu-droid/CreateNewPost.php";
	
	$post_details = get_post_details($post_key);
	$myvars = "title=" . $post_details['Title'] .
				"&content=" . $post_details['Content'] .
				"&name=" . $post_details['Name'] .
				"&iid=" . $post_details['InstanceId'];
	
	//echo $post_key . "==" . $app_code;
	//echo $post_url;
	//var_dump($myvars);
	
	$ch = curl_init( $post_url );
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	// Close connection
	curl_close($ch);
	
	// Reject this post. After move, dont reject.
	//reject_joke($post_key,"bad");
	
	// Return response
	return $response;
}

function prepare_new_jokes_list($joke_list){
	
	$result = array();
	$admin_info = get_admin_info();
	$key = $admin_info['key'];
	$base_url = get_option("siteurl") . "/Post-Utility/moderate_joke.php?key=".$key;
	
	foreach($joke_list as $joke){
		
		$url = $base_url . "&post_key=".$joke['PostKey'];
		$like_joke = '<a href="'.$url."&vote=yay".'" target="_blank">Good Joke</a>';
		$dont_like_joke = '<a href="'.$url."&vote=bay".'" target="_blank">I don\'t Like the joke, but Start Voting</a>';
		$other_lang_joke = '<a href="'.$url."&vote=boo&reason=lang".'" target="_blank">Reject - Other Language</a>';
		$bad_joke = '<a href="'.$url."&vote=boo&reason=bad".'" target="_blank">Pathetic Joke</a>';
		$adult_joke = '<a href="'.$url."&vote=boo&reason=adult".'" target="_blank">Reject - Adult Joke</a>';
		$duplicate_joke = '<a href="'.$url."&vote=boo&reason=duplicate".'" target="_blank">Duplicate Joke</a>';
		$offensive_joke = '<a href="'.$url."&vote=boo&reason=offensive".'" target="_blank">Reject - Offensive Content</a>';
		$ad_content = '<a href="'.$url."&vote=boo&reason=advertising".'" target="_blank">Advertising Content</a>';
		$not_joke = '<a href="'.$url."&vote=boo&reason=nonsense".'" target="_blank">Not a Joke</a>';
		
		$joke['like_joke'] = $like_joke;
		$joke['dont_like_joke'] = $dont_like_joke;
		$joke['other_lang_joke'] = $other_lang_joke;
		$joke['bad_joke'] = $bad_joke;
		$joke['adult_joke'] = $adult_joke;
		$joke['duplicate_joke'] = $duplicate_joke;
		$joke['offensive_joke'] = $offensive_joke;
		$joke['ad_content'] = $ad_content;
		$joke['not_joke'] = $not_joke;
		
		$result[] = $joke;
	}
	
	return $result;
}

function notify_admin_about_new_post($post_key){
	
	global $gcm_url;
	$post_data = get_post_details($post_key);
	
	$post_content = $post_data['Content'];
	$post_title = $post_data['Title'];
	$author = $post_data['name'];
	
	$admin_info = get_admin_info();

	// Notify admin about new post
	
	$admin_email = $admin_info['email'];
	$name = $admin_info['name'];
	$key = $admin_info['key'];
	
	$url = get_option("siteurl") . "/Post-Utility/moderate_joke.php?key=".$key."&post_key=".$post_key;
		
	$sub = "Please review the new post: " . $post_title;

	$content = "Hello ". $name . ",<br>".
			"A new post has been submitted. Kindly review this post.<br><br>".
			$post_content.
			"<br>".
			"By: " . $author . "/" . "<br><br>".
			'<a href="'.$url."&vote=yay".'">I Like the post</a><br>'.
			'<a href="'.$url."&vote=bay".'">I don\'t Like the post, but Start Voting</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=lang".'">Reject - Other Language</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=bad".'">Reject - Pathetic Joke</a><br><br>'.
			//'<a href="'.$url."&vote=boo&reason=veg".'">Reject - Veg Joke</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=adult".'">Reject - Adult Content</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=duplicate".'">Reject - Duplicate Contnet</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=offensive".'">Reject - Offensive Content</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=advertising".'">Reject - Advertising Content</a><br><br>'.
			'<a href="'.$url."&vote=boo&reason=nonsense".'">Reject - Total NonSense</a><br><br>';
	
	// Var Dump for Debugging
	//var_dump($admin_info);

	if($admin_info['voting_pref'] == "E"){
		$result = send_email_by_own_server($admin_email,$name,$sub,$content);
		return array("message" => $result);
	}
	else if($admin_info['voting_pref'] == "PN" && strcmp($admin_info['iid'], $post_data['InstanceId']) != 0){

		// Send Notification
		$app_url = get_option("siteurl");
		$iid = $admin_info['iid'];
		$myvars = 	'iid=' . $iid . 
					'&app_url=' . $app_url . 
					'&subject=' . "New Post is submitted by user" . 
					'&content=' . $sub . 
					'&message_id=' . $post_data['PostId'] . 
					'&output_type=NoReturn';
		
		$url = $gcm_url . 'SendInfoMessageToAppUser.php';
		$response = send_notification($url,$myvars);

	}
	else{
		return array("message" => "Admin not notified");
	}	
}

function start_voting($post_key,$post_title,$post_content){

	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT * FROM $table WHERE Role = 'Moderator' AND VotingPref = 'E'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			
			$email = $row['EMail'];
			$key = $row['ModeratorKey'];
			$name = $row['Name'];
			
			$url = get_option("siteurl") . "/Post-Utility/moderate_joke.php?key=".$key."&post_key=".$post_key;
		
			$sub = "Please review the new joke: " . $post_title;
		
			$content = "Hello ". $name . ",<br>".
					"A new joke has been submitted. Kindly review this joke.<br><br>".
					$post_content.
					"<br><br>".
					'<a href="'.$url.'&vote=yay" target="_blank">I like this joke</a><br><br>'.
					'<a href="'.$url.'&vote=boo" target="_blank">I hate this joke</a><br><br>';
			
			$output = send_email_by_gmail($email,$name,$sub,$content);
			
		}
	}
	
	// Set Status to Voting in Progress
	$table = $wpdb->prefix.'post_temp';
	$query = "UPDATE $table SET Status = 'Voting' WHERE PostKey = '$post_key'";
	$stmt = $db->prepare($query);
	$sqlVars = array();
	$stmt->execute($sqlVars);
	return $output;
}

function vote_up($post_key,$moderator_id){

	global $db;
	global $wpdb;
	
	$post = get_post_details($post_key);
	$post_id = $post["PostId"];
	
	$table = $wpdb->prefix.'votes';
	$query = "INSERT INTO $table (PostId, ModeratorId, Vote) VALUES('$post_id','$moderator_id','Like')";
	$stmt = $db->prepare($query);
	$sqlVars = array();
	$stmt->execute($sqlVars);
	
	echo "Your vote has been recorded successfully.<br>";
	
	$post_status = $post['status'];
	if($post_status == "Approved"){
		// Already Approved... so no need to calculate again
		return;
	}
	
	// Count Likes
	$query = "SELECT count(*) as c FROM $table WHERE PostId = $post_id AND Vote = 'Like'";
	$stmt = $db->prepare($query);
	if($stmt->execute($sqlVars)){
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$upVotes = $row["c"];
	}
	
	// Count number of moderators
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT count(*) as c FROM $table";
	$stmt = $db->prepare($query);
	if($stmt->execute($sqlVars)){
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$moderators = $row["c"];
	}
	
	$minUpVotes = ceil(0.6 * $moderators);
	if($upVotes >= $minUpVotes){
		// Approve Post
		approve_joke($post_key);
		
		//Notify admin about approval / rejection
		notify_admin_about_joke_status($post_key);
	}
}

function vote_down($post_key,$moderator_id){

	global $db;
	global $wpdb;
	
	$post = get_post_details($post_key);
	$post_id = $post["PostId"];

	$table = $wpdb->prefix.'votes';
	$query = "INSERT INTO $table (PostId, ModeratorId, Vote) VALUES('$post_id','$moderator_id','Hate')";
	$stmt = $db->prepare($query);
	$sqlVars = array();
	$stmt->execute($sqlVars);
	
	echo "<br>Your vote has been recorded successfully";
	
	$post_status = $post['status'];
	if($post_status == "Rejected"){
		// Already rejected... so no need to calculate
		return;
	}
	
	// Count Hates
	$query = "SELECT count(*) as c FROM $table WHERE PostId = $post_id AND Vote = 'Hate'";
	$stmt = $db->prepare($query);
	if($stmt->execute($sqlVars)){
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$downVotes = $row["c"];
	}
	
	// Count number of moderators
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT count(*) as c FROM $table";
	$stmt = $db->prepare($query);
	if($stmt->execute($sqlVars)){
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$moderators = $row["c"];
	}
	
	$maxDownVotes = $moderators - ceil(0.6 * $moderators);
	if($downVotes > $maxDownVotes){
		// Reject Post
		reject_joke($post_key,"bad");
		
		//Notify admin about rejection
		notify_admin_about_joke_status($post_key);
	}
}

function notify_admin_about_joke_status($post_key){
	// Notify Admin about joke status
	global $db;
	global $wpdb;
	global $gcm_url;
	
	$post = get_post_details($post_key);
	
	$sub = "Joke: " . $post['title'] . " is " . $post['status'];
	$content = "Joke by: " . $post['email'] . " has been " . $post['status'] . "<br><br>";
	
	$votes_data = get_votes_for_joke($post_key);
	
	foreach($votes_data as $vote_data){
		$content = $content . $vote_data['Moderator'] . " voted " . $vote_data['Vote'] . "<br>";	
	}

	// Send Notification
	$app_url = get_option("siteurl");
	$admin_info = get_admin_info();
	$iid = $admin_info['InstanceId'];
	$myvars = 	'iid=' . $iid . 
				'&app_url=' . $app_url . 
				'&subject=' . $sub . 
				'&content=' . $content . 
				'&message_id=' . $post_id . 
				'&output_type=Return';
	
	$url = $gcm_url . 'SendInfoMessageToAppUser.php';
	$response = send_notification($url,$myvars);
}

function get_votes_for_joke($post_id){

	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'vote_status';
	$query = "SELECT * FROM $table WHERE PostId = '$post_id'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$votes_data = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$votes_data[] = $row;
		}
	}
	
	return $votes_data;
}

function delete_joke($post_key){
	
	global $db;
	global $wpdb;
	
	// Set Status to Rejected
	$table = $wpdb->prefix.'post_temp';
	$query = "DELETE FROM $table WHERE PostKey = :post_key";
	$stmt = $db->prepare($query);
	$sqlVars = array("post_key" => $post_key);

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
	}
}

function approve_joke($post_key){
	// Joke was approved.
	global $db;
	global $wpdb;
	global $gcm_url;
	
	$post = get_post_details($post_key);
	
	$iid = $post['InstanceId'];
	$post_id = $post['PostId'];
	
	/*
	$user = $post['user'];
	if($user == ""){
		// User is not set ! Check and create user.
		check_and_create_user($post_key, $email);
		// Reload post data...
		$post = get_post_details($post_key);
	}
	
	if($email == ""){
		// Get Email from User Id
		$email = get_email_from_username($user);
	}
	*/
	
	$app_url = get_option("siteurl");
	$sub = "Your joke with title: " . $post['title'] . " is approved";
	$content = "Your joke is approved" . "<br><br>" . "Approved jokes will be pushed to all users in some time.";
	
	// Send Notification
	$myvars = 	'iid=' . $iid . 
				'&app_url=' . $app_url . 
				'&subject=' . $sub . 
				'&content=' . $content . 
				'&message_id=' . $post_id . 
				'&output_type=Return';
	
	$url = $gcm_url . "SendInfoMessageToAppUser.php";
	$response = send_notification($url,$myvars);
	
	$response_decoded = json_decode($response,true);
	$success = $response_decoded["success"];
	$failure = $response_decoded["failure"];

	$gcm_status = "Un-Known";
	
	if($success > 0 && $failure <= 0){
		$gcm_status = "Sent";
	}
	
	if($success <= 0 && $failure > 0){
		$gcm_status = "Failed";
	}
	
	// Set Status to Approved
	$table = $wpdb->prefix.'post_temp';
	$query = "UPDATE $table SET Status = 'Approved', GCMStatus = '$gcm_status' WHERE PostKey = '$post_key'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
	}

	// Post Data
	$message = create_post($post_key);
	return array("message" => $message);
}

function reject_joke($post_key,$reason){
	
	global $db;
	global $wpdb;
	global $gcm_url;

	$app_url = get_option("siteurl");
	
	$post = get_post_details($post_key);
	
	$post_id = $post['PostId'];
	$iid = $post['InstanceId'];
		
	/*
	if($email == ""){
		$user = $post['user_name'];
		// Get Email from User Id
		$email = get_email_from_username($user);
	}
	*/
	
	$sub = "Your joke with title: " . $post['Title'] . " is rejected";
	
	$content = "Your joke has been rejected" . "<br><br>" . "Reason of rejection: ";
	
	if($reason == "bad"){
		$content = $content . "Jokes are checked by a team of moderators. We accept good quality jokes only.";
	}
	else if($reason == "lang"){
		$content = "We have noticed that you have uploaded jokes in a different language."
					. "<br><br>Our app is targeted towards Hindi speaking / reading audience. "
					. "Hence at this time we cannot accept your jokes.<br><br>"
					. "If you are interested in owning a similar jokes app in your <b>local</b> language and earning <b>revenue</b> from it, then we can work together in <u>partnership</u>."
					. "<br><br>To know more details about how to <b>earn revenue</b> from your own jokes app in local language, please see details on : "
					. "<a href = \"http://hanu-droid.varunverma.org/hanu-jokes/\">Hanu Jokes App Framework</a><br><br>"
					. "OR you can contact us via <a href=\"mailto:developer@ayansh.com?subject=Develop Jokes app\">Email</a>";
	}
	else if($reason == "veg"){
		$content = $content . "Desi Jokes is exclusively Adult Jokes app. We do not approve Veg jokes.";
	}
	else if($reason == "adult"){
		$content = $content . "Hindi Jokes is exclusively Veg Jokes app. We do not approve adult/offensive jokes.";
	}
	else if($reason == "offensive"){
		$content = $content . "Your joke has some offensive content. " . 
							"We discourage jokes based on Hatered, Religion, Gang Rape, etc.";
	}
	else if($reason == "advertising"){
		$content = $content . "You did not really submit a Joke. Please do not use this app for promoting other apps/products.";
	}
	else if($reason == "duplicate"){
		$content = $content . "Your joke was already submitted by other users. Hence we are rejecting this joke.";
	}
	else {
		$content = $content . "We accept good quality jokes only.";
	}
	
	$content = $content . "<br><br>" . "We appreciate your effort to submit a jokes.";
	$content = $content . "<br><br>" . "Better luck next time";
	
	//echo "<br>Content going === " . $content . "===" . $reason . "++++";
	// Send Notification
	$myvars = 	'iid=' . $iid . 
				'&app_url=' . $app_url . 
				'&subject=' . $sub . 
				'&content=' . $content . 
				'&message_id=' . $post_id . 
				'&output_type=Return';
	
	$url = $gcm_url . 'SendInfoMessageToAppUser.php';

	if($reason == "nonsense"){
		$gcm_status = "Not-Req.";
		$response = "Reason is: " . $reason . ", So Push Notification not sent.";
	}
	else{
		
		$response = send_notification($url,$myvars);

		$response_decoded = json_decode($response,true);
		$success = $response_decoded["success"];
		$failure = $response_decoded["failure"];

		$gcm_status = "Un-Known";
		if($success > 0 && $failure <= 0){
			$gcm_status = "Sent";
		}
	
		if($success <= 0 && $failure > 0){
			$gcm_status = "Failed";
		}

	}

	// Set Status to Rejected
	$table = $wpdb->prefix.'post_temp';
	$query = "UPDATE $table SET Status = 'Rejected', GCMStatus = '$gcm_status' WHERE PostKey = '$post_key'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
		$response = "Could not update status : " . $query;
	}
	else{
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
	}
	
	return $response;
}

function create_post($post_key){

	// Post Data
	$url = get_option("siteurl") . '/wp-content/plugins/hanu-droid/NewPost.php';
	$myvars = 'post_key=' . $post_key;

	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	// Close connection
	curl_close($ch);
	return $response;
}

function add_tag_to_post($post_tag, $postid){

	wp_set_post_tags( $postid, $post_tag, true );
	$result = array("PostID" => $postid, "message" => "Tag added to post");
	return $result;

}

function recycle_old_post($post_tag, $postid){

	global $db;
	global $wpdb;
	$post_id = 0;
	$post_title = "No posts found for recycling";

	if($postid != null && $postid > 0){
		$post_id = $postid;
		$post_title = "Post Id: " . $postid;
	}
	else{

		$post_table_name = $wpdb->prefix.'posts';
		$term_data_table = $wpdb->prefix.'hanu_term_data';

		$last_year = date('Y-m-d H:i:s', strtotime('-365 days'));

		// Select only 1 Post
		$query = "SELECT ID, post_title, post_date, post_modified FROM $post_table_name INNER JOIN $term_data_table
			ON $post_table_name.ID = $term_data_table.object_id
			WHERE post_date_gmt <= '$last_year' AND post_status = 'publish' AND 
				  post_type = 'post' AND $term_data_table.taxonomy = 'post_tag' AND 
				  $term_data_table.name = '$post_tag' ORDER BY post_date_gmt ASC LIMIT 1";
		
		$stmt = $db->prepare($query);
		$sqlVars = array();

		if (!$stmt->execute($sqlVars)){
			// Error: column does not exist
		}
		else{
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$post_id = $row['ID'];
				$post_title = $row['post_title'];
			}
		}
		$stmt = null;

	}

	// Update the post -- set the publish date to today
	if($post_id > 0){

		$current_time = current_time('mysql');
		$post = array(
			'ID' => $post_id,
			'post_date'     => $current_time,
			'post_date_gmt' => get_gmt_from_date($current_time)
		);

		wp_update_post( $post, $wp_error );
	}
	
	$result = array("PostID" => $post_id, "PostTitle" => $post_title);
	return $result;

}

function check_and_create_user($post_key, $email){
	
	// Should not be used now !
	global $db;
	global $wpdb;
	global $gcm_url;
	
	// Check if user exists...
	$table = $wpdb->prefix.'users';
	$query = "SELECT * FROM $table WHERE user_email = '$email'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if($result["ID"] > 0){
			// User is registered with this email
			$user_name = $result['user_login'];
			$table = $wpdb->prefix.'post_temp';
			$query = "UPDATE $table SET user_name = '$user_name' WHERE PostKey = '$post_key'";
			$stmt = $db->prepare($query);
			$stmt->execute($sqlVars);
		}
		else{
			// User is not registered. Create new user.
			$split = explode("@",$email);
			$user_name = $split[0];
			$user = array ('user_login' => $user_name  , 'user_pass' => $user_name, 'user_email' => $email, 'role' => "author");
			$user_id = wp_insert_user( $user ) ;
			if($user_id > 0){
				// Created :)
				$table = $wpdb->prefix.'post_temp';
				$query = "UPDATE $table SET user_name = '$user_name' WHERE PostKey = '$post_key'";
				$stmt = $db->prepare($query);
				$stmt->execute($sqlVars);
			}
		}
	}
	
	$app_url = get_option("siteurl");
	$myvars = 	'email=' . $email . 
				'&user=' . $user_name . 
				'&pwd=' . $user_name . 
				'&app_url=' . $app_url;
				
	$url = $gcm_url . 'SendUserDataForEMail.php';
	$response = send_notification($url,$myvars);
}

function get_email_from_username($user){
	
	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'users';
	$query = "SELECT * FROM $table WHERE user_login = '$user'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$email = $row['user_email'];
	}
	
	return $email;
}

function get_admin_info(){

	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT * FROM $table WHERE Role = 'Admin'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$email = $row['EMail'];
		$key = $row['ModeratorKey'];
		$name = $row['Name'];
		$voting_pref = $row['VotingPref'];
		$iid = $row['InstanceId'];
		return array('email' => $email, 'key' => $key, 'name' => $name, 'voting_pref' => $voting_pref, 'iid' => $iid);
	}
}

function get_moderators(){

	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT * FROM $table WHERE Role = 'Moderator' and VotingPref = 'CE'";
	$stmt = $db->prepare($query);
	$sqlVars = array();
	
	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$result = array();
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$result[] = $row;
		}
	}
	
	return $result;
}

function get_moderator_info_by_key($key){

	global $db;
	global $wpdb;
	
	$table = $wpdb->prefix.'moderators';
	$query = "SELECT * FROM $table WHERE ModeratorKey = '$key'";
	$stmt = $db->prepare($query);
	$sqlVars = array();

	if (!$stmt->execute($sqlVars)){
		// Error: column does not exist
	}
	else{
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$role = $row['Role'];
		$id = $row['ID'];
		return array('moderator_id' => $id, 'role' => $role);
	}
}

function send_email_by_own_server($to, $name, $subject, $message, $cc = NULL){

	$config = getConfig();
	$mail = new PHPMailer(true);

	$mail->isSMTP(); 					// telling the class to use SMTP
	$mail->SMTPDebug  = 2;         		// enables SMTP debug information (for testing)
														// 1 = errors and messages
														// 2 = messages only
	$mail->Debugoutput = 'html';		//Ask for HTML-friendly debug output

	$mail->Host       = $config["smtp_server"]["ayansh"]["host"];	// sets localhost as the SMTP server
	$mail->Port       = $config["smtp_server"]["ayansh"]["port"];	// set the SMTP port for the server
	$mail->SMTPSecure = "tls";     		// sets the prefix to the servier
	$mail->SMTPAuth   = true;       	// enable SMTP authentication
	
	$mail->Username   = $config["smtp_server"]["ayansh"]["user"];  	// username
	$mail->Password   = $config["smtp_server"]["ayansh"]["password"];            	// password

	$mail->setFrom($config["smtp_server"]["ayansh"]["user"], 'Ayansh Support Team');
	$mail->addAddress($to, $name);
	
	$mail->ContentType = "text/html";
	$mail->CharSet = "UTF-8";

	$mail->Subject = $subject;
	$mail->msgHTML($message);
	
	// Add CC
	if(is_null($cc)){
		// Nothing to do.
	}
	else{
		// Add CC
		foreach($cc as $cc_address){
			$mail->addCC($cc_address['email'],$cc_address['name']);
		}
	}
	
	if(!$mail->send()) {
		$output = "Mailer Error: " . $mail->ErrorInfo;
	} else {
		$output = "Message sent to: ". $to;
	}
	return $output;
}

function send_email_by_gmail($to, $name, $subject, $message, $cc = NULL){

	$mail = new PHPMailer(true);

	$mail->isSMTP(); 							// telling the class to use SMTP
	$mail->SMTPDebug  = 0;                		// enables SMTP debug information (for testing)
													// 1 = errors and messages
													// 2 = messages only
	$mail->Debugoutput = 'html';				//Ask for HTML-friendly debug output

	$mail->Host       = $config["smtp_server"]["gmail"]["host"];      // sets GMAIL as the SMTP server
	$mail->Port       = $config["smtp_server"]["gmail"]["port"];                   // set the SMTP port for the GMAIL server
	$mail->SMTPSecure = "tls";                 // sets the prefix to the servier
	$mail->SMTPAuth   = true;                  // enable SMTP authentication
	
	$mail->Username   = $config["smtp_server"]["gmail"]["user"];  // GMAIL username
	$mail->Password   = $config["smtp_server"]["gmail"]["password"];            // GMAIL password

	$mail->setFrom($config["smtp_server"]["gmail"]["user"], 'DJ Admin');
	$mail->addAddress($to, $name);
	
	$mail->ContentType = "text/html";
	$mail->CharSet = "UTF-8";

	$mail->Subject = $subject;
	$mail->msgHTML($message);
	
	// Add CC
	if(is_null($cc)){
		// Nothing to do.
	}
	else{
		// Add CC
		foreach($cc as $cc_address){
			$mail->addCC($cc_address['email'],$cc_address['name']);
		}
	}
	
	if(!$mail->send()) {
		$output = "Mailer Error: " . $mail->ErrorInfo;
	} else {
		$output = "Message sent to: ". $to;
	}
	return $output;
}

function send_notification($url,$notification){
	// Send Notification
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $notification);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

	$response = curl_exec( $ch );
	
	// Close connection
	curl_close($ch);
	
	return $response;
}

?>
