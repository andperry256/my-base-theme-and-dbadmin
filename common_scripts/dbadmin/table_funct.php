<?php
//==============================================================================
if (!function_exists('get_base_table')) :
//==============================================================================

/*
The first three functions are responsible for scanning through the table
hierarchy from a given table/view back to its base table. Typically there
would only be a single iteration or at the very most two, but the definition
of MAX_TABLE_NESTING_LEVEL provides a 'safety net' to prevent these functions
from running into an infinte look in the case of a data error.
*/
if (!defined('MAX_TABLE_NESTING_LEVEL'))
{
    define('MAX_TABLE_NESTING_LEVEL',5);
}

//==============================================================================
/*
Function get_base_table

This function scans the table hierarchy for a given table/view through to its
origin (i.e. the base table)
*/
//==============================================================================

function get_base_table($table,$db=false)
{
    if ($db === false)
    {
        $db = admin_db_connect();
    }
    for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
    {
        $where_clause = 'table_name=?';
        $where_values = array('s',$table);
        $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            if (empty($row['parent_table']))
            {
                return $table;
            }
            else
            {
                $table = $row['parent_table'];
            }
        }
        else
        {
            return $table;
        }
    }
    return false;  // This should not occur
}

//==============================================================================
/*
Function get_table_for_field

This function scans the table hierarchy from the given table back to the base
table until it finds a record for a given table field in the table fields table.
*/
//==============================================================================

function get_table_for_field($table,$field,$db=false)
{
    if ($db === false)
    {
        $db = admin_db_connect();
    }
    for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
    {
        $where_clause = 'table_name=?';
        $where_values = array('s',$table);
        $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = array('s',$table,'s',$field);
            $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
            if ((mysqli_num_rows($query_result2) > 0) || (empty($row['parent_table'])))
            {
                return $table;
            }
            else
            {
                $table = $row['parent_table'];
            }
        }
    }
    return false;  // This should not occur
}

//==============================================================================
/*
Function get_table_for_info_field

This function scans the table hierarchy from the given table back to the base
table until it finds non-empty value for a given table info field.
*/
//==============================================================================

function get_table_for_info_field($table,$info_field,$db=false)
{
    if ($db === false)
    {
        $db = admin_db_connect();
    }
    for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
    {
        $where_clause = 'table_name=?';
        $where_values = array('s',$table);
        $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            if ((empty($row['parent_table'])) || (!empty($row[$info_field])))
            {
                return $table;
            }
            else
            {
                $table = $row['parent_table'];
            }
        }
    }
    return false;  // This should not occur
}

//==============================================================================
/*
Function page_link_url
*/
//==============================================================================

function page_link_url($page_no)
{
    global $base_url, $relative_path;
    global $page_url_table,$page_url_list_size;
    $page_offset = $page_url_list_size * ($page_no - 1);
    $url = "$base_url/$relative_path/?-table=$page_url_table&-startoffset=$page_offset&-listsize=$page_url_list_size";
    return $url;
}

//==============================================================================
/*
Function generate_grid_styles()
*/
//==============================================================================

function generate_grid_styles($table)
{
    $db = admin_db_connect();
    $grid_coords = array();
    $field_count = 0;
    $auto_count = 0;
    $result = '';
    $cells_used = array();

    $base_table = get_table_for_info_field($table,'grid_columns');
    $where_clause = 'table_name=?';
    $where_values = array('s',$base_table);
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'')))
    {
        $grid_columns = $row['grid_columns'];
    }

    // Loop through the fields for the given table
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $base_table = get_table_for_field($table,$field_name);
    
        // Extract and save the grid co-ordinates for the given field.
        $where_clause = 'table_name=? AND field_name=? AND list_mobile=1';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $grid_coords[$field_name] = $row2['grid_coords'];
            $field_count++;
            if ($grid_coords[$field_name] == 'auto')
            {
                // Co-ordinates set to 'auto' - update the count
                $auto_count++;
            }
            else
            {
                // Custom co-ordinates in use - check that they are valid.
                $row_no = trim(strtok($grid_coords[$field_name],'/'));
                if (!is_numeric($row_no))
                {
                    return false;
                }
                else
                {
                    $col_no = trim(strtok('/'));
                    if (!is_numeric($col_no))
                    {
                        $col_no = 2;
                        $col_span = 1;
                    }
                    elseif ($col_no == 1)
                    {
                        return false;
                    }
                    else
                    {
                        $col_span = trim(strtok('/'));
                        if (!is_numeric($col_span))
                        {
                            $col_span = 1;
                        }
                    }
                }
        
                // Check that fields don't overlap in the layout.
                $end_col = $col_no + $col_span;
                if (!isset($cells_used[$row_no]))
                {
                    $cells_used[$row_no] = array();
                }
                for ($i=$col_no; $i<$end_col; $i++)
                {
                    if (isset($cells_used[$row_no][$i]))
                    {
                        return false;
                    }
                    else
                    {
                        $cells_used[$row_no][$i] = true;
                    }
                }
            }
        }
    }
    $base_table = get_base_table($table);

    if ($auto_count == $field_count)
    {
        /*
        Auto co-ordinates in use. Create styles to place the record fields one per
        row in the grid. The cell containing the select box goes into column 1
        spanning all the rows. The cell containing relationship links goes in a row
        at the bottomn spanning both columns.
        */
        $result .= "<style>\n";
        if (isset($grid_columns))
        {
            $result .= "div.table-listing {\n";
            $result .= "  grid-template-columns: $grid_columns\n";
            $result .= "}\n";
        }
        $row_no = 1;
        $where_clause = 'table_name=?';
        $where_values = array('s',$base_table);
        $add_clause = 'ORDER BY display_order ASC';
        $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            if (isset($grid_coords[$row['field_name']]))
            {
                $result .= ".field-{$row['field_name']} {\n";
                $result .= "  grid-row: $row_no;\n";
                $result .= "  grid-column: 2;\n";
                $result .= "}\n";
                $row_no++;
            }
        }
        $result .= ".record-select {\n";
        $result .= "  grid-row: 1 / $row_no;\n";
        $result .= "  grid-column: 1;\n";
        $result .= "}\n";
        $result .= ".relationships {\n";
        $result .= "  grid-row: $row_no;\n";
        $result .= "  grid-column: 1 / 3;\n";
        $result .= "}\n";
        $result .= "</style>\n";
        return $result;
    }
    elseif ($auto_count == 0)
    {
        /*
        Custom co-ordinates in use. Create styles to place each record field
        according to its own custom co-ordinates. The cell containing the select box
        goes into column 1 spanning all the rows. The cell containing relationship
        links goes in a row at the bottomn spanning all the columns.
        */
        $result .= "<style>\n";
        if (isset($grid_columns))
        {
            $result .= "div.table-listing {\n";
            $result .= "  grid-template-columns: $grid_columns\n";
            $result .= "}\n";
        }
        $row_count = 1;
        $col_count = 1;
        $where_clause = 'table_name=?';
        $where_values = array('s',$base_table);
        $add_clause = 'ORDER BY display_order ASC';
        $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            if (isset($grid_coords[$row['field_name']]))
            {
                $row_no = trim(strtok($grid_coords[$row['field_name']],'/'));
                $col_no = trim(strtok('/'));
                if (!is_numeric($col_no))
                {
                    $col_no = 2;
                    $col_span = 1;
                }
                else
                {
                    $col_span = trim(strtok('/'));
                    if (!is_numeric($col_span))
                    {
                        $col_span = 1;
                    }
                }
                if ($row_no > $row_count)
                {
                    $row_count = $row_no;
                }
                if ($col_no > $col_count)
                {
                    $col_count = $col_no;
                }
                $result .= ".field-{$row['field_name']} {\n";
                $result .= "  grid-row: $row_no;\n";
                $col_end = $col_no + $col_span;
                $result .= "  grid-column: $col_no / $col_end;\n";
                $result .= "}\n";
            }
        }
        $result .= ".record-select {\n";
        $row_end = $row_count + 1;
        $result .= "  grid-row: 1 / $row_end;\n";
        $result .= "  grid-column: 1;\n";
        $result .= "}\n";
        $result .= ".relationships {\n";
        $result .= "  grid-row: $row_no;\n";
        $col_end = $col_count + 1;
        $result .= "  grid-column: 1 / $col_end;\n";
        $result .= "}\n";
        $result .= "</style>\n";
        return $result;
    }
    else
    {
        // Mixture of custom and auto co-ordinates (invalid)
        return false;
    }
}

