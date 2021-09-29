<?php
	require("allowed_hosts.php");
	if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
	{
		exit("Authentication failure");
	}
	phpinfo();
?>
