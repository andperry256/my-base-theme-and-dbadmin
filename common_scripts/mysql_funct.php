<?php
//==============================================================================

if (!defined('USE_PREPARED_STATEMENTS'))
{
	/*
	This is a temporary definition for use during development of new MySQL
	functions. It is yet to be determined whether the final system will use
	prepared statements or an alternative mechanism.
	*/
	define('USE_PREPARED_STATEMENTS',false);
}
if (!function_exists('print_stack_trace_for_mysqli_error'))
{
//==============================================================================

function print_stack_trace_for_mysqli_error($ofp=false)
{
	global $argc;
	$eol = (isset($argc)) ? "\n" : "<br />\n";
	ob_start();
	debug_print_backtrace();
	$trace = ob_get_contents();
	ob_end_clean();
	if ($ofp === false)
	{
		print(str_replace("\n",$eol,$trace));
	}
	else
	{
		$content = explode("\n",$trace);
		foreach ($content as $line)
		{
			fprintf($ofp,"  $line$eol");
		}
	}
}

//==============================================================================
/*
Function run_mysqli_query

This is called in place of a regular call to mysqli_query and is used to output
a more useful error message if the MySQL function call raises an exception.

If the $strict option is set, then it will also abort with an error message if
the MySQL function call runs without an exception but returns an error value.

On an online server, errors are output to a log file rather than the screen.
*/
//==============================================================================

function run_mysqli_query($db,$query,$strict=false)
{
	global $argc, $RootDir;
	$eol = (isset($argc)) ? "\n" : "<br />\n";
	$error_id = substr(md5(date('YmdHis')),0,8);
	$date_and_time = date('Y-m-d H:i:s');
	$fatal_error_message = "There has been a fatal error, details of which have been logged.$eol";
	$fatal_error_message .= "Please report this to the webmaster quoting code <strong>$error_id</strong>.$eol";
	try
	{
		$result = mysqli_query($db,$query);
	}
	catch (Exception $e)
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error caught on running MySQL query:$eol$query$eol");
			print($e->getMessage().$eol);
			print_stack_trace_for_mysqli_error();
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error caught on running MySQL query:\n  $query\n");
			fprintf($ofp,'  '.$e->getMessage()."\n");
			fclose($ofp);
			print_stack_trace_for_mysqli_error($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	if ((!$result) && ($strict))
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error result returned from MySQL query:$eol$query$eol");
			print_stack_trace_for_mysqli_error();
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error result returned from MySQL query:\n  $query\n");
			fclose($ofp);
			print_stack_trace_for_mysqli_error($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	return $result;
}

//==============================================================================

function mysqli_query_normal($db,$query)
{
	return run_mysqli_query($db,$query,false);
}

//==============================================================================

function mysqli_query_strict($db,$query)
{
	return run_mysqli_query($db,$query,true);
}

//==============================================================================
/*
Function run_prepared_statement

This is called in place of a regular call to mysqli_stmt_execute and is used to
output a more useful error message if the MySQL function call raises an
exception.

If the $strict option is set, then it will also abort with an error message if
the MySQL function call runs without an exception but returns a value that
indicates a potential error condition making it unsafe for the calling script
to continue execution. This parameter is passed by all calling functions and is
contained within the call line to each of these as an optional parameter with a
default (see functions below). Normally the $strict option would be set to
'true' for select queries and 'false' for other query types (to be ratified).

On an online server, errors are output to a log file rather than the screen.
*/
//==============================================================================

function run_prepared_statement($stmt,$strict)
{
	global $argc, $RootDir;
	$eol = (isset($argc)) ? "\n" : "<br />\n";
	$error_id = substr(md5(date('YmdHis')),0,8);
	$date_and_time = date('Y-m-d H:i:s');
	$fatal_error_message = "There has been a fatal error, details of which have been logged.$eol";
	$fatal_error_message .= "Please report this to the webmaster quoting code <strong>$error_id</strong>.$eol";
	try
	{
		mysqli_stmt_execute($stmt);
	}
	catch (Exception $e)
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error caught on running MySQL query:$eol$query$eol");
			print($e->getMessage().$eol);
			print_stack_trace_for_mysqli_error();
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error caught on running MySQL query:\n  $query\n");
			fprintf($ofp,'  '.$e->getMessage()."\n");
			fclose($ofp);
			print_stack_trace_for_mysqli_error($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	$result = mysqli_stmt_get_result($stmt);
	if (($result === false) && ($strict))
	{
		if (is_file("/Config/linux_pathdefs.php"))
		{
			// Local server
			print("Error result returned from MySQL query:$eol$query$eol");
			print_stack_trace_for_mysqli_error();
		}
		else
		{
			// Online server
			$ofp = fopen("$RootDir/logs/php_error.log",'a');
			fprintf($ofp,"[$date_and_time] [$error_id] Error result returned from MySQL query:\n  $query\n");
			fclose($ofp);
			print_stack_trace_for_mysqli_error($ofp);
			print($fatal_error_message);
		}
		exit;
	}
	return (!empty($result))
		? $result
		: true;
}

//==============================================================================
/*
Function query_field_type

This function returns the variable type for a given field given the table and
field name. The following results may be returned:-

i - Integer (including Boolean).
d - Double (any non-integer number type).
s - String.
*/
//==============================================================================

function query_field_type($db,$table,$field_name)
{
	if (function_exists('base_table'))
	{
		$table = base_table($table);
	}
	if ($row = mysqli_fetch_assoc(mysqli_query_strict($db,"SHOW COLUMNS FROM $table WHERE Field='$field_name'")))
	{
		if (preg_match('/int/',($row['Type'])))
		{
			return 'i';
		}
		elseif (preg_match('/dec/',($row['Type'])))
		{
			return 'd';
		}
		else
		{
			return 's' ;
		}
	}
	else
	{
		return 's';
	}
}

//==============================================================================
/*
Function raise_query_validation_error

This function is called to raise an exception when a query fails validations
in one of the below functions. It is currently only used to trap situations
where the number of question marks in the query does not relate correctly to
the number of supplied parameters.
*/
//==============================================================================

function raise_query_validation_error($query)
{
	$eol = (isset($argc)) ? "\n" : "<br />\n";
	if (is_file("/Config/linux_pathdefs.php"))
	{
		// Local server
		print("Failed to validate MySQL query:$eol$query$eol");
		print_stack_trace_for_mysqli_error();
	}
	else
	{
		// Online server
		$ofp = fopen("$RootDir/logs/php_error.log",'a');
		fprintf($ofp,"[$date_and_time] [$error_id] Failed to validate MySQL query:\n  $query\n");
		fclose($ofp);
		print_stack_trace_for_mysqli_error($ofp);
		print($fatal_error_message);
	}
	exit;
}

//==============================================================================
/*
Function mysqli_select_query

This function is called to run a SELECT query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$fields - List of fields being select ('*' or comma separated string)
$where_clause - WHERE clause to be used in the query (optional). Values to
                compare with are included as question marks.
$where_values - Values associated with the WHERE clause (array).
$add_clause - Any additional clause to be added to the query (opitional -
              includes for example ORDER or LIMIT direactive).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_select_query($db,$table,$fields,$where_clause,$where_values,$add_clause,$strict=true)
{
	$query = "SELECT $fields FROM $table";
	$where_values_count = count($where_values);
	if ($where_values_count != (substr_count($where_clause,'?')*2))
	{
		raise_query_validation_error("$query WHERE $where_clause");
	}
	if (!empty($where_clause))
	{
		$query .= " WHERE $where_clause";
	}
	if (!empty($add_clause))
	{
		$query .= " $add_clause";
	}
	if (USE_PREPARED_STATEMENTS)
	{
		$type_list = '';
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			$type_list .= $where_values[$i];
		}
		$stmt = mysqli_prepare($db,$query);
		if (!empty($type_list))
		{
			mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
		}
		return run_prepared_statement($stmt,$strict);
	}
	else
	{
		$pos = 0;
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			if ($where_values[$i+1] == chr(0))
			{
				$param = 'NULL';
			}
			elseif ($where_values[$i] == 's')
			{
				$param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
			}
			else
			{
				$param = $where_values[$i+1];
			}
			$pos = strpos($query,'?',$pos);
			$query = substr($query,0,$pos).$param.substr($query,$pos+1);
			$pos++;
		}
		return run_mysqli_query($db,$query,$strict);
	}
}

//==============================================================================
/*
Function mysqli_update_query

This function is called to run an UPDATE query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$set_fields - List of fields being updated (comma separated string)
$set_values - Values associated with the field list (array). Each item
              occupies two array elements (variable type followed by value).
$where_clause - WHERE clause to be used in the query (optional). Values to
                compare with are included as question marks.
$where_values - Values associated with the WHERE clause (array). Each item
                occupies two array elements (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values,$strict=false)
{
	$query = "UPDATE $table SET ";
	$tok = strtok($set_fields,',');
	while ($tok !== false)
	{
		$query .= "$tok=?,";
		$tok = strtok(',');
	}
	$query = rtrim($query,',');
	if (!empty($where_clause))
	{
		$query .= " WHERE $where_clause";
	}
	$all_values = array_merge($set_values,$where_values);
	$all_values_count = count($all_values);
	if ($all_values_count != substr_count($query,'?')*2 )
	{
		raise_query_validation_error($query);
	}
	if (USE_PREPARED_STATEMENTS)
	{
		$type_list = '';
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			$type_list .= $where_values[$i];
		}
		$stmt = mysqli_prepare($db,$query);
		mysqli_stmt_bind_param($stmt, $type_list, ...$all_values);
		return run_prepared_statement($stmt,$strict);
	}
	else
	{
		$pos = 0;
		for ($i=0; $i<$all_values_count; $i+=2)
		{
			if ($all_values[$i+1] == chr(0))
			{
				$param = 'NULL';
			}
			elseif ($all_values[$i] == 's')
			{
				$param = "'".mysqli_real_escape_string($db,$all_values[$i+1])."'";
			}
			else
			{
				$param = $all_values[$i+1];
			}
			$pos = strpos($query,'?',$pos);
			$query = substr($query,0,$pos).$param.substr($query,$pos+1);
			$pos++;
		}
		return run_mysqli_query($db,$query,$strict);
	}
}

//==============================================================================
/*
Function mysqli_insert_query

This function is called to run an INSERT query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$fields - List of specified fields (comma separated string)
$values - Values associated with the field list (array). Each item occupies
          two array elements (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_insert_query($db,$table,$fields,$values,$strict=false)
{
	$values_count = count($values);
	if ( $values_count != (substr_count($fields,',') + 1)*2 )
	{
		raise_query_validation_error("INSERT INTO $table ...");
	}
	$values_template = '';
	if (USE_PREPARED_STATEMENTS)
	{
		$type_list = '';
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			$type_list .= $where_values[$i];
			$values_template .= '?,';
		}
		$values_template = rtrim($values_template,',');
		$stmt = mysqli_prepare($db,"INSERT INTO $table ($fields) VALUES ($values_template)");
		mysqli_stmt_bind_param($stmt, $type_list, ...$values);
		return run_prepared_statement($stmt,$strict);
	}
	else
	{
		$values_list = '';
		for ($i=0; $i<$values_count; $i+=2)
		{
			if ($values[$i+1] == chr(0))
			{
				$param = 'NULL';
			}
			elseif ($values[$i] == 's')
			{
				$param = "'".mysqli_real_escape_string($db,$values[$i+1])."'";
			}
			else
			{
				$param = $values[$i+1];
			}
			$values_list .= $param.',';
		}
		$values_list = rtrim($values_list,',');
		return run_mysqli_query($db,"INSERT INTO $table ($fields) VALUES ($values_list)",$strict);
	}
}

//==============================================================================
/*
Function mysqli_delete_query

This function is called to run a DELETE query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$where_clause - WHERE clause to be used in the query. Values to compare with
                are included as question marks.
$where_values - Values associated with the WHERE clause (array). Each item
                occupies two array elements (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_delete_query($db,$table,$where_clause,$where_values,$strict=false)
{
	// N.B. Query must have a WHERE clause.
	$query = "DELETE FROM $table WHERE $where_clause";
	$where_values_count = count($where_values);
	if ($where_values_count != (substr_count($where_clause,'?')*2) || ($where_values_count == 0))
	{
		raise_query_validation_error($query);
	}
	if (USE_PREPARED_STATEMENTS)
	{
		$type_list = '';
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			$type_list .= $where_values[$i];
		}
		$stmt = mysqli_prepare($db,$query);
		if (!empty($type_list))
		{
			mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
		}
		return run_prepared_statement($stmt,$strict);
	}
	else
	{
		$pos = 0;
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			if ($where_values[$i+1] == chr(0))
			{
				$param = 'NULL';
			}
			elseif ($where_values[$i] == 's')
			{
				$param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
			}
			else
			{
				$param = $where_values[$i+1];
			}
			$pos = strpos($query,'?',$pos);
			$query = substr($query,0,$pos).$param.substr($query,$pos+1);
			$pos++;
		}
		return run_mysqli_query($db,$query,$strict);
	}
}

//==============================================================================
/*
Function mysqli_free_format_query

This function is called to run a query using a prepared statement.and which
does not exactly fit a category covered by one of the preceding functions.

It basically takes a ready-made query but with the option to supply and bind
paramaters.

The following parameters are passed:-
$db - Link to the connected database
$query - Query text,
$where_values - Parameters to be bound. Each item occupies two array elements
                (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

*/
//==============================================================================

function mysqli_free_format_query($db,$query,$where_values,$strict=true)
{
	$where_values_count = count($where_values);
	if ($where_values_count != (substr_count($query,'?')*2))
	{
		raise_query_validation_error($query);
	}
	if (USE_PREPARED_STATEMENTS)
	{
		$type_list = '';
		for ($i=0; $i<$where_values_count; $i+=2)
		{
			$type_list .= $where_values[$i];
		}
		$stmt = mysqli_prepare($db,$query);
		if (!empty($type_list))
		{
			mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
		}
		return run_prepared_statement($stmt,$strict);
	}
	else
	{
		foreach ($where_values as $value)
		{
			$pos = 0;
			for ($i=0; $i<$where_values_count; $i+=2)
			{
				if ($where_values[$i+1] == chr(0))
				{
					$param = 'NULL';
				}
				elseif ($where_values[$i] == 's')
				{
					$param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
				}
				else
				{
					$param = $where_values[$i+1];
				}
				$pos = strpos($query,'?',$pos);
				$query = substr($query,0,$pos).$param.substr($query,$pos+1);
				$pos++;
			}
		}
		return run_mysqli_query($db,$query,$strict);
	}
}

//==============================================================================
}
//==============================================================================
?>
