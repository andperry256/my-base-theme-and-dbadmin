<?php
//==============================================================================

ini_set('error_reporting','E_ALL');
require("functions.php");
if (isset($_GET['site'])) {
    $local_site_dir = $_GET['site'];
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
    require("$base_dir/mysql_connect.php");
}
else {
    exit("Site parameter not specified");
}
if (isset($_GET['table'])) {
    $table = $_GET['table'];
}
else {
    exit("Table parameter not specified");
}

if (isset($_GET['subpath'])) {
    $tables_dir = "$base_dir/admin2/{$_GET['subpath']}/tables";
    require("$custom_pages_path/dbadmin/db-"."{$_GET['subpath']}/db_funct.php");
}
else {
    $tables_dir = "$base_dir/admin2/tables";
    require("$custom_pages_path/dbadmin/db_funct.php");
}

$tables = [];
if ($table == '*') {
    if (is_dir($tables_dir)) {
        $dirlist = scandir($tables_dir);
        foreach ($dirlist as $file) {
            if ((is_dir("$tables_dir/$file")) && (!is_link("$tables_dir/$file")) && ($file != '.') && ($file != '..')) {
                $tables[$file] = true;
            }
        }
    }
}
else {
    $tables[$table] = true;
}

$db = admin_db_connect();
foreach ($tables as $table => $val) {
    print("<h1>Table $table</h1>\n");
    $source = "$tables_dir/$table/fields.ini";
    if (!is_file($source)) {
        print("fields.ini file cannot be loaded<br />");
    }

    $base_table = get_base_table($table);
    $where_clause = 'table_name=?';
    $where_values = ['s',$base_table];
    $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
    if (mysqli_num_rows($query_result) > 0) {
        $list_desktop = [];
        $input = file($source);
        foreach ($input as $line) {
            if (substr($line,0,1) == '[') {
                $field_name = trim($line,"[] \n\r\t");
                print("Processing field $field_name ...<br />\n");
                $list_desktop[$field_name] = 1;
            }
            elseif (substr($line,0,15) == 'visibility:list') {
                $vis_status = trim(substr($line,15)," =\"\n\r\t");
                if ($vis_status == 'hidden') {
                    $list_desktop[$field_name] = 0;
                }
            }
            elseif (substr($line,0,12) == 'widget:label') {
                $label = trim(substr($line,12)," =\"\n\r\t");
                $set_fields = 'alt_label';
                $set_values = ['s',$label];
                $where_clause = 'table_name=? AND field_name=?';
                $where_values = ['s',$table,'s',$field_name];
                mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
            }
            elseif (substr($line,0,18) == 'widget:description') {
                $description = trim(substr($line,18)," =\"\n\r\t");
                $set_fields = 'description';
                $set_values = ['s',$description];
                $where_clause = 'table_name=? AND field_name=?';
                $where_values = ['s',$table,'s',$field_name];
                mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
            }
            elseif (substr($line,0,10) == 'vocabulary') {
                print("<span style=\"color:red\">&nbsp;&nbsp;Vocabulary found</span><br />\n");
            }
        }

        foreach ($list_desktop as $field => $status) {
            $set_fields = 'list_desktop';
            $set_values = ['i',$status];
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = ['s',$table,'s',$field];
            mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
}
print("<p>Operation completed</p>\n");

//==============================================================================
