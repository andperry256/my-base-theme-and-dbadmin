<?php
//==============================================================================
/*
N.B. This file is now largely obsolete but is still available in case a
particular site needs to revert to the use of Xataface for any reason.
*/
//==============================================================================

if (!defined('NEXT_SEQ_NO_INDICATOR'))
	define('NEXT_SEQ_NO_INDICATOR',999);
if (!defined('DEFAULT_XATAFACE_LIST_SIZE'))
	define('DEFAULT_XATAFACE_LIST_SIZE',30);
if (isset($BaseDir))
{
	if (is_file("$BaseDir/_link_to_common/date_funct.php"))
		require_once("$BaseDir/_link_to_common/date_funct.php");
	if (is_file("$BaseDir/page_content.php"))
		require_once("$BaseDir/page_content.php");
	if (is_file("$BaseDir/_link_to_common/mysql_mysqli.inc.php"))
		require_once("$BaseDir/_link_to_common/mysql_mysqli.inc.php");
}

//==============================================================================
/*
Function setup_conf_files
*/
//==============================================================================

function setup_conf_files($local_db,$online_db,$local_username,$main_username,$local_password,$main_password)
{
	global $Location, $DBMode, $MainDomain, $UserAuthenticated;
	global $xfl_version;

	if (!isset($UserAuthenticated))
		die ("User authentication failed");

	if (empty($main_username))
		$main_username = $online_db;
	$content1 = file_get_contents('conf_L.ini');
	$content2 = file_get_contents('templates/Dataface_Logo_L.html');

	// Select required DB name (local or remote).
	if (($Location == 'real') || ($DBMode == 'remote'))
		$content1 = str_replace("\"{dbname}\"","\"$online_db\"",$content1);
	else
		$content1 = str_replace("\"{dbname}\"","\"$local_db\"",$content1);

	// Select remote host name if connecting remotely from local server.
	if ($DBMode == 'remote')
		$content1 = str_replace("\"localhost\"","\"$MainDomain\"",$content1);

	// Select username and password.
	if ($DBMode == 'local')
	{
		$content1 = str_replace("{user}","$local_username",$content1);
		$content1 = str_replace("{passwd}","$local_password",$content1);
	}
	else
	{
		$content1 = str_replace("{user}","$main_username",$content1);
		$content1 = str_replace("{passwd}","$main_password",$content1);
	}

	// Set up INI variables for use by the script in mysql_mysqli.inc.php
	ini_set('mysqli.default_user',$main_username);
	ini_set('mysqli.default_pw',$main_password);
	if ($DBMode == 'remote')
		ini_set('mysqli.default_host',$MainDomain);
	else
		ini_set('mysqli.default_host','localhost');
	ini_set('mysqli.default_port',3306);

	// Strip out warning if connected to master database.
	if ($Location == MASTER_LOCATION)
	{
		$pos1 = strpos($content2,'<!--1-->');
		$pos2 = strpos($content2,'<!--2-->');
		$len = strlen($content2);
		$fragment1 = substr($content2,0,$pos1+8);
		$fragment2 = substr($content2,$pos2,$len-$pos2);
		$content2 = $fragment1.$fragment2;
	}

	// Add date/time parameter to URL of logo image file (forcing it to reload browser
	// cache if file has been updated).
	if (isset($xfl_version))
		$content2 = str_replace("xataface_logo.jpg","xataface_logo.jpg?v=$xfl_version",$content2);

	file_put_contents('conf.ini',$content1);
	file_put_contents('templates/Dataface_Logo.html',$content2);
}

//==============================================================================
/*
Function print_bodytext_header and related functions
*/
//==============================================================================

function print_bodytext_header_variant($file,$style)
{
	fprintf($file,"<!--\n");
	fprintf($file,"****** Dynamically generated file - DO NOT EDIT ******\n");
	fprintf($file,"-->\n");
	fprintf($file,"{use_macro file=\"Dataface_Main_Template.html\"}\n");
	fprintf($file,"{fill_slot name=\"main_column\"}\n");
	fprintf($file,"<div id=\"$style\">\n");
}

