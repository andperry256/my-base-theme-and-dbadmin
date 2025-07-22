<?php
  /*
    This page is activated by the associated _home.php script on submission
    of the editing form. It performs the required database update and then
    automatically redirects to the URL of the page/post from which the edit
    was originally instigated.
  */
  $local_site_dir = $_POST['local_site_dir'];
  require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  require_once("$base_dir/mysql_connect.php");
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
  $where_clause = 'post_name=?';
  $where_values = array('s',$post_name);
  $query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,'');
  if ($row = mysqli_fetch_assoc($query_result))
  {
      $set_fields = 'post_content';
      $set_values = array('s',$_POST['content']);
      $where_clause = 'post_name=?';
      $where_values = array('s',$post_name);
      mysqli_update_query($db,'wp_posts',$set_fields,$set_values,$where_clause,$where_values);
  }
  header("Location: {$_POST['returnurl']}");
  exit;
?>
