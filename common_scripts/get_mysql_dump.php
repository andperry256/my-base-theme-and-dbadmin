<?php
//==============================================================================

require("allowed_hosts.php");
if ((is_file('/Config/linux_pathdefs.php')) && (!isset($_GET['site']))) {
    exit("Site not specified\n");
}
elseif (isset($_GET['site'])) {
    $local_site_dir = $_GET['site'];
}
if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php")) {
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
else {
    exit("Path definitions script not found\n");
}
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication failure\n");
}
elseif (!isset($local_site_dir)) {
    exit("Site not specified\n");
}
elseif (!isset($_GET['dbname'])) {
    exit("Database not specified\n");
}
elseif (!isset($_GET['sqlfile'])) {
    exit("SQL filename not specified\n");
}
$dbname = $_GET['dbname'];
$sqlfile = $_GET['sqlfile'];

if ((isset($_GET['domname'])) && (is_file("$site_mysql_backup_dir/$domname/$dbname/$sqlfile.sql"))) {
    exit(file_get_contents("$site_mysql_backup_dir/$domname/$dbname/$sqlfile.sql"));
}
elseif (is_file("$site_mysql_backup_dir/$dbname/$sqlfile.sql")) {
    exit(file_get_contents("$site_mysql_backup_dir/$dbname/$sqlfile.sql"));
}

//==============================================================================
?>