<?php
//==============================================================================

// Load paths and validate parameters
if (is_file('/Config/linux_pathdefs.php')) {
    $local_site_dir = strtok(substr($_SERVER['REQUEST_URI'],1),'/');
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/common_scripts/dbadmin/table_funct.php");
require("$base_dir/common_scripts/dbadmin/record_funct.php");
if (empty($_GET['sub_path'])) {
    exit("Sub-path not specified");
}
$sub_path = $_GET['sub_path'];
if (empty($_GET['table'])) {
    exit("Table not specified");
}
$table = $_GET['table'];
require("$base_dir/common_scripts/session_funct.php");
run_session();
if (empty(get_session_var("dbauth-$sub_path"))) {
    exit("Authentication failure");
}
if (!is_file("$base_dir/non_wp_header.php")) {
    exit("Non-WP header file not found");
}
require("$base_dir/non_wp_header.php");
if (get_session_var("$sub_path-filtered-table") == $table) {
    $sort_clause = get_session_var("$sub_path-sort-clause");
    $search_clause = get_session_var("$sub_path-search-clause");
}
else {
    $sort_clause = '';
    $search_clause = '';
}

//==============================================================================
?>
<style>
    a {
        color:#fff !important;
        text-decoration: none !important;
    }
    .table-header {
        background-color: #7f7f7f;
        color: #fff;
    }
</style>
<?php
//==============================================================================

// Connect to database.
require("$base_dir/wp-custom-scripts/pages/dbadmin/$sub_path/db_funct.php");
$db = admin_db_connect();

print("<h2>Table [$table]</h2>\n");

// Build lists of primary keys and fields to display.
$base_table = get_base_table($table,$db);
$display_fields = [];
$query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
while ($row = mysqli_fetch_assoc($query_result)) {
    $where_clause = 'table_name=? AND field_name=? AND list_desktop=1';
    $where_values = ['s',$base_table,'s',$row['Field']];
    if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,''))) {
        $display_fields[$row2['display_order']] = $row2['field_name'];
    }
}
$excluded_fields = (isset($_GET['excluded'])) ? $_GET['excluded'] : '^';

print("<table>\n");

/*
Output the table header line. Each field name is output as a link to reload the
page with that particular field excluded.
*/
print("<tr>");
foreach ($display_fields as $order => $field) {
    if (strpos($excluded_fields,"^$field^") === false) {
        $label = field_label($base_table,$field,$db);
        $excluded_fields_par=(urlencode("$excluded_fields$field^"));
        print("<td class=\"table-header\">");
        print("<a href=\"./display_table.php?sub_path=$sub_path&table=$table&excluded=$excluded_fields_par\">$label</a></td>");
    }
}
print("</tr>\n");

/*
Main loop to output the table records. The primary keys are only used to order
the records in the case of a base table.
*/
$query_result = mysqli_select_query($db,$table,'*',$search_clause,[],$sort_clause);
while ($row = mysqli_fetch_assoc($query_result)) {
    print("<tr>");
    foreach ($display_fields as $order => $field) {
        if (strpos($excluded_fields,"^$field^") === false) {
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = ['s',$base_table,'s',$field];
            if (($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,''))) && 
                ($row2['widget_type'] == 'checkbox')) {
                $value = ($row[$field]) ? '[X]' : '';
            }
            else {
                $value = $row[$field];
            }
            print("<td>$value</td>");        
        }
    }
    print("</tr>\n");
}

print("</table>\n");

//==============================================================================
