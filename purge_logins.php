<?php
	define ('SESSION_TIMEOUT',3600);  // 1 hour
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
	$db = db_connect($dbid);
	$purge_time = time() - SESSION_TIMEOUT;
	mysqli_query($db,"DELETE FROM login_sessions WHERE access_time<$purge_time");
	print("Old logins purged\n");
?>