//==============================================================================
/*
Function combined_add_clause
*/
//==============================================================================

function combined_add_clause($where_par,$search_clause,$add_clause)
{
    if ((!empty($where_par)) && (!empty($search_clause)))
    {
        return "WHERE ($where_par) AND ($search_clause) $add_clause";
    }
    elseif ((empty($where_par)) && (empty($search_clause)))
    {
        return "$add_clause";
    }
    else
    {
        return "WHERE $where_par$search_clause $add_clause";
    }
}

//==============================================================================
/*
Function display_table
*/
//==============================================================================

function display_table($params)
{
    global $base_url, $relative_path, $relative_sub_path, $location;
    global $widget_types;
    global $db_admin_dir;
    global $display_table;
    global $page_url_table, $page_url_list_size;
    $db = admin_db_connect();
    $mode = get_viewing_mode();
    print("<style>\n".file_get_contents("$db_admin_dir/page_link_styles.css")."</style>\n");

    //============================================================================
    // Part 1 - Data Initialisation
    //============================================================================

    // Interpret the URL parameters
    if (!isset($_GET['-table']))
    {
        print("<p>Table parameter missing.</p>\n");
        return;
    }
    $table = $_GET['-table'];
    $base_table = get_base_table($table);
    $where_clause = "table_name=?";
    $where_values = array('s',$base_table);
    $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,'');
    if ($row = mysqli_fetch_assoc($query_result))
    {
        if (isset($_GET['-listsize']))
        {
            $list_size = $_GET['-listsize'];
        }
        elseif ((isset($_POST['listsize2'])) && ($_POST['listsize2'] > 0))
        {
            $list_size = $_POST['listsize2'];
        }
        else
        {
            $list_size = $row['list_size'];
        }
        if ($list_size < 30)
        {
            $list_size = 30;
        }
        if ($list_size > 800)
        {
            $list_size = 800;
        }
    }
    else
    {
        print("<p>Table record for <em>$base_table</em> not found.</p>\n");
        return;
    }
    if (((isset($_POST['listsize2'])) &&
        (($_POST['listsize2']) != ($_POST['listsize'])))
        || (isset($_GET['-reorder'])))
    {
        /*
        Force the start offset to 0 under any of the following conditions:-
        1. A new list size has been requested.
        2. The listing is being re-ordered by a given field.
        */
        $start_offset = 0;
    }
    elseif (isset($_GET['-startoffset']))
    {
        $start_offset = $_GET['-startoffset'];
    }
    elseif (isset($_POST['startoffset']))
    {
        $start_offset = $_POST['startoffset'];
    }
    else
    {
        $start_offset = 0;
    }

    /*
        Set the 'where' parameter. This is a selection filter that is initiated
        via a URL parameter, and is separate from the filter that is initiated
        via the search box. Once set, it has to be cleared by clicking on the 
        'Clear Filters' button that subsequently appears.
    */
    if (!empty($_GET['-where']))
    {
        $where_par = stripslashes($_GET['-where']);
    }
    elseif (!empty($_POST['-where']))
    {
        $where_par = stripslashes($_POST['-where']);
    }
    elseif (!empty(get_session_var("$relative_sub_path-$table-where-par")))
    {
        $where_par = get_session_var("$relative_sub_path-$table-where-par");
    }
    else
    {
        $where_par = '';
    }
    update_session_var("$relative_sub_path-$table-where-par",$where_par);
    
    // Initialise table filtering if not set
    if (get_session_var("$relative_sub_path-$table-is-filtered") === false)
    {
        update_session_var("$relative_sub_path-$table-is-filtered",true);
        update_session_var("$relative_sub_path-$table-sort-level",0);
        update_session_var("$relative_sub_path-$table-sort-clause",'');
        update_session_var("$relative_sub_path-$table-where-par",'');
        update_session_var("$relative_sub_path-$table-search-string",'');
        update_session_var("$relative_sub_path-$table-search-clause",'');
        update_session_var("$relative_sub_path-$table-show-relationships",false);
    }

    // Retrieve table filter data
    $sort_level = get_session_var("$relative_sub_path-$table-sort-level");
    $sort_clause = get_session_var("$relative_sub_path-$table-sort-clause");
    $field_sort_level = array();
    $field_sort_order = array();
    for ($i=1; $i<=$sort_level; $i++)
    {
        $field = get_session_var("$relative_sub_path-$table-sort-field-$i");
        $field_sort_level[$field] = $i;
        $field_sort_order[$field] = get_session_var("$relative_sub_path-$table-sort-order-$i");
    }
    $search_string = get_session_var("$relative_sub_path-$table-search-string");
    $search_clause = get_session_var("$relative_sub_path-$table-search-clause");
    $show_relationships = get_session_var("$relative_sub_path-$table-show-relationships");

    $display_table = true;
    $form_started = false;

    //============================================================================
    // Part 2 - Processing of Actions
    //============================================================================

    if (isset($_POST['submitted']))
    {
        // Not quite sure yet why we need this, but it seems to prevent problems
        // with multiple submissions.
        $submit_option = $_POST['submitted'];
        unset($_POST['submitted']);
    
        switch ($submit_option)
        {
            case 'delete':
                $result = delete_record_set($table);
                break;
    
            case 'select_update';
                $result = select_update($table,'selection');
                if ($result === true)
                {
                    $display_table = false;
                    $form_started = true;
                }
                break;
    
            case 'select_update_all';
                $result = select_update($table,'all');
                if ($result === true)
                {
                    $display_table = false;
                    $form_started = true;
                }
                break;
    
            case 'run_update';
                $result = run_update($table,'selection');
                break;
    
            case 'run_update_all';
                if (isset($_POST['confirm_update_all']))
                {
                    $result = run_update($table,'all');
                }
                else
                {
                    print("<p class=\"highlight-error\">'Update All' confirmation flag was not set - please try again.</p>\n");
                }
                break;
    
            case 'select_copy';
                $result = select_copy($table);
                if ($result === true)
                {
                    $display_table = false;
                    $form_started = true;
                }
                break;
    
            case 'run_copy';
                $result = run_copy($table);
                break;
        }
    }

    //============================================================================
    // Part 3 - Generation of Page
    //============================================================================

    if ($mode == 'mobile')
    {
        $result = generate_grid_styles($table);
        if ($result === false)
        {
            print("<p class=\"highlight-error\">ERROR - Cannot resolve the grid co-ordinates. Please check the table field definitions for possible errors.</p>\n");
            $display_table = false;
        }
        else
        {
            print($result);
        }
    }
    if (!$display_table)
    {
        print("<div style=\"display:none\">\n");
    }

    // Calculate pagination parameters
    $add_clause = combined_add_clause($where_par,$search_clause,'');
    $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
    $table_size = mysqli_num_rows($query_result);
    $page_count = ceil($table_size / $list_size);
    $current_page = floor($start_offset / $list_size +1);

    // Generate the page links
    $page_url_table = $table;
    $page_url_list_size = $list_size;
    $page_links = page_links($page_count,$current_page,4,'current-page-link','other-page-link','page_link_url');

    // Determine the access level for the table
    $access_level = get_table_access_level($table);

    print("<h2>Table $table</h2>");
    if (!$form_started)
    {
        /*
        The target URL of the post is to the same script for the same table. A post
        is only performed for update, copy and delete requests, for all of which the
        next stage is performed by a second iteration of the current script.
        */
        print("<form method=\"post\" action=\"$base_url/common_scripts/dbadmin/apply_table_search.php?sub_path=$relative_sub_path&table=$table\">\n");
        print("<div class=\"top-navigation-item\"><input type=\"text\" size=\"24\" name=\"search_string\"/>");
        print("&nbsp;<input type=\"button\" value=\"Search\" onClick=\"applySearch(this.form)\"/>");
        print("</div>\n");
        if (!empty($search_string))
        {
            print("<div class=\"search-string\">[$search_string]</div>\n</form>\n");
        }
        else
        {
            print("</form><br />\n");
        }
        print("<form method=\"post\" action=\"$base_url/$relative_path/?-table=$table\">\n");
    }

    // Ensure that certain parameters are propagated through any subsquent form submission.
    print("<input type=\"hidden\" name=\"startoffset\" value=\"$start_offset\"/>\n");
    print("<input type=\"hidden\" name=\"listsize\" value=\"$list_size\"/>\n");

    // Output top navigation
    print("<p class=\"small\">Found $table_size records");
    print("&nbsp;&nbsp;&nbsp;Showing&nbsp;<input class=\"small\" name=\"listsize2\" value=$list_size size=4>&nbsp;results&nbsp;per&nbsp;page");
    print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Apply\" onClick=\"submitForm(this.form)\"/></p>");
    print("<p>$page_links");
    $where_clause = "table_name=? AND UPPER(query) LIKE 'SELECT%'";
    $where_values = array('s',$base_table);
    $query_result = mysqli_select_query($db,'dba_relationships','*',$where_clause,$where_values,'');
    if (!empty($where_par))
    {
        print("<div class=\"top-navigation-item clear-filter-button\"><a class=\"admin-link\" href=\"$base_url/common_scripts/dbadmin/clear_where_filter.php?sub_path=$relative_sub_path&table=$table&option=$option\">Clear Filters</a></div>\n");
    }
    if (mysqli_num_rows($query_result) > 0)
    {
        // One or more select relationships are defined for the given table
        $option = ($show_relationships) ? 'Hide' : 'Show';
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/common_scripts/dbadmin/show_hide_relationships.php?sub_path=$relative_sub_path&table=$table&option=$option\">$option Relationships</a></div>\n");
    }
    print("</p>\n");
    if ($access_level == 'full')
    {
        print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-action=new&-table=$table\">New&nbsp;Record</a></div>\n");
    }
    print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/$relative_path/?-table=$table&-showall\">Show&nbsp;All</a></div>\n");
    print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$base_url/common_scripts/dbadmin/display_table.php?sub_path=$relative_sub_path&table=$table\" target=\"_blank\">Print</a></div>\n");
    if (isset($params['additional_links']))
    {
        print($params['additional_links']);
    }
    print("<div style=\"clear:both\">&nbsp;</div>\n");
    // End of top navigation

    // Determine fields to be processed.
    $fields = array();
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
    
        // Determine whether the field is to be displayed in the table listing.
        $tab = get_table_for_field($table,$field_name);
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$tab,'s',$field_name);
        $query_result3 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if ($row3 = mysqli_fetch_assoc($query_result3))
        {
            // Table field found
            if ($row3["list_$mode"] == 1)
            {
                $fields[$field_name] = $row3['display_order'];
            }
        }
    }

    // Construct array for primary key data
    $primary_key = array();
    $where_clause = 'table_name=? AND is_primary=1';
    $where_values = array('s',$base_table);
    $add_clause = 'ORDER BY display_order ASC';
    $query_result = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $primary_key[$row['field_name']] = '';
    }
    if (count($primary_key) == 0)
    {
        exit("ERROR - no primary key defined for table/view $base_table");
    }

    // Output table header
    if ($mode == 'desktop')
    {
        print("<table class=\"table-listing\">\n");
        print("<tr>\n");
        print("<td class=\"table-listing-header\"><input type=\"checkbox\" name=\"select_all\" onclick=\"checkAll(this)\"></td>");
    }
    else
    {
        print("<div class=\"table-listing\">\n");
        print("<div class=\"table-listing-cell record-select table-listing-header\"><input type=\"checkbox\" name=\"select_$record_offset\" onclick=\"checkAll(this)\"></div> <!-- .table-listing-cell -->");
    }
    foreach ($fields as $f => $ord)
    {
        // Output the field name with a sort link
        $sort_link = "$base_url/common_scripts/dbadmin/apply_table_sort.php?sub_path=$relative_sub_path&table=$table&field=$f";
        if ($mode == 'desktop')
        {
            print("<td class=\"table-listing-header\"><a href=\"$sort_link\">");
        }
        else
        {
            print("<div class=\"table-listing-cell field-$f table-listing-header\"><a href=\"$sort_link\">");
        }
        print(field_label($table,$f));
        if (isset($field_sort_level[$f]))
        {
            // Output details of sort
            print("<br /><span class=\"small-text\">");
            if ($sort_level > 1)
            {
                print("[{$field_sort_level[$f]}]");
            }
            print("[".strtolower($field_sort_order[$f])."]</span>");
        }
        if ($mode == 'desktop')
        {
            print("</a></td>");
        }
        else
        {
            print("</a></div <!-- .table-listing-cell -->");
        }
    }
    if ($mode == 'desktop')
    {
        print("\n</tr>\n");
    }
    else
    {
        print("\n</div> <!-- .table-listing -->\n");
    }  // End of table header

    // Process table records
    $record_offset = $start_offset;
    $add_clause = combined_add_clause($where_par,$search_clause,"$sort_clause LIMIT $start_offset,$list_size");
    $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
    $row_no = 0;
    while ($row = mysqli_fetch_assoc($query_result))
    {
        if ($access_level == 'read-only')
        {
            $record_action = 'view';
        }
        else
        {
            $record_action = 'edit';
        }
        if ($mode == 'desktop')
        {
            print("<tr>\n");
        }
        else
        {
            print("<div class=\"table-listing\">\n");
        }
        if (($row_no % 2) == 1)
        {
            $style = 'table-listing-odd-row';
        }
        else
        {
            $style = 'table-listing-even-row';
        }
        $row_no++;
        foreach ($primary_key as $f => $val)
        {
            $primary_key[$f] = $row[$f];
        }
        if ($mode == 'desktop')
        {
            print("<td class=\"$style\"><input type=\"checkbox\" name=\"select_$record_offset\"");
        }
        else
        {
            print("<div class=\"table-listing-cell record-select $style\"><input type=\"checkbox\" name=\"select_$record_offset\"");
        }
        if ((isset($submit_option)) &&
            (($submit_option == 'select_update') || ($submit_option == 'select_update_all') || ($submit_option == 'select_copy')) &&
            (isset($_POST["select_$record_offset"])))
        {
            /*
            We are currently displaying a select update/copy screen. The table record
            list is present but invisible with a "display:none" style. We are here
            because the given record was checked in the initial screen and needs to
            be checked again to carry it through the next form submission.
            */
            print(" checked");
        }
        if ($mode == 'desktop')
        {
            print("></td>");
        }
        else
        {
            print("></div> <!-- .table-listing-cell -->");
        }
        $record_offset++;
        $record_id = encode_record_id($primary_key);
        foreach ($fields as $f => $ord)
        {
            /* */
            $where_clause = 'table_name=? AND field_name=?';
            $where_values = array('s',$base_table,'s',$f);
            if (($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,''))) && 
                ($row2['widget_type'] == 'checkbox'))
            {
                $value = ($row[$f]) ? '[X]' : '';
            }
            else
            {
                $value = $row[$f];
            }
            if ($mode == 'desktop')
            {
                print("<td class=\"$style\"><a href=\"$base_url/$relative_path/?-table=$table&-action=$record_action&-recordid=$record_id\">$value</a></td>");
            }
            else
            {
                print("<div class=\"table-listing-cell field-$f $style\"><a href=\"$base_url/$relative_path/?-table=$table&-action=$record_action&-recordid=$record_id\">$value</a></div> <!-- .table-listing-cell -->");
            }
        }
        print("\n");
        if ($mode == 'desktop')
        {
            print("</tr>\n");
        }
        else
        {
            // Mobile mode - </div> is output below (after relationships)
        }
    
        if ($show_relationships)
        {
            if ($mode == 'desktop')
            {
                $colspan = count($fields) + 1;
                print("<tr><td class=\"$style small-text\" colspan=\"$colspan\">");
            }
            else
            {
                print("<div class=\"table-listing-cell relationships $style small-text\">");
            }
            $where_clause = "table_name=? AND UPPER(query) LIKE 'SELECT%'";
            $where_values = array('s',$base_table);
            $query_result2 = mysqli_select_query($db,'dba_relationships','*',$where_clause,$where_values,'');
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                /*
                Add a link for the given relationship. Scan the relationship query for
                variables of type $<field_name> and replace each such variable with the
                corresponding fields value from the current record.
                */
                $query = $row2['query'];
                $matches = array();
                while (preg_match(RELATIONSHIP_VARIABLE_MATCH_1,$query,$matches))
                {
                    $leading_char = substr($matches[0],0,1);
                    $field_name = substr($matches[0],2);
                    $value = mysqli_real_escape_string($db,$row[$field_name]);
                    $value = str_replace('$','\\$',$value);
                    $query = str_replace($matches[0],"$leading_char$value",$query);
                }
                $query_result3 = mysqli_query_normal($db,$query);
                if (($query_result3 !== false) && (mysqli_num_rows($query_result3) > 0))
                {
                    $uc_query = strtoupper($query);
                    $pos1 = strpos($uc_query,' FROM ');
                    $pos2 = strpos($uc_query,' WHERE ');
                    if ($pos2 !== false)
                    {
                        $tempstr =  trim(substr($query,$pos1+6));
                        $target_table = strtok($tempstr,' ');
                        $where_clause = trim(substr($query,$pos2+7));
                        $where_par = urlencode($where_clause);
                    }
                    print("&nbsp;&nbsp;<a href=\"./?-table=$target_table&-where=$where_par\" target=\"_blank\">{$row2['relationship_name']}</a>");
                }
            }
            if ($mode == 'desktop')
            {
                print("</td></tr>\n");
            }
            else
            {
                print("</div> <!-- .table-listing-cell -->\n");
            }
        }  // End of processing relationships
    
        if ($mode == 'mobile')
        {
            print("</div> <!-- .table-listing -->\n");
        }
        else
        {
            // Desktop mode - </tr> was output above (before relationships)
        }
    }  // End of loop for table records

    if ($mode == 'desktop')
    {
        print("</table>\n");
    }

    print("<p>$page_links</p>\n");
    if ($access_level == 'full')
    {
        print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update Selected\" onClick=\"selectUpdate(this.form)\"/>");
        print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update All\" onClick=\"selectUpdateAll(this.form)\"/>");
        print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Copy Selected\" onClick=\"selectCopy(this.form)\"/>");
        print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Delete Selected\" onClick=\"confirmDelete(this.form)\"/>");
    }
    if (!$display_table)
    {
        print("</div> <!-- display:none -->\n");
    }
    print("<input type=\"hidden\" name=\"-where\" value=\"$where_par\"/>");
    print("<input type=\"hidden\" name=\"submitted\" id=\"submitted\"/>");
    print("</form>\n");
}

