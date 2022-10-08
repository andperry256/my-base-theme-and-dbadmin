<?php
  if (!isset($argc))
  {
    exit("Script allowed in command line mode only\n");
  }
  $tok1 = strtok(__DIR__,'/');
  $tok2 = strtok('/');
  $root_dir = "/$tok1/$tok2";
  require("$root_dir/public_html/path_defs.php");
  $content = file("$root_dir/maintenance/crontab.txt");
  if (empty($content))
  {
    exit("Crontab data not found\n");
  }
  elseif (!isset($local_site_dir))
  {
    exit("Local site directory not set\n");
  }
  $temp = file_get_contents("http://remote.andperry.com/store_crontab.php?site_path=$local_site_dir&command=_delete_&datetime=$date_and_time");
  print("$temp\n");
  foreach ($content as $line)
  {
    if (preg_match('/^[\*0-9]/',$line))
    {
      $schedule = strtok($line,' ');
      for ($i=1; $i<=4; $i++)
      {
        $schedule .= ' '.strtok(' ');
      }
      $command = '';
      $tok = strtok(' ');
      while ($tok !== false)
      {
        $command .= " $tok";
        $tok = strtok(' ');
      }
      $command = urlencode(trim($command));
      $schedule = urlencode($schedule);
      $date_and_time = date('YmdHis');
      $temp = file_get_contents("http://remote.andperry.com/store_crontab.php?site_path=$local_site_dir&command=$command&schedule=$schedule&datetime=$date_and_time");
      print("$temp\n");
    }
  }
?>
