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

$post_tag = $_POST['post_tag'];
if($post_tag == null || $post_tag == ""){
	die("Post Tag no supplied");
}

createDBConnection();
	
// Get an very old post for recyling.
$result = recycle_old_post($post_tag);
echo json_encode($result);

?>