//==============================================================================
/*
Function delete_record_set
*/
//==============================================================================

function delete_record_set($table)
{
    global $relative_sub_path;
    $db = admin_db_connect();
    $base_table = get_base_table($table);
    $primary_keys = array();
    $deletions = array();
    $sort_clause = get_session_var("$relative_sub_path-$table-sort-clause");
    $where_par = get_session_var("$relative_sub_path-$table-where-par");
    $search_clause = get_session_var("$relative_sub_path-$table-search-clause");
    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'select_')
        {
            $record_offset = substr($key,7);
    
            // Build up array of deletions indexed by record ID.
            if (is_numeric($record_offset))
            {
                $add_clause = combined_add_clause($where_par,$search_clause,"$sort_clause LIMIT $record_offset,1");
                $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
                if ($row = mysqli_fetch_assoc($query_result))
                {
                    $where_clause = 'table_name=? AND is_primary=1';
                    $where_values = array('s',$base_table);
                    $add_clause = 'ORDER by display_order ASC';
                    $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
                    while ($row2 = mysqli_fetch_assoc($query_result2))
                    {
                        $field_name = $row2['field_name'];
                        $primary_keys[$field_name] = $row[$field_name];
                    }
                    $deletions[$record_offset] = encode_record_id($primary_keys);
                }
            }
        }
    }

    $delete_count = 0;
    foreach ($deletions as $key => $record_id)
    {
        $record = new db_record;
        $record->action = 'delete';
        $record->table = $table;
    
        $where_clause = '';
        $where_values = array();
        $primary_keys = fully_decode_record_id($record_id);
        foreach ($primary_keys as $field => $value)
        {
            $where_clause .= " $field=? AND";
            $where_values[count($where_values)] = query_field_type($db,$table,$field);
            $where_values[count($where_values)] = $value;
        }
        $where_clause = rtrim($where_clause,'AND ');
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            // Populate the record fields
            $where_clause = 'table_name=?';
            $where_values = array('s',$base_table);
            $add_clause = 'ORDER by display_order ASC';
            $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $field_name = $row2['field_name'];
                $record->SetField($field_name,$row[$field_name],query_field_type($db,$table,$field_name));
            }
        }
    
        // Call the delete function on the record
        $result = delete_record($record,$record_id);
        unset($record);
        if ($result === false)
        {
            return;
        }
        else
        {
            $delete_count++;
        }
    }
    print("<p class=\"highlight-success\">$delete_count record(s) deleted.</p>\n");
    return true;
}

