<?php
//==============================================================================
if (!function_exists('get_table_access_level'))
{
/*
Function get_table_access_level
*/
//==============================================================================

function get_table_access_level($table)
{
	global $Location, $RelativePath, $db_master_location;
	$db = admin_db_connect();
	$db_sub_path = str_replace('dbadmin/','',$RelativePath);
	if (isset($db_master_location[$db_sub_path]))
	{
		$master_location = $db_master_location[$db_sub_path];
	}
	else
	{
		return('read-only');  // This should not occur
	}

	$query_result = mysqli_query_normal($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
	if ((session_var_is_set('read_only')) && (get_session_var('read_only')))
	{
		$access_level = 'read-only';
	}
	elseif ($row = mysqli_fetch_assoc($query_result))
	{
		$access_level = $row[$Location.'_access'];
		if ($access_level == 'auto-full')
		{
			if ($Location == $master_location)
			{
				return 'full';
			}
			else
			{
				return 'read-only';
			}
		}
		elseif ($access_level == 'auto-edit')
		{
			if ($Location == $master_location)
			{
				return 'edit';
			}
			else
			{
				return 'read-only';
			}
		}
		else
		{
			return $access_level;
		}
	}
	else
	{
		$access_level = 'read-only';  // This should not occur
	}
}

//==============================================================================
/*
Function hs
*/
//==============================================================================

function hs()
{
	print("<div class=\"halfspace\">&nbsp</div>\n");
}

//==============================================================================
/*
Functions next_seq_number and update_seq_number

These functions are used to determine/set the value of the sequence number field
for a new table record, interpreting the value of constant NEXT_SEQ_NO_INDICATOR
as an indication to use the next number in sequence.

The afterSave method for a given table class would typically call
update_seq_number to perform the whole update operation for the sequence number.
The next_seq_number function (apart from being called from within
update_seq_number) would only be called in special circumstances.
*/
//==============================================================================

function next_seq_number($table,$sort_1_value,$interval=10)
{
	if (!defined('NEXT_SEQ_NO_INDICATOR'))
	{
		exit("Constant NEXT_SEQ_NO_INDICATOR not defined");
	}
	$db = admin_db_connect();
	$table = get_table_for_info_field($table,'seq_no_field');
	$row = mysqli_fetch_assoc(mysqli_query_strict($db,"SELECT * FROM dba_table_info WHERE table_name='$table'"));
	if ((isset($row['seq_no_field'])) && (!empty($row['seq_no_field'])))
	{
		// Calculate next sequence number, given:-
		// (1) Table name.
		// (2) First-level sort field value (optional).
		$sort_1_name = $row['sort_1_field'];
		$seq_no_name =  $row['seq_no_field'];
		$seq_type = $row['seq_method'];
		$next_seq_no_indicator = NEXT_SEQ_NO_INDICATOR;
		$query = "SELECT * FROM $table WHERE $seq_no_name<>$next_seq_no_indicator";
		if ((!empty($sort_1_name)) && ($seq_type == 'repeat'))
		{
			$sort_1_value = addslashes($sort_1_value);
			$query .= " AND  $sort_1_name='$sort_1_value'";
		}
		$query .= " ORDER BY $seq_no_name DESC";
		$query_result = mysqli_query_normal($db,$query);
		return ($row = mysqli_fetch_assoc($query_result))
			? $row[$seq_no_name] + $interval
			: $interval;
	}
	else
	{
		// This should not occur
		return NEXT_SEQ_NO_INDICATOR;
	}
}

function update_seq_number($table,$sort_1_value,$seq_no,$interval=10)
{
	if (!defined('NEXT_SEQ_NO_INDICATOR'))
	{
		exit("Constant NEXT_SEQ_NO_INDICATOR not defined");
	}
	$db = admin_db_connect();
	$row = mysqli_fetch_assoc(mysqli_query_strict($db,"SELECT * FROM dba_table_info WHERE table_name='$table'"));
	if (($seq_no == NEXT_SEQ_NO_INDICATOR) && (isset($row['seq_no_field'])) && (!empty($row['seq_no_field'])))
	{
		// Update record with new sequence number
		$new_seq_no = next_seq_number($table,$sort_1_value,$interval);
		$sort_1_name = $row['sort_1_field'];
		$seq_no_name =  $row['seq_no_field'];
		$primary_keys = array();
		$query = "UPDATE $table SET $seq_no_name=$new_seq_no WHERE $seq_no_name=$seq_no";
		if (!empty($sort_1_name))
		{
			$primary_keys[$sort_1_name] = $sort_1_value;
			$sort_1_value = addslashes($sort_1_value);
			$query .= " AND $sort_1_name='$sort_1_value'";
		}
		mysqli_query_normal($db,$query);
		if (function_exists('update_session_var'))
		{
			$primary_keys[$seq_no_name] = $new_seq_no;
			$record_id = encode_record_id($primary_keys);
			update_session_var('saved_record_id',$record_id);
		}
		return $new_seq_no;
	}
	else
	{
		// No update required
		return $seq_no;
	}
}

//==============================================================================
/*
Function enable_non_null_empty
*/
//==============================================================================

function enable_non_null_empty($table,$field)
{
	$db = admin_db_connect();
	mysqli_query_normal($db,"UPDATE dba_table_fields SET required=1 WHERE table_name='$table' AND field_name='$field'");
}

//==============================================================================
/*
Function time_compare
*/
//==============================================================================

function time_compare($time1,$time2)
{
	return (strtotime($time1)) - (strtotime($time2));
}

//==============================================================================
/*
Function get_viewing_mode
*/
//==============================================================================

function get_viewing_mode()
{
	if (isset($_COOKIE['viewing_mode']))
	{
		return $_COOKIE['viewing_mode'];
	}
	else
	{
		return 'desktop';
	}
}

//==============================================================================
}
//==============================================================================
?>
