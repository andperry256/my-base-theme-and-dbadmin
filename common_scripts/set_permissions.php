<?php
	require("allowed_hosts.php");
	if ((isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
	{
		exit("Authentication Failure");
	}
	$local_site_dir = $_GET['site'];
	require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
	print("<p>Setting directory and file permissions ...</p>\n");
	$command = "$RootDir/maintenance/set_php_file_perms_$Location";
	exec($command);
	print("<p>Operation completed</p>\n");
?>
