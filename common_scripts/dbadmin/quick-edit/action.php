<?php
  /*
    This page is activated by the associated _home.php script on submission
    of the editing form. It performs the required database update and then
    automatically redirects to the URL of the page/post from which the edit
    was originally instigated.
  */
  $local_site_dir = 'longcroft';
  require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  require_once("$PrivateScriptsDir/mysql_connect.php");
  $db = db_connect($_POST['dbid']);

  if ((!isset($_POST['post_name'])) || (!isset($_POST['content'])) ||
      (!isset($_POST['keycode'])) || (!isset($_POST['returnurl'])))
  {
    exit("Invalid referrer");
  }
  if ($_POST['keycode'] != PAGE_EDIT_KEYCODE)
  {
    exit("Authentication failure");
  }

  $post_name = $_POST['post_name'];
  $content_par = addslashes($_POST['content']);
  $query_result = mysqli_query($db,"SELECT * FROM wp_posts WHERE post_name='$post_name'");
  if ($row = mysqli_fetch_assoc($query_result))
  {
    mysqli_query($db,"UPDATE wp_posts SET post_content='$content_par' WHERE  post_name='$post_name'");
  }
  header("Location: {$_POST['returnurl']}");
  exit;
?>
