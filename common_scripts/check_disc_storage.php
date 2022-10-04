<?php
  if (!isset($argc))
  {
    exit("Script allowed in command line mode only\n");
  }
  $tok1 = strtok(__DIR__,'/');
  $tok2 = strtok('/');
  $root_dir = "/$tok1/$tok2";
  require("$root_dir/public_html/path_defs.php");
  $content = file("$root_dir/maintenance/disc_storage.txt");
  if (empty($content))
  {
    exit("Disc storage data not found\n");
  }
  elseif (!isset($local_site_dir))
  {
    exit("Local site directory not set\n");
  }
  foreach($content as $line)
  {
    $tok = strtok($line,' ');
    if ($tok == '/dev/sda1')
    {
      for ($i=0; $i<4; $i++)
      {
        $tok = strtok(' ');
      }
      $used_storage = (int)trim($tok,'%');
      break;
    }
  }
  $date_and_time = date('YmdHis');
  $temp = file_get_contents("http://remote.andperry.com/report_disc_storage.php?site_path=$local_site_dir&datetime=$date_and_time&used_storage=$used_storage");
  print($temp);
?>
