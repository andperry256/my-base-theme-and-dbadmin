<?php
//==============================================================================
if (!function_exists('get_table_info_field')) :
//==============================================================================
/*
Functions get_table_info_field and get_table_fields_field

These functions operate on the dba_table_info and da_table_fields tables
respectively, returning the appropriate value for a given field. It moves up
through the hieracrchy from the current table/view to the base table until a
value is found.
*/
//==============================================================================

function get_table_info_field($table,$field_name)
{
    $db = admin_db_connect();
    $count = 0;
    $where_clause = 'table_name=?';
    $where_values = array('s',$table);
    $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
    while (($row = mysqli_fetch_assoc($query_result)) && ($count < 5))
    {
        if (!empty($row[$field_name]))
        {
            return $row[$field_name];
        }
        $where_clause = 'table_name=?';
        $where_values = array('s',$row['parent_table']);
        $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        $count++;
    }
    return '';
}

function get_table_fields_field($table,$field_name,$field)
{
    $db = admin_db_connect();
    $count = 0;
    $where_clause = 'table_name=?';
    $where_values = array('s',$table);
    $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
    while (($row = mysqli_fetch_assoc($query_result)) && ($count < 5))
    {
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$row['table_name'],'s',$field_name);
        if (($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,''))) &&
            (!empty($row2[$field])))
        {
            return $row2[$field];
        }
        $where_clause = 'table_name=?';
        $where_values = array('s',$row['parent_table']);
        $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        $count++;
    }
    return '';
}

//==============================================================================
/*
Function field_label

This function returns the label to be used for a given table field, using the
alternate label if available, otherwise the standard label.
*/
//==============================================================================

function field_label($table,$field)
{
    $db = admin_db_connect();
    $label = get_table_fields_field($table,$field,'alt_label');
    if (empty($label))
    {
        $label = get_table_fields_field($table,$field,'field_name');
        $label = str_replace('-',' ',$label);
        $label = str_replace('_',' ',$label);
        $label = ucwords($label);
    }
    return $label;
}

//==============================================================================
/*
Function check_field_status
*/
//==============================================================================

function check_field_status($table,$field,$value)
{
    $db = admin_db_connect();
    $base_table = get_base_table($table);
    $value = trim($value);
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$field);
    $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        $widget_type = $row['widget_type'];
        if (is_numeric($value))
        {
            return true;
        }
        elseif (($widget_type == 'input-num') && (!empty($value)))
        {
            return report_error("Attempt to set numeric field <em>$field</em> to a non-numeric value.");
        }
        elseif (($row['required'] == 2) && (empty($value)))
        {
            return report_error("Field <em>$field</em> is required but not set.");
        }
    }
    return true;
}

//==============================================================================
/*
Function generate_widget
*/
//==============================================================================

