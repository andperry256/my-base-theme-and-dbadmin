<?php
  if (isset($_GET['site']))
  {
    $site = $_GET['site'];
  }
  else
  {
    exit("Site parameter not specified");
  }
  if (isset($_GET['dbname']))
  {
    $dbname = $_GET['dbname'];
  }
  else
  {
    exit("Database parameter not specified");
  }

  if (is_file("/Config/localhost.php"))
  {
    // Local server
    require("/Config/linux_pathdefs.php");
    require("$Localhost_RootDir/Sites/$site/public_html/path_defs.php");
  }
  else
  {
    // Online site
    require("../../path_defs.php");
  }
  require("$PrivateScriptsDir/mysql_connect.php");
  $db = mysqli_connect( 'localhost', REAL_DB_USER, REAL_DB_PASSWD, $dbname );
  if (!$db)
  {
    exit("Unable to connect to database");
  }
  $query_result = mysqli_query($db,"SELECT * FROM dba_master_location WHERE rec_id=1");
  if ($row = mysqli_fetch_assoc($query_result))
  {
    if ($row['location'] == $Location)
    {
      exit('master');
    }
    else
    {
      exit('sub-master');
    }
  }
  else
  {
    exit('unknown');
  }
?>
