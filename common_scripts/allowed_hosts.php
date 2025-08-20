<?php
//==============================================================================

global $allowed_hosts;
if (is_file(__DIR__."/../../private_scripts/allowed_hosts.php")) {
    include(__DIR__."/../../private_scripts/allowed_hosts.php");
}
else {
    // This should not normally occur
    $additional_allowed_hosts = [];
}
$allowed_hosts = array_merge(['127.0.0.1' => 'Localhost'],$additional_allowed_hosts);

//==============================================================================
