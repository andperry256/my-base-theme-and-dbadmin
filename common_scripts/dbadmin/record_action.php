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

error_reporting(E_ALL & ~E_DEPRECATED);
if ((!session_id()) && (!headers_sent()))
{
    session_start();
}

// Interpret the URL parameters
if (isset($_GET['-action']))
{
    $action = $_GET['-action'];
}
else
{
    exit("No action specified\n");
}

if (isset($_GET['-table']))
{
    $table = $_GET['-table'];
}
else
{
    exit("No table specified\n");
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
    exit("No record ID specified\n");
}

if (isset($_GET['-basedir']))
{
    $base_dir = $_GET['-basedir'];
}
else
{
    exit("No base directory specified\n");
}

if (isset($_GET['-relpath']))
{
    $relative_path = $_GET['-relpath'];
}
else
{
    exit("No relative path specified\n");
}

require("$base_dir/path_defs.php");
require("$base_dir/mysql_connect.php");
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
require("$db_admin_dir/functions.php");
require("$db_admin_dir/classes.php");
$post_copy = $_POST;  // deslash does not seem to be required any longer here
$no_action = true;
require("$custom_pages_path/$relative_path/_home.php");
$relative_path = $_GET['-relpath'];  // Required because value is getting corrupted (not sure why)

if (($action == 'edit') && (isset($_POST['save_as_new'])))
{
    $action = 'new';
    update_session_var(array('dba_action',$relative_path),$action);
    $record_id = '';
}
run_session();

// Save all the $_GET and $_POST variables for use by the next script
if (session_var_is_set('get_vars'))
{
    delete_session_var('get_vars');
}
foreach ($_GET as $key => $value)
{
    update_session_var(array('get_vars',$key),$value);
}
if (session_var_is_set('post_vars'))
{
    delete_session_var('post_vars');
}
foreach ($_POST as $key => $value)
{
    update_session_var(array('post_vars',$key),$value);
}
if (is_file("$custom_pages_path/$relative_path/tables/$table/$table.php"))
{
    require("$custom_pages_path/$relative_path/tables/$table/$table.php");
}
elseif (is_file("$alt_include_path/tables/$table/$table.php"))
{
    require("$alt_include_path/tables/$table/$table.php");
}
$base_table = get_base_table($table);
$classname = "tables_$table";
$base_classname = "tables_$base_table";
if ((!class_exists ($classname,false))  && (!class_exists ($base_classname,false)))
{
    exit("Table class not found\n");
}
$base_table = get_base_table($table);
$db = admin_db_connect();
$dbase = admin_db_name();

// Handle the saving of the record.
$record = new db_record;
$new_primary_keys = array();
$query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
while ($row = mysqli_fetch_assoc($query_result))
{
    $field_name = $row['Field'];
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$field_name);
    $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
    if ($row2 = mysqli_fetch_assoc($query_result2))
    {
        if ($row2['widget_type'] == 'checkbox')
        {
            if (isset($_POST["field_$field_name"]))
            {
                $record->SetField($field_name,1,'i');
            }
            else
            {
                $record->SetField($field_name,0,'i');
            }
        }
        elseif ($row2['widget_type'] == 'checklist')
        {
            $field_value = '^';
            foreach ($_POST as $key => $value)
            {
                if (strpos($key,"item_$field_name"."___") !== false)
                {
                    $item = urldecode(substr($key,strlen("item_$field_name"."___")));
                    $field_value .= "$item^";
                }
            }
            $record->SetField($field_name,$field_value,'s');
        }
        elseif(($row2['widget_type'] == 'file') && (!empty(basename($_FILES["file_$field_name"]['name']))))
        {
            $record->SetField($field_name,basename($_FILES["file_$field_name"]['name']),'s');
        }
        else
        {
            $record->SetField($field_name,$post_copy["field_$field_name"],query_field_type($db,$table,$field_name));
        }

        if ($row2['is_primary'])
        {
            if (($action == 'new') && ($row2['widget_type'] == 'auto-increment'))
            {
                // Predict the next auto-increment value for the field
                $where_clause = 'TABLE_SCHEMA=? AND TABLE_NAME=?';
                $where_values = array('s',$dbase,'s',$base_table);
                $query_result3 = mysqli_select_query($db,'information_schema.TABLES','AUTO_INCREMENT',$where_clause,$where_values,'');
                if ($row3 = mysqli_fetch_assoc($query_result3))
                {
                    $new_primary_keys[$field_name] = $row3['AUTO_INCREMENT'];
                }
                else
                {
                    exit("Failed to obtain next auto-increment value - this should not occur");
                }
            }
            elseif ($row2['widget_type'] == 'time')
            {
                $time = strtotime($_POST["field_$field_name"]);
                $new_primary_keys[$field_name] = date("H:i:s",$time);
            }
            elseif (($row2['widget_type'] == 'file') && (!empty(basename($_FILES["file_$field_name"]['name']))))
            {
                $new_primary_keys[$field_name] = basename($_FILES["file_$field_name"]['name']);
            }
            else
            {
                $new_primary_keys[$field_name] = $post_copy["field_$field_name"];
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
update_session_var('saved_record_id',$new_record_id);

if (save_record($record,$old_record_id,$new_record_id))
{
    // Record successfully saved
    if ($action == 'new')
    {
        // Update the action to ensure that a duplicate record is not created on
        // a repeat save.
        $action = 'edit';
    }
    header("Location: $base_url/$relative_path/?-action=$action&-table=$table&-recordid=".get_session_var('saved_record_id')."&-saveresult=1");
    exit;
}
else
{
    // An error condition has occurred
    header("Location: $base_url/$relative_path/?-action=$action&-table=$table&-recordid=$old_record_id&-saveresult=0");
    exit;
}

//==============================================================================
?>