//==============================================================================
/*
Function select_update
*/
//==============================================================================

function select_update($table,$option)
{
    global $base_url, $relative_path;
    $db = admin_db_connect();
    $mode = get_viewing_mode();
    $base_table = get_base_table($table);
    if ($option == 'selection')
    {
        $item_found = false;
        foreach ($_POST as $key => $value)
        {
            if (substr($key,0,7) == 'select_')
            {
                $item_found = true;
                break;
            }
        }
        if (!$item_found)
        {
            print("<p class=\"highlight-error\">No records selected.</p>\n");
            return false;
        }
        print("<h2>Update Records</h2>\n");
    }
    elseif ($option == 'all')
    {
        print("<h2>Update All Records</h2>\n");
    }

    print("<form method=\"post\" action=\"$base_url/$relative_path/?-table=$table\">\n");
    if ($option == 'all')
    {
        print("<p><strong>Important</strong> - You are updating all records in the table - please check to confirm&nbsp;&nbsp;<input type=\"checkbox\" name=\"confirm_update_all\"></p>\n");
    }
    $last_display_group = '';
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] != 'file'))
        {
            $display_group = $row2['display_group'];
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
                if ($mode == 'desktop')
                {
                    print("<table class=\"update-selection\">\n");
                    print("<tr><td class=\"update-selection-header\">Select</td>");
                    print("<td class=\"update-selection-header\">Field</td>");
                    print("<td class=\"update-selection-header\">Value</td></tr>\n");
                }
                $last_display_group = $display_group;
            }
            if ($mode == 'desktop')
            {
                print("<tr>");
                print("<td class=\"update-selection\">");
            }
            else
            {
                print("<div class=\"update-field\">");
                print("<div class=\"update-field-cell update-field-select\">");
            }
            if (($row2['widget_type'] != 'auto-increment') && ($row2['widget_type'] != 'static'))
            {
                print("<input type=\"checkbox\" name=\"select_$field_name\">");
            }
            if ($mode == 'desktop')
            {
                print("</td>");
            }
            else
            {
                print("</div>");
            }
            $label = field_label($table,$field_name);
            if ($mode == 'desktop')
            {
                print("<td class=\"update-selection\">$label</td>");
                print("<td class=\"update-selection\">");
                generate_widget($table,$field_name,false);
                print("</td></tr>\n");
            }
            else
            {
                print("<div class=\"update-field-cell update-field-name\">$label</div>");
                print("<div class=\"update-field-cell update-field-value\">");
                generate_widget($table,$field_name,false);
                print("</div></div>\n");
            }
        }
    }
    if ($mode == 'desktop')
    {
        print("</table>\n");
    }
    print("<p>&nbsp;&nbsp;<input type=\"checkbox\" name=\"show_progress\">&nbsp;Show&nbsp;Progress</p>\n");
    if ($option == 'selection')
    {
        print("&nbsp;&nbsp;<input type=\"button\" value=\"Update\" onClick=\"runUpdate(this.form)\"/>");
    }
    elseif ($option == 'all')
    {
        print("&nbsp;&nbsp;<input type=\"button\" value=\"Update\" onClick=\"runUpdateAll(this.form)\"/>");
    }

    // N.B. Do not generate the "submitted" input tag or the closing </form> tag
    // at this point.
    return true;
}

