<?php
  if (is_file("/Config/localhost.php"))
  {
    // Local server
    require("/Config/linux_pathdefs.php");
    $debug_file_path = "$Localhost_RootDir/Sites/{$_GET['site']}/public_html/wp-content/debug.log";
  }
  else
  {
    // Online site
    $debug_file_path = "../wp-content/debug.log";
  }

  if ((isset($_GET['clear'])) && (is_file($debug_file_path)))
  {
    // Delete log file
    unlink($debug_file_path);
    $new_url = './display_debug_log.php';
    if (isset($_GET['site']))
    {
        $new_url .= "?site={$_GET['site']}";
    }
    header("Location: $new_url");
    exit;
  }

  if (is_file($debug_file_path))
  {
    // Display log file
    if (isset($_GET['site']))
    {
      $link = "./display_debug_log.php?site={$_GET['site']}&clear";
    }
    else
    {
      $link = "./display_debug_log.php?clear";
    }
    print("<p><a href=\"$link\">Clear Log</a></p>\n");
    $content = file($debug_file_path);
    foreach ($content as $line)
    {
      print("$line<br />\n");
    }
    print("<p><a href=\"$link\">Clear Log</a></p>\n");
  }
  else
  {
    print("Debug log file not found.");
  }
?>
