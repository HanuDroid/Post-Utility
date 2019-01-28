<?php
/*  Copyright 2012  Varun Verma  (email : varunverma@varunverma.org)

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
*/

require( 'jokes_utility_functions.php' );

$moderator_key = $_GET['key'];
$post_key = $_GET['post_key'];
$vote = $_GET['vote'];
$reason = $_GET['reason'];

// Create DB Connection
createDBConnection();

$moderator = get_moderator_info_by_key($moderator_key);
$moderator_role = $moderator['role'];

$post = get_post_details($post_key);

//echo "++ Called with reason code: " . $reason . " ++";

if(strcmp($moderator_role,"Admin") == 0){
	// Admin Guy
	if(strcmp($vote,"yay")==0){
		// Admin Voted up ... so now others can also vote
		vote_up($post_key,$moderator['moderator_id']);
		start_voting($post_key,$post['title'],$post['content']);
	}
	elseif(strcmp($vote,"bay")==0){
		// Admin voted down, but lets have others vote too...
		vote_down($post_key,$moderator['moderator_id']);
		start_voting($post_key,$post['title'],$post['content']);
	}
	else{
		// Admin Rejected ... so others need not vote.. We send reject notification
		//vote_down($post_key,$moderator['moderator_id']);
		$response = reject_joke($post_key,$reason);
		echo "<br>Notification Status:" . $response . "<br>";
	}
}
else{
	// Other Moderator
	if(strcmp($vote,"yay")==0){
		// Voted up ... 
		vote_up($post_key,$moderator['moderator_id']);
	}
	else{
		// Voted down ...
		vote_down($post_key,$moderator['moderator_id']);
	}
}

?>