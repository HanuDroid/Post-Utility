<!--
	Copyright 2019  Varun Verma  (email : support@ayansh.com)

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

$jokes_data = get_joke_list_by_status("Rejected");
$response = delete_old_posts($jokes_data,2); 
$content = "Following (Rejected) jokes are deleted:<br>" . $response;

$jokes_data = get_joke_list_by_status("Approved");
$response = delete_old_posts($jokes_data,7); 
$content .= "<br><br>Following (Approved) jokes are deleted:<br>" . $response;

// Inform Admin
$admin = get_admin_info();

$sub = date('d-M') . " : Old jokes are deleted.";

$output = send_email_by_own_server($admin['email'], $admin['name'], $sub, $content);
echo $output;

function delete_old_posts($jokes_data,$days){

	$content = "";
	foreach($jokes_data as $joke_data){

		echo "<br>Post Id = ".$joke_data['PostId']." Created Date = ".$joke_data['created_date'];
		$datetime1 = date_create($joke_data['created_date']);
		$datetime2 = new DateTime("now");
		$interval = date_diff($datetime1, $datetime2);
		echo " Date Diff = ".$interval->d;
		if($interval->d >= $days){
			// Older than specified number of days
			echo "This joke will be deleted";
			delete_joke($joke_data['PostKey']);
			$content .= "Joke Id: " . $joke_data['PostId'] . 
						", Title: " . $joke_data['Title'] . 
						", By: " . $joke_data['email'] . 
						" is deleted." .
						"<br>====================================<br>";
		}
		else{
			echo "This joke will NOT be deleted";
		}
	}

	return $content;
}
	
?>
