<?php
	if (session_status() ==  PHP_SESSION_NONE)
	{
		session_start();
	}
	require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");

	/*
	Log the user off by clearing the username but leaving the $_SESSION[SV_USER]
	variable intact. This enables the user to be kept logged off in the local
	network enviroment, which may occasionally be required for testing purposes.
	*/
	$_SESSION[SV_USER] = '';
	header("Location: $BaseURL");
	exit;
?>