function print_bodytext_header($file)
{
	print_bodytext_header_variant($file,'bodytext');
}

function print_wide_bodytext_header($file)
{
	print_bodytext_header_variant($file,'wide_bodytext');
}

function print_unbounded_bodytext_header($file)
{
	print_bodytext_header_variant($file,'unbounded_bodytext');
}

//==============================================================================
/*
Function print_bodytext_footer
*/
//==============================================================================

function print_bodytext_footer($file)
{
	fprintf($file,"</div>\n");
	fprintf($file,"{/fill_slot}\n");
	fprintf($file,"{/use_macro}\n");
}

//==============================================================================
/*
Function save_primary_key_info
*/
//==============================================================================

function save_primary_key_info()
{
	if (isset($_GET['-table']))
	{
		$URL_Pars = urldecode($_SERVER['REQUEST_URI']);

		if (isset($_SESSION['primary_keys']))
			unset($_SESSION['primary_keys']);
		$_SESSION['primary_keys'] = array();

		$tok = strtok($URL_Pars,"\?");
		$tok = strtok("&\?");
		while ($tok !== false)
		{
			if ((strpos($tok,'=') !== false) && (substr($tok,0,1) != '-'))
			{
				// Parameter specification in which the parameter name does not start with a hyphen.
				// Indicates a table field specification.
				$equals_sign_pos = strpos($tok,'=');
				$key_name = substr($tok,0,$equals_sign_pos);
				$key_value = substr($tok,$equals_sign_pos+1,strlen($tok)-$equals_sign_pos-1);
				$_SESSION['primary_keys'][$key_name] = urldecode($key_value);
			}
			$tok = strtok("&\?");
		}
	}
}

//==============================================================================
/*
Function reorder_records
*/
//==============================================================================

function reorder_records($table)
{
	global $RootTable;
	$db = xface_db_connect();

	// Find the name of the root table if the current table is a view.
	if (isset($RootTable[$table]))
		$table = $RootTable[$table];

	// Find the primary key field(s) for the table.
	$primary_keys = array();
	$keyno = 0;
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		if ($row['Key'] == 'PRI')
			$primary_keys[$keyno++] = $row['Field'];
	}

	// Run a query to re-order the table by the primary key provided that at least
	// one primary key field has been found.
	$query = "ALTER TABLE $table ORDER BY ";
	foreach ($primary_keys as $keyno => $field)
	{
		if ($keyno > 0)
			$query .= ',';
		$query .= $field;
	}
	if (isset($primary_keys[0]))
		mysqli_query($db,$query);
}

//==============================================================================
/*
Function renumber_records
*/
//==============================================================================