//==============================================================================
/*
Function run_update
*/
//==============================================================================

function run_update($table,$option)
{
    global $relative_sub_path;
    global $location;
    $post_copy = array_deslash($_POST);
    $db = admin_db_connect();
    $base_table = get_base_table($table);
    $primary_keys = array();
    $updates = array();
    $sort_clause = get_session_var("$relative_sub_path-$table-sort-clause");
    $where_par = get_session_var("$relative_sub_path-$table-where-par");
    $search_clause = get_session_var("$relative_sub_path-$table-search-clause");

    // Build up array of updates indexed by record ID.
    if ($option == 'selection')
    {
        foreach ($post_copy as $key => $value)
        {
            if (substr($key,0,7) == 'select_')
            {
                $record_offset = substr($key,7);
                if (is_numeric($record_offset))
                {
                    $add_clause = combined_add_clause($where_par,$search_clause,"$sort_clause LIMIT $record_offset,1");
                    $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
                    if ($row = mysqli_fetch_assoc($query_result))
                    {
                        $where_clause = 'table_name=? AND is_primary=1';
                        $where_values = array('s',$base_table);
                        $add_clause = 'ORDER by display_order ASC';
                        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
                        while ($row2 = mysqli_fetch_assoc($query_result2))
                        {
                            $field_name = $row2['field_name'];
                            $primary_keys[$field_name] = $row[$field_name];
                        }
                        $updates[$record_offset] = encode_record_id($primary_keys);
                    }
                }
            }
        }
    }
    elseif ($option == 'all')
    {
        $add_clause = combined_add_clause($where_par,$search_clause,$sort_clause);
        $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
        $record_count = mysqli_num_rows($query_result);
        for ($record_offset=0; $record_offset<$record_count; $record_offset++)
        {
            $add_clause2 = "$add_clause LIMIT $record_offset,1";
            if ($row = mysqli_fetch_assoc(mysqli_select_query($db,$table,'*','',array(),$add_clause2)))
            {
                $where_clause = 'table_name=? AND is_primary=1';
                $where_values = array('s',$base_table);
                $add_clause2 = 'ORDER by display_order ASC';
                $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause2);
                while ($row2 = mysqli_fetch_assoc($query_result2))
                {
                    $field_name = $row2['field_name'];
                    $primary_keys[$field_name] = $row[$field_name];
                }
                $updates[$record_offset] = encode_record_id($primary_keys);
            }
        }
    }

    // Count number of fields being updated
    $field_count = 0;
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = ' table_name=? AND field_name=? AND is_primary=0';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            if (isset($_POST["select_$field_name"]))
            {
                $field_count++;
            }
        }
    }

    // Main loop to process the updates
    $success_count = 0;
    $failure_count = 0;
    foreach ($updates as $key => $record_id)
    {
        $record = new db_record;
        $record->action = 'update';
        $record->table = $table;
    
        $pk_values = '^';
        $where_clause = '';
        $where_values = array();
        $primary_keys = fully_decode_record_id($record_id);
        foreach ($primary_keys as $field => $value)
        {
            $pk_values .= "$value^";
            $where_clause .= " $field=? AND";
            $where_values[count($where_values)] = query_field_type($db,$table,$field);
            $where_values[count($where_values)] = $value;
        }
        $where_clause = rtrim($where_clause,'AND ');
        if (isset($_POST['show_progress']))
        {
            set_time_limit(30);
            print("Processing record $pk_values<br />\n");
        }
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            // Populate the record fields
            $query_result2 = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $field_name = $row2['Field'];
                $where_clause = 'table_name=? AND field_name=?';
                $where_values = array('s',$base_table,'s',$field_name);
                $query_result3 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                if ($row3 = mysqli_fetch_assoc($query_result3))
                {
                    if (isset($_POST["select_$field_name"]))
                    {
                        // Field is being updated
                        if ($row3['widget_type'] == 'checkbox')
                        {
                            if (isset($_POST["field_$field_name"]))
                            {
                                $field_value = 1;
                            }
                            else
                            {
                                $field_value = 0;
                            }
                        }
                        elseif ($row3['widget_type'] == 'checklist')
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
                        }
                        else
                        {
                            $field_value = stripslashes($_POST["field_$field_name"]);
                        }
                    }
                    else
                    {
                        // Field is not being updated
                        $field_value = $row[$field_name];
                    }
                    $record->SetField($field_name,$field_value,query_field_type($db,$table,$field_name));
                }
            }
        }
    
        // Call the save function on the record
        $result = save_record($record,$record_id,$record_id);
        unset($record);
        if ($result === false)
        {
            $failure_count++;
        }
        else
        {
            $success_count++;
        }
    }
    $alert_message = "$success_count record(s) updated, $failure_count record(s) not updated.";
    if ((isset($location)) && ($location == 'local') && (isset($_POST['show_progress'])))
    {
        print("<script>alert(\"$alert_message\")</script>\n");
    }
    else
    {
        print("<p class=\"highlight-success\">$alert_message</p>\n");
    }
    if (!empty(get_session_var('save_info')))
    {
        delete_session_var('save_info');
    }
    return true;
}

