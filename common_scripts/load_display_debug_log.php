<?php
  $dt = date("YmdHis");
  header("Location: ./display_debug_log.php?site={$_GET['site']}&dt=$dt");
  exit;
?>
