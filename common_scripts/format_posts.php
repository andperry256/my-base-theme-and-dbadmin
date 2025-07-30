<?php
//==============================================================================
/*
This script is only used for occasional maintenance. It normally resides in the
'common_scripts' directory, but needs to be copied temporarily into the
'public_html' directory in order to run it.
*/
//==============================================================================

if (!isset($argc)) {
    exit("Script allowed in command line mode only\n");
}
$min_line_length = 12;
if (!is_file(__DIR__.'/path_defs.php')) {
    exit("path_defs.php script not found\n");
}
include("path_defs.php");
$db = db_connect(WP_DBID);
$where_clause = "post_type='post'";
$where_values = [];
$add_clause = "ORDER BY post_name ASC";
$query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,$add_clause);
while ($row = mysqli_fetch_assoc($query_result)) {
    $content = $row['post_content'];
    $temp_str = str_replace('&nbsp;','_',$content);
    $temp_str = strip_tags($temp_str);
    $temp_substr = substr($temp_str,0,$min_line_length);
    $space_count = substr_count($temp_substr,' ');

    // Replace required number of spaces with '&nbsp;'.
    if ($space_count > 0) {
        print("Adding $space_count non-breaking space(s) to post [{$row['post_name']}]\n");
        $new_content = '';
        $length = strlen($content);
        $count = 0;
        for ($i=0; $i<$length; $i++) {
            if ((substr($content,$i,1) == ' ') && ($count < $space_count)) {
                $new_content .= '&nbsp;';
                $count++;
            }
            else {
                $new_content .= substr($content,$i,1);
            }
        }
        $content = $new_content;
        $set_fields = 'post_content';
        $set_values = ['s',$content];
        $where_clause = 'ID=?';
        $where_values = ['i',$row['ID']];
        mysqli_update_query($db,'wp_posts',$set_fields,$set_values,$where_clause,$where_values);
    }

    // Add <!--no-more--> directive if required.
    if ((strpos($content,'youtube_watch.svg') !== false) && (strpos($content,'<!--no-more-->') === false)) {
        print("Adding '<!--no-more-->' directive to post [{$row['post_name']}]\n");
        $content .= "\n<!--no-more-->";
        $set_fields = 'post_content';
        $set_values = ['s',$content];
        $where_clause = 'ID=?';
        $where_values = ['i',$row['ID']];
        mysqli_update_query($db,'wp_posts',$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
?>
