<?php
//==============================================================================
/*
N.B. The option for running with prepared statements has been removed in favour
of the alternative mechanism. If this needs to be reinstated, then please refer
to an old version of the code from the Git repository or alternatively the home
backup archive for September 2023.

//==============================================================================

Field/Variable Types

The following field types are returned from the function 'query_field_type' and
are also used as field types in the arrays of field values passed as parameters
to the various MySQL query handling functions:-

i - Integer (including Boolean).
d - Double (any non-integer number type).
s - String.

The following field types are also available to be passed in array parameters
to functions (for special settings of the field value):-

f  - Field Name.
n  - Null.
sn - String/Null (set to null if empty).

//==============================================================================
*/

if (!defined('NOINSERT'))
{
    define('NOINSERT',2);
}
global $RootDir,$error_logfile, $home_remote_ip_addr, $display_error_online;
$error_logfile = "$RootDir/logs/php_error.log";
$display_error_online = ((isset($home_remote_ip_addr)) && (isset($_SERVER['REMOTE_ADDR'])) && ($_SERVER['REMOTE_ADDR'] == $home_remote_ip_addr));

//==============================================================================
if (!function_exists('array_deslash')):
//==============================================================================
/*
Function array_deslash

Although not a MySQL function as such, this function is included here as it is
most likely to be used when MySQL functions are also in use.

It is used to perform the 'stripslashes' function on all elements of an array.
*/
//==============================================================================

function array_deslash (array $data)
{
    foreach ($data as $key => $val)
    {
        $data [$key] = is_array ($val) ? array_deslash ($val) : stripslashes ($val);
    }
    return $data;
}

//==============================================================================
/*
Function print_stack_trace_for_mysqli_error

This function is called to output the PHP stack trace when an exeception is
raised in one of the other functions. It outputs to the screen or log file
according to the setting of the parameter $display_errors.
*/
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
            $line = str_replace('%','%%',$line);
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
    global $argc, $error_logfile, $display_error_online;
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
            print_stack_trace_for_mysqli_error($display_error_online);
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
            print_stack_trace_for_mysqli_error($display_error_online);
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
Function query_field_type

This function returns the variable type for a given field given the table and
field name. The possible values that can be returned as as specified in the 
comment at the top of this script.
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
        // This should not occur
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
    global $error_logfile, $display_error_online;
    $eol = (isset($argc)) ? "\n" : "<br />\n";
    $error_id = substr(md5(date('YmdHis')),0,8);
    $date_and_time = date('Y-m-d H:i:s');
    if ((is_file("/Config/linux_pathdefs.php")) || ($display_error_online))
    {
        // Local server or online server from home
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
        print_stack_trace_for_mysqli_error($display_error_online);
        print($fatal_error_message);
    }
    exit;
}

//==============================================================================
/*
Function mysqli_select_query

This function is called to run a SELECT query.

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
$strict (optional) - See run_mysqli_query function.

The query result (data or false) is returned.
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
    $pos = 0;
    for ($i=0; $i<$where_values_count; $i+=2)
    {
        if ($where_values[$i] == 's')
        {
            $param = (!empty($where_values[$i+1]))
                ? "'".mysqli_real_escape_string($db,$where_values[$i+1])."'"
                : "''";
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

//==============================================================================
/*
Function mysqli_update_query

This function is called to run an UPDATE query.

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
$strict (optional) - See run_mysqli_query function.

The query result (true/false) is returned.
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
    $pos = 0;
    for ($i=0; $i<$all_values_count; $i+=2)
    {
        if (($all_values[$i] == 'n') || 
            (($all_values[$i] == 'sn') && (empty($all_values[$i+1]))))
        {
            $param = 'NULL';
        }
        elseif (($all_values[$i] == 's') || ($all_values[$i] == 'sn'))
        {
            $param = ($all_values[$i+1] != '')
                ? "'".mysqli_real_escape_string($db,$all_values[$i+1])."'"
                : "''";
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

//==============================================================================
/*
Function mysqli_insert_query

This function is called to run an INSERT query.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$fields - List of specified fields (comma separated string). Set to '*' to
          indicate all fields in order.
$values - Values associated with the field list (array). Each item occupies
          two array elements (variable type followed by value).
$strict (optional) - See run_mysqli_query function.

The query result (true/false) is returned.
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
    $values_list = '';
    for ($i=0; $i<$values_count; $i+=2)
    {
        if (($values[$i] == 'n') || 
            (($values[$i] == 'sn') && (empty($values[$i+1]))))
        {
            $param = 'NULL';
        }
        elseif (($values[$i] == 's') || ($values[$i] == 'sn'))
        {
            $param = (!empty($values[$i+1]))
                ? "'".mysqli_real_escape_string($db,$values[$i+1])."'"
                : "''";
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

//==============================================================================
/*
Function mysqli_conditional_insert_query

This function performs a similar operation to mysqli_insert_query, except that
it performs an additional check to determine whether a record with matching
primary keys already exists. It takes the same parameters as mysqli_insert_query
plus $where_clause and $where_values as used in other functions.

The function returns one of the following:-
* true/false from running the insert query.
* NOINSERT if a matching record was found and no insert made.

The calling software must check the returned result against true/false/NOINSERT
using the '===' operator.
*/
//==============================================================================