function renumber_records($table)
{
	/*
	Renumber records in increments of 10. The parameter $table is an array element
	reference to the global array $TableSequencing, whose elements are each an array
	containing the following information about the table being renumbered:-
	[0] Name of field for first level grouping/sorting (optional)
	[1] Name of sequence number field
	[2] Numbering method:
		continuous = use single number series
		repeat = start from 10 whenever first level sort field changes
	*/
	global $RootTable;
	global $TableSequencing;
	$db = xface_db_connect();
	set_time_limit(30);

	// Find the name of the root table if the current table is a view.
	if (isset($RootTable[$table]))
		$table = $RootTable[$table];

	if (isset($TableSequencing[$table]))
	{
		// Set up basic query string according to sort method
		if (!empty($TableSequencing[$table][0]))
			$query = "SELECT * FROM $table ORDER BY {$TableSequencing[$table][0]},{$TableSequencing[$table][1]}";
		else
		{
			$query = "SELECT * FROM $table ORDER BY {$TableSequencing[$table][1]}";
			$TableSequencing[$table][2] = 'continuous';   // Force continuous method if no first-level sort
		}

		// Renumber records to a range outside the required new range
		$query_result = mysqli_query($db,$query);
		$record_count = mysqli_num_rows($query_result);
		$query_result = mysqli_query($db,"$query DESC LIMIT 1");
		if ($row = mysqli_fetch_assoc($query_result))
			$max_rec_id = $row[$TableSequencing[$table][1]];
		else
			$max_rec_id = 0;
		if ($max_rec_id < ($record_count * 10))
			$max_rec_id = $record_count * 10;
		mysqli_query($db,"UPDATE $table SET {$TableSequencing[$table][1]}={$TableSequencing[$table][1]}+$max_rec_id");

		// Re-order the table
		if (!empty($TableSequencing[$table][0]))
			mysqli_query($db,"ALTER TABLE $table ORDER BY {$TableSequencing[$table][0]},{$TableSequencing[$table][1]}");
		else
			mysqli_query($db,"ALTER TABLE $table ORDER BY {$TableSequencing[$table][1]}");

		// Renumber records in increments of 10
		$new_id = 0;
		$first_sort_prev_value = '';
		$query_result = mysqli_query($db,$query);
		while ($row = mysqli_fetch_assoc($query_result))
		{
			if (($TableSequencing[$table][2] == 'repeat') && ($row[$TableSequencing[$table][0]] != $first_sort_prev_value))
				$new_id = 10;
			else
				$new_id += 10;
			mysqli_query($db,"UPDATE $table SET {$TableSequencing[$table][1]}=$new_id WHERE {$TableSequencing[$table][1]}={$row[$TableSequencing[$table][1]]} LIMIT 1");
			if ($TableSequencing[$table][2] == 'repeat')
				$first_sort_prev_value = $row[$TableSequencing[$table][0]];
		}
	}
}

//==============================================================================
/*
Function renumber_record_range
*/
//==============================================================================

function renumber_record_range($table,$pk_values)
{
	/*
	This is an alternative to using the renumber_records function. It checks whether
	the operation can be narrowed down to a single value of a first level sort record.
	If this is the case then only the given record range is renumbered, otherwise
	the normal renumber_records function is called.
	The primary keys are supplied as an array in the format used elsewhere in this file.
	*/
	global $RootTable;
	global $TableSequencing;
	$db = xface_db_connect();
	set_time_limit(30);

	// Find the name of the root table if the current table is a view.
	if (isset($RootTable[$table]))
		$table = $RootTable[$table];

	$records_renumbered = false;
	if ((isset($TableSequencing[$table])) && ($TableSequencing[$table][2] == 'repeat'))
	{
		foreach ($pk_values as $key => $value)
		{
			if ($key == $TableSequencing[$table][0])
			{
				// Renumber records to a range outside the required new range
				$query = "SELECT * FROM $table WHERE $key='$value' ORDER BY {$TableSequencing[$table][1]}";
				$query_result = mysqli_query($db,$query);
				$record_count = mysqli_num_rows($query_result);
				$query_result = mysqli_query($db,"$query DESC LIMIT 1");
				if ($row = mysqli_fetch_assoc($query_result))
					$max_rec_id = $row[$TableSequencing[$table][1]];
				else
					$max_rec_id = 0;
				if ($max_rec_id < ($record_count * 10))
					$max_rec_id = $record_count * 10;
				mysqli_query($db,"UPDATE $table SET {$TableSequencing[$table][1]}={$TableSequencing[$table][1]}+$max_rec_id WHERE $key='$value'");

				// Renumber records in increments of 10
				$new_id = 0;
				$query_result = mysqli_query($db,$query);
				while ($row = mysqli_fetch_assoc($query_result))
				{
					$new_id += 10;
					mysqli_query($db,"UPDATE $table SET {$TableSequencing[$table][1]}=$new_id WHERE {$TableSequencing[$table][1]}={$row[$TableSequencing[$table][1]]} LIMIT 1");
				}
				$records_renumbered = true;
				break;
			}
		}
	}

	// Renumber the full table if sub-range not processed
	if (!$records_renumbered)
		renumber_records($table);
}

//==============================================================================
/*
Function set_default_seq_numbers
*/
//==============================================================================

