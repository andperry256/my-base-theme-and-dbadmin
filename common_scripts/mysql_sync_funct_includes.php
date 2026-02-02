<?php
//================================================================================
/*
This script contains lines of code that need to be included within the various
functions in the mysql_sync_funct.php script.
*/
//================================================================================

// Global variables pre-defined on calling the function
global $server_station_id;
global $www_root_dir;

// Global variables for use in called functions
global $eol;
global $mysql_error_log;
global $password2;

include("$www_root_dir/$local_site_dir/path_defs.php");
include("$base_dir/mysql_connect.php");

$eol = isset($_SERVER['REMOTE_ADDR']) ? "<br />\n" : "\n";
$password2 = $password;
$mysql_backup_dir = "/media/Data/Users/Common/Documents/MySQL_Backup";
$mysql_error_log = "$mysql_backup_dir/mysql_errors.log";

$local_mysql_backup_dir = "$mysql_backup_dir/$server_station_id";
$remote_mysql_backup_dir = "$mysql_backup_dir/$main_domain";
$local_db = $dbinfo[$dbid][0];
$online_db = $dbinfo[$dbid][1];
$local_sql_script = "$local_mysql_backup_dir/$local_db/db1.sql";
$remote_sql_script = "$remote_mysql_backup_dir/$local_db/db1.sql";
$rsync_command = "rsync -r -e \"ssh -p $online_port -l $cpanel_user -i $private_key_path\"";
$ssh_command = "ssh -p $online_port -l $cpanel_user -i $private_key_path $main_domain";

//==============================================================================
