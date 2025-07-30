<?php
//==============================================================================
/*
  This script is called to run maintenance on all databases in a given web site.
  The following parameters are passed:

  Par 1 - cPanel username.
  Par 2 - Local site directory sub-path.
  Par 3 - Relative path for given database.
  Par 4 onwards - optional parameters as follows:-
    -ucs - Include updating of charsets.
    -opt - Include table optimisation.
    -pur - Purge all dynamically generated views (i.e. _view_*).
*/
//==============================================================================

// Handle main parameters
if (!isset($argc)) {
    exit("script only allowed in command mode\n");
}
if (count($argv) < 4) {
    exit("ERROR - Missing parameter(s)\n");
}
$cpuser = $argv[1];
$local_site_dir = $argv[2];
$relative_path = $argv[3];
$online_root_dir = "/home/$cpuser";
$root_dir = (is_dir($online_root_dir))
    ? $online_root_dir
    : "/media/Data/Users/Common/Documents/WebSite/Sites/$local_site_dir";

if (is_file("/Config/linux_pathdefs.php")) {
    // Local Server
    require_once("/Config/linux_pathdefs.php");
    require_once("$www_root_dir/Sites/$local_site_dir/public_html/path_defs.php");
}
elseif (is_file("$online_root_dir/public_html/path_defs.php")) {
    // Online Server
    require_once("$online_root_dir/public_html/path_defs.php");
}
else {
    exit("Path definitions file not found\n");
}

// Handle optional parameters
$update_charsets = false;
$optimise = false;
$purge = false;
foreach($argv as $key => $value) {
    if ($value == '-ucs') {
        $update_charsets = true;
    }
    elseif ($value == '-opt') {
        $optimise = true;
    }
    elseif ($value == '-pur') {
        $purge = true;
    }
}

// Run the maintenance
require("$base_dir/mysql_connect.php");
require("$base_dir/common_scripts/dbadmin/widget_types.php");
require("$base_dir/common_scripts/dbadmin/table_funct.php");
require("$base_dir/common_scripts/dbadmin/record_funct.php");
require("$base_dir/common_scripts/dbadmin/view_funct.php");
require("$base_dir/common_scripts/dbadmin/update_table_data.php");
if (is_file("$custom_pages_path/$relative_path/db_funct.php")) {
    require("$custom_pages_path/$relative_path/db_funct.php");
    update_table_data($update_charsets,$optimise,$purge);
}
else {
    exit("File $custom_pages_path/$relative_path/db_funct.php not found.\n");
}

//==============================================================================
?>
