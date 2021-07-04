<?php
define ('HOME_IP_ADDR','212.159.74.141');
define ('LONGCROFT_IP_ADDR','217.45.173.179');
  $redundant_table_prefixes = array ('wp_duplicator', 'wp_itsec');
  if (($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) && ($_SERVER['REMOTE_ADDR'] != HOME_IP_ADDR) && ($_SERVER['REMOTE_ADDR'] != LONGCROFT_IP_ADDR) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
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
    print("<h1>Optimising Database $dbname</h1>\n");
    $db = db_connect($dbid);
    $table_field = "Tables_in_$dbname";
    $query_result = mysqli_query($db,"SHOW FULL TABLES FROM `$dbname` WHERE Table_type<>'VIEW'");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      $table = $row[$table_field];
      $table_deleted = false;
      foreach ($redundant_table_prefixes as $t)
      {
        if (substr($table,0,strlen($t)) == $t)
        {
          mysqli_query($db,"DROP TABLE $table");
          print("Table $table deleted<br />\n");
          $table_deleted = true;
        }
      }
      if (!$table_deleted)
      {
        if (mysqli_query($db,"OPTIMIZE TABLE $table"))
        {
          print("Table $table optimised<br />\n");
        }
        else
        {
          print("Unable to optimise table $table<br />\n");
        }
      }
    }
  }
?>
