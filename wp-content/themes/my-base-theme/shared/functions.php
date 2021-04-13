<?php
//================================================================================
/*
 * My Base Theme functions and definitions.
 *
 * Additional functions that may need to be accessed by scripts running
 * outside the WordPress environment.
 */
 //================================================================================

if (!function_exists('start_session'))
{

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
	else
	{
		// This should not occur
		exit ("ERROR - Unable to start session");
	}
	$GlobalSessionID = session_id();

	if (isset($wpdb))
	{
		// Running inside the WP environment
		if (is_file('/var/www/html/user_authentication.php'))
		{
			include('/var/www/html/user_authentication.php');
		}
		if (!isset($_SESSION['theme_mode']))
		{
			$_SESSION['theme_mode'] = 'light';
		}
		$env = 'wp';
	}
	elseif (function_exists('wp_db_connect'))
	{
		// Running outside the WP environment
		wp_db_connect();
		$env = 'non-wp';
	}
	if (!isset($wpdb))
	{
		// This should not occur
		exit("ERROR - Unable to connect to WP database.");
	}

	/*
	The following query checks whether the table wp_session_updates is present.
	If it is then the current session variables are all transferred to the array
	$GlobalSessionVars and the PHP session is closed.
	If the table is not present then no action is performed and the PHP session
	left permanently open (not recommended).
	*/

	$query_result = $wpdb->query("SELECT * FROM wp_session_updates");
	if ($query_result !== false)
	{
		// Transfer all updates for the current session from the database to the
		// appropriate $_SESSION variables.
		if ($env == 'wp')
		{
			// Inside WordPress environment
			$query_result2 = $wpdb->get_results("SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID'");
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
						if (!isset($_SESSION[$row2->name]))
						{
							$_SESSION[$row2->name] = array();
						}
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
		}
		else
		{
			// Outside WordPress environment
			$query_result2 = $wpdb->query("SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID'");
			while ($row2 = $query_result2->fetch_assoc())
			{
				if ($row2['type'] == 'update')
				{
					// Update is a variable assignment
					if ($row2['name2'] == '#')
					{
						$_SESSION[$row2['name']] = $row2['value'];
					}
					else
					{
						if (!isset($_SESSION[$row2['name']]))
						{
							$_SESSION[$row2['name']] = array();
						}
						$_SESSION[$row2['name']][$row2['name2']] = $row2['value'];
					}
				}
				elseif (($row2['type'] == 'delete') && (isset($_SESSION[$row2['name']])))
				{
					// Update is a variable deletion (unset)
					if ($row2['name2'] == '#')
					{
						unset($_SESSION[$row2['name']]);
					}
					else
					{
						unset($_SESSION[$row2['name']][$row2['name2']]);
					}
				}
			}
		}
		$query_result = $wpdb->query("DELETE FROM wp_session_updates WHERE session_id='$GlobalSessionID'");

		// Transfer all $_SESSION variables into the $GlobalSessionVars array.
		$GlobalSessionVars = array();
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
	if (!isset($wpdb))
	{
		if (function_exists('wp_db_connect'))
		{
			wp_db_connect();
		}
		else
		{
			// This should not occur
			exit("ERROR - Unable to connect to WP database.");
		}
	}
	if (isset($GlobalSessionVars))
	{
		$timestamp = time();
		$old_timestamp = $timestamp - 86400;  // 24 hours ago
		$wpdb->query("DELETE FROM wp_session_updates WHERE timestamp<$old_timestamp");
		if (empty($name2))
		{
			$GlobalSessionVars[$name] = $value;
			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='$name'";
			$insert_query = "INSERT INTO wp_session_updates (session_id,name,value,type,timestamp) VALUES ('$GlobalSessionID','$name','$value','update',$timestamp)";
			$update_query = "UPDATE wp_session_updates SET value='$value',type='update' WHERE session_id='$GlobalSessionID' AND name='$name'";
		}
		else
		{
			if (!isset($GlobalSessionVars[$name]))
			{
				$GlobalSessionVars[$name] = array();
			}
			$GlobalSessionVars[$name][$name2] = $value;
			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
			$insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,value,type,timestamp) VALUES ('$GlobalSessionID','$name','$name2','$value','update',$timestamp)";
			$update_query = "UPDATE wp_session_updates SET value='$value',type='update' WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
		}
		$query_result = $wpdb->query($select_query);
		if ((!$query_result) || ($query_result->num_rows == 0))
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
	if (!isset($wpdb))
	{
		if (function_exists('wp_db_connect'))
		{
			wp_db_connect();
		}
		else
		{
			// This should not occur
			exit("ERROR - Unable to connect to WP database.");
		}
	}
	if (isset($GlobalSessionVars))
	{
		$timestamp = time();
		if (empty($name2))
		{
			if (isset($GlobalSessionVars[$name]))
			{
				unset($GlobalSessionVars[$name]);
			}
			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='$name'";
			$insert_query = "INSERT INTO wp_session_updates (session_id,name,type,timestamp) VALUES ('$GlobalSessionID','$name','delete',$timestamp)";
			$update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='$name'";
		}
		else
		{
			if (isset($GlobalSessionVars[$name][$name2]))
			{
				unset($GlobalSessionVars[$name][$name2]);
			}
			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
			$insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,type,timestamp) VALUES ('$GlobalSessionID','$name','$name2','delete',$timestamp)";
			$update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='$name' AND name2='$name2'";
		}
		$query_result = $wpdb->query($select_query);
		if ((!$query_result) || ($query_result->num_rows == 0))
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
}
//================================================================================
?>
