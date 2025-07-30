<?php
//==============================================================================

// Load paths and validate parameters
if (is_file('/Config/linux_pathdefs.php')) {
    $local_site_dir = strtok(substr($_SERVER['REQUEST_URI'],1),'/');
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/common_scripts/session_funct.php");
require("$base_dir/common_scripts/dbadmin/table_funct.php");
require("$base_dir/common_scripts/dbadmin/widget_types.php");
run_session();
if (empty($_GET['sub_path'])) {
    exit("Sub-path not specified");
}
$sub_path = $_GET['sub_path'];
if (empty($_GET['table'])) {
    exit("Table not specified");
}
$table = $_GET['table'];
if (is_dir("$base_dir/wp-custom-scripts/pages/dbadmin/$sub_path")) {
    include("$base_dir/wp-custom-scripts/pages/dbadmin/$sub_path/db_funct.php");
}
elseif (is_dir("$base_dir/wp-custom-scripts/pages/$sub_path")) {
    include("$base_dir/wp-custom-scripts/pages/$sub_path/db_funct.php");
}
else {
    exit("Unable to load DB functions script.");
}
$db = admin_db_connect();
$base_table = get_base_table($table,$db);

// Build search clause
$search_clause = '';
update_session_var("$sub_path-$table-search-clause",'');
if (!empty($_POST['search_string'])) {
    $lc_search_string = strtolower($_POST['search_string']);
    $search_clause = '';
    $field_processed = false;
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result)) {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = ['s',$base_table,'s',$field_name];
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2)) {
            if (($widget_types[$row2['widget_type']]) && (!$row2['exclude_from_search'])) {
                // Add field to search clause
                if ($field_processed) {
                    $search_clause .= " OR";
                }
                $field_processed = true;
                $search_clause .= " LOWER($field_name) LIKE '%";
                $search_clause .= mysqli_real_escape_string($db,$lc_search_string);
                $search_clause .= "%'";
            }
        }
    }
}
update_session_var("$sub_path-$table-search-string",$_POST['search_string']);
update_session_var("$sub_path-$table-search-clause",$search_clause);
header ("Location: $base_url/dbadmin/$sub_path?-table=$table");
exit;

//==============================================================================
?>