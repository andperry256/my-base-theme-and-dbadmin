<?php
  session_start();
  if (isset($_POST['save_setting']))
  {
    setcookie('viewing_mode',$_GET['view'],time()+(86400*30),'/',$_SERVER['HTTP_HOST']);
  }
  elseif (isset($_COOKIE['viewing_mode']))
  {
    setcookie('viewing_mode',$_GET['view'],time()-3600);
  }
  $_SESSION['viewing_mode'] = $_GET['view'];
  header("Location: {$_GET['returnurl']}");
  exit;
?>
