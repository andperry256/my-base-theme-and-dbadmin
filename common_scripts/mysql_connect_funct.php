<?php
//==============================================================================
/*
This script is designed by be included by the mysql_connect.php script for the
given site.
*/
//==============================================================================
/*
Function db_connect

This is the main function to connect to the MySQL database associated with a
given database ID as defined in the $dbinfo array for the site. Each element of
this array has the database ID as the key and is itself an array with the
following elements:-
0 - Local database name.
1 - Online database name.
2 - Default character set (optional).

This function performs a MySQLi connection using either object orientated or
procedural style, as specified by the $mode parameter (defaults to procedural
style).

The database user name normally defaults to that defined by the constant
REAL_DB_USER, but there is the option to override this with an optional
parameter (e.g. to gain elevated access).
*/
//==============================================================================

function db_connect($dbid,$mode='p',$alt_user='')
{
	global $DBMode, $Location, $dbinfo;
	$main_user = (!empty($alt_user))
		? $alt_user
		: REAL_DB_USER;
	switch ($DBMode)
	{
		case 'normal':
		$connect_params = ($Location == 'local')
		? array( 'localhost', $main_user, REAL_DB_PASSWD, $dbinfo[$dbid][0] )
		: array( 'localhost', $main_user, REAL_DB_PASSWD, $dbinfo[$dbid][1] );
		break;
		case 'local':
		$connect_params = array( 'localhost', LOCAL_DB_USER, LOCAL_DB_PASSWD, $dbinfo[$dbid][0] );
		break;
		case 'remote':
		$connect_params = array( REMOTE_DB_HOST, $main_user, REAL_DB_PASSWD, $dbinfo[$dbid][1] );
		break;
	}
	$connect_error = false;
	switch ($mode)
	{
		case 'o':
		// Object orientated style
		$link = new mysqli( $connect_params[0], $connect_params[1], $connect_params[2], $connect_params[3] );
		if ($link->connect_errno)
		{
			$connect_error = true;
		}
		elseif (!empty($dbinfo[$dbid][2]))
		{
			$link->set_charset($dbinfo[$dbid][2]);
		}
		break;
		case 'p':
		// Procedural style
		$link = mysqli_connect( $connect_params[0], $connect_params[1], $connect_params[2], $connect_params[3] );
		if (mysqli_connect_errno())
		{
			$connect_error = true;
		}
		elseif (!empty($dbinfo[$dbid][2]))
		{
			mysqli_set_charset($link,$dbinfo[$dbid][2]);
		}
		break;
	}
	if ($connect_error)
	{
		if (($Location == 'local') && (function_exists('print_stack_trace_for_mysqli_error')))
		{
			print_stack_trace_for_mysqli_error();
		}
		else
		{
			exit("Unable to establish a database connection\n");
		}
	}
	else
	{
		return $link;
	}
}

//==============================================================================
/*
Function wp_db_connect

This function connects to the WordPress database for the site. It always
returns an object.
*/
//==============================================================================

function wp_db_connect()
{
	return db_connect(WP_DBID,'o');
}

//==============================================================================
/*
Function db_name

This function returns the database name associated with a given database ID as
defined in the $dbinfo array for the site.
*/
//==============================================================================

function db_name($dbid)
{
	global $dbinfo, $DBMode, $Location;
	switch ($DBMode)
	{
		case 'normal':
			if ($Location == 'local')
			{
				return $dbinfo[$dbid][0];
			}
			else
			{
				return $dbinfo[$dbid][1];
			}
			break;
		case 'local':
			return $dbinfo[$dbid][0];
			break;
		case 'remote':
			return $dbinfo[$dbid][1];
			break;
	}
}

//==============================================================================
?>
