<?php
  if (isset($_GET['site']))
  {
    $local_site_dir = $_GET['site'];
  }
  if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php"))
  {
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  }
  if (!isset($local_site_dir))
  {
    exit("Site not specified");
  }
  $debug_file_path = array();
  if (is_file("/Config/localhost.php"))
  {
    // Local server
    $debug_file_path[0] = "$RootDir/logs/php_error.log";
    $debug_file_path[1] = "$BaseDir/wp-content/debug.log";
  }
  else
  {
    // Online site
    $debug_file_path[0] = "$RootDir/logs/php_error.log";
    $debug_file_path[1] = "$RootDir/logs/".str_replace('.','_',$MainDomain).'.php.error.log';
    $debug_file_path[2] = "$BaseDir/wp-content/debug.log";
  }

  if (isset($_GET['clear']))
  {
    foreach($debug_file_path as $file)
    {
      if (is_file($file))
      {
        // Delete log file
        unlink($file);
      }
    }
    header("Location: ./load_display_debug_log.php?site={$_GET['site']}");
    exit;
  }
  $links = "<a href=\"./load_display_debug_log.php?site={$_GET['site']}\">Reload</a>";
  $links .= " | <a href=\"./display_debug_log.php?site={$_GET['site']}&clear\">Clear</a>";

  print("$links<br />\n");
  $files_found = false;
  foreach($debug_file_path as $file)
  {
    if (is_file($file))
    {
      print("<br />\n");
      print("==================== $file ====================<br />\n");
      print("<br />\n");
      $content = file($file);
      foreach ($content as $line)
      {
        print("$line<br />\n");
      }
      $files_found = true;
    }
  }
  print("<br />\n");
  if ($files_found)
  {
    print("$links<br />\n");
  }
  else
  {
    print("No debug logs found\n");
  }
?>
