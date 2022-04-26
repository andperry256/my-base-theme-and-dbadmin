<?php
if (session_status() ==  PHP_SESSION_NONE)
{
	session_start();
}
/*
Log the user off by clearing the username but leaving the $_SESSION[SV_USER]
variable intact. This enables the user to be kept logged off in the local
network environment, which may occasionally be required for testing purposes.

The variables $PrivateScriptsDir and $dbid must be preset by the calling script.
*/
require_once("$PrivateScriptsDir/mysql_connect.php");
$db = db_connect($dbid);
$user_key = SV_USER;
$_SESSION[$user_key] = '';
mysqli_query($db,"DELETE FROM wp_session_updates WHERE name='$user_key'");
if (defined('SV_ACCESS_LEVEL'))
{
	$access_level_key = SV_ACCESS_LEVEL;
	$_SESSION[$access_level_key] = 0;
	mysqli_query($db,"DELETE FROM wp_session_updates WHERE name='$access_level_key'");
}
if (isset($_COOKIE[$LoginCookieID]))
{
	setcookie($LoginCookieID,'',time()-3600,$LoginCookiePath);
}
header("Location: $BaseURL");
exit;
?>
