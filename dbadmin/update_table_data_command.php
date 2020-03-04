<?php
//==============================================================================
/*
  This script is called to run the 'update_table_data' function for a given
  database in command line mode. It is normally included in a site specific
  script in which the following variables must be defined:-

  $local_site_dir
  $OnlineRootDir
  $RelativePath (sometimes taken from a command line parameter)
*/
//==============================================================================

if (!isset($argc))
{
  exit("script only allowed in command mode\n");
}
if (is_file("/Config/linux_pathdefs.php"))
{
  // Local Server
  require_once("/Config/linux_pathdefs.php");
  require_once("$Localhost_RootDir/Sites/$local_site_dir/public_html/path_defs.php");
}
elseif (is_file("$OnlineRootDir/public_html/path_defs.php"))
{
  // Online Server
  require_once("$OnlineRootDir/public_html/path_defs.php");
}
else
{
  exit("Path definitions file not found\n");
}
if (is_file("$CustomPagesPath/$RelativePath/db_funct.php"))
{
  require("$PrivateScriptsDir/mysql_connect.php");
  require("$CustomPagesPath/$RelativePath/db_funct.php");
  require("$BaseDir/_link_to_common/dbadmin/widget_types.php");
  require("$BaseDir/_link_to_common/dbadmin/table_funct.php");
  require("$BaseDir/_link_to_common/dbadmin/record_funct.php");
  require("$BaseDir/_link_to_common/dbadmin/update_table_data.php");
  update_table_data();
}
else
{
  exit("File $CustomPagesPath/$RelativePath/db_funct.php not found.\n");
}
//==============================================================================
?>