//==============================================================================
/*
Function select_copy
*/
//==============================================================================

function select_copy($table)
{
    global $base_url, $relative_path;
    $db = admin_db_connect();
    $mode = get_viewing_mode();
    $base_table = get_base_table($table);
    $item_found = false;
    foreach ($_POST as $key => $value)
    {
        if (substr($key,0,7) == 'select_')
        {
            $item_found = true;
            break;
        }
    }
    if (!$item_found)
    {
        print("<p class=\"highlight-error\">No records selected.</p>\n");
        return false;
    }
    print("<h2>Copy Records</h2>\n");
    print("<p class=\"small\">* = Primary key field - at least one must be selected (unless there is an auto-increment field).</p>\n");

    print("<form method=\"post\" action=\"$base_url/$relative_path/?-table=$table\">\n");
    $last_display_group = '';
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=?';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] != 'file'))
        {
            $display_group = $row2['display_group'];
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
                if ($mode == 'desktop')
                {
                    print("<table class=\"update-selection\">\n");
                    print("<tr><td class=\"update-selection-header\">Select</td>");
                    print("<td class=\"update-selection-header\">Field</td>");
                    print("<td class=\"update-selection-header\">Value</td></tr>\n");
                }
                $last_display_group = $display_group;
            }
            if ($mode == 'desktop')
            {
                print("<tr>");
                print("<td class=\"update-selection\">");
            }
            else
            {
                print("<div class=\"update-field\">");
                print("<div class=\"update-field-cell update-field-select\">");
            }
            if (($row2['widget_type'] != 'auto-increment') && ($row2['widget_type'] != 'static'))
            {
                print("<input type=\"checkbox\" name=\"select_$field_name\">");
                if ($row2['is_primary'])
                {
                    print("&nbsp;*");
                }
            }
            if ($mode == 'desktop')
            {
                print("</td>");
            }
            else
            {
                print("</div>");
            }
            $label = field_label($table,$field_name);
            if ($mode == 'desktop')
            {
                print("<td class=\"update-selection\">$label</td>");
                print("<td class=\"update-selection\">");
                generate_widget($table,$field_name,false);
                print("</td></tr>\n");
            }
            else
            {
                print("<div class=\"update-field-cell update-field-name\">$label</div>");
                print("<div class=\"update-field-cell update-field-value\">");
                generate_widget($table,$field_name,false);
                print("</div></div>\n");
            }
        }
    }
    if ($mode == 'desktop')
    {
        print("</table>\n");
    }
    print("<p>&nbsp;&nbsp;<input type=\"checkbox\" name=\"show_progress\">&nbsp;Show&nbsp;Progress</p>\n");
    print("&nbsp;&nbsp;<input type=\"button\" value=\"Copy\" onClick=\"runCopy(this.form)\"/>");

    // N.B. Do not generate the "submitted" input tag or the closing </form> tag
    // at this point.
    return true;
}

