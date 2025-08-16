<?php
//==============================================================================

if ((isset($base_dir)) && (isset($private_scripts_dir))) {
    require_once("$base_dir/mysql_connect.php");
    require_once("$base_dir/common_scripts/mysql_funct.php");
    require_once("$base_dir/common_scripts/session_funct.php");
}
else {
    exit("Directory paths not found\n");
}

//==============================================================================
