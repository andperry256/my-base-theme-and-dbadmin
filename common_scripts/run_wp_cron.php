<?php
  if (!isset($argc))
  {
    exit("Script allowed in command line mode only\n");
  }
  $tok1 = strtok(__DIR__,'/');
  $tok2 = strtok('/');
  $_SERVER['SCRIPT_NAME'] = '';
  include("/$tok1/$tok2/public_html/common_scripts/wp-cron.php");
?>
