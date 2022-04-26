<?php
  if (session_status() ==  PHP_SESSION_NONE)
  {
    session_start();
  }

  $local_site_dir = $_GET['site'];
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
	require_once("$PrivateScriptsDir/mysql_connect.php");
  $db = db_connect($AuthDBID);
  $username = $_POST['username'];
  $password = $_POST['password'];
  $UserAuthenticated = false;
  if ((preg_match("/^[A-Z0-9.]*$/i", $username)) &&
      ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM $AuthDB_Table WHERE $AuthDB_UsernameField='$username'"))))
  {
    if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd']))
    {
      // User authorised
      $_SESSION[SV_USER] = $username;
      if ((isset($row[$AuthDB_AccessLevelField])) && (defined('SV_ACCESS_LEVEL')))
      {
        $_SESSION[SV_ACCESS_LEVEL] = $row[$AuthDB_AccessLevelField];
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
?>
