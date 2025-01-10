<?php
//==============================================================================

global $allowed_hosts, $location;
require(__DIR__."/../../private_scripts/allowed_hosts.php");
$allowed_hosts = array_merge(array('127.0.0.1' => 'Localhost'),$additional_allowed_hosts);
if (false) // (($location == 'real') && (!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])))
{
    exit("Authentication failure");
}

//==============================================================================
?>