function set_default_seq_numbers()
{
	global  $TableSequencing;
	$db = xface_db_connect();
	$next_seq_no_indicator = NEXT_SEQ_NO_INDICATOR;
	if (isset($TableSequencing))
	{
		foreach ($TableSequencing as $key => $t)
			mysqli_query($db,"ALTER TABLE $key CHANGE {$t[1]} {$t[1]} INT(11) NOT NULL DEFAULT '$next_seq_no_indicator'");
	}
}


//==============================================================================
/*
Function next_seq_number
*/
//==============================================================================

function next_seq_number($table,$sort_1_value)
{
	global  $TableSequencing;
	$db = xface_db_connect();
	if (isset($TableSequencing[$table]))
	{
		// Calculate next sequence number, given:-
		// (1) Table name.
		// (2) First-level sort field value (optional).
		$sort_1_name = $TableSequencing[$table][0];
		$seq_no_name =  $TableSequencing[$table][1];
		$seq_type = $TableSequencing[$table][2];
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
/*
Function validate_new_record
*/
//==============================================================================

function validate_new_record($record)
{
	global $RootTable;
	$db = xface_db_connect();

	$app =&Dataface_Application::getInstance();
	$query =$app->getQuery();
	if ($query['-action']=='new')
	{
		$table = $query['-table'];

		// Find the name of the root table if the current table is a view.
		if (isset($RootTable[$table]))
			$table = $RootTable[$table];

		// Build and run query to check for existing records with same primary key values.
		// Also build the global primary key array for later use.
		if (isset($_SESSION['primary_keys']))
			unset($_SESSION['primary_keys']);
		$_SESSION['primary_keys'] = array();
		$where_clause = 'WHERE';
		$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
		$keyno = 0;
		while ($row = mysqli_fetch_assoc($query_result))
		{
			if ($row['Key'] == 'PRI')
			{
				if ($keyno > 0)
					$where_clause .= " AND";
				$field = $row['Field'];
				$value = $record->strval($field);
				$value = addslashes($value);
				$where_clause .= " $field = '$value'";
				$_SESSION['primary_keys'][$field] = $value;
				$keyno++;
			}
		}
		if ($where_clause > 'WHERE')
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM $table $where_clause");
			if (mysqli_num_rows($query_result2)>0)
			{
				return PEAR::raiseError("Duplicate Record ID", DATAFACE_E_NOTICE);
			}
		}
	}
}

//==============================================================================
/*
Function exit_from_save_record
*/
//==============================================================================

function exit_from_save_record($record)
{
	global $BaseURL;
	global $AdminSubURL;
	global $RelativePath;
	global $RelativeSubPath;
	global $ListSize;
	global $TableSequencing;
	global $ReturnLinkNamePrefix;
	if (!isset($ReturnLinkNamePrefix))
		$ReturnLinkNamePrefix = '';
	$db = xface_db_connect();
    global $ReturnToListing;
	$return_to_listing = false;
	// Leave variables $SequenceNo and $RenumberTable unset by default

	$app =&Dataface_Application::getInstance();
	$query =$app->getQuery();
	$table = $query['-table'];
	if (($query['-action']=='edit') || ($query['-action']=='new'))
	{
		// Indicate return to table listing if the 'return to listing' flag is set and
		// the table listing is viewable on a single page.
		if ((isset($ReturnToListing[$table])) && ($ReturnToListing[$table]))
		{
			if (isset($RootTable[$table]))
				$root_table = $RootTable[$table];
			else
				$root_table = $table;
			if (isset($ListSize[$root_table]))
				$PageListSize = $ListSize[$root_table];
			else
				$PageListSize = DEFAULT_XATAFACE_LIST_SIZE;
			$query_result = mysqli_query($db,"SELECT * FROM $table");
			if (mysqli_num_rows($query_result) <= $PageListSize)
				$return_to_listing = true;
		}

		// Indicate return to table listing if primary key has been changed
		if (isset($_SESSION['new_primary_keys']))
		{
			// This condition will only occur where the calling function has created the
			// array $_SESSION['new_primary_keys'] to cover an additional change made to
			// the database record.
			$NewPKValues = $_SESSION['new_primary_keys'];
			unset($_SESSION['new_primary_keys']);
		}
		else
		{
			$NewPKValues = array();
			foreach ($_SESSION['primary_keys'] as $key => $value)
				$NewPKValues[$key] = $record->strval($key);
		}
		if ($NewPKValues != $_SESSION['primary_keys'])
				$return_to_listing = true;

		// Search for any fields with leading/trailing spaces and re-save with the spaces removed.
		// Check for a sequence number field set to 'next number' and re-save with proper value.
		// First build a query to re-extract the record based on the current primary key values.
		$WhereClause = 'WHERE';
		foreach ($NewPKValues as $key => $value)
		{
			if (strlen($WhereClause) > 5)
				$WhereClause .= ' AND ';
			$value = addslashes($value);
			$WhereClause .= " $key='$value'";
		}
		if ($WhereClause > 'WHERE')
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM $table $WhereClause;");
			if ($row = mysqli_fetch_assoc($query_result2))
			{
				$row2 = array();
				// Loop through all fields of record
				foreach ($row as $key => $value)
				{
					// Handle leading/trailing spaces
					$trim_value = trim($value);
					if (strcmp($trim_value,$value) != 0)
						$row2[$key] = $trim_value;

					// Handle sequence number$ReturnLinkNamePrexix.
					if ((isset($TableSequencing[$table])) &&
						($TableSequencing[$table][1] == $key))
					{
						$SequenceNo = $trim_value;
						if ($SequenceNo == NEXT_SEQ_NO_INDICATOR)
						{
							$RenumberTable = false;
							if (!empty($TableSequencing[$table][0]))
							{
								$row2[$key] = next_seq_number($table,$row[$TableSequencing[$table][0]]);
								if ($TableSequencing[$table][2] == 'continuous')
								{
									// This is an unusual but possible situation. Opt to renumber the table
									// to ensure that records remain ordered by the first level sort field.
									$RenumberTable = true;
								}
							}
							else
								$row2[$key] = next_seq_number($table,'');
						}
					}
				}

				// Renumber the table if either:-
				// (1) There is a change to the primary key other than the handling of a default
				//     next sequence number, or:-
				// (2) The sequence number is not divisible by 10.
				if ((!isset($RenumberTable)) &&
				    (isset($SequenceNo)) &&
				    (($NewPKValues != $_SESSION['primary_keys']) || (($SequenceNo % 10) != 0)))
				{
						$RenumberTable = true;
				}

				// Loop through all fields found to have been modified
				foreach ($row2 as $key2 => $value2)
				{
					$value2 = addslashes($value2);
					mysqli_query($db,"UPDATE $table SET $key2='$value2' $WhereClause");

					// If it is a primary key field then:-
					// (1) Update the NewPKValues array to congtain the new value for use in the next check
					// (2) Indicate return to table listing
					if (isset($_SESSION['primary_keys'][$key2]))
					{
						$NewPKValues[$key2] = $value2;
						$return_to_listing = true;
					}
				}
			}
		}

		// Indicate return to table listing if record no longer present in view.
		// Need to re-build query in case a primary key value has changed.
		$WhereClause = 'WHERE';
		foreach ($NewPKValues as $key => $value)
		{
			if (strlen($WhereClause) > 5)
				$WhereClause .= ' AND';
			$value = addslashes($value);
			$WhereClause .= " $key='$value'";
		}
		if ($WhereClause > 'WHERE')
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM $table $WhereClause;");
			if (mysqli_num_rows($query_result2) == 0)
				$return_to_listing = true;
		}
	}

	// Renumber the table if required
	if ((isset($RenumberTable)) && ($RenumberTable) && ($table != 'photos'))
	{
		renumber_record_range($table,$NewPKValues);
		$return_to_listing = true;
	}

	// Return to:-
	// (1) Required URL is a return link is specified.
	// (2) Table listing if indicated by any of the above checks.
	if (isset($_SESSION[$ReturnLinkNamePrefix.'return_link']))
	{
		$link = $_SESSION[$ReturnLinkNamePrefix.'return_link'];
		unset($_SESSION[$ReturnLinkNamePrefix.'return_link']);
		header("Location: $link");
		exit;
	}
	elseif ($return_to_listing)
	{
		if (isset($AdminSubURL))
		{
			if (isset($RelativeSubPath))
			{
				$admin_url = "$AdminSubURL/$RelativeSubPath";
			}
			else
			{
				$admin_url = $AdminSubURL;
			}
		}
		else
		{
			$admin_url = "$BaseURL/$RelativePath";
		}
		header("Location: $admin_url/index.php?-table=$table");
		exit;
	}
}