function mysqli_conditional_insert_query($db,$table,$fields,$values,$where_clause,$where_values,$strict=false,$debug=false)
{
    // Check INSERT query
    $field_count =  ($fields == '*')
        ? mysqli_num_rows(mysqli_query_normal($db,"SHOW COLUMNS FROM $table"))
        : substr_count($fields,',') + 1;
    $values_count = count($values);
    if ($values_count != $field_count*2)
    {
        raise_query_validation_error("INSERT INTO $table ...",$field_count,$fields,$values);
    }
  
    // Build SELECT query
    $where_clause_count = substr_count($where_clause,'?');
    $where_values_count = count($where_values);
    $select_query = "SELECT * FROM $table WHERE $where_clause";
    if ($where_values_count != $where_clause_count*2)
    {
        raise_query_validation_error("$select_query ...",$where_clause_count,'',$where_values);
    }
    else
    {
        $pos = 0;
        for ($i=0; $i<$where_values_count; $i+=2)
        {
            if ($where_values[$i] == 's')
            {
                $param = (!empty($where_values[$i+1]))
                    ? "'".mysqli_real_escape_string($db,$where_values[$i+1])."'"
                    : "''";
            }
            else
            {
                $param = $where_values[$i+1];
            }
            $pos = strpos($select_query,'?',$pos);
            $select_query = substr($select_query,0,$pos).$param.substr($select_query,$pos+1);
            $pos += strlen($param) + 1;
        }
    }
  
    if (mysqli_num_rows(mysqli_query($db,$select_query)) > 0)
    {
        return NOINSERT;
    }
    else
    {
        $values_list = '';
        for ($i=0; $i<$values_count; $i+=2)
        {
            if (($values[$i] == 'n') || 
                (($values[$i] == 'sn') && (empty($values[$i+1]))))
            {
                $param = 'NULL';
            }
            elseif (($values[$i] == 's') || ($values[$i] == 'sn'))
            {
                $param = (!empty($values[$i+1]))
                    ? "'".mysqli_real_escape_string($db,$values[$i+1])."'"
                    : "''";
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

This function is called to run a DELETE query.

The following parameters are passed:-
$db - Link to the connected database
$table - Associated table
$where_clause - WHERE clause to be used in the query. Values to compare with
                are included as question marks.
$where_values - Values associated with the WHERE clause (array). Each item
                occupies two array elements (variable type followed by value).
$strict (optional) - See run_mysqli_query function.

The query result (true/false) is returned.
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
    $pos = 0;
    for ($i=0; $i<$where_values_count; $i+=2)
    {
        if ($where_values[$i] == 's')
        {
            $param = (!empty($where_values[$i+1]))
                ? "'".mysqli_real_escape_string($db,$where_values[$i+1])."'"
                : "''";
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

//==============================================================================
/*
Function mysqli_free_format_query

This function is called to run a query which does not exactly fit a category
covered by one of the preceding functions.

It basically takes a ready-made query but with the option to supply and bind
paramaters.

The following parameters are passed:-
$db - Link to the connected database
$query - Query text,
$where_values - Parameters to be bound (array). Each item occupies two array
                elements (variable type followed by value).
$strict (optional) - See run_mysqli_query function.

The query result (true/false or data) is returned.
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
    for ($i=0; $i<$where_values_count; $i+=2)
    {
        $pos = 0;
        for ($i=0; $i<$where_values_count; $i+=2)
        {
            if ($where_values[$i] == 's')
            {
                $param = (!empty($where_values[$i+1]))
                    ? "'".mysqli_real_escape_string($db,$where_values[$i+1])."'"
                    : "''";
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

//==============================================================================
endif;
//==============================================================================
?>
