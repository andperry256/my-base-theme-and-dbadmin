<?php
	if (session_status() ==  PHP_SESSION_NONE)
	{
		session_start();
	}
	if (!isset($local_site_dir))
	{
		exit("Local site directory not specified");
	}
	if (!isset($dbid))
	{
		exit("Databse ID not specified");
	}
	$NoAuth = true;
	require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
	require_once("$PrivateScriptsDir/mysql_connect.php");

	/*
	Log the user off by clearing the username but leaving the session record
	intact. This enables the user to be kept logged off in the local network
	enviroment, which may occasionally be required for testing purposes.
	*/
	$db = db_connect($dbid);
	$session_id = session_id();
	$time = time();
	mysqli_query($db,"UPDATE login_sessions SET username='',access_time=$time WHERE session_id='$session_id'");
	header("Location: $BaseURL");
	exit;
?>