//==============================================================================
/*
Function format_view_name
*/
//==============================================================================

function format_view_name($view_name)
{
	$view_name = preg_replace("/[^A-Za-z0-9_]/i",'_',$view_name);
	return $view_name;
}

//==============================================================================
/*
Function create_view_structure
*/
//==============================================================================

function create_view_structure($view,$table,$where_clause)
{
	global $BaseDir, $RelativePath;
	$db = xface_db_connect();

	mysqli_query($db,"CREATE OR REPLACE VIEW $view AS SELECT * FROM $table WHERE $where_clause");
	if (substr($view,0,6) == '_view_')
	{
		mysqli_query($db,"DROP TABLE IF EXISTS $view");
		$old_view = substr($view,6,strlen($view)-6);
		mysqli_query($db,"DROP VIEW IF EXISTS $old_view");
	}
	$file = "$BaseDir/$RelativePath/tables/$table/$view.php";
	if (!is_file($file))
	{
		$ofp = fopen($file,'w');
		fprintf($ofp,"<?php\n");
		fprintf($ofp,"include(\"tables/$table/$table.php\");\n");
		fprintf($ofp,"class tables_$view extends tables_$table {}\n");
		fprintf($ofp,"?>\n");
		fclose($ofp);
	}
	$link = "$BaseDir/$RelativePath/tables/$view";
	if (!is_link($link))
		symlink("$BaseDir/$RelativePath/tables/$table","$link");
}

