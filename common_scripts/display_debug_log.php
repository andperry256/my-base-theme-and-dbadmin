<?php
  require("allowed_hosts.php");
  if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
  {
    exit("Authentication failure");
  }
  if (isset($_GET['site']))
  {
    $local_site_dir = $_GET['site'];
  }
  if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php"))
  {
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  }
  else
  {
    exit("Path definitions script not found");
  }
  if (!isset($local_site_dir))
  {
    exit("Site not specified");
  }

  // Set up debug file paths
  $debug_file_path = array();
  if (is_file("/Config/localhost.php"))
  {
    // Local server
    $debug_file_path[0] = "$RootDir/logs/php_error.log";
    $debug_file_path[2] = "$RootDir/logs/wp_debug.log";
  }
  else
  {
    // Online site
    $debug_file_path[0] = "$RootDir/logs/php_error.log";
    $debug_file_path[1] = "$RootDir/logs/".str_replace('.','_',$MainDomain).'.php.error.log';
    $debug_file_path[2] = "$RootDir/logs/wp_debug.log";
  }

  // Clear logs if required
  $clear_time_file_path = "$RootDir/maintenance/debug_log_clear_time.txt";
  if ((isset($_POST['clear'])) || (isset($_GET['clear'])))
  {
    foreach($debug_file_path as $file)
    {
      if (is_file($file))
      {
        unlink($file);
      }
    }
    $ofp = fopen($clear_time_file_path,'w');
    fprintf($ofp,date('Y-m-d H:i:s'));
    fclose($ofp);
  }

  // Manage debug settings in wp-config.php
  $config = file("$BaseDir/wp-config.php");
  $debug = false;
  $debug_log = false;
  $debug_display = false;
  $ofp = fopen("$BaseDir/wp-config.new",'w');
  foreach($config as $line)
  {
    if (substr($line,0,18) == "define('WP_DEBUG',")
    {
      if (isset($_POST['debug']))
      {
        $debug = true;
      }
      elseif (isset($_POST['submitted']))
      {
        $debug = false;
      }
      else
      {
        $debug = (trim(strtok(substr($line,18),')')) == 'true');
      }
      if ($debug)
      {
        fprintf($ofp,"define('WP_DEBUG', true);\n");
      }
      else
      {
        fprintf($ofp,"define('WP_DEBUG', false);\n");
      }
    }
    elseif(substr($line,0,22) == "define('WP_DEBUG_LOG',")
    {
      $debug_log = trim(strtok(substr($line,22),')')," '");
      fprintf($ofp,"$line");
    }
    elseif(substr($line,0,26) == "define('WP_DEBUG_DISPLAY',")
    {
      if (isset($_POST['debug_display']))
      {
        $debug_display = true;
      }
      elseif (isset($_POST['submitted']))
      {
        $debug_display = false;
      }
      else
      {
        $debug_display = (trim(strtok(substr($line,26),')')) == 'true');
      }
      if ($debug_display)
      {
        fprintf($ofp,"define('WP_DEBUG_DISPLAY', true);\n");
      }
      else
      {
        fprintf($ofp,"define('WP_DEBUG_DISPLAY', false);\n");
      }
    }
    else
    {
      fprintf($ofp,str_replace('%','%%',$line));
    }
  }
  fclose($ofp);
  $config1 = file_get_contents("$BaseDir/wp-config.php");
  $config2 = file_get_contents("$BaseDir/wp-config.new");
  $config1a = file("$BaseDir/wp-config.php");
  $config2a = file("$BaseDir/wp-config.new");
  if (($config2 != $config1) && (count($config1a) == count($config2a)))
  {
    // Debug status has changed - update wp-config.php.
    unlink("$BaseDir/wp-config.php");
    rename("$BaseDir/wp-config.new","$BaseDir/wp-config.php");
  }
  else
  {
    unlink("$BaseDir/wp-config.new");
  }
?>
<fieldset>
  <form method="post">
    <p>WP_DEBUG:&nbsp;<input type="checkbox" name="debug" <?php if ($debug) echo " checked"; ?> />
       &nbsp;&nbsp;&nbsp; WP_DEBUG_LOG:&nbsp;<?php if (!empty($debug_log)) echo "$debug_log"; ?>
       &nbsp;&nbsp;&nbsp; WP_DEBUG_DISPLAY:&nbsp;<input type="checkbox" name="debug_display" <?php if ($debug_display) echo " checked"; ?> /></p>
    <p>Clear&nbsp;Logs:&nbsp;<input type="checkbox" name="clear" />
      <?php
        if (is_file($clear_time_file_path))
        {
          print("&nbsp;&nbsp;&nbsp; [Last cleared ".trim(file_get_contents($clear_time_file_path)."]"));
        }
      ?>
    </p>
    <p><input type="submit" value="Update/Reload"</p>
    <input type="hidden" name="submitted" />
  </form>
</fieldset>
<?php
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
  if (!$files_found)
  {
    print("No debug logs found\n");
  }
?>
