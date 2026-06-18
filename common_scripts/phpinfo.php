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
print("<p style=\"text-align:center; font-size:150%; font-weight:bold\">Loaded Extensions</strong></p>\n");
print("<div style=\"display:block; width:890px; background-color: #ffe; border:solid 1px #666; padding:20px; margin:1.0em auto\">\n");
$extensions = get_loaded_extensions();
usort($extensions, 'strcasecmp');
foreach($extensions as $extension) {
    print("$extension<br />\n");
}
print("</div>\n");

//==============================================================================