//==============================================================================
/*
Function delete_view_structure
*/
//==============================================================================

function delete_view_structure($view,$table)
{
	global $BaseDir, $RelativePath;
	$db = xface_db_connect();

	mysqli_query($db,"DROP VIEW IF EXISTS $view");
	$file = "$BaseDir/$RelativePath/tables/$table/$view.php";
	if (is_file($file))
		unlink($file);
	$link = "$BaseDir/$RelativePath/tables/$view";
	if (is_link($link))
		unlink($link);
}

//==============================================================================
/*
Function create_child_table_structure
*/
//==============================================================================

function create_child_table_structure($child,$parent)
{
	global $BaseDir, $RelativePath;
	$db = xface_db_connect();

	mysqli_query($db,"CREATE TABLE IF NOT EXISTS $child LIKE $parent");
	$file = "$BaseDir/$RelativePath/tables/$parent/$child.php";
	if (!is_file($file))
	{
		$ofp = fopen($file,'w');
		fprintf($ofp,"<?php\n");
		fprintf($ofp,"include(\"tables/$parent/$parent.php\");\n");
		fprintf($ofp,"class tables_$child extends tables_$parent {}\n");
		fprintf($ofp,"?>\n");
		fclose($ofp);
	}
	$link = "$BaseDir/$RelativePath/tables/$child";
	if (!is_link($link))
		symlink("$BaseDir/$RelativePath/tables/$parent","$link");
}

//==============================================================================
/*
Function delete_child_table_structure
*/
//==============================================================================

function delete_child_table_structure($child,$parent)
{
	global $BaseDir, $RelativePath;
	$db = xface_db_connect();

	mysqli_query($db,"DROP TABLE $child");
	$file = "$BaseDir/$RelativePath/tables/$parent/$child.php";
	if (is_file($file))
		unlink($file);
	$link = "$BaseDir/$RelativePath/tables/$child";
	if (is_link($link))
		unlink($link);
}

//==============================================================================
/*
Function set_temp_view_name
*/
//==============================================================================

