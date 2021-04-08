<?php
//================================================================================
/*
 * My Base Theme functions and definitions.
 *
 * Additional functions that may need to be accessed by scripts running
 * outside the WordPress environment.
 */
//================================================================================
/*
 * Function start_session
 *
 * Strictly speaking this function should be held inside the main functions.php
 * script for the theme, but is here because its funtionality is closely
 * linked to that of other functions in this script. The main functions.php
 * script must contain the following statement after including this file:-
 *
 * add_action( 'init', 'start_session', 1);
 */
//================================================================================

function start_session()
{
	global $wpdb;
	global $GlobalSessionVars;
	global $GlobalSessionID;
	if ((!session_id()) && (!headers_sent()))
	{
		session_start();
	}
	$GlobalSessionID = session_id();
	if (is_file('/var/www/html/user_authentication.php'))
	{
		include('/var/www/html/user_authentication.php');
	}
	if (!isset($_SESSION['theme_mode']))
	{
		$_SESSION['theme_mode'] = 'light';
	}
	$query_result = $wpdb->query("SELECT * FROM session_updates");
	if ($query_result !== false)
	{
		// The session_updates table exists in the database which means that the
		// new session handling mechanism is in use.
		$GlobalSessionVars = array();

		// Transfer all updates for the current session from the database to the
		// appropriate $_SESSION variables.
		$query_result2 = $wpdb->get_results("SELECT * FROM session_updates WHERE session_id='$GlobalSessionID'");
		foreach ($query_result2 as $row2)
		{
			if ($row2->type == 'update')
			{
				// Update is a variable assignment
				if ($row2->name2 == '#')
				{
					$_SESSION[$row2->name] = $row2->value;
				}
				else
				{
					$_SESSION[$row2->name][$row2->name2] = $row2->value;
				}
			}
			elseif (($row2->type == 'delete') && (isset($_SESSION[$row2->name])))
			{
				// Update is a variable deletion (unset)
				if ($row2->name2 == '#')
				{
					unset($_SESSION[$row2->name]);
				}
				else
				{
					unset($_SESSION[$row2->name][$row2->name2]);
				}
			}
		}
		$query_result = $wpdb->query("DELETE FROM session_updates WHERE session_id='$GlobalSessionID'");

		// Transfer all $_SESSION variables into the $GlobalSessionVars array.
		foreach($_SESSION as $name => $value)
		{
			if (is_array($_SESSION[$name]))
			{
				$GlobalSessionVars[$name] = array();
				foreach($_SESSION[$name] as $name2 => $value2)
				{
					$GlobalSessionVars[$name][$name2] = $value2;
				}
			}
			else
			{
				$GlobalSessionVars[$name] = $value;
			}
		}

		// Close the session.
		session_write_close();
	}
}

//================================================================================

function session_var_is_set($name,$name2='')
{
	global $GlobalSessionVars;
	if (isset($GlobalSessionVars))
	{
		if (empty($name2))
		{
			return isset($GlobalSessionVars[$name]);
		}
		else
		{
			return isset($GlobalSessionVars[$name][$name2]);
		}
	}
	elseif (empty($name2))
	{
		return isset($_SESSION[$name]);
	}
	else
	{
		return isset($_SESSION[$name][$name2]);
	}
}

//================================================================================

function get_session_var($name,$name2='')
{
	global $GlobalSessionVars;
	if (isset($GlobalSessionVars))
	{
		if (empty($name2))
		{
			return $GlobalSessionVars[$name];
		}
		else
		{
			return $GlobalSessionVars[$name][$name2];
		}
	}
	elseif (empty($name2))
	{
		return $_SESSION[$name];
	}
	else
	{
		return $_SESSION[$name][$name2];
	}
}

//================================================================================

function update_session_var($name,$value,$name2='')
{
	global $GlobalSessionVars;
	global $GlobalSessionID;
	global $wpdb;
	if ((isset($GlobalSessionVars)) && (isset($wpdb)))
	{
		$timestamp = time();
		if (empty($name2))
		{
			$GlobalSessionVars[$name] = $value;
			$select_query = "SELECT * FROM session_updates WHERE session_id='$GlobalSessionID' AND name='$name'";
			$insert_query = "INSERT INTO session_updates (session_id,name,value,type,timestamp) VALUES ('$GlobalSessionID','$name','$value','update',$timestamp)";
			$update_query = "UPDATE session_updates SET value='$value',type='update' WHERE session_id='$GlobalSessionID' AND name='$name'";
		}
		else
		{
			if (!isset($GlobalSessionVars[$name]))
			{
				$GlobalSessionVars[$name] = array();
			}
			$GlobalSessionVars[$name][$name2] = $value;
			$select_query = "SELECT * FROM session_updates WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
			$insert_query = "INSERT INTO session_updates (session_id,name,name2,value,type,timestamp) VALUES ('$GlobalSessionID','$name','$name2','$value','update',$timestamp)";
			$update_query = "UPDATE session_updates SET value='$value',type='update' WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
		}
		$query_result = $wpdb->query($select_query);
		if ($query_result->num_rows == 0)
		{
			$wpdb->query($insert_query);
		}
		else
		{
			$wpdb->query($update_query);
		}
	}
	elseif (empty($name2))
	{
		$_SESSION[$name] = $value;
	}
	else
	{
		if (!isset($_SESSION[$name]))
		{
			$_SESSION[$name] = array();
		}
		$_SESSION[$name][$name2] = $value;
	}
}

//================================================================================

function delete_session_var($name,$name2='')
{
	global $GlobalSessionVars;
	global $GlobalSessionID;
	global $wpdb;
	if ((isset($GlobalSessionVars)) && (isset($wpdb)))
	{
		$timestamp = time();
		if (empty($name2))
		{
			if (isset($GlobalSessionVars[$name]))
			{
				unset($GlobalSessionVars[$name]);
			}
			$select_query = "SELECT * FROM session_updates WHERE session_id='$GlobalSessionID' AND name='$name'";
			$insert_query = "INSERT INTO session_updates (session_id,name,type,timestamp) VALUES ('$GlobalSessionID','$name','delete',$timestamp)";
			$update_query = "UPDATE session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='$name'";
		}
		else
		{
			if (isset($GlobalSessionVars[$name][$name2]))
			{
				unset($GlobalSessionVars[$name][$name2]);
			}
			$select_query = "SELECT * FROM session_updates WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
			$insert_query = "INSERT INTO session_updates (session_id,name,name2,type,timestamp) VALUES ('$GlobalSessionID','$name','$name2','delete',$timestamp)";
			$update_query = "UPDATE session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
		}
		$query_result = $wpdb->query($select_query);
		if ($query_result->num_rows == 0)
		{
			$wpdb->query($insert_query);
		}
		else
		{
			$wpdb->query($update_query);
		}
	}
	elseif ((empty($name2)) && (isset($_SESSION[$name])))
	{
		unset($_SESSION[$name]);
	}
	elseif ((!empty($name2)) && (isset($_SESSION[$name][$name2])))
	{
		unset($_SESSION[$name][$name2]);
	}
}

//================================================================================
?>
