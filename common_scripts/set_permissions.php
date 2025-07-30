<?php
//==============================================================================

require("allowed_hosts.php");
$local_site_dir = $_GET['site'];
require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
if ((isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication Failure");
}
print("<p>Setting directory and file permissions ...</p>\n");
$command = "$root_dir/maintenance/set_php_file_perms_$location";
exec($command);
print("<p>Operation completed</p>\n");

//==============================================================================
?>