function set_temp_view_name()
{
	if ((!isset($_SESSION['TEMP_VIEW'])) || (empty($_SESSION['TEMP_VIEW'])))
	{
		$temp_str1 = str_replace('.','_',$_SERVER['REMOTE_ADDR']);
		$temp_str2 = date('His');
		$_SESSION['TEMP_VIEW'] = "_view_temp_$temp_str1"."_$temp_str2";
	}
}

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
Function import_table_from_csv

This function loads the data from a CSV file into a given DB table. There are
two alternative methods (as specified by the 'method' parameter):-

Short - This uses the MySQL LOAD DATA INFILE construct.
Long -  This performs the operation long hand to avoid having to set up any
        special MySQL privileges.
*/
//==============================================================================

function import_table_from_csv($file_path,$db,$table,$method)
{
	mysqli_query($db,"DELETE FROM $table");
	if ($method == 'short')
	{
		$query = "LOAD DATA INFILE '$file_path' INTO TABLE $table FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n'";
		mysqli_query($db,$query);
	}
	elseif ($method == 'long')
	{
		$file_contents = file($file_path);
		$field_list = $file_contents[0];
		unset($file_contents[0]);
		foreach ($file_contents as $line)
		{
			// Process escape sequences
			$line = stripcslashes($line);
			// Convert escape sequence for double quotes (CSV to MySQL)
			$line = str_replace('""',"\\\"",$line);
			// Add record to table
			mysqli_query($db,"INSERT INTO $table ($field_list) VALUES ($line)");
		}
	}
}

//==============================================================================
/*
Function load_table_from_sql

This function dumps the data from an SQL file into a given DB table. The
MySQL parameters/credentials are taken from the conf.ini from the current
Xataface application.
*/
//==============================================================================

function load_table_from_sql($filepath,$table)
{
	global $BaseDir,$RelativePath;
	$conf_data = file("$BaseDir/$RelativePath/conf.ini");
	foreach ($conf_data as $line)
	{
		$tok = strtok($line,' =');
		$setting = substr($line,strlen($tok));
		$setting = trim($setting, " =\"\n\r");
		switch ($tok)
		{
			case 'host':
				$host = $setting;
				break;

			case 'user':
				$username = $setting;
				break;

			case 'password':
				$password = $setting;
				break;

			case 'name':
				$dbname = $setting;
				break;
		}
	}
	$command = "mysql --host=$host --user=$username --password=$password -D $dbname < \"$filepath\"";
	exec($command);
}

//==============================================================================
/*
Function load_tables

This function restores from a set of CSV dumps, all tables listed in the
$DumpTables array for the given database.

The method must be specified as:-

'short'/'long' - see description above for import_table_from_csv.
'sqldump' - for calling load_table_from_sql
*/
//==============================================================================

function load_tables($filename_prefix,$method)
{
	global $DumpTables, $DBDumpDir;
	$db = xface_db_connect();
	if (($db) && (isset($DumpTables)) && (isset($DBDumpDir)))
	{
		foreach ($DumpTables as $table)
		{
			if ($method == 'sqldump')
			{
				$dump_file = "$DBDumpDir/$filename_prefix$table.sql";
				load_table_from_sql($dump_file,$table);
			}
			else
			{
				$dump_file = "$DBDumpDir/$filename_prefix$table.csv";
				import_table_from_csv($dump_file,$db,$table,$method);
			}
		}
	}
}

//==============================================================================
/*
Function export_table_to_csv

This function dumps the data from a given DB table into a CSV file. There are
two alternative methods (as specified by the 'method' parameter):-

Short - This uses the MySQL SELECT INTO OUTFILE construct.
Long -  This performs the operation long hand to avoid having to set up any
        special MySQL privileges
*/
//==============================================================================

