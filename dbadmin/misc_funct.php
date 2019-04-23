<?php
//==============================================================================
if (!function_exists('get_master_location'))
{
//==============================================================================
/*
Function get_master_location
*/
//==============================================================================

function get_master_location($default)
{
	global $PrivateScriptsDir;
	global $local_site_dir;
	global $RelativePath;
	global $MainDomain;
	global $Location;

	require_once("$PrivateScriptsDir/mysql_connect.php");
	$db = sites_db_connect();
	$query_result = mysqli_query($db,"SHOW TABLES LIKE 'dbases'");
	if (mysqli_num_rows($query_result) > 0)
	{
		$query2 = "SELECT * FROM dbases WHERE site_path='$local_site_dir' AND sub_path='$RelativePath' AND (mode='master' OR mode='sub-master')";
		if ($Location == 'real')
			$query2 .= " AND domname='$MainDomain'";
		else
			$query2 .= " AND dbname LIKE 'local_%'";
		$query_result2 = mysqli_query($db,$query2);
		if ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if ($row2['mode'] == 'master')
				return $Location;
			elseif ($row2['auto_restore'] == 0)
				return '*';
			elseif ($Location == 'real')
				return 'local';
			else
				return 'real';
		}
	}
	return $default;
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
Function next_seq_number
*/
//==============================================================================

function next_seq_number($table,$sort_1_value)
{
	if (!defined('NEXT_SEQ_NO_INDICATOR'))
	{
		exit("Constant NEXT_SEQ_NO_INDICATOR not defined");
	}
	$db = admin_db_connect();

	// Search for sequencing information in table dba_table_info, starting with
	// the table itself and working back to the base table.
	for ($i=5; $i>0; $i--)
	{
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			if ((empty($row['parent_table'])) || (!empty($row['seq_no_field'])))
			{
				break;
			}
			else
			{
				$table = $row['parent_table'];
			}
		}
	}

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
			if (empty($sort_1_value))
				$query .= " AND  ($sort_1_name='' OR $sort_1_name IS NULL)";
			else
				$query .= " AND  $sort_1_name='$sort_1_value'";
		}
		$query .= " ORDER BY $seq_no_name DESC";
		$query_result = mysqli_query($db,$query);
		if ($row = mysqli_fetch_assoc($query_result))
			return $row[$seq_no_name] + 10;
		else
			return 10;
	}
	else
	{
		// This should not occur
		return NEXT_SEQ_NO_INDICATOR;
	}
}

//==============================================================================
}
//==============================================================================
?>
