<?php
	require("allowed_hosts.php");
	if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,10) != '192.168.0.'))
	{
		exit("Authentication failure");
	}
	phpinfo();
?>
