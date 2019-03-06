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

$result = array();

$check_result = check_recycle_required();

if($check_result["required"] == true){

    // Get an very old post for recyling.
    $post_tag = get_tag_for_recycling();

    if($post_tag == ""){

        $result = array("status_code" => "Success",
                    "message" => "No tags found that can be recycled");

    }
    else{

        $result = recycle_old_post($post_tag,0);
    }   

}
else{
    $result = array("status_code" => "Success",
                    "message" => "Recycling not required",
                    "log" => $check_result["log"]);
}

echo json_encode($result);
    
// Functions
function check_recycle_required(){

    global $wpdb;
    global $db;

    $check_result = array("required" => false,
                            "log" => "Default Value");

    $post_table_name = $wpdb->prefix.'posts';

    // Get Max Post Date
    $query = "SELECT max(post_date_gmt) as latest_post_date FROM $post_table_name WHERE post_status = 'publish' AND post_type = 'post'";
    $stmt = $db->prepare($query);
    $sqlVars = array();

    if (!$stmt->execute($sqlVars)){
        $check_result = array("required" => false, "log" => "Error in SQL");
    }
    else{
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $latest_post_date = new DateTime($row['latest_post_date']); 
        }

        $now = new DateTime();  //Current Time
        
        $time_diff = $now->getTimestamp() - $latest_post_date->getTimestamp();

        // If time diff is more than 8 hours
        if($time_diff > 8*60*60){
            $check_result = array("required" => true, "log" => "");
        }
        else{
            $check_result = array("required" => false, 
                                "log" => "Last post within 8 hours. Current time is: ".$now->format('Y-m-d H:i:s'));
        }
    }

    return $check_result;
}

function get_tag_for_recycling(){

    global $wpdb;
    global $db;

    $terms_table = $wpdb->prefix.'terms';
    $taxonomy_table = $wpdb->prefix.'term_taxonomy';

    $query = "SELECT terms.name, taxonomy.taxonomy, taxonomy.count FROM $terms_table as terms 
                inner join $taxonomy_table as taxonomy on terms.term_id = taxonomy.term_id 
                where taxonomy.taxonomy = 'post_tag' order by taxonomy.count desc limit 2";

    $stmt = $db->prepare($query);
    $sqlVars = array();
    
    if (!$stmt->execute($sqlVars)){
        return "";
    }
    else{

        $tag_list = array();

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            $tag_list[] = $row['name'];
        }

        if(empty($tag_list)){
            return "";
        }
        else{

            $now = date("H");
            if(count($tag_list) > 1 && $now > 12){
                return $tag_list[1];
            }
            else{
                return $tag_list[0];
            }

        }

    }

}

?>