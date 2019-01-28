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
	
	// App URL
	$app_url = get_option("siteurl");

// Get a List of moderators.
	$moderators = get_moderators();

// For each moderator, see the jokes where he has not voted.	
	foreach($moderators as $moderator){
		
		// Get Jokes for which he has not voted
		$jokeList = get_joke_list_for_pending_votes($moderator['ID']);
		$pending_jokes = count($jokeList);
		
		if($pending_jokes == 0){
			continue;
		}
		
		$sub = date('d-M') . " - Consolidated Reminder: ". $pending_jokes ." Jokes waiting for your approval.";
		
		$content = "Hello ". $moderator['Name'] . ",<br>".
				$pending_jokes . " jokes are waiting for your approval. Kindly approve / reject them.<br><br>";
		
		foreach($jokeList as $joke){
				
			$url = $app_url . "/Jokes-Utility/moderate_joke.php?key="
					.$moderator['ModeratorKey']."&post_key=".$joke['PostKey'];
			
			$content .= $joke['Content'] . "<br><br>".
						'<a href="'.$url.'&vote=yay" target="_blank">I like this joke</a><br><br>'.
						'<a href="'.$url.'&vote=boo" target="_blank">I hate this joke</a><br><br>'.
						"=====================================================<br>";
			
		}
		
		$admin = get_admin_info();
		$cc = array("address" => array("email" => $admin['email'], "name" => $admin['name']));
		
		// Consolidate and email.
		$output = send_email_by_gmail($moderator['EMail'], $moderator['Name'], $sub, $content, $cc);
		echo $output;
	}

?>