<?php
//================================================================================
if (!function_exists('run_session'))
{
//================================================================================
/*
 * Function run_session
 *
 * This function is used both inside and outside the WordPress environment.
 * When used inside WordPress it is invoked through the inclusion of this file
 * within the main functions.php file for the theme. The latter must run the
 * following statement immediately after the file inclusion:-
 *
 * add_action( 'init', 'run_session', 1);
 */
//================================================================================

function run_session()
{
  global $wpdb;
  global $GlobalSessionVars;
  global $GlobalSessionID;
  if ((!session_id()) && (!headers_sent()))
  {
    session_start();
  }
  if (!session_id())
  {
    // This should not occur
    exit ("ERROR - Unable to start session");
  }
  $GlobalSessionID = session_id();

  if ((isset($wpdb)) && (function_exists('have_posts')))
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
    $wpdb = wp_db_connect();
    $env = 'non-wp';
  }
  if (!isset($wpdb))
  {
    // This should not occur
    exit("ERROR - Unable to connect to the WP database.");
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
      // Inside the WordPress environment
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
      // Outside the WordPress environment
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
    $wpdb->query("DELETE FROM wp_session_updates WHERE session_id='$GlobalSessionID'");

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
/*
 * Session variable handling functions
 *
 * This section includes the following functions:-
 * 1. session_var_is_set
 * 2. get_session_var
 * 3. update_session_var
 * 4. delete_session_var
 *
 * Each function determines whether actual $_SESSION variables are in force
 * or whether they have been saved to $GlobalSessionVars and the PHP session
 * closed.
 *
 * Each function takes a parameter '$name' which can be one of:-
 * 1. A single name to index a simple array.
 * 2. An array of two names to index a 2-dimensional array.
 */
 //================================================================================

 function session_var_is_set($name)
 {
   global $GlobalSessionVars;
   if (!is_array($name))
   {
     $name = array($name,'');
   }

   if (count($name) != 2)
   {
     return false;
   }
   elseif (isset($GlobalSessionVars))
   {
     if (empty($name[1]))
     {
       return isset($GlobalSessionVars[$name[0]]);
     }
     else
     {
       return isset($GlobalSessionVars[$name[0]][$name[1]]);
     }
   }
   elseif (empty($name[1]))
   {
     return isset($_SESSION[$name[0]]);
   }
   else
   {
     return isset($_SESSION[$name[0]][$name[1]]);
   }
 }

 //================================================================================

 function get_session_var($name,$check=true)
 {
   global $GlobalSessionVars;
   if (!is_array($name))
   {
     $name = array($name,'');
   }

   if (count($name) != 2)
   {
     return false;
   }
   elseif (($check) && (!session_var_is_set($name[0],$name[1])))
   {
     return false;
   }
   elseif (isset($GlobalSessionVars))
   {
     if (empty($name[1]))
     {
       return $GlobalSessionVars[$name[0]];
     }
     else
     {
       return $GlobalSessionVars[$name[0]][$name[1]];
     }
   }
   elseif (empty($name[1]))
   {
     return $_SESSION[$name[0]];
   }
   else
   {
     return $_SESSION[$name[0]][$name[1]];
   }
 }

 //================================================================================

 function update_session_var($name,$value)
 {
   global $GlobalSessionVars;
   global $GlobalSessionID;
   global $wpdb;
   if (!isset($wpdb))
   {
     if (function_exists('wp_db_connect'))
     {
       $wpdb = wp_db_connect();
     }
     else
     {
       exit("ERROR - Unable to connect to WP database.");
     }
   }
   if (!is_array($name))
   {
     $name = array($name,'');
   }

   if (count($name) != 2)
   {
     return false;
   }
   elseif (isset($GlobalSessionVars))
   {
     $timestamp = time();
     $old_timestamp = $timestamp - 86400;  // 24 hours ago
     $wpdb->query("DELETE FROM wp_session_updates WHERE timestamp<$old_timestamp");
     $value_par = addslashes($value);
     if (empty($name[1]))
     {
       $GlobalSessionVars[$name[0]] = $value;
       $select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
       $insert_query = "INSERT INTO wp_session_updates (session_id,name,value,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','$value_par','update',$timestamp)";
       $update_query = "UPDATE wp_session_updates SET value='$value_par',type='update' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
     }
     else
     {
       if (!isset($GlobalSessionVars[$name[0]]))
       {
         $GlobalSessionVars[$name[0]] = array();
       }
       $GlobalSessionVars[$name[0]][$name[1]] = $value;
       $select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
       $insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,value,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','{$name[1]}','$value_par','update',$timestamp)";
       $update_query = "UPDATE wp_session_updates SET value='$value_par',type='update' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
     }
     $query_result = $wpdb->query($select_query);
     if (isset($query_result->num_rows))
     {
       $num_rows = $query_result->num_rows;
     }
     elseif(isset($wpdb->num_rows))
     {
       $num_rows = $wpdb->num_rows;
     }
     else
     {
       // This should not occur
       $num_rows = -1;
     }
     if ($num_rows == 0)
     {
       $wpdb->query($insert_query);
     }
     else
     {
       $wpdb->query($update_query);
     }
   }
   elseif (empty($name[1]))
   {
     $_SESSION[$name[0]] = $value;
   }
   else
   {
     if (!isset($_SESSION[$name[0]]))
     {
       $_SESSION[$name[0]] = array();
     }
     $_SESSION[$name[0]][$name[1]] = $value;
   }
 }

 //================================================================================

 function delete_session_var($name)
 {
   global $GlobalSessionVars;
   global $GlobalSessionID;
   global $wpdb;
   if (!isset($wpdb))
   {
     if (function_exists('wp_db_connect'))
     {
       $wpdb = wp_db_connect();
     }
     else
     {
       exit("ERROR - Unable to connect to WP database.");
     }
   }
   if (!is_array($name))
   {
     $name = array($name,'');
   }

   if (count($name) != 2)
   {
     return false;
   }
   elseif (isset($GlobalSessionVars))
   {
     $timestamp = time();
     if (empty($name[1]))
     {
       if (isset($GlobalSessionVars[$name[0]]))
       {
         unset($GlobalSessionVars[$name[0]]);
       }
       $select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
       $insert_query = "INSERT INTO wp_session_updates (session_id,name,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','delete',$timestamp)";
       $update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
     }
     else
     {
       if (isset($GlobalSessionVars[$name[0]][$name[1]]))
       {
         unset($GlobalSessionVars[$name[0]][$name[1]]);
       }
       $select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
       $insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','{$name[1]}','delete',$timestamp)";
       $update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
     }
     $query_result = $wpdb->query($select_query);
     if (isset($query_result->num_rows))
     {
       $num_rows = $query_result->num_rows;
     }
     elseif(isset($wpdb->num_rows))
     {
       $num_rows = $wpdb->num_rows;
     }
     else
     {
       // This should not occur
       $num_rows = -1;
     }
     if ($num_rows == 0)
     {
       $wpdb->query($insert_query);
     }
     else
     {
       $wpdb->query($update_query);
     }
   }
   elseif ((empty($name[1])) && (isset($_SESSION[$name[0]])))
   {
     unset($_SESSION[$name[0]]);
   }
   elseif ((!empty($name[1])) && (isset($_SESSION[$name[0]][$name[1]])))
   {
     unset($_SESSION[$name[0]][$name[1]]);
   }
 }
//================================================================================
}
//================================================================================
?>
