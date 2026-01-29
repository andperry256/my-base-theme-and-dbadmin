<?php
//==============================================================================

$dbname = $_GET['dbname'] ?? '-';
$uri_elements = explode(trim($_SERVER['REQUEST_URI']),'/');
$local_site_dir = $uri_elements[0];
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
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

if (!isset($_GET['nonst'])) {

    //Add nosync tables to exclusion list
    $nosync_table_list = ($location == 'local')
        ? get_url_content("http://home.andperry.com/andperry.com/nosync_tables.php?dbname=$dbname")
        : get_url_content("https://remote.andperry.com/nosync_tables.php?dbname=$dbname");
    $nosync_table_list = trim($nosync_table_list,'^');
    $tok = strtok($nosync_table_list,'^');
    while ($tok !== false) {
        $exclusions .= "--ignore-table=$dbname.$tok ";
        $tok = strtok('^');
    }
}
exit (trim($exclusions));

//==============================================================================
