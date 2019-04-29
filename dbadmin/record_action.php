<?php
//==============================================================================
/*
This script is specified as the action script for the main record edit form.
It runs as a standalone script (i.e. not within the WordPress framework).

The script intentionally generates no output to the client during normal
operation, so that at the end it can re-direct back to the originator script
using the header() function. The $_POST variables are all conveyed back to the
originator script within the $_SESSION['post_vars'] array.
*/
//==============================================================================

session_start();
error_reporting(E_ALL & ~E_DEPRECATED);

// Interpret the URL parameters
if (isset($_GET['-action']))
{
  $action = $_GET['-action'];
}
else
{
  die("No action specified\n");
}

if (isset($_GET['-table']))
{
  $table = $_GET['-table'];
}
else
{
  die("No table specified\n");
}

if (isset($_GET['-recordid']))
{
  $record_id = $_GET['-recordid'];
}
elseif ($action == 'new')
{
  $record_id = '';
}
else
{
  die("No record ID specified\n");
}

if (isset($_GET['-basedir']))
{
  $BaseDir = $_GET['-basedir'];
}
else
{
  die("No base directory specified\n");
}

if (isset($_GET['-relpath']))
{
  $RelativePath = $_GET['-relpath'];
}
else
{
  die("No relative path specified\n");
}

// Save all the $_GET and $_POST variables for use by the next script
if (isset($_SESSION['get_vars']))
{
  unset($_SESSION['get_vars']);
}
foreach ($_GET as $key => $value)
{
  $_SESSION['get_vars'][$key] = $value;
}
if (isset($_SESSION['post_vars']))
{
  unset($_SESSION['post_vars']);
}
foreach ($_POST as $key => $value)
{
  $_SESSION['post_vars'][$key] = $value;
}

require("$BaseDir/path_defs.php");
require("$DBAdminDir/functions.php");
require("$DBAdminDir/classes.php");
$NoAction = true;
require("$CustomPagesPath/$RelativePath/_home.php");
$RelativePath = $_GET['-relpath'];  // Required because value is getting corrupted (not sure why)
if (is_file("$CustomPagesPath/$RelativePath/tables/$table/$table.php"))
{
  require("$CustomPagesPath/$RelativePath/tables/$table/$table.php");
}
$base_table = get_base_table($table);
$classname = "tables_$table";
$base_classname = "tables_$base_table";
if ((!class_exists ($classname,false))  && (!class_exists ($base_classname,false)))
{
  die("Table class not found\n");
}
$base_table = get_base_table($table);
$db = admin_db_connect();
$dbase = admin_db_name();

// Handle the saving of the record.
$record = new db_record;
$new_primary_keys = array();
$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
while ($row = mysqli_fetch_assoc($query_result))
{
  $field_name = $row['Field'];
  $query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
  if ($row2 = mysqli_fetch_assoc($query_result2))
  {
    if ($row2['widget_type'] == 'checkbox')
    {
      if (isset($_POST["field_$field_name"]))
      {
        $record->SetField($field_name,1);
      }
      else
      {
        $record->SetField($field_name,0);
      }
    }
    else
    {
      $record->SetField($field_name,stripslashes($_POST["field_$field_name"]));
    }
    if ($row2['is_primary'])
    {
      if (($action == 'new') && ($row2['widget_type'] == 'auto-increment'))
      {
        // Predict the next auto-increment value for the field
        $query_result3 = mysqli_query($db,"SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dbase' AND TABLE_NAME='$base_table'");
        if ($row3 = mysqli_fetch_assoc($query_result3))
        {
          $new_primary_keys[$field_name] = $row3['AUTO_INCREMENT'];
        }
        else
        {
          exit("Failed to obtain next auto-increment value - this should not occur");
        }
      }
      else
      {
        $new_primary_keys[$field_name] = stripslashes($_POST["field_$field_name"]);
      }
    }
  }
}
$record->action = $action;
$record->table = $table;

/*
  Need to urlencode the old record ID for comparison with the new, because
  the old ID was copied from a $_GET variable and will thus have already
  undergone a urldecode.
*/
$old_record_id = urlencode($record_id);
$new_record_id = encode_record_id($new_primary_keys);

/*
  Save the new record ID as a session variable. This may be overridden
  by an action in the afterSave function, thus allowing an updated record ID
  to be used should a primary key field be updated by afterSave.
*/
$_SESSION['saved_record_id'] = $new_record_id;

if (save_record($record,$old_record_id,$new_record_id))
{
  // Record successfully saved
  if ($action == 'new')
  {
    // Update the action to ensure that a duplicate record is not created on
    // a repeat save.
    $action = 'edit';
  }
  header("Location: $BaseURL/$RelativePath/?-action=$action&-table=$table&-recordid={$_SESSION['saved_record_id']}&-saveresult=1");
  exit;
}
else
{
  // An error condition has occurred
  header("Location: $BaseURL/$RelativePath/?-action=$action&-table=$table&-recordid=$old_record_id&-saveresult=0");
  exit;
}

//==============================================================================
?>