function generate_widget($table,$field_name,$field_value)
{
    global $base_dir, $base_url, $db_admin_url;
    $db = admin_db_connect();
    $mode = get_viewing_mode();
    $base_table = get_table_for_field($table,$field_name);

    if ($field_value === false)
    {
        // No field value - indicates a new record. Find default.
        $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table where Field='$field_name'");
        if ($row = mysqli_fetch_assoc($query_result))
        {
                $field_value = $row['Default'];
        }
        else
        {
            return '';  // This should not occur
        }
    }
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$field_name);
    $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
    if ($row=mysqli_fetch_assoc($query_result))
    {
        switch ($row['widget_type'])
        {
            case 'date':
                datepicker_widget("field_$field_name",$field_value);
                break;
    
            case 'input-text':
            case 'input-text-small':
                print("<input type=\"text\" name=\"field_$field_name\" value=\"$field_value\" size=\"");
                if ($row['widget_type'] == 'input-text-small')
                {
                    print("8");
                }
                elseif ($mode == 'desktop')
                {
                    print("64");
                }
                else
                {
                    print("30");
                }
                print("\"");
                if ((!empty($row['vocab_table'])) && (!empty($row['vocab_field'])))
                {
                    print("list=\"list_$field_name\">");
                    print("<datalist id=\"list_$field_name\">\n");
                    $vocab_table = $row['vocab_table'];
                    $vocab_field = $row['vocab_field'];
                    $add_clause = "ORDER BY $vocab_field ASC";
                    $query_result2 = mysqli_select_query($db,$vocab_table,$vocab_field,'',array(),$add_clause);
                    while ($row2 = mysqli_fetch_assoc($query_result2))
                    {
                        print("<option value=\"{$row2[$vocab_field]}\"></option>\n");
                    }
                    print("</datalist>");
                }
                else
                {
                    print(">");
                }
                break;
    
            case 'input-num':
                print("<input type=\"text\" name=\"field_$field_name\" size=\"12\" value=\"$field_value\">");
                break;
    
            case 'password':
                print("<input type=\"password\" name=\"field_$field_name\" value=\"$field_value\">");
                break;
    
            case 'enum':
                print("<select name=\"field_$field_name\">\n");
                print("<option value=\"\">Please select ...</option>\n");
                $query_result2 = mysqli_query_normal($db,"SHOW COLUMNS FROM $table where Field='$field_name' AND Type LIKE 'enum(%'");
                if ($row2 = mysqli_fetch_assoc($query_result2))
                {
                        $options = substr($row2['Type'],5);
                        $options = rtrim($options,')');
                        $options = str_replace("'",'',$options);
                        $tok = strtok($options,",");
                        while ($tok !== false)
                        {
                            print("<option value=\"$tok\"");
                            if ($tok == $field_value)
                            {
                                print(" selected");
                            }
                            print(">$tok</option>\n");
                            $tok = strtok(",");
                        }
                }
                print("</select>");
                break;
    
            case 'time':
                if (empty($row['vocab_table']))
                {
                    /*
                    Generate a simple input widget if no vocabulary is specified. The use
                    of a proper time picker is for future development. If a vocabulary is
                    specified then drop down to the next case (for a select widget).
                    */
                    print("<input type=\"text\" name=\"field_$field_name\" size=\"8\" value=\"$field_value\">");
                    break;
                }
    
            case 'select':
                print("<select name=\"field_$field_name\">\n");
                print("<option value=\"\">Please select ...</option>\n");
                $vocab_table = $row['vocab_table'];
                $vocab_field = $row['vocab_field'];
                $add_clause = "ORDER BY $vocab_field ASC";
                $query_result2 = mysqli_select_query($db,$vocab_table,$vocab_field,'',array(),$add_clause);
                while ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    print("<option value=\"{$row2[$vocab_field]}\"");
                    if ((($row['widget_type'] == 'time') && (time_compare($row2[$vocab_field],$field_value) == 0)) ||
                        ($row2[$vocab_field] == $field_value))
                    {
                        print(" selected");
                    }
                    print(">{$row2[$vocab_field]}</option>\n");
                }
                print("</select>");
                break;
    
            case 'checklist':
                $vocab_table = $row['vocab_table'];
                $vocab_field = $row['vocab_field'];
                $item_list = array();
                $tok = strtok($field_value,'^');
                while ($tok !== false)
                {
                    if (!empty($tok))
                    {
                        $item_list[$tok] = true;
                    }
                    $tok = strtok('^');
                }
                $add_clause = "ORDER BY $vocab_field ASC";
                $query_result2 = mysqli_select_query($db,$vocab_table,$vocab_field,'',array(),$add_clause);
                while ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    $item = $row2[$vocab_field];
                    if ($item != '*')
                    {
                        /*
                        Dots, spaces and open square brackets are not allowed in $_POST
                        variable names. Spaces and brackets are converted by the urlencode,
                        but dots then need to be replaced with the hex code that will
                        convert back in urldecode.
                        */
                        $item_par = urlencode($item);
                        $item_par = str_replace('.','%2e',$item_par);
                        print("<input type=\"checkbox\" name=\"item_$field_name"."___$item_par\"");
                        if (isset($item_list[$item]))
                        {
                            print(" checked");
                        }
                        print(">&nbsp;$item<br/>\n");
                    }
                }
                break;
    
            case 'textarea':
                print("<textarea  name=\"field_$field_name\" rows=\"6\" cols=\"64\">$field_value</textarea>\n");
                break;
    
            case 'checkbox':
                print("<input type=\"checkbox\" name=\"field_$field_name\"");
                if ($field_value)
                {
                    print(" checked");
                }
                print(">");
                break;
    
            case 'auto-increment':
                print("AI [$field_value]");
                print("<input type=\"hidden\" name=\"field_$field_name\" value=\"$field_value\">");
                break;
    
            case 'static':
            case 'static-date':
                print("$field_value");
                if (strpos($field_value,'<a href') === false)
                {
                    print("<input type=\"hidden\" name=\"field_$field_name\" value=\"$field_value\">");
                }
                else
                {
                    /*
                    Do not put the actual field value here because the presence of hyperlinks can cause
                    issues with the displayed output. Hyperlinks should in any case only be used in fields
                    that are auto-generated on record save, thus removing the need for values to be
                    carried over from the current screen.
                    */
                    print("<input type=\"hidden\" name=\"field_$field_name\" value=\"\">");
                }
                break;
    
            case 'hidden':
                print("******");
                print("<input type=\"hidden\" name=\"field_$field_name\" value=\"$field_value\">");
                break;
    
            case 'file':
                if (!empty($field_value))
                {
                    print("<input type=\"text\" name=\"field_$field_name\" value=\"$field_value\">");
                    print("<input type=\"hidden\" name=\"existing_$field_name\" value=\"$field_value\">");
                }
                print("<br /><input type=\"file\" name=\"file_$field_name\"><br />");
                if (!empty($row['allowed_filetypes']))
                {
                    print("<span class=\"small\">Allowed types:-&nbsp;&nbsp;{$row['allowed_filetypes']}</span><br />");
                }
                print("<input type=\"checkbox\" name=\"overwrite_$field_name\">&nbsp;Allow overwrite");
                if ((!empty($field_value)) && ($fileext = pathinfo($field_value,PATHINFO_EXTENSION)) &&
                    (($fileext == 'gif') || ($fileext == 'jpg') || ($fileext == 'jpeg') || ($fileext == 'png')))
                {
                    // Output thumbnail image in widget
                    $file_path = "$base_dir/{$row['relative_path']}/$field_value";
                    if (is_file($file_path))
                    {
                        $file_url = "$base_url/{$row['relative_path']}/$field_value";
                        print("<br /><img src=\"$file_url\" class=\"widget-image\" /><br />\n");
                    }
                }
                break;
        }
    }
}

//==============================================================================
/*
Function handle_file_widget_before_save
*/
//==============================================================================

function handle_file_widget_before_save(&$record,$field)
{
    global $base_dir;
    $db = admin_db_connect();
    $table = $record->table;
    $base_table = get_base_table($table);

    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$field);
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'')))
    {
        if ((empty(basename($_FILES["file_$field"]['name']))) && (isset($_POST["existing_$field"])))
        {
            // Existing file but no new file.
            if ((!empty($_POST["field_$field"])) && ($_POST["field_$field"] != $_POST["existing_$field"]))
            {
                /*
                File being renamed on server with no upload. No need to check the
                overwrite flag, because if the required target file already exists, it is
                either an orphan file or associated with a different record and therefore
                cannot be dealt with automatically.
                */
                $target_file = "$base_dir/{$row['relative_path']}/{$_POST["field_$field"]}";
                if (is_file($target_file))
                {
                    return report_error("File of new name already exists on server.");
                }
            }
            else
            {
                // No action required on file.
                return true;
            }
        }
        else
        {
            $filename = basename($_FILES["file_$field"]['name']);
            $fileext = pathinfo($filename,PATHINFO_EXTENSION);
            $allowed_filetypes = $row['allowed_filetypes'];
            $found = false;
            $tok = strtok($allowed_filetypes,',');
            while ($tok !== false)
            {
                $tok = trim($tok,'.');
                if ($tok == $fileext)
                {
                    $found = true;
                    break;
                }
                $tok = strtok(',');
            }
            if (!$found)
            {
                return report_error("Invalid file type.");
            }
            if (!empty($filename))
            {
                $target_file = (isset($_POST["existing_$field"]))
                ? "$base_dir/{$row['relative_path']}/{$_POST["existing_$field"]}"
                : "$base_dir/{$row['relative_path']}/$filename";
                if ((is_file($target_file)) && (!isset($_POST["overwrite_$field"])))
                {
                    return report_error("File already exists on server and <em>overwrite</em> option not selected.");
                }
            }
            // Add the new filename to the record.
            $record->SetField($field,$filename,query_field_type($db,$table,$field));
        }
    }
    else
    {
        // This should not occur.
    }
}

//==============================================================================
/*
Function handle_file_widget_after_save
*/
//==============================================================================

