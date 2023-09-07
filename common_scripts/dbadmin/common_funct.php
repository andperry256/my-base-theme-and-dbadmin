<?php
//==============================================================================
/*
  This file contains those functions that may need to be called from outside
  the DB admin interface.
*/
//==============================================================================
if (!function_exists('encode_record_id'))
{
//==============================================================================
/*
Function encode_record_id
*/
//==============================================================================

function encode_record_id($fields)
{
  $result = '';
  ksort($fields);
  foreach($fields as $name => $value)
  {
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
  $result = array();
  $tok = strtok($record_id,'=');
  while ($tok !== false)
  {
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
  if ((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS']))
  {
    return urlencode("https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
  }
  else
  {
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
  if ((session_var_is_set(SV_USER)) && (!empty(get_session_var(SV_USER))))
  {
    return true;
  }
  else
  {
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
  if (!session_var_is_set('dba_action'))
  {
    update_session_var('dba_action','');
  }
  if (!session_var_is_set('dba_table'))
  {
    update_session_var('dba_table','');
  }
  if (($action != get_session_var('dba_action')) || ($table != get_session_var('dba_table')))
  {
    // Action and/or table has changed - clear temporary session variables
    if (session_var_is_set('get_vars'))
    {
      delete_session_var('get_vars');
    }
    if (session_var_is_set('post_vars'))
    {
      delete_session_var('post_vars');
    }
  }
  update_session_var('dba_action',$action);
  update_session_var('dba_table',$table);

  if (empty($table))
  {
    // No table is being displayed - force filters to be cleared on next table display.
    update_session_var('filtered_table','');
  }

  if ((isset($_GET['-showall'])) || ($table != get_session_var('filtered_table')))
  {
    // Clear all filters
    if (!isset($_GET['-where']))
    {
      update_session_var('search_clause','');
    }
    update_session_var('sort_clause','');
    update_session_var('show_relationships',false);

    // Do not allow an outstanding action to proceed, in case another window has
    // altered the filters for the current session.
    if (isset($_POST['submitted']))
    {
      unset($_POST['submitted']);
    }
  }
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

function page_links($page_count,$current_page,$page_range,$current_page_link_style,$other_page_link_style,$url_function)
{
  if (!function_exists($url_function))
  {
    exit("Function $url_function does not exist");
  }
  $result = '';
  if ($page_count > 1)
  {
    if ($current_page > $page_range+1)
    {
      $first_linked_page = $current_page - $page_range;
    }
    else
    {
      $first_linked_page = 2;
    }
    if ($current_page < $page_count-$page_range-1)
    {
      $last_linked_page = $current_page + $page_range;
    }
    else
    {
      $last_linked_page = $page_count - 1;
    }

    if ($current_page != 1)
    {
      $result .= " <a class=\"$other_page_link_style\" href=\"".$url_function($current_page-1)."\">Prev</a>";
    }
    if ($current_page == 1)
    {
      $class = $current_page_link_style;
    }
    else
    {
      $class = $other_page_link_style;
    }
    $result .= " <a class=\"$class\" href=\"".$url_function(1)."\">1</a>";
    if ($current_page != 1)
    {
      if ($first_linked_page > 2)
      {
        $result .= " &hellip;";
      }
    }
    for ($page = $first_linked_page; $page <= $last_linked_page; $page++)
    {
      if ($page == $current_page)
      {
        $class = $current_page_link_style;
      }
      else
      {
        $class = $other_page_link_style;
      }
      $result .= " <a class=\"$class\" href=\"".$url_function($page)."\">$page</a>";
    }
    if ($last_linked_page < $page_count-1)
    {
      $result .= " &hellip;";
    }
    if ($current_page == $page_count)
    {
      $class = $current_page_link_style;
    }
    else
    {
      $class = $other_page_link_style;
    }
    $result .= " <a class=\"$class\" href=\"".$url_function($page_count)."\">$page_count</a>";
    if ($current_page != $page_count)
    {
      $result .= " <a class=\"$other_page_link_style\" href=\"".$url_function($current_page+1)."\">Next</a>";
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
}
//==============================================================================
?>
