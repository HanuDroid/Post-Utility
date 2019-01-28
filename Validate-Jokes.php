<!--
	Copyright 2012  Varun Verma  (email : varunverma@varunverma.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
-->
<?php

require( 'jokes_utility_functions.php' );

createDBConnection();

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
	
?>