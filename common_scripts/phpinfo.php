<?php
//==============================================================================

if (is_file("/Config/local_network.php")) {
    require("/Config/local_network.php");
}
require("allowed_hosts.php");
require("local_ip_funct.php");
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication failure");
}
phpinfo();

//==============================================================================
?>