function handle_file_widget_after_save($record,$field)
{
    global $base_dir;
    $db = admin_db_connect();
    $table = $record->table;
    $base_table = get_base_table($table);
    $filename = $record->FieldVal($field);
    $old_filename = get_session_var(array('post_vars',"existing_$field"));

    $where_clause = 'table_name=? AND field_name=?';
    $where_values = array('s',$base_table,'s',$field);
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'')))
    {
        $old_target_file = "$base_dir/{$row['relative_path']}/$old_filename";
        $new_target_file = "$base_dir/{$row['relative_path']}/$filename";
        if ((empty(basename($_FILES["file_$field"]['name']))) && (isset($_POST["existing_$field"])))
        {
            // Existing file but no new file.
            if ((!empty($_POST["field_$field"])) && ($_POST["field_$field"] != $_POST["existing_$field"]))
            {
                // File being renamed on server with no upload.
                rename($old_target_file,$new_target_file);
                if ((is_file($old_target_file)) || (!is_file($new_target_file)))
                {
                    return report_error("Unable to rename file <em>$old_filename</em> to <em>$filename</em>.");
                }
            }
            else
            {
                // No action required on file.
                return true;
            }
        }
        else
        {
            if (!empty(basename($_FILES["file_$field"]['name'])))
            {
                // A new file is being uploaded. Delete the old file if present and
                // move the new file to the destination.
                if (is_file($old_target_file))
                {
                    unlink($old_target_file);
                    if (is_file($old_target_file))
                    {
                        return report_error("Unable to delete existing file <em>$old_filename</em>.");
                    }
                }
                $result = move_uploaded_file($_FILES["file_$field"]['tmp_name'],$new_target_file);
                if ($result === false)
                {
                    return report_error("File <em>$filename</em> could not be uploaded.");
                }
        
                // Update record with the filename.
                $where_clause = 'table_name=? AND is_primary=1';
                $where_values = array('s',$base_table);
                $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                $set_fields = "$field";
                $set_values = array('s',$filename);
                $where_clause = '';
                $where_values = array();
                while ($row = mysqli_fetch_assoc($query_result))
                {
                    $pk_field = $row['field_name'];
                    $pk_value = $record->FieldVal($pk_field);
                    $query = rtrim($query,' AND');
                    $where_clause .= "$pk_field=? AND ";
                    $where_values[count($where_values)] = 's';
                    $where_values[count($where_values)] = $pk_value;
                }
                $where_clause = rtrim($where_clause,' AND');
                mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);
            }
        }
    }
    else
    {
        // This should not occur.
    }
}

//==============================================================================
/*
Function report_error
*/
//==============================================================================

function report_error($message)
{
    update_session_var('error_message',$message);
    return false;
}

//==============================================================================
/*
Function run_relationship_update_queries
*/
//==============================================================================

function run_relationship_update_queries($record)
{
    $db = admin_db_connect();
    $table = $record->table;
    $action = $record->action;
    if (($action == 'edit') || ($action == 'update'))
    {
        $base_table = get_base_table($table);
        $where_clause = "table_name=? AND UPPER(query) LIKE 'UPDATE%'";
        $where_values = array('s',$base_table);
        $query_result = mysqli_select_query($db,'dba_relationships','*',$where_clause,$where_values,'');
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $query = $row['query'];
            $matches = array();
    
            /*
            Substitute variable names of type $$name. Only valid for primary key fields
            and works on the original value of the field.
            */
            while (preg_match(RELATIONSHIP_VARIABLE_MATCH_2,$query,$matches))
            {
                $leading_char = substr($matches[0],0,1);
                $field_name = substr($matches[0],3);
                $value = $record->OldPKVal($field_name);
                $value = str_replace('$','\\$',$value);
                $query = str_replace($matches[0],"$leading_char$value",$query);
            }
    
            /*
            Substitute variable names of type $name. Works on the final value of the field.
            */
            while (preg_match(RELATIONSHIP_VARIABLE_MATCH_1,$query,$matches))
            {
                $leading_char = substr($matches[0],0,1);
                $field_name = substr($matches[0],2);
                $value = $record->FieldVal($field_name);
                $value = str_replace('$','\\$',$value);
                $query = str_replace($matches[0],"$leading_char$value",$query);
            }
            mysqli_query_normal($db,$query);
        }
    }
    else
    {
        /*
        No action - this function will only operate where an existing record is
        being updated, as opposed to the creation of a new record.
        */
    }
}

//==============================================================================
/*
Function run_relationship_delete_queries
(with sub-function run_relationship_delete_query)
*/
//==============================================================================

function run_relationship_delete_query($query,$remainder)
{
    $db = admin_db_connect();
    $query = preg_replace('/DELETE\/UPDATE/i','UPDATE',$query);
    $words = preg_split("/[\s]+/", $query);
    switch ($words[0])
    {
        case 'UPDATE':
            /*
            This is a 'DELETE/UPDATE' query, which indicates that it is actually an
            update query but running on the result of a deletion. This is handled as
            the end of the line - i.e. no further queries will be executed.
            */
            mysqli_query_normal($db,$query);
            return;
        
        case 'DELETE':
            if (!empty($remainder))
            {
                if (strtoupper($words[1]) == 'FROM')
                {
                    $next_query = strtok($remainder,';');
                    $next_remainder = trim(substr($remainder,strlen($next_query)),'; ');
            
                    // Run a SELECT query on the set of records that are due to be
                    // deleted by the current query.
                    $query_result = mysqli_query_normal($db,preg_replace('/DELETE FROM/i','SELECT * FROM',$query));
                    while ($row = mysqli_fetch_assoc($query_result))
                    {
                        // Substitute variable names of type $name.
                        $matches = array();
                        $query2 = $next_query;
                        while (preg_match(RELATIONSHIP_VARIABLE_MATCH_1,$query2,$matches))
                        {
                            $leading_char = substr($matches[0],0,1);
                            $field_name = substr($matches[0],2);
                            $value = $row[$field_name];
                            $value = str_replace('$','\\$',$value);
                            $query2 = str_replace($matches[0],"$leading_char$value",$query2);
                        }
            
                        // Run the next query in line against the individual record from the
                        // SELECT query (via a recursive function call).
                        run_relationship_delete_query($query2,$next_remainder);
                    }
                }
            }
            // Run the current query.
            mysqli_query_normal($db,$query);
            return;
    
        default:
            return;
    }
}

