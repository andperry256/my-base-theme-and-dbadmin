<?php
//==============================================================================

// Variables $local_site_dir and $relative_path must be set up beforehand
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require_once("$base_dir/common_scripts/date_funct.php");
require_once("$base_dir/mysql_connect.php");
require("$custom_pages_path/$relative_path/db_funct.php");
$db = admin_db_connect();
$table = $_GET['table'];

$where_clause = "table_name='transactions'";
$query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,[],'');
if ($row = mysqli_fetch_assoc($query_result)) {
    $list_size = $row['list_size'];
}
else {
    exit("ERROR - Failed to read table info from DB - this should not occur!!");
}

if (isset($_POST['submitted'])) {
    if (!date_is_valid($_POST['date_selection'])) {
        print("<p>Invalid Date</p>\n");
        print("<p><a href=\"{$_GET['-returnurl']}\">Try again</a></p>\n");
    }
    else {
        $where_clause = 'date>?';
        $where_values = ['s',$_POST['date_selection']];
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,'');
        $display_offset = mysqli_num_rows($query_result);
        $display_offset = floor($display_offset/$list_size) * $list_size;
        header("Location: $base_url/$relative_path/?-table=$table&-startoffset=$display_offset");
        exit;
    }
}

//==============================================================================
?>
