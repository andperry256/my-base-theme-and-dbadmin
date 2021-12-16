<?php
  if (session_status() ==  PHP_SESSION_NONE)
  {
    session_start();
  }

  require_once("../../path_defs.php");
	require_once("$PrivateScriptsDir/mysql_connect.php");
  $db = db_connect($AuthDBID);
  $username = $_POST['username'];
  $password = $_POST['password'];
  $UserAuthenticated = false;
  if ((preg_match("/^[A-Z0-9]*$/i", $username)) &&
      ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM admin_passwords WHERE username='$username'"))))
  {
    if ((!empty($password)) && (crypt($password,$row['enc_passwd']) == $row['enc_passwd']))
    {
      // User authorised
      $_SESSION['user'] = $username;
      $_SESSION['access_level'] = $row['access_level'];
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
