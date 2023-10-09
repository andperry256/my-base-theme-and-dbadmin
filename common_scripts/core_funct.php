<?php
//==============================================================================

if ((isset($BaseDir)) && (isset($PrivateScriptsDir)))
{
    require_once("$PrivateScriptsDir/mysql_connect.php");
    require_once("$BaseDir/common_scripts/mysql_funct.php");
    require_once("$BaseDir/common_scripts/session_funct.php");
}
else
{
    exit("Directory paths not found\n");
}

//==============================================================================
?>
