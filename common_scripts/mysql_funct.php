<?php
//==============================================================================
if (!function_exists('run_prepared_statement'))
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
	if (!empty($where_clause))
	{
		$query .= " WHERE $where_clause";
	}
	$type_list = '';
	foreach ($where_values as $value)
	{
		$type_list .= substr(gettype($value),0,1);
	}
	if (!empty($add_clause))
	{
		$query .= " $add_clause";
	}
	$stmt = mysqli_prepare($db,$query);
	if (!empty($type_list))
	{
		mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
	}
	return run_prepared_statement($stmt,$strict);
}

//==============================================================================
/*
Function mysqli_update_query

This function is called to run an UPDATE query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$set_fields - List of fields being updated (comma separated string)
$set_values - Values associated with the field list (array)
$where_clause - WHERE clause to be used in the query (optional). Values to
                compare with are included as question marks.
$where_values - Values associated with the WHERE clause (array).
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
	$type_list = '';
	$all_values = array_merge($set_values,$where_values);
	foreach ($all_values as $value)
	{
		print("$value<br>");
		$type_list .= substr(gettype($value),0,1);
	}
	print("$type_list<br>");
	print("$query<br>");
	$stmt = mysqli_prepare($db,$query);
	mysqli_stmt_bind_param($stmt, $type_list, ...$all_values);
	return run_prepared_statement($stmt,$strict);
}

//==============================================================================
/*
Function mysqli_insert_query

This function is called to run an INSERT query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$fields - List of specified fields (comma separated string)
$values - Values associated with the field list (array)
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_insert_query($db,$table,$fields,$values,$strict=false)
{
	$values_template = '';
	$type_list = '';
	foreach ($values as $value)
	{
		$values_template .= '?,';
		$type_list .= substr(gettype($value),0,1);
	}
	$values_template = rtrim($values_template,',');
	$stmt = mysqli_prepare($db,"INSERT INTO $table ($fields) VALUES ($values_template)");
	mysqli_stmt_bind_param($stmt, $type_list, ...$values);
	return run_prepared_statement($stmt,$strict);
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
$where_values - Values associated with the WHERE clause (array).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_delete_query($db,$table,$where_clause,$where_values,$strict=false)
{
	// N.B. Query must have a WHERE clause.
	$query = "DELETE FROM $table WHERE $where_clause";
	$type_list = '';
	foreach ($where_values as $value)
	{
		$type_list .= substr(gettype($value),0,1);
	}
	$stmt = mysqli_prepare($db,$query);
	if (!empty($type_list))
	{
		mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
	}
	return run_prepared_statement($stmt,$strict);
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
$where_values - Parameters to be bound.
$strict (optional) - See run_prepared_statement function.

*/
//==============================================================================

function mysqli_free_format_query($db,$query,$where_values,$strict=true)
{
	$type_list = '';
	foreach ($where_values as $value)
	{
		$type_list .= substr(gettype($value),0,1);
	}
	$stmt = mysqli_prepare($db,$query);
	if (!empty($type_list))
	{
		mysqli_stmt_bind_param($stmt, $type_list, ...$where_values);
	}
	return run_prepared_statement($stmt,$strict);
}

//==============================================================================
}
//==============================================================================
?>