function run_relationship_delete_queries($record)
{
    $db = admin_db_connect();
    $table = $record->table;
    $base_table = get_base_table($table);
    $where_clause = "table_name=? AND UPPER(query) LIKE 'DELETE%'";
    $where_values = array('s',$base_table);
    $query_result = mysqli_select_query($db,'dba_relationships','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $query = strtok($row['query'],';');
        $remainder = trim(substr($row['query'],strlen($query)),'; ');
    
        // Substitute variable names of type $name.
        $matches = array();
        while (preg_match(RELATIONSHIP_VARIABLE_MATCH_1,$query,$matches))
        {
            $leading_char = substr($matches[0],0,1);
            $field_name = substr($matches[0],2);
            $value = $record->FieldVal($field_name);
            $value = str_replace('$','\\$',$value);
            $query = str_replace($matches[0],"$leading_char$value",$query);
        }
    
        // Run the query and any sub-queries.
        run_relationship_delete_query($query,$remainder);
    }
}

//==============================================================================
/*
Function previous_record_link
*/
//==============================================================================

function previous_record_link($table,$record_id)
{
    global $base_url, $relative_path;
    global $select_this_record;
    if (empty($record_id))
    {
        return;
    }
    $db = admin_db_connect();
    $primary_keys = decode_record_id($record_id);
    $sort_field_list = array();
    $alt_order = get_table_info_field($table,'alt_field_order');
    $index = 0;
    if (!empty($alt_order))
    {
        // Use alternate field order
        $tok = strtok($alt_order,',');
        while ($tok !== false)
        {
            $sort_field_list[$index++] = $tok;
            $tok = strtok(',');
        }
    }
    else
    {
        // Use primary keys
        foreach ($primary_keys as $key => $value)
        {
            $sort_field_list[$index++] = $key;
        }
    }
    $query_result = mysqli_select_query($db,$table,'*',$select_this_record['where_clause'],$select_this_record['where_values'],'');
    if (($query_result) && ($current_record = mysqli_fetch_assoc($query_result)))
    {
        $sort_field_count = count($sort_field_list);
    
        $where_clause = get_session_var('search_clause')." AND {$sort_field_list[0]}<=?";
        if (substr($where_clause,0,5) == ' AND ')
        {
            $where_clause = substr($where_clause,5);
        }
        $where_values = array('s',$current_record[$sort_field_list[0]]);
        $add_clause = 'ORDER BY ';
        for ($index=0; $index<$sort_field_count; $index++)
        {
            $add_clause .= " {$sort_field_list[$index]} DESC, ";
        }
        $add_clause = rtrim($add_clause,', ');
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            if (isset($current_record_processed))
            {
                // Processing previous record
                $prev_rec_primary_keys = array();
                foreach($primary_keys as $key => $value)
                {
                    $prev_rec_primary_keys[$key] = $row[$key];
                }
                $previous_record_id = encode_record_id($prev_rec_primary_keys);
                print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-table=$table&-action=edit&-recordid=$previous_record_id\">Previous</a></div>");
                return;
            }
            elseif (count(array_diff_assoc($row,$current_record)) == 0)
            {
                // Processing current record
                $current_record_processed = true;
            }
        }
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" style=\"color:silver\" href=#>Previous</a></div>");
    }
}

//==============================================================================
/*
Function next_record_link
*/
//==============================================================================

function next_record_link($table,$record_id)
{
    global $base_url, $relative_path;
    global $select_this_record;
    if (empty($record_id))
    {
        return;
    }
    $db = admin_db_connect();
    $primary_keys = decode_record_id($record_id);
    $sort_field_list = array();
    $alt_order = get_table_info_field($table,'alt_field_order');
    $index = 0;
    if (!empty($alt_order))
    {
        // Use alternate field order
        $tok = strtok($alt_order,',');
        while ($tok !== false)
        {
            $sort_field_list[$index++] = $tok;
            $tok = strtok(',');
        }
    }
    else
    {
        // Use primary keys
        foreach ($primary_keys as $key => $value)
        {
            $sort_field_list[$index++] = $key;
        }
    }
    $query_result = mysqli_select_query($db,$table,'*',$select_this_record['where_clause'],$select_this_record['where_values'],'');
    if (($query_result) && ($current_record = mysqli_fetch_assoc($query_result)))
    {
        $sort_field_count = count($sort_field_list);
    
        // Loop through table to find current and next record
        $where_clause = get_session_var('search_clause')." AND {$sort_field_list[0]}>=?";
        if (substr($where_clause,0,5) == ' AND ')
        {
            $where_clause = substr($where_clause,5);
        }
        $where_values = array('s',$current_record[$sort_field_list[0]]);
        $add_clause = 'ORDER BY';
        for ($index=0; $index<$sort_field_count; $index++)
        {
            $add_clause .= " {$sort_field_list[$index]} ASC, ";
        }
        $add_clause = rtrim($add_clause,', ');
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            if (isset($current_record_processed))
            {
                // Processing next record
                $next_rec_primary_keys = array();
                foreach($primary_keys as $key => $value)
                {
                    $next_rec_primary_keys[$key] = $row[$key];
                }
                $next_record_id = encode_record_id($next_rec_primary_keys);
                print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-table=$table&-action=edit&-recordid=$next_record_id\">Next</a></div>");
                return;
            }
            elseif (count(array_diff_assoc($row,$current_record)) == 0)
            {
                // Processing current record
                $current_record_processed = true;
            }
        }
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" style=\"color:silver\" href=#>Next</a></div>");
    }
}

//==============================================================================
/*
Function save_record
*/
//==============================================================================

