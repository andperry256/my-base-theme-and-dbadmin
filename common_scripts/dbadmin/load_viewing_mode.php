<?php
//==============================================================================

include(__DIR__.'/../session_start.php'); // Parent Dir: common_scripts
setcookie('viewing_mode',$_GET['mode'],time()+(86400*30),'/',$_SERVER['HTTP_HOST']);
header("Location: {$_GET['returnurl']}");
exit;

//==============================================================================
