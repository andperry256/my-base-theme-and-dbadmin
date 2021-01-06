<?php
	if (($_SERVER['REMOTE_ADDR'] != '212.159.74.141') && (substr($_SERVER['REMOTE_ADDR'],0,10) != '192.168.0.'))
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