function save_record($record,$old_record_id,$new_record_id)
{
    global $custom_pages_path, $relative_path;
    $action = $record->action;
    $table = $record->table;
    global $custom_pages_path, $relative_path;
    $db = admin_db_connect();
    $base_table = get_base_table($table);
    $old_primary_keys = fully_decode_record_id($old_record_id);
    $new_primary_keys = fully_decode_record_id($new_record_id);
    foreach($new_primary_keys as $key => $value)
    {
        if (!isset($old_primary_keys[$key]))
        {
            $old_primary_keys[$key] = '';
        }
    }
    $record->SaveOldPKs($old_primary_keys);

    // Generate the strings for all the record fields in the format that they
    // would be used in a MySQL query.
    $old_mysql_fields = array();
    $new_mysql_fields = array();
    $field_is_null = array();

    $auto_inc_field_present = false;
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $field_value = $record->FieldVal($field_name);
            if ($row2['widget_type'] == 'auto-increment')
            {
                // Auto-increment field needs to be omitted but need to note that there
                // is one present.
                $auto_inc_field_present = true;
            }
            elseif (empty($field_value))
            {
                // Field is empty.
                if (($row['Null'] == 'YES') &&
                    (($row2['widget_type'] == 'date') || ($row2['widget_type'] == 'static-date') || 
                     ($row2['widget_type'] == 'enum') || ($record->FieldType($field_name) != 's')))
                {
                    // Set to null if allowed.
                    $field_is_null[$field_name] = true;
                    $new_mysql_fields[$field_name] = null;
                }
                elseif (($record->FieldType($field_name) == 'i') || ($record->FieldType($field_name) == 'd'))
                {
                    // Set to zero if numeric.
                    $new_mysql_fields[$field_name] = 0;
                }
                else
                {
                    $new_mysql_fields[$field_name] = $field_value;
                }
            }
            else
            {
                $new_mysql_fields[$field_name] = $field_value;
            }
            if ($row2['is_primary'])
            {
                $old_mysql_fields[$field_name] = $old_primary_keys[$field_name];
            }
        }
    }

    // Determine whether the save operation involves a primary key change
    if (($new_record_id != $old_record_id) && (!$auto_inc_field_present))
    {
        // Check for duplicate record ID
        $fields = '';
        $where_clause = '';
        $where_values = array();
        foreach ($new_primary_keys as $field => $val)
        {
            $fields .= "$field,";
            $where_clause .= "$field=? AND ";
            $where_values[count($where_values)] = $record->FieldType($field);
            $where_values[count($where_values)] = $val;
        }
        $where_clause = rtrim($where_clause,' AND');
        $fields = rtrim($fields,',');
        $query_result = mysqli_select_query($db,$table,$fields,$where_clause,$where_values,'');
        if (($query_result !== false) && (mysqli_num_rows($query_result) > 0))
        {
            return report_error("Unable to save due to duplicate record ID.\n");
        }
    }

    $classname = "tables_$base_table";
    if  (class_exists ($classname,false))
    {
        $table_obj = new $classname;
    
        // Run any validate methods
        foreach ($new_mysql_fields as $field => $value)
        {
            $result = check_field_status($table,$field,$record->FieldVal($field));
            if ($result === false)
            {
                return false;
            }
    
            $method = $field.'__validate';
            if (method_exists($table_obj,$method))
            {
                $result = call_user_func_array(array($table_obj,$method),array($record,$record->FieldVal($field)));
                if ($result === false)
                {
                    return false;
                }
            }
    
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = array('s',$base_table,'s',$field);
            $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
            if (($row = mysqli_fetch_assoc($query_result)) && ($row['widget_type'] == 'file'))
            {
                $result = handle_file_widget_before_save($record,$field);
                if ($result === false)
                {
                    return false;
                }
                $new_mysql_fields[$field] = $record->FieldVal($field);
            }
        }
    
        // Run beforeSave method if available
        if (method_exists($table_obj,'beforeSave'))
        {
            $result = $table_obj->beforeSave($record);
            if ($result === false)
            {
                return false;
            }
        }
    }

    if (($action == 'edit') || ($action == 'update'))
    {
        // Save the record
        $set_fields = '';
        $set_values = array();
        foreach ($new_mysql_fields AS $field => $value)
        {
            $set_fields .= "$field,";
            $set_values = (isset($field_is_null[$field]))
                ? array_merge($set_values,(array('n',null)))
                : array_merge($set_values,(array($record->FieldType($field),$value)));
        }
        $set_fields = rtrim($set_fields,',');
        $where_clause = '';
        $where_values = array();
        foreach ($old_primary_keys as $field => $value)
        {
            $where_clause .= " $field=? AND ";
            $where_values[count($where_values)] = $record->FieldType($field);
            $where_values[count($where_values)] = $value;
        }
        $where_clause = rtrim($where_clause,' AND');
        $main_query_result = mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);
    
        if (isset($_POST['replicate_changes']))
        {
            // Replicate change to online site
            $db2 = online_db_connect();
            mysqli_query_normal($db2,$query);
        }
    }
    elseif (($action == 'new') || ($action == 'copy'))
    {
        // Insert the record
        $fields = '';
        $values = array();
        foreach ($new_mysql_fields as $field => $value)
        {
            $fields .= "$field,";
            $values = (isset($field_is_null[$field]))
                ? array_merge($values,(array('n',null)))
                : array_merge($values,(array($record->FieldType($field),$value)));
        }
        $fields = rtrim($fields,',');
        $main_query_result = mysqli_insert_query($db,$table,$fields,$values);
    }

    // Update any auto-increment fields in the record object to reflect the
    // newly assigned value.
    $where_clause = "table_name=? AND is_primary=1 AND widget_type='auto-increment'";
    $where_values = array('s',$base_table);
    $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['field_name'];
        $record->SetField($field_name,$new_primary_keys[$field_name],query_field_type($db,$table,$field_name));
    }

    // Update auto sequence number if applicable
    $where_clause = 'table_name=?';
    $where_values = array('s',$base_table);
    if (($row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,''))) &&
        (!empty($row['seq_no_field'])) && ($seq_no = $record->FieldVal($row['seq_no_field'])) && ($seq_no == NEXT_SEQ_NO_INDICATOR))
    {
        $where_values = array('s',$row['seq_no_field']);
        if (($row2 = mysqli_fetch_assoc(mysqli_free_format_query($db,"SHOW COLUMNS FROM $base_table WHERE Field=?",$where_values))) &&
        ($row2['Default'] == NEXT_SEQ_NO_INDICATOR))
        {
            $sort_1_value = $record->FieldVal($row['sort_1_field']);
            $seq_no = update_seq_number($base_table,$sort_1_value,$seq_no);
            $record->SetField($row['seq_no_field'],$seq_no,query_field_type($db,$table,$row['seq_no_field']));
            $primary_keys = fully_decode_record_id($new_record_id);
            $primary_keys[$row['seq_no_field']] = $seq_no;
            $new_record_id = encode_record_id($primary_keys);
        }
    }

    // Run the after save function for any file widgets
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] == 'file'))
        {
            $result = handle_file_widget_after_save($record,$field_name);
            if ($result === false)
            {
                return false;
            }
        }
    }
    /*
    Save the new record ID as a session variable. This may be overridden
    by an action in the afterSave function, thus allowing an updated record ID
    to be used should a primary key field be updated by afterSave.
    */
    update_session_var('saved_record_id',$new_record_id);

    // Run afterSave method if available
    if  (class_exists ($classname,false))
    {
        if (method_exists($table_obj,'afterSave'))
        {
            $table_obj->afterSave($record);
        }
    }
    /*
    Run any update queries specified in the relationship records for the
    associated table, but only where an existing record is being updated.
    */
    run_relationship_update_queries($record);

    if (isset($main_query_result))
    {
        return $main_query_result;
    }
    else
    {
        return true;
    }
}

