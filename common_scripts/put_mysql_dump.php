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
elseif (!isset($_GET['domname'])) {
    exit("Domain name not specified\n");
}
elseif (!isset($_GET['dbname'])) {
    exit("Database not specified\n");
}
elseif (!isset($_GET['sqlfile'])) {
    exit("SQL filename not specified\n");
}
$dbname = $_GET['dbname'];
$sqlfile = $_GET['sqlfile'];
$domname = $_GET['domname'];

$content = file_get_contents("http://{$_SERVER['REMOTE_ADDR']}/$local_site_dir/common_scripts/get_mysql_dump.php?site=$local_site_dir&domname=$domname&dbname=$dbname&sqlfile=$sqlfile");
if (!empty($content)) {
    if (!is_dir("$site_mysql_backup_dir/$dbname")) {
        mkdir("$site_mysql_backup_dir/$dbname,0775");
    }
    file_put_contents("$site_mysql_backup_dir/$dbname/$sqlfile.sql");
}

//==============================================================================
