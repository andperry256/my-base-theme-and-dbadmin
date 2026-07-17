<?php
//==============================================================================

if (is_file('/Config/linux_pathdefs.php')) {
    include('/Config/linux_pathdefs.php');
    $uri_elements = explode('/',ltrim($_SERVER['REQUEST_URI'],'/'));
    $base_dir = "/media/Data/www/{$uri_elements[0]}";
    $dbname_offset = 0;
}
else {
    $path_elements = explode('/',ltrim(__DIR__,'/'));
    $base_dir = "/{$path_elements[0]}/{$path_elements[1]}/public_html";
    $dbname_offset = 1;
}
require("$base_dir/path_defs.php");
require(__DIR__."/allowed_hosts.php");
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication failure");
}

foreach ($dbinfo as $dbid => $info) {
    $dbname = $info[$dbname_offset];
    if ((!empty($dbname)) && ($db = db_connect($dbid))) {
        print("Processing database [$dbname]<br />\n");
        mysqli_query($db,"SET SESSION innodb_strict_mode = 0");
        mysqli_query($db,"SET SESSION sql_mode = ''");
        $table_field = "Tables_in_$dbname";
        mysqli_set_charset($db,"utf8mb4");
        $query_result = mysqli_query_normal($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type='BASE TABLE'");
        while ($row = mysqli_fetch_assoc($query_result)) {
            $table = $row[$table_field];
            print("... Processing table [$table]");
            try {
                mysqli_query($db,"ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            }
            catch (Exception $e) {
                print(" - <b>ERROR</b>");
            }
            print("<br />\n");
        }
        mysqli_query($db,"ALTER DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }
}
print("Operation Completed<br />\n");