//==============================================================================
/*
Function handle_record

This function performs the main handling of a record screen during an 'edit',
'new' or 'update operation'.

It basically operates on the table/view itself, though it refers to the
associated base table for widget information.
*/
//==============================================================================

function handle_record($action,$params)
{
    global $base_url, $base_dir, $db_admin_url, $relative_path, $location, $presets;
    global $select_this_record;
    $db = admin_db_connect();
    $mode = get_viewing_mode();

    // Interpret the URL parameters
    if (isset($_GET['-table']))
    {
        $table = $_GET['-table'];
    }
    else
    {
        print("<p>No table parameter specified.</p>\n");
        return;
    }
    $base_table = get_base_table($table);

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
        print("<p>No record ID parameter specified.</p>\n");
        return;
    }

    $presets = array();
    if (isset($params['presets']))
    {
        $presets = fully_decode_record_id($params['presets']);
    }

    // Create query to select this record (used more than once)
    $select_this_record = array('where_clause' => '', 'where_values' => array());
    $primary_keys = decode_record_id($record_id);
    foreach ($primary_keys as $field => $value)
    {
        $select_this_record['where_clause'] .= "$field=? AND ";
        $select_this_record['where_values'][count($select_this_record['where_values'])] = query_field_type($db,$table,$field);
        $select_this_record['where_values'][count($select_this_record['where_values'])] = $value;
    }
    $select_this_record['where_clause'] = rtrim($select_this_record['where_clause'],'AND ');

    // Determine the access level for the table
    $access_level = get_table_access_level($table);

    /*
    Output any success/error message from a save operation.
    Clear the $_SERVER['get_vars'] array in the event of success, but leave it
    intact in the event of a failure as it may contain information that needs to
    be carried through to the completion of the operation.
    */
    if (isset($_GET['-saveresult']))
    {
        if ($_GET['-saveresult'] == 1)
        {
            print("<p class=\"highlight-success\">Record successfully saved</p>\n");
            if (!empty(get_session_var('save_info')))
            {
                print("<p>".get_session_var('save_info')."</p>\n");
                delete_session_var('save_info');
            }
        }
        else
        {
            print("<p class=\"highlight-error\">".get_session_var('error_message')."</p>\n");
            delete_session_var('error_message');
        }
    }

    // Output top navigation
    if ($access_level == 'full')
    {
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=new&-table=$table\">New&nbsp;Record</a></div>");
    }
    print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-table=$table\">Show&nbsp;All</a></div>");
    previous_record_link($table,$record_id);
    next_record_link($table,$record_id);
    if (isset($_GET['-returnurl']))
    {
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"{$_GET['-returnurl']}\">Go&nbsp;Back</a></div>");
    }
    elseif (session_var_is_set(array('get_vars','-returnurl')))
    {
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"".get_session_var(array('get_vars','-returnurl'))."\">Go&nbsp;Back</a></div>");
    }
    else
    {
        $return_url = cur_url_par();
    }
    if (isset($params['additional_links']))
    {
        print($params['additional_links']);
    }
    print("<div style=\"clear:both\">&nbsp;</div>\n");

    $param_list = "-action=$action&-table=$table&-recordid=";
    $param_list .= urlencode($record_id);
    $param_list .= "&-basedir=";
    $param_list .= urlencode($base_dir);
    $param_list .= "&-relpath=";
    $param_list .= urlencode($relative_path);
    if (isset($_GET['-returnurl']))
    {
        $param_list .= "&-returnurl=";
        $param_list .= urlencode($_GET['-returnurl']);
    }
    elseif (session_var_is_set(array('get_vars','-returnurl')))
    {
        $param_list .= "&-returnurl=";
        $param_list .= urlencode(get_session_var(array('get_vars','-returnurl')));
    }
    print("<form method=\"post\" action=\"$db_admin_url/record_action.php?$param_list\" enctype=\"multipart/form-data\">\n");
    $last_display_group = '';

    // Check that the record exists unless the action is set to 'new'.
    // Check the action first as the query result will not be valid in the event
    // of action being set to 'new'.
    $query_result = mysqli_select_query($db,$table,'*',$select_this_record['where_clause'],$select_this_record['where_values'],'');
    if (($action == 'new') || ($row = mysqli_fetch_assoc($query_result)))
    {
        // Main loop for processing record fields
        $query_result2 = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $field_name = $row2['Field'];
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = array('s',$base_table,'s',$field_name);
            $query_result3 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
            if ($row3 = mysqli_fetch_assoc($query_result3))
            {
                // Process new display group if required
                $display_group = $row3['display_group'];
                if ($display_group != $last_display_group)
                {
                    if (!empty($last_display_group))
                    {
                        if ($mode == 'desktop')
                        {
                            print("</table>\n");
                        }
                        if ($display_group != '-default-')
                        {
                            print("<strong>$display_group</strong>\n");
                        }
                    }
                    $last_display_group = $display_group;
                    if ($mode == 'desktop')
                    {
                        print("<table class=\"table-record\">\n");
                    }
                }
        
                $label = field_label($table,$field_name);
                $description = $row3['description'];
                if (substr($description,0,1) == '@')
                {
                    $linked_field = strtok(substr($description,1)," \n");
                    $where_clause = 'table_name=? AND field_name=?';
                    $where_values = array('s',$base_table,'s',$linked_field);
                    if ($row4 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'')))
                    {
                        // Copy description from other field
                        $description = str_replace("@$linked_field",$row4['description'],$description);
                    }
                }
                $where_clause = 'table_name=?';
                $where_values = array('s',$base_table);
                if ((defined('NEXT_SEQ_NO_INDICATOR')) &&
                    ($row2['Default'] == NEXT_SEQ_NO_INDICATOR) &&
                    ($row4 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,''))) &&
                    ($row4['seq_no_field'] == $field_name))
                {
                    if (!empty($description))
                    {
                        $description .= "<br />";
                    }
                    $description .= NEXT_SEQ_NO_INDICATOR . " = Save automatically with next number in sequence.";
                }
        
                // Create the URL to edit the field attributes in table dba_table_fields
                if ($table == 'dba_table_fields')
                {
                    $edit_field_atts_url = '';
                }
                else
                {
                    $temp_pk = array();
                    $temp_table = get_table_for_field($table,$field_name);
                    $temp_pk['table_name'] = $temp_table;
                    $temp_pk['field_name'] = $field_name;
                    $edit_field_atts_url = "$base_url/$relative_path/?-action=edit&-table=dba_table_fields&-recordid=".encode_record_id($temp_pk);
                    if (isset($return_url))
                    {
                        $edit_field_atts_url .= "&-returnurl=$return_url";
                    }
                }
        
                // Select the CSS class for use with the field label
                if (($row3['required'] == 2) && ($row3['widget_type'] != 'checkbox') && ($row3['widget_type'] != 'static'))
                {
                    $class = 'required';
                }
                else
                {
                    $class = 'not-required';
                }
        
                /*
                Determine the value to be placed into the field. This will be set to
                the first of the following which applies (list checked in order):-
                1. The value from the record following a successful record save.
                2. The associated $_POST variable following an unsuccessful save attempt.
                    (passed via the $_SESSION['post_vars'] array.)
                3. A blank/default value. Any presets passed in $_GET['-presets'] will be
                    applied.
                */
                if (isset($_GET['-saveresult']))
                {
                    $widget_type = $row3['widget_type'];
                    if ($_GET['-saveresult'] == 1)
                    {
                        /*
                        Condition indicates that record has been successfully saved.
                        The query to select the record (see above) should therefore
                        have executed successfully with a result.
                        */
                        $value = $row[$field_name];
                    }
                    elseif ($widget_type == 'checkbox')
                    {
                        $value = (session_var_is_set(array('post_vars',"field_$field_name")))
                            ? 1
                            : 0;
                    }
                    else
                    {
                        $value = get_session_var(array('post_vars',"field_$field_name"));
                        if (empty($value))
                        {
                            $value = (query_field_type($db,$base_table,$field_name) == 's')
                                ? ''
                                : 0;
                        }
                    }
                }
                elseif (($action == 'edit') || ($action == 'update'))
                {
                    $value = $row[$field_name];
                }
                elseif (($action == 'new') && (isset($presets[$field_name])))
                {
                    $value = $presets[$field_name];
                }
                else
                {
                    // Action = 'new'
                    $value = false;
                }
            }
    
            switch ($action)
            {
                case 'edit':
                case 'update':
                    if ($access_level != 'read-only')
                    {
                        if ($mode == 'desktop')
                        {
                            print("<tr><td><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></td>\n");
                            print("<td>");
                        }
                        else
                        {
                            print("<div class=\"edit-field\"><div class=\"edit-field-cell edit-field-name\"><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></div>\n");
                            print("<div class=\"edit-field-cell edit-field-value\">");
                        }
                        generate_widget($table,$field_name,$value);
                        if (!empty($description))
                        {
                            print("<p class=\"field-description\">$description</p>");
                        }
                        if ($mode == 'desktop')
                        {
                            print("</td></tr>\n");
                        }
                        else
                        {
                            print("</div></div>\n");
                        }
                        break;
                    }
                // Drop down to next case if access level is read-only.
        
                case 'view':
                    if ($mode == 'desktop')
                    {
                        print("<tr><td>$label</td>\n");
                        print("<td>{$row[$field_name]}</td></tr>\n");
                    }
                    else
                    {
                        print("<div class=\"edit-field\"><div class=\"edit-field-cell edit-field-name\">$label</div>\n");
                        print("<div class=\"edit-field-cell edit-field-value\">{$row[$field_name]}</div></div>\n");
                    }
                    break;
        
                case 'new':
                    if ($access_level == 'full')
                    {
                        if ($mode == 'desktop')
                        {
                            print("<tr><td><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></td>\n");
                            print("<td>");
                        }
                        else
                        {
                            print("<div class=\"edit-field\"><div class=\"edit-field-cell edit-field-name\"><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></div>\n");
                            print("<div class=\"edit-field-cell edit-field-value\">");
                        }
                        generate_widget($table,$field_name,$value);
                        if (!empty($description))
                        {
                            print("<p class=\"field-description\">$description</p>");
                        }
                        if ($mode == 'desktop')
                        {
                            print("</td></tr>\n");
                        }
                        else
                        {
                            print("</div></div>\n");
                        }
                    }
                    else
                    {
                        print("<p>Record insertion not enabled in this context</p>\n");
                    }
                    break;
            }
        }
        if ($mode == 'desktop')
        {
            print("</table>\n");
        }
        if ($access_level != 'read-only')
        {
            if ($_GET['-action'] == 'edit')
            {
                // Generate 'save as new record' selector
                print("<input type=\"checkbox\" name =\"save_as_new\">&nbsp;Save as new record\n");
                print("<div class=\"halfspace\">&nbsp;</div>");
        
                // Generate 'replicate changes' selector if required conditions are met
                $where_clause = 'table_name=?';
                $where_values = array('s',$base_table);
                if (($location == 'local') &&
                    (function_exists('online_db_connect')) &&
                    ($row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,''))) &&
                    ($row['replicate_enabled']))
                {
                    // Check that corresponding online record exists
                    $db2 = online_db_connect();
                    if (($db2) && (mysqli_num_rows(mysqli_select_query($db2,$table,'*',$select_this_record['where_clause'],$select_this_record['where_values'],'')) > 0))
                    {
                        print("<input type=\"checkbox\" name =\"replicate_changes\">&nbsp;Replicate changes\n");
                        print("<div class=\"halfspace\">&nbsp;</div>");
                    }
                }
            }
            print("<input type=\"Submit\" value =\"Save\">\n");
            print("<input type=\"hidden\" name=\"submitted\"/>\n");
        }
        print("</form>\n");
    }
    else
    {
        print("<p>Record not found.</p>\n");
    }
    if (session_var_is_set('get_vars'))
    {
        delete_session_var('get_vars');
    }
    if (session_var_is_set('post_vars'))
    {
        delete_session_var('post_vars');
    }
}

