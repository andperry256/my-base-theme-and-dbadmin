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
if (!defined('NULLSTR'))
{
  define('NULLSTR',chr(0));
}
global $RootDir,$error_logfile;
$error_logfile = "$RootDir/logs/php_error.log";

//==============================================================================

if (!function_exists('deslash'))
{
//==============================================================================
/*
Function deslash

Although not a MySQL function as such, this function is included here as it is
most likely to be used when MySQL functions are also in use.

It is used to perform the 'stripslashes' function on all elements of an array,
mainly for use in processing the global $_POST array (in which strings are
always escaped).
*/
//==============================================================================

function deslash (array $data)
{
  foreach ($data as $key => $val)
  {
    $data [$key] = is_array ($val) ? deslash ($val) : stripslashes ($val);
  }
  return $data;
}

//==============================================================================

function print_stack_trace_for_mysqli_error($display_errors)
{
  global $argc, $error_logfile;
  $eol = (isset($argc)) ? "\n" : "<br />\n";
  ob_start();
  debug_print_backtrace();
  $trace = ob_get_contents();
  ob_end_clean();
  if ($display_errors)
  {
    print(str_replace("\n",$eol,$trace));
  }
  else
  {
    $ofp = fopen($error_logfile,'a');
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

function run_mysqli_query($db,$query,$strict=false,$debug=false)
{
  global $argc, $error_logfile;
  if ($debug)
  {
    exit ("$query\n");
  }
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
      print_stack_trace_for_mysqli_error(true);
    }
    else
    {
      // Online server
      $ofp = fopen($error_logfile,'a');
      fprintf($ofp,"[$date_and_time] [$error_id] Error caught on running MySQL query:\n  $query\n");
      fprintf($ofp,'  '.$e->getMessage()."\n");
      fclose($ofp);
      print_stack_trace_for_mysqli_error(false);
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
      print_stack_trace_for_mysqli_error(true);
    }
    else
    {
      // Online server
      $ofp = fopen($error_logfile,'a');
      fprintf($ofp,"[$date_and_time] [$error_id] Error result returned from MySQL query:\n  $query\n");
      fclose($ofp);
      print_stack_trace_for_mysqli_error(false);
      print($fatal_error_message);
    }
    exit;
  }
  return $result;
}

//==============================================================================

function mysqli_query_normal($db,$query,$debug=false)
{
  return run_mysqli_query($db,$query,false,$debug);
}

//==============================================================================

function mysqli_query_strict($db,$query,$debug=false)
{
  return run_mysqli_query($db,$query,true,$debug);
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
  global $argc, $error_logfile;
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
      print_stack_trace_for_mysqli_error(true);
    }
    else
    {
      // Online server
      $ofp = fopen($error_logfile,'a');
      fprintf($ofp,"[$date_and_time] [$error_id] Error caught on running MySQL query:\n  $query\n");
      fprintf($ofp,'  '.$e->getMessage()."\n");
      fclose($ofp);
      print_stack_trace_for_mysqli_error(false);
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
      print_stack_trace_for_mysqli_error(true);
    }
    else
    {
      // Online server
      $ofp = fopen($error_logfile,'a');
      fprintf($ofp,"[$date_and_time] [$error_id] Error result returned from MySQL query:\n  $query\n");
      fclose($ofp);
      print_stack_trace_for_mysqli_error(false);
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
where the number of items in the query does not relate correctly to the number
of supplied values.

The following parameters are passed:-
$query - The MySQL query being processed. This is only for output to the user
         and in some cases is a partial rather than whole query.
$param_count - The number of items to which values should be applied.
$fields - A comma separated list of field names for an UPDATE or INSERT query.
          Otherwise empty.
$values - The values being applied in the query (array). In a correct
          configutaion, each item would occupy two array elements (variable
          type followed by value).
*/
//==============================================================================

function raise_query_validation_error($query,$param_count,$fields,$values)
{
  global $error_logfile;
  $eol = (isset($argc)) ? "\n" : "<br />\n";
  $error_id = substr(md5(date('YmdHis')),0,8);
  $date_and_time = date('Y-m-d H:i:s');
  if (is_file("/Config/linux_pathdefs.php"))
  {
    // Local server
    print("Failed to validate MySQL query:$eol$query$eol");
    $value_count = count($values);
    $total_count = ($value_count > $param_count*2)
      ? $value_count
      : $param_count*2;
    print("(Params=$param_count, Values=$value_count)$eol");
    print("<style>\n");
    print("table { border-collapse: collapse; }\n");
    print("td { border: solid 1px #ccc; padding-left: 0.5em }\n");
    print("</style>\n");
    print("<table>\n");
    for ($i=0; $i<=$total_count; $i++)
    {
      print("<tr><td style=\"width:15.0em\">");
      if (($i%2 == 0) && ($i < $param_count*2))
      {
        $param = strtok($fields,',');
        $fields = substr($fields,strlen($param)+1);
        print (empty($param))
          ? '***'
          : $param;
      }
      print("</td><td>{$values[$i]}</td></tr>\n");
    }
    print("</table>\n");
    print_stack_trace_for_mysqli_error(true);
  }
  else
  {
    // Online server
    $ofp = fopen($error_logfile,'a');
    fprintf($ofp,"[$date_and_time] [$error_id] Failed to validate MySQL query:\n  $query\n");
    fclose($ofp);
    print_stack_trace_for_mysqli_error(false);
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
$fields - List of fields being selected ('*' or comma separated string)
$where_clause - WHERE clause to be used in the query (optional). Values to
                compare with are included as question marks.
$where_values - Values associated with the WHERE clause (array).Each item
                occupies two array elements (variable type followed by value).
$add_clause - Any additional clause to be added to the query (opitional -
              includes for example ORDER or LIMIT direactive).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_select_query($db,$table,$fields,$where_clause,$where_values,$add_clause,$strict=true,$debug=false)
{
  $query = "SELECT $fields FROM $table";
  $where_clause_count = substr_count($where_clause,'?');
  $where_values_count = count($where_values);
  if ($where_values_count != $where_clause_count*2)
  {
    raise_query_validation_error("$query WHERE $where_clause ...",$where_clause_count,'',$where_values);
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
      if ($where_values[$i] == 's')
      {
        $param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
      }
      else
      {
        $param = $where_values[$i+1];
      }
      $pos = strpos($query,'?',$pos);
      $query = substr($query,0,$pos).$param.substr($query,$pos+1);
      $pos += strlen($param) + 1;
    }
    return run_mysqli_query($db,$query,$strict,$debug);
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

function mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values,$strict=false,$debug=false)
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
  $param_count = substr_count($set_fields,',') + 1 + substr_count($where_clause,'?');
  $all_values = array_merge($set_values,$where_values);
  $all_values_count = count($all_values);
  if ($all_values_count != $param_count*2)
  {
    raise_query_validation_error($query,$param_count,$set_fields,$all_values);
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
      if ($all_values[$i+1] == NULLSTR)
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
      $pos += strlen($param) + 1;
    }
    return run_mysqli_query($db,$query,$strict,$debug);
  }
}

//==============================================================================
/*
Function mysqli_insert_query

This function is called to run an INSERT query using a prepared statement.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$fields - List of specified fields (comma separated string). Set to '*' to
          indicate all fields in order.
$values - Values associated with the field list (array). Each item occupies
          two array elements (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

The query result is returned.
*/
//==============================================================================

function mysqli_insert_query($db,$table,$fields,$values,$strict=false,$debug=false)
{
  $field_count =  ($fields == '*')
    ? mysqli_num_rows(mysqli_query_normal($db,"SHOW COLUMNS FROM $table"))
    : substr_count($fields,',') + 1;
  $values_count = count($values);
  if ($values_count != $field_count*2)
  {
    raise_query_validation_error("INSERT INTO $table ...",$field_count,$fields,$values);
  }
  if (USE_PREPARED_STATEMENTS)
  {
    $type_list = '';
    $values_template = '';
    for ($i=0; $i<$values_count; $i+=2)
    {
      $type_list .= $values[$i];
      $values_template .= '?,';
    }
    $values_template = rtrim($values_template,',');
    $stmt = ($fields == '*')
      ? mysqli_prepare($db,"INSERT INTO $table VALUES ($values_template)")
      : mysqli_prepare($db,"INSERT INTO $table ($fields) VALUES ($values_template)");
    mysqli_stmt_bind_param($stmt, $type_list, ...$values);
    return run_prepared_statement($stmt,$strict);
  }
  else
  {
    $values_list = '';
    for ($i=0; $i<$values_count; $i+=2)
    {
      if ($values[$i+1] == NULLSTR)
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
    return ($fields == '*')
      ? run_mysqli_query($db,"INSERT INTO $table VALUES ($values_list)",$strict,$debug)
      : run_mysqli_query($db,"INSERT INTO $table ($fields) VALUES ($values_list)",$strict,$debug);
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

function mysqli_delete_query($db,$table,$where_clause,$where_values,$strict=false,$debug=false)
{
  $query = "DELETE FROM $table";
  $where_clause_count = substr_count($where_clause,'?');
  $where_values_count = count($where_values);
  if ($where_values_count != $where_clause_count*2)
  {
    raise_query_validation_error($query,$where_clause_count,'',$where_values);
  }
  if (!empty($where_clause))
  {
    $query .= " WHERE $where_clause";
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
      if ($where_values[$i] == 's')
      {
        $param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
      }
      else
      {
        $param = $where_values[$i+1];
      }
      $pos = strpos($query,'?',$pos);
      $query = substr($query,0,$pos).$param.substr($query,$pos+1);
      $pos += strlen($param) + 1;
    }
    return run_mysqli_query($db,$query,$strict,$debug);
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
$where_values - Parameters to be bound (array). Each item occupies two array
                elements (variable type followed by value).
$strict (optional) - See run_prepared_statement function.

*/
//==============================================================================

function mysqli_free_format_query($db,$query,$where_values,$strict=true,$debug=false)
{
  $where_clause_count = substr_count($query,'?');
  $where_values_count = count($where_values);
  if ($where_values_count != $where_clause_count*2)
  {
    raise_query_validation_error($query,$where_clause_count,'',$where_values);
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
    for ($i=0; $i<$where_values_count; $i+=2)
    {
      $pos = 0;
      for ($i=0; $i<$where_values_count; $i+=2)
      {
        if ($where_values[$i] == 's')
        {
          $param = "'".mysqli_real_escape_string($db,$where_values[$i+1])."'";
        }
        else
        {
          $param = $where_values[$i+1];
        }
        $pos = strpos($query,'?',$pos);
        $query = substr($query,0,$pos).$param.substr($query,$pos+1);
        $pos += strlen($param) + 1;
      }
    }
    return run_mysqli_query($db,$query,$strict,$debug);
  }
}

//==============================================================================
}
//==============================================================================
?>