function export_table_to_csv($file_path,$db,$table,$fields,$where_clause,$order_clause,$limit_clause,$method)
{
	if ($method == 'short')
	{
    $query = "SELECT * INTO OUTFILE '$file_path' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\\n' FROM $table";
    mysqli_query($db,$query);
	}
	elseif ($method == 'long')
	{
		$ofp = fopen($file_path,'w');
		if (empty($fields))
		{
			// No fields are specified - indicates all fields.
			// Set the field list for queries to '*' and generate the header line.
			$field_selection = '*';
			$header_line = '';
			$field_count = 0;
			$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
			while ($row = mysqli_fetch_assoc($query_result))
			{
				if ($field_count > 0)
					$header_line .= ',';
				$header_line .= $row['Field'];
				$field_count++;
			}
			fprintf($ofp,"$header_line\n");
		}
		else
		{
			// Field list is provided as an array.
			// Generate the field list for queries and use the same string as the header line.
			$field_selection = '';
			$field_count = 0;
			foreach ($fields as $field_name => $field_desc)
			{
				if ($field_count > 0)
					$field_selection .= ',';
				$field_selection .= $field_name;
				$field_count++;
			}
			fprintf($ofp,"$field_selection\n");
		}

		// Add any extra clauses to the query
		if (!empty($where_clause))
			$where_clause = "WHERE $where_clause";
		if (!empty($order_clause))
			$order_clause = "ORDER BY $order_clause";
		if (!empty($limit_clause))
			$limit_clause = "LIMIT $limit_clause";

		// Query and main loop to process the table records.
		$query_result = mysqli_query($db,"SELECT $field_selection FROM $table $where_clause $order_clause $limit_clause");
		while ($row = mysqli_fetch_assoc($query_result))
		{
			$field_count = 0;
			foreach($row as $field)
			{
				// Create escape sequence for percent sign (for fprintf)
				$field = str_replace('%','%%',$field);
				// Create escape sequence for double quotes (for CSV)
				$field = str_replace('"','""',$field);
				// Create other escape sequences
				$field = addcslashes($field,"\n\r\\");

				if ($field_count > 0)
				{
					// Not the first field so output a comma
					fprintf($ofp,",");
				}
				// Output the field
				fprintf($ofp,"\"$field\"");
				$field_count++;
			}
			// Add line terminator
			fprintf($ofp,"\n");
		}
		fclose($ofp);
	}
}

//==============================================================================
/*
Function dump_table_to_sql

This function dumps the data from a given DB table into an SQL file. The
MySQL parameters/credentials are taken from the conf.ini from the current
Xataface application.
*/
//==============================================================================

function dump_table_to_sql($filepath,$table)
{
	global $BaseDir,$RelativePath;
	$conf_data = file("$BaseDir/$RelativePath/conf.ini");
	foreach ($conf_data as $line)
	{
		$tok = strtok($line,' =');
		$setting = substr($line,strlen($tok));
		$setting = trim($setting, " =\"\n\r");
		switch ($tok)
		{
			case 'host':
				$host = $setting;
				break;

			case 'user':
				$username = $setting;
				break;

			case 'password':
				$password = $setting;
				break;

			case 'name':
				$dbname = $setting;
				break;
		}
	}
	$command = "mysqldump --host=$host --user=$username --password=$password $dbname $table > \"$filepath\"";
	exec($command);
}

//==============================================================================
/*
Function dump_tables

This function creates a set of CSV dumps for all tables listed in the
$DumpTables array for the given database.

The method must be specified as:-

'short'/'long' - see description above for export_table_to_csv
'sqldump' - for calling dump_table_to_sql
*/
//==============================================================================

function dump_tables($filename_prefix,$method)
{
	global $DumpTables, $DBDumpDir;
	$db = xface_db_connect();
	if (($db) && (isset($DumpTables)) && (isset($DBDumpDir)))
	{
		foreach ($DumpTables as $table)
		{
			if ($method='sqldump')
			{
				$dump_file = "$DBDumpDir/$filename_prefix$table.sql";
			}
			else
			{
				$dump_file = "$DBDumpDir/$filename_prefix$table.csv";
			}
			if (is_file($dump_file))
			{
				unlink ($dump_file);
			}
			if ($method='sqldump')
			{
				dump_table_to_sql($dump_file,$table);
			}
			else
			{
				export_table_to_csv($dump_file,$db,$table,'','','','',$method);
			}
		}
	}
}

//==============================================================================
?>