//==============================================================================
/*
Function delete_record

N.B. Where there is a file widget, the associated file is not deleted by default
by this function. If deletion is required then this must be done in the
afterDelete method for the associated table class.
*/
//==============================================================================

function delete_record($record,$record_id)
{
    global $custom_pages_path, $relative_path, $alt_include_path, $db_admin_dir;
    $db = admin_db_connect();
    $table = $record->table;
    $base_table = get_base_table($table);
    $classname = "tables_$base_table";

    // May need to include table class here because it is not automatically
    // loaded in all contexts.
    if (!class_exists ($classname,false))
    {
        if (is_file("$custom_pages_path/$relative_path/tables/$table/$table.php"))
        {
            require("$custom_pages_path/$relative_path/tables/$table/$table.php");
        }
        elseif (is_file("$alt_include_path/tables/$base_table/$table.php"))
        {
            require("$alt_include_path/tables/$base_table/$table.php");
        }
    }

    if  (class_exists ($classname,false))
    {
        $table_obj = new $classname;
    
        // Run beforeDelete method if available
        if (method_exists($table_obj,'beforeDelete'))
        {
            $result = $table_obj->beforeDelete($record);
            if ($result === false)
            {
                return false;
            }
        }
    
        // Delete the record
        $where_clause = '';
        $where_values = array();
        $primary_keys = fully_decode_record_id($record_id);
        foreach ($primary_keys as $field => $value)
        {
            $where_clause .= "$field=? AND ";
            $where_values[count($where_values)] = $record->FieldType($field);
            $where_values[count($where_values)] = $record->FieldVal($field);
        }
        $where_clause = rtrim($where_clause,' AND');
        mysqli_delete_query($db,$table,$where_clause,$where_values);
    
        if  (class_exists ($classname,false))
        {
            // Run afterDelete method if available
            if (method_exists($table_obj,'afterDelete'))
            {
                $result = $table_obj->afterDelete($record);
            }
        }
    }
    /*
    Run any delete or delete/update queries specified in the relationship
    records for the associatedtable.
    */
    run_relationship_delete_queries($record);
    return true;
}

