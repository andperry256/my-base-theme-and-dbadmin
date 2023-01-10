<?php
	require("allowed_hosts.php");
	if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR'])))
	{
		exit("Authentication failure");
	}
	phpinfo();
?>
