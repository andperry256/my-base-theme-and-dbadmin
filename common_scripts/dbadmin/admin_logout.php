<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE)
{
    session_start();
}
/*
Log the user off by clearing the username but leaving the $_SESSION[SV_USER]
variable intact. This enables the user to be kept logged off in the local
network environment, which may occasionally be required for testing purposes.

The variables from path_defs.php must be preset by the calling script along
with $dbid which indicates the WordPress database.
*/
require_once("$private_scripts_dir/mysql_connect.php");
$db = db_connect($dbid);
$user_key = SV_USER;
$_SESSION[$user_key] = '';
$where_clause = 'name=?';
$where_values = array('s',$user_key);
mysqli_delete_query($db,'wp_session_updates',$where_clause,$where_values);
if (defined('SV_ACCESS_LEVEL'))
{
    $access_level_key = SV_ACCESS_LEVEL;
    $_SESSION[$access_level_key] = 0;
    $where_clause = 'name=?';
    $where_values = array('s',$access_level_key);
    mysqli_delete_query($db,'wp_session_updates',$where_clause,$where_values);
}
if (isset($_COOKIE[$login_cookie_id]))
{
    setcookie($login_cookie_id,'',time()-3600,$login_cookie_path);
}
header("Location: $base_url");
exit;

//==============================================================================
?>
