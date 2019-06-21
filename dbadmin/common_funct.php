<?php
//==============================================================================
/*
	This file contains those functions that may need to be called from outside
	the DB admin interface.
*/
//==============================================================================
if (!function_exists('encode_record_id'))
{
//==============================================================================
/*
Function encode_record_id
*/
//==============================================================================

function encode_record_id($fields)
{
	$result = '';
	ksort($fields);
	foreach($fields as $name => $value)
	{
		$result .= urlencode($name).'='.urlencode($value).'/';
	}
	return urlencode($result);
}

//==============================================================================
/*
Functions decode_record_id / fully_decode_record_id
*/
//==============================================================================

function decode_record_id($record_id)
{
	$result = array();
	$tok = strtok($record_id,'=');
	while ($tok !== false)
	{
		$field_name = urldecode($tok);
		$tok = strtok('/');
		$field_value = urldecode($tok);
		$result[$field_name] = $field_value;
		$tok = strtok('=');
	}
	return $result;
}

function fully_decode_record_id($record_id)
{
	$record_id = urldecode($record_id);
	return decode_record_id($record_id);
}

//==============================================================================
/*
Function cur_url_par
*/
//==============================================================================

function cur_url_par()
{
	if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']))
	{
		return urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
	}
	else
	{
		return urlencode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
	}
}

//==============================================================================
/*
Function user_is_authenticated
*/
//==============================================================================

function user_is_authenticated()
{
	global $AuthDBID;
	$db = db_connect($AuthDBID);
	$session_id = session_id();
	$query_result = mysqli_query($db,"SELECT * FROM login_sessions WHERE session_id='$session_id'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		return true;
	}
	else
	{
		return false;
	}
}

//==============================================================================
/*
Function check_new_action
*/
//==============================================================================

function check_new_action($action,$table)
{
	if (!isset($_SESSION['dba_action']))
	{
		$_SESSION['dba_action'] = '';
	}
	if (!isset($_SESSION['dba_table']))
	{
		$_SESSION['dba_table'] = '';
	}
	if (($action != $_SESSION['dba_action']) || ($table != $_SESSION['dba_table']))
	{
		// Action and/or table has changed - clear temporary session variables
		if (isset($_SESSION['get_vars']))
		{
			unset($_SESSION['get_vars']);
		}
		if (isset($_SESSION['post_vars']))
		{
			unset($_SESSION['post_vars']);
		}
	}
	$_SESSION['dba_action'] = $action;
	$_SESSION['dba_table'] = $table;
}

//==============================================================================
}
//==============================================================================
?>
