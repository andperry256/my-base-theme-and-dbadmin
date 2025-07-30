<?php
//==============================================================================
/*
  This file contains those functions that may need to be called from outside
  the DB admin interface.
*/
//==============================================================================
if (!function_exists('encode_record_id')) :
//==============================================================================
/*
Function encode_record_id
*/
//==============================================================================

function encode_record_id($fields)
{
    $result = '';
    foreach($fields as $name => $value) {
        $result .= urlencode($name).'='.urlencode($value).'/';
    }
    return urlencode($result);
}

//==============================================================================
/*
Functions decode_record_id / fully_decode_record_id
*/
//==============================================================================

function decode_record_id($record_id)
{
    $result = [];
    $tok = strtok($record_id,'=');
    while ($tok !== false) {
        $field_name = urldecode($tok);
        $tok = strtok('/');
        $field_value = urldecode($tok);
        $result[$field_name] = $field_value;
        $tok = strtok('=');
    }
    return $result;
}

function fully_decode_record_id($record_id)
{
    $record_id = urldecode($record_id);
    return decode_record_id($record_id);
}

//==============================================================================
/*
Function cur_url_par
*/
//==============================================================================

function cur_url_par()
{
    if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'])) {
        return urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    }
    else {
        return urlencode("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
    }
}

//==============================================================================
/*
Function user_is_authenticated
*/
//==============================================================================

function user_is_authenticated()
{
    if ((session_var_is_set(SV_USER)) && (!empty(get_session_var(SV_USER)))) {
        return true;
    }
    else {
        return false;
    }
}

//==============================================================================
/*
Function check_new_action
*/
//==============================================================================

function check_new_action($action,$table)
{
    global $relative_path;
    if (!session_var_is_set(['dba_action',$relative_path])) {
        update_session_var(['dba_action',$relative_path],'');
    }
    if (!session_var_is_set(['dba_table',$relative_path])) {
        update_session_var(['dba_table',$relative_path],'');
    }
    if (($action != get_session_var(['dba_action',$relative_path])) || ($table != get_session_var('dba_table'))) {
        // Action and/or table has changed - clear temporary session variables
        if (session_var_is_set('get_vars')) {
            delete_session_var('get_vars');
        }
        if (session_var_is_set('post_vars')) {
            delete_session_var('post_vars');
        }
    }
    update_session_var(['dba_action',$relative_path],$action);
    update_session_var(['dba_table',$relative_path],$table);
}

//==============================================================================
/*
Function page_links

N.B. The parameter $url_function carries the name of the function used locally
to return the URL for a given page. This function only takes a single paramater,
namely for the page number, and must make use of global variables to obtain the
necessary other information for generating the required URL.
*/
//==============================================================================

function page_links($page_count,$current_page,$page_range,$current_page_link_style,$other_page_link_style,$url_function,$opt_par='')
{
    if (!function_exists($url_function)) {
        exit("Function $url_function does not exist");
    }
    $result = '';
    if ($page_count > 1) {
        if ($current_page > $page_range+1) {
            $first_linked_page = $current_page - $page_range;
        }
        else {
            $first_linked_page = 2;
        }
        if ($current_page < $page_count-$page_range-1) {
            $last_linked_page = $current_page + $page_range;
        }
        else {
            $last_linked_page = $page_count - 1;
        }

        if ($current_page != 1) {
            $link = (!empty($opt_par)) ? $url_function($current_page-1,$opt_par) : $url_function($current_page-1);
            $result .= " <a class=\"$other_page_link_style\" href=\"$link\">Prev</a>";
        }
        if ($current_page == 1) {
            $class = $current_page_link_style;
        }
        else {
            $class = $other_page_link_style;
        }
        $link = (!empty($opt_par)) ? $url_function(1,$opt_par) : $url_function(1);
        $result .= " <a class=\"$class\" href=\"$link\">1</a>";
        if ($current_page != 1) {
            if ($first_linked_page > 2) {
                $result .= " &hellip;";
            }
        }
        for ($page = $first_linked_page; $page <= $last_linked_page; $page++) {
            if ($page == $current_page) {
                $class = $current_page_link_style;
            }
            else {
                $class = $other_page_link_style;
            }
            $link = (!empty($opt_par)) ? $url_function($page,$opt_par) : $url_function($page);
            $result .= " <a class=\"$class\" href=\"$link\">$page</a>";
        }
        if ($last_linked_page < $page_count-1) {
            $result .= " &hellip;";
        }
        if ($current_page == $page_count) {
            $class = $current_page_link_style;
        }
        else {
            $class = $other_page_link_style;
        }
        $link = (!empty($opt_par)) ? $url_function($page_count,$opt_par) : $url_function($page_count);
        $result .= " <a class=\"$class\" href=\"$link\">$page_count</a>";
        if ($current_page != $page_count) {
            $link = (!empty($opt_par)) ? $url_function($current_page+1,$opt_par) : $url_function($current_page+1);
            $result .= " <a class=\"$other_page_link_style\" href=\"$link\">Next</a>";
        }
    }
    return $result;
}

//================================================================================
/*
* Function static_widget_warning
*
* This function returns a simple string containing an HTML comment line
* to warn that a widget needs to be set to static in order to operate
* correctly. This would typically be used when automatically setting a field
* with a clickable iink on saving a record. The text would show up on an
* editable widget but not on a static one.
*/
//================================================================================

function static_widget_warning()
{
    return("<!--### Widget must be STATIC to view correctly ###-->\n");
}

//==============================================================================
endif;
//==============================================================================
?>
