<?php
  /*
    This script is used to edit a page or post 'on the fly' without having to
    log on to the WordPress interface. It needs to be included from a custom
    page script for which there needs to be an associated authentication.php
    script to ensure that this can only be executed if an appropriate used is
    logged in.

    The following variables/constants need to be set prior to invoking this
    script:-

    $local_site_dir
    $DBAdminURL
    $dbid
    PAGE_EDIT_KEYCODE
  */
  if (!isset($wpdb))
  {
    exit("Script not valid outside WordPress environment.");
  }
  elseif (!isset($local_site_dir))
  {
    exit("Local site directory not set.");
  }
  elseif (!isset($DBAdminURL))
  {
    exit("DB Admin URL not set.");
  }
  elseif (!isset($dbid))
  {
    exit("Database ID not set.");
  }
  elseif (!defined('PAGE_EDIT_KEYCODE'))
  {
    exit("Page edit keycode not set.");
  }
  require_once("$PrivateScriptsDir/mysql_connect.php");
  $db = db_connect($dbid);

  $return_url = urldecode($_GET['-returnurl']);
  if (isset($_GET['-pageurl']))
  {
    // Special situation (i.e. when editing other from the page/post itself).
    $page_url = urldecode($_GET['-pageurl']);
    $page_url = strtok($page_url,'?');
  }
  else
  {
    // Normal situation (i.e. when editing from the page/post itself).
    $page_url = strtok($return_url,'?');
  }
  if (trim($page_url,'/') == $BaseURL)
  {
    $page_slug = 'home';
  }
  else
  {
    $tempstr = strrev($page_url);
    $page_slug = strrev(strtok($tempstr,'/'));
  }

  print("<h1>Edit page/post [$page_slug]</h1>\n");
  $query_result = mysqli_query_strict($db,"SELECT * FROM wp_posts WHERE post_name='$page_slug'");
  if ($row = mysqli_fetch_assoc($query_result))
  {
    print("<form method=\"post\" action=\"$DBAdminURL/quick-edit/action.php\">\n");
    print("<textarea name=\"content\" rows=\"12\">{$row['post_content']}</textarea>\n");
    print("<input type=\"submit\" value=\"Save\" />\n");
    print("<input type=\"hidden\" name=\"post_name\" value=\"$page_slug\" />\n");
    print("<input type=\"hidden\" name=\"local_site_dir\" value=\"$local_site_dir\" />\n");
    print("<input type=\"hidden\" name=\"dbid\" value=\"$dbid\" />\n");
    print("<input type=\"hidden\" name=\"keycode\" value=\"".PAGE_EDIT_KEYCODE."\" />\n");
    print("<input type=\"hidden\" name=\"returnurl\" value=\"$return_url\" />\n");
    print("</form>\n");
  }
?>
