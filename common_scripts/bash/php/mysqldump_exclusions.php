<?php
//==============================================================================

$dbname = $argv[1] ?? '-';
$nosync_table_list = $argv[2] ?? null;
require(__DIR__."/../../../path_defs.php");
require("$base_dir/mysql_connect.php");
foreach ($dbinfo as $dbid => $info) {
    if ((($location == 'local') && ($info[0] == $dbname)) ||
        (($location == 'real') && ($info[1] == $dbname))) {
        $found = true;
        break;
    }
}
if (empty($found)) {
    exit;
}
$db = db_connect($dbid);
$dbname = ($location == 'local') ? $dbinfo[$dbid][0] : $dbinfo[$dbid][1];
$exclusions = '';

// Add views to exclusion list
$field_name = "Tables_in_$dbname";
$query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE `$field_name` LIKE 'dataface__%' OR Table_type LIKE 'VIEW'");
while ($row = mysqli_fetch_assoc($query_result)) {
    $exclusions .= "--ignore-table=$dbname.{$row[$field_name]} ";
}

if (!empty($nosync_table_list)) {

    //Add nosync tables to exclusion list
    $nosync_tables = explode('^',trim($nosync_table_list,'^'));
    foreach ($nosync_tables as $table) {
        $exclusions .= "--ignore-table=$dbname.$table ";
    }
}
exit (trim($exclusions));

//==============================================================================
