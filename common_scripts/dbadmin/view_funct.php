<?php
//==============================================================================
if (!function_exists('format_view_name')) :
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
Function set_temp_view_name
*/
//==============================================================================

function set_temp_view_name()
{
    if ((!session_var_is_set('TEMP_VIEW')) || (empty(get_session_var('TEMP_VIEW'))))
    {
        $temp_str1 = str_replace('.','_',$_SERVER['REMOTE_ADDR']);
        $temp_str2 = date('His');
        update_session_var('TEMP_VIEW',"_view_temp_$temp_str1"."_$temp_str2");
    }
}

//==============================================================================
/*
Function create_view_structure
*/
//==============================================================================

function create_view_structure($view,$table,$conditions)
{
    global $custom_pages_path, $relative_path, $alt_include_path;
    $db = admin_db_connect();

    // Create the view and drop any old tables/views that may conflict with the
    // current setup.
    mysqli_query_normal($db,"CREATE OR REPLACE VIEW $view AS SELECT * FROM $table WHERE $conditions");
    if (substr($view,0,6) == '_view_')
    {
        mysqli_query_normal($db,"DROP TABLE IF EXISTS $view");
        $old_view = substr($view,6);
        mysqli_query_normal($db,"DROP VIEW IF EXISTS $old_view");
    }

    // Add new class definition and symbolic link to directory if the directory
    // for the table already exists.
    if (!is_dir("$custom_pages_path/$relative_path/tables/$table"))
    {
        mkdir("$custom_pages_path/$relative_path/tables/$table",0755);
    }
    $file = "$custom_pages_path/$relative_path/tables/$table/$view.php";
    if (!is_file($file))
    {
        $ofp = fopen($file,'w');
        fprintf($ofp,"<?php\n");
        if (is_file("$custom_pages_path/$relative_path/tables/$table/$table.php"))
        {
            fprintf($ofp,"include(\"\$custom_pages_path/\$relative_path/tables/$table/$table.php\");\n");
        }
        elseif (is_file("$alt_include_path/tables/$table/$table.php"))
        {
            fprintf($ofp,"include(\"\$alt_include_path/tables/$table/$table.php\");\n");
        }
        fprintf($ofp,"class tables_$view extends tables_$table {}\n");
        fprintf($ofp,"?>\n");
        fclose($ofp);
    }
    $link = "$custom_pages_path/$relative_path/tables/$view";
    if (!is_link($link))
    {
        symlink("$custom_pages_path/$relative_path/tables/$table","$link");
    }

    // Set the parent table in the table info record for the view
    $where_clause = 'table_name=?';
    $where_values = ['s',$view];
    $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
    if (mysqli_num_rows($query_result) == 0)
    {
        // New table info record
        $fields = 'table_name,parent_table';
        $values = ['s',$view,'s',$table];
        mysqli_insert_query($db,'dba_table_info',$fields,$values);
        $where_clause = 'table_name=?';
        $where_values = ['s',$table];
        $query_result2 = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            /*
            Copy the access level fields from the table into the view. Only do this for a
            new table info record, thus allowing a view to be subsequently altered from
            the parent table should that ever be required.
            */
            $set_fields = 'local_access';
            $set_values = ['s',$row2['local_access']];
            $where_clause = 'table_name=?';
            $where_values = ['s',$view];
            mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
            $set_fields = 'real_access';
            $set_values = ['s',$row2['real_access']];
            $where_clause = 'table_name=?';
            $where_values = ['s',$view];
            mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
    else
    {
        // Update existing table info record
        $set_fields = 'parent_table';
        $set_values = ['s',$table];
        $where_clause = 'table_name=?';
        $where_values = ['s',$view];
        mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
/*
Function delete_view_structure
*/
//==============================================================================

function delete_view_structure($view,$table)
{
    global $custom_pages_path, $relative_path;
    $db = admin_db_connect();

    mysqli_query_normal($db,"DROP VIEW IF EXISTS $view");
    $file = "$custom_pages_path/$relative_path/tables/$table/$view.php";
    if (is_file($file))
    {
        unlink($file);
    }
    $link = "$custom_pages_path/$relative_path/tables/$view";
    if (is_link($link))
    {
        unlink($link);
    }
    $where_clause = 'table_name=? AND parent_table=?';
    $where_values = ['s',$view,'s',$table];
    mysqli_delete_query($db,'dba_table_info',$where_clause,$where_values);
}

//==============================================================================
/*
Function create_child_table_structure
*/
//==============================================================================

function create_child_table_structure($child,$parent)
{
    global $custom_pages_path, $relative_path;
    $db = admin_db_connect();

    mysqli_query_normal($db,"CREATE TABLE IF NOT EXISTS $child LIKE $parent");
    $file = "$custom_pages_path/$relative_path/tables/$parent/$child.php";
    if (!is_file($file))
    {
        $ofp = fopen($file,'w');
        fprintf($ofp,"<?php\n");
        fprintf($ofp,"include(\"\$custom_pages_path/\$relative_path/tables/$parent/$parent.php\");\n");
        fprintf($ofp,"class tables_$child extends tables_$parent {}\n");
        fprintf($ofp,"?>\n");
        fclose($ofp);
    }
    $link = "$custom_pages_path/$relative_path/tables/$child";
    if (!is_link($link))
    {
        symlink("$custom_pages_path/$relative_path/tables/$parent","$link");
    }

    // Set the parent table in the table info record for the child table
    $where_clause = 'table_name=?';
    $where_values = ['s',$child];
    $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
    if (mysqli_num_rows($query_result) == 0)
    {
        // New table info record
        $fields = 'table_name,parent_table';
        $values = ['s',$child,'s',$parent];
        mysqli_insert_query($db,'dba_table_info',$fields,$values);
        $where_clause = 'table_name=?';
        $where_values = ['s',$parent];
        $query_result2 = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            /*
            Copy the access level fields from the table into the child table. Only do
            this for a new table info record, thus allowing a child table to be
            subsequently altered from  the parent table should that ever be required.
            */
            $set_fields = 'local_access';
            $set_values = ['s',$row2['local_access']];
            $where_clause = 'table_name=?';
            $where_values = ['s',$child];
            mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
            $set_fields = 'real_access';
            $set_values = ['s',$row2['real_access']];
            $where_clause = 'table_name=?';
            $where_values = ['s',$child];
            mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
        }
    }
    else
    {
        // Update existing table info record
        $set_fields = 'parent_table';
        $set_values = ['s',$parent];
        $where_clause = 'table_name=?';
        $where_values = ['s',$child];
        mysqli_update_query($db,'dba_table_info',$set_fields,$set_values,$where_clause,$where_values);
    }
}

//==============================================================================
/*
Function delete_child_table_structure
*/
//==============================================================================

function delete_child_table_structure($child,$parent)
{
    global $custom_pages_path, $relative_path;
    $db = admin_db_connect();

    mysqli_query_normal($db,"DROP TABLE $child");
    $file = "$custom_pages_path/$relative_path/tables/$parent/$child.php";
    if (is_file($file))
    {
        unlink($file);
    }
    $link = "$custom_pages_path/$relative_path/tables/$child";
    if (is_link($link))
    {
        unlink($link);
    }
    $where_clause = 'table_name=? AND parent_table=?';
    $where_values = ['s',$child,'s',$parent];
    mysqli_delete_query($db,'dba_table_info',$where_clause,$where_values);
}

//==============================================================================
/*
Function set_primary_key_on_view
*/
//==============================================================================

function set_primary_key_on_view($table,$field)
{
    $db = admin_db_connect();
    $set_fields = 'is_primary,required';
    $set_values = ['i',1,'i',2];
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = ['s',$table,'s',$field];
    mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
}

//==============================================================================
/*
Function clear_primary_key_on_view
*/
//==============================================================================

function clear_primary_key_on_view($table,$field)
{
    $db = admin_db_connect();
    $set_fields = 'is_primary,required';
    $set_values = ['i',0,'i',0];
    $where_clause = 'table_name=? AND field_name=?';
    $where_values = ['s',$table,'s',$field];
    mysqli_update_query($db,'dba_table_fields',$set_fields,$set_values,$where_clause,$where_values);
}

//==============================================================================
endif;
//==============================================================================
?>
