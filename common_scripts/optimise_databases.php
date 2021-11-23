<?php
  require("allowed_hosts.php");
  if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
  {
  	exit("Authentication Failure");
  }
  if (is_file("/Config/linux_pathdefs.php"))
  {
    if (!isset($_GET['site']))
    {
      exit("Site not specified");
    }
    else
    {
      $local_site_dir = $_GET['site'];
    }
  }
  require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
  require("$PrivateScriptsDir/mysql_connect.php");
  $add_tags = (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'wget') === false);
  foreach ($dbinfo as $dbid => $info)
  {
    if ($Location == 'local')
    {
      $dbname = $info[0];
    }
    else
    {
      $dbname = $info[1];
    }
    print("\n");
    if ($add_tags)
    {
      print('<h1>');
    }
    print("Optimising Database $dbname\n");
    if ($add_tags)
    {
      print('</h1>');
    }
    $db = db_connect($dbid);
    $table_field = "Tables_in_$dbname";
    $query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type<>'VIEW'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      $table = $row[$table_field];
      $table_deleted = false;
      if (mysqli_query($db,"OPTIMIZE TABLE $table"))
      {
        print("Table $table optimised\n");
        if ($add_tags)
        {
          print('<br />');
        }
      }
      else
      {
        print("Unable to optimise table $table\n");
        if ($add_tags)
        {
          print('<br />');
        }
      }
    }
  }
?>
