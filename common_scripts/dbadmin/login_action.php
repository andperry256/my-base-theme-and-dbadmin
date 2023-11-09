<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE)
{
    session_start();
}

$local_site_dir = $_GET['site'];
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require_once("$private_scripts_dir/mysql_connect.php");
$db = db_connect($auth_dbid);
$username = $_POST['username'];
$password = $_POST['password'];
$user_authenticated = false;
$where_clause = "$auth_db_username_field=?";
$where_values = array('s',$username);
if ((preg_match("/^[A-Z0-9.]*$/i", $username)) &&
    ($row = mysqli_fetch_assoc(mysqli_select_query($db,$auth_db_table,'*',$where_clause,$where_values,''))))
{
    if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd']))
    {
        // User authorised
        $_SESSION[SV_USER] = $username;
        if ((isset($row[$auth_db_access_level_field])) && (defined('SV_ACCESS_LEVEL')))
        {
            $_SESSION[SV_ACCESS_LEVEL] = $row[$auth_db_access_level_field];
        }
        header("Location: {$_GET['returnurl']}");
        exit;
    }
}
if (strpos($_GET['returnurl'],'?') === false)
{
    header("Location: {$_GET['returnurl']}?noauth");
}
else
{
    header("Location: {$_GET['returnurl']}&noauth");
}
exit;

//==============================================================================
?>