//==============================================================================
/*
Function run_copy
*/
//==============================================================================

function run_copy($table)
{
    global $relative_sub_path;
    global $location;
    $post_copy = array_deslash($_POST);
    $db = admin_db_connect();
    $base_table = get_base_table($table);
    $primary_keys = array();
    $updates = array();
    $sort_clause = get_session_var("$relative_sub_path-$table-sort-clause");
    $where_par = get_session_var("$relative_sub_path-$table-where-par");
    $search_clause = get_session_var("$relative_sub_path-$table-search-clause");

    // Build up array of updates indexed by record ID.
    foreach ($post_copy as $key => $value)
    {
        if (substr($key,0,7) == 'select_')
        {
            $record_offset = substr($key,7);
            if (is_numeric($record_offset))
            {
                $add_clause = combined_add_clause($where_par,$search_clause,"$sort_clause LIMIT $record_offset,1");
                $query_result = mysqli_select_query($db,$table,'*','',array(),$add_clause);
                if ($row = mysqli_fetch_assoc($query_result))
                {
                    $where_clause = 'table_name=? AND is_primary=1';
                    $where_values = array('s',$base_table);
                    $add_clause = 'ORDER by display_order ASC';
                    $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,$add_clause);
                    while ($row2 = mysqli_fetch_assoc($query_result2))
                    {
                        $field_name = $row2['field_name'];
                        $primary_keys[$field_name] = $row[$field_name];
                    }
                    $updates[$record_offset] = encode_record_id($primary_keys);
                }
            }
        }
    }

    // Check the number of primary key fields being updated
    $primary_key_count = 0;
    $query_result = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
    while ($row = mysqli_fetch_assoc($query_result))
    {
        $field_name = $row['Field'];
        $where_clause = 'table_name=? AND field_name=? AND is_primary=1';
        $where_values = array('s',$base_table,'s',$field_name);
        $query_result2 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
        if (($row2 = mysqli_fetch_assoc($query_result2)) &&
            ((isset($_POST["select_$field_name"])) || ($row2['widget_type'] == 'auto-increment')))
        {
            $primary_key_count++;
        }
    }
    if ($primary_key_count == 0)
    {
        print("<p class=\"highlight-error\">No primary key field selected<p>\n");
        return false;
    }

    // Main loop to process the updates
    $success_count = 0;
    $failure_count = 0;
    foreach ($updates as $key => $record_id)
    {
        $record = new db_record;
        $record->action = 'copy';
        $record->table = $table;
    
        $pk_values = '^';
        $where_clause = '';
        $where_values = array();
        $primary_keys = fully_decode_record_id($record_id);
        foreach ($primary_keys as $field => $value)
        {
            $pk_values .= "$value^";
            $where_clause .= " $field=? AND ";
            $where_values[count($where_values)] = query_field_type($db,$table,$field);
            $where_values[count($where_values)] = $value;
        }
        $where_clause = rtrim($where_clause,' AND');
        if (isset($_POST['show_progress']))
        {
            set_time_limit(30);
            print("Processing record $pk_values<br />\n");
        }
        $query_result = mysqli_select_query($db,$table,'*',$where_clause,$where_values,'');
        if ($row = mysqli_fetch_assoc($query_result))
        {
            // Populate the record fields
            $query_result2 = mysqli_query_normal($db,"SHOW COLUMNS FROM $table");
            while ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $field_name = $row2['Field'];
                $where_clause = 'table_name=? AND field_name=?';
                $where_values = array('s',$base_table,'s',$field_name);
                $query_result3 = mysqli_select_query($db,'dba_table_fields','*',$where_clause,$where_values,'');
                if ($row3 = mysqli_fetch_assoc($query_result3))
                {
                    if (isset($_POST["select_$field_name"]))
                    {
                        // Field is being updated
                        if ($row3['widget_type'] == 'checkbox')
                        {
                            if (isset($_POST["field_$field_name"]))
                            {
                                $field_value = 1;
                            }
                            else
                            {
                                $field_value = 0;
                            }
                        }
                        else
                        {
                            $field_value = stripslashes($_POST["field_$field_name"]);
                        }
                    }
                    else
                    {
                        // Field is not being updated
                        $field_value = $row[$field_name];
                    }
                    $record->SetField($field_name,$field_value,query_field_type($db,$table,$field_name));
                }
            }
        }
    
        // Call the save function on the record
        $result = save_record($record,$record_id,$record_id);
        unset($record);
        if ($result === false)
        {
            $failure_count++;
        }
        else
        {
            $success_count++;
        }
    }
    $alert_message = "$success_count record(s) copied, $failure_count record(s) not copied.";
    if ((isset($location)) && ($location == 'local') && (isset($_POST['show_progress'])))
    {
        print("<script>alert(\"$alert_message\")</script>\n");
    }
    else
    {
        print("<p class=\"highlight-success\">$alert_message</p>\n");
    }
    if (!empty(get_session_var('save_info')))
    {
        delete_session_var('save_info');
    }
    return true;
}

