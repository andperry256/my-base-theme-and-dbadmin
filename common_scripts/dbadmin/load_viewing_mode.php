<?php
  session_start();
  setcookie('viewing_mode',$_GET['mode'],time()+(86400*30),'/',$_SERVER['HTTP_HOST']);
  header("Location: {$_GET['returnurl']}");
  exit;
?>