//==============================================================================
/*
Function delete_record_on_save

This function is intended solely for use in calling from the afterSave method
of a given table. The 'saved_record_id' session variable will always have been
set in this context.
*/
//==============================================================================

function delete_record_on_save($record)
{
    delete_record($record,get_session_var('saved_record_id'));
}

//==============================================================================
/*
Function load_return_url
*/
//==============================================================================

function load_return_url()
{
    if (!headers_sent())
    {
        if (isset($_GET['-returnurl']))
        {
            header("Location: {$_GET['-returnurl']}");
            exit;
        }
        elseif (session_var_is_set(array('get_vars','-returnurl')))
        {
            header("Location: ".get_session_var(array('get_vars','-returnurl')));
            exit;
        }
    }
}

//==============================================================================

function pre_change_snapshot($record)
{
    global $pre_change_snapshot_fields;
    $pre_change_snapshot_fields = array();
    $db = admin_db_connect();
    $table = get_base_table($record->table);
    $action = $record->action;
    if ($action == 'update')
    {
        $action = 'edit';
    }
    elseif ($action == 'copy')
    {
        $action = 'new';
    }
    $pre_change_snapshot_fields['-table'] = $table;
    $pre_change_snapshot_fields['-action'] = $action;
    $pre_change_snapshot_fields['-recordid'] = '^';

    if(($action == 'edit') || ($action == 'delete'))
    {
        // Build query to select old record
        $where_clause1 = 'table_name=? AND is_primary=1';
        $where_values1 = array('s',$table);
        $where_clause2 = '';
        $where_values2 = array();
        $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause1,$where_values1,'');
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $field_name = $row['field_name'];
            if ($action == 'edit')
            {
                $field_value = $record->OldPKVal($field_name);
            }
            else
            {
                $field_value = $record->FieldVal($field_name);
            }
            $where_clause2 .= "$field_name=? AND ";
            $where_values2[count($where_values2)] = query_field_type($db,$table,$field_name);
            $where_values2[count($where_values2)] = $field_value;
        }
        $where_clause2 = rtrim($where_clause2,' AND');
        if ($row = mysqli_fetch_assoc(mysqli_select_query($db,$table,'*',$where_clause2,$where_values2,'')))
        {
            // Add record field details to the snapshot array and build the
            // record ID string.
            $where_clause = 'table_name=?';
            $where_values = array('s',$table);
            $add_clause = 'ORDER BY display_order ASC';
            $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $pre_change_snapshot_fields[$row2['field_name']] = $row[$row2['field_name']];
                if ($row2['is_primary'])
                {
                    $pre_change_snapshot_fields['-recordid'] .= "{$pre_change_snapshot_fields[$row2['field_name']]}^";
                }
            }
        }
    }
}

//==============================================================================

function post_change_snapshot($record)
{
    global $pre_change_snapshot_fields;
    $post_change_snapshot_fields = array();
    $db = admin_db_connect();
    $table = get_base_table($record->table);
    $action = ucwords($record->action);
    if ($action == 'Update')
    {
        $action = 'Edit';
    }
    elseif ($action == 'Copy')
    {
        $action = 'New';
    }
    $record_id = '^';
    $record_changed = false;
    if (($action == 'New') || ($action == 'Edit'))
    {
        // Add record field details to the snapshot array, check for changes on an
        // edit and build the record ID string.
        $where_clause = 'table_name=?';
        $where_values = array('s',$table);
        $add_clause = 'ORDER BY display_order ASC';
        $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $post_change_snapshot_fields[$row['field_name']] = $record->FieldVal($row['field_name']);
            if (($action == 'Edit') && ($post_change_snapshot_fields[$row['field_name']] != $pre_change_snapshot_fields[$row['field_name']]))
            {
                $record_changed = true;
            }
            if ($row['is_primary'])
            {
                $record_id .= "{$post_change_snapshot_fields[$row['field_name']]}^";
            }
        }
    }

    if (($record_changed) || ($action != 'Edit'))
    {
        $details = '';
        if ($action != 'Delete')
        {
            // Format details of new/changed fields
            $details .= "<style>strong {color:steelblue;}</style>\n";
            $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
            while ($row = mysqli_fetch_assoc($query_result))
            {
                if ($action == 'New')
                {
                    $details .= "Field: <strong>{$row['Field']}</strong><br />\n";
                    $details .= "Value: {$post_change_snapshot_fields[$row['Field']]}<br />\n";
                }
                elseif (($action == 'Edit') && ($post_change_snapshot_fields[$row['Field']] != $pre_change_snapshot_fields[$row['Field']]))
                {
                    $details .= "Field: <strong>{$row['Field']}</strong><br />\n";
                    $details .= "Old: {$pre_change_snapshot_fields[$row['Field']]}<br />\n";
                    $details .= "New: {$post_change_snapshot_fields[$row['Field']]}<br />\n";
                }
            }
        }
    
        // Add record to change log
        $date_and_time = date('Y-m-d H:i:s');
        if ($action == 'Delete')
        {
            $record_id = $pre_change_snapshot_fields['-recordid'];
        }
        $fields = 'date_and_time,table_name,action,record_id,details';
        $values = array('s',$date_and_time,'s',$table,'s',$action,'s',$record_id,'s',$details);
        mysqli_insert_query($db,'dba_change_log',$fields,$values);
    }
}

//==============================================================================
endif;
//==============================================================================
?>