//==============================================================================
/*
Function renumber_records

This function renumbers the records of a given table in increments of 10.
*/
//==============================================================================

function renumber_records($table)
{
    $db = admin_db_connect();
    set_time_limit(30);
    $table = get_table_for_info_field($table,'seq_no_field');
    $where_clause = 'table_name=?';
    $where_values = array('s',$table);
    $row = mysqli_fetch_assoc(mysqli_select_query($db,'dba_table_info','*',$where_clause,$where_values,''));
    if ((isset($row['seq_no_field'])) && (!empty($row['seq_no_field'])) && ($row['renumber_enabled']))
    {
        // Set up basic query string according to sort method
        if (!empty($row['sort_1_field']))
        {
            $saved_add_clause = "ORDER BY {$row['sort_1_field']},{$row['seq_no_field']}";
            $level_1_sort = true;
        }
        else
        {
            $saved_add_clause = "ORDER BY {$row['seq_no_field']}";
            $row['seq_method'] = 'continuous';   // Force continuous method if no first-level sort
            $level_1_sort = false;
        }
        mysqli_query_normal($db,"ALTER TABLE $table $saved_add_clause");
    
        // Renumber records to temporary range (outside existing range)
        $query_result = mysqli_select_query($db,$table,'*','',array(),'');
        $record_count = mysqli_num_rows($query_result);
        $add_clause = "ORDER BY {$row['seq_no_field']} DESC LIMIT 1";
        $query_result2 = mysqli_select_query($db,$table,'*','',array(),$add_clause);
        if ($row2 = mysqli_fetch_assoc($query_result2))
        {
            $max_rec_id = $row2[$row['seq_no_field']];
        }
        else
        {
            $max_rec_id = 0;
        }
        $temp_rec_id = $max_rec_id + 10;
        $query_result2 = mysqli_select_query($db,$table,'*','',array(),$saved_add_clause);
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            if ($level_1_sort)
            {
                $set_fields = "{$row['seq_no_field']}";
                $set_values = array('i',$temp_rec_id);
                $where_clause = "{$row['sort_1_field']}=? AND {$row['seq_no_field']}=?";
                $where_values = array('s',$row2[$row['sort_1_field']],'i',$row2[$row['seq_no_field']]);
                mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);
            }
            else
            {
                $set_fields = "{$row['seq_no_field']}";
                $set_values = array('i',$temp_rec_id);
                $where_clause = "{$row['seq_no_field']}=?";
                $where_values = array('i',$row2[$row['seq_no_field']]);
                mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);
            }
            $temp_rec_id += 10;
        }
    
        // Renumber records to final values
        $new_id = 0;
        $first_sort_prev_value = '';
        $query_result2 = mysqli_select_query($db,$table,'*','',array(),$saved_add_clause);
        while ($row2 = mysqli_fetch_assoc($query_result2))
        {
            if (($row['seq_method'] == 'repeat') && ($row2[$row['sort_1_field']] != $first_sort_prev_value))
            {
                $new_id = 10;
            }
            else
            {
                $new_id += 10;
            }
            $set_fields = "{$row['seq_no_field']}";
            $set_values = array('i',$new_id);
            $where_clause = "{$row['seq_no_field']}=?";
            $where_values = array('i',$row2[$row['seq_no_field']]);
            mysqli_update_query($db,$table,$set_fields,$set_values,$where_clause,$where_values);
            if ($row['seq_method'] == 'repeat')
            {
                $first_sort_prev_value = $row2[$row['sort_1_field']];
            }
        }
        print("<p>Records renumbered for table <i>$table</i>.</p>\n");
    }
}

//==============================================================================
endif;
//==============================================================================
?>
