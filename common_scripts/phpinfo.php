<?php
	if (($_SERVER['REMOTE_ADDR'] != '212.159.74.141') && ($_SERVER['REMOTE_ADDR'] != '81.133.202.101') && (substr($_SERVER['REMOTE_ADDR'],0,10) != '192.168.0.'))
	{
		exit("Authentication Failure");
	}
	phpinfo();
?>
