<?php
//==============================================================================
/*
  N.B. This cannot be run as a standalone script.

  It is called to run the 'update_table_data' function for a given database in
  command line mode and must be included in a site specific script in which the
  following variables are set up beforehand:-

  1. $local_site_dir
  2. $OnlineRootDir
  3. $RelativePath - This is normally taken from a command line parameter.
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
  require_once("$WWWRootDir/Sites/$local_site_dir/public_html/path_defs.php");
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

$update_charsets = false;
$optimise = false;
$purge = false;
foreach($argv as $key => $value)
{
  if ($value == '-ucs')
  {
    $update_charsets = true;
  }
  elseif ($value == '-opt')
  {
    $optimise = true;
  }
  elseif ($value == '-pur')
  {
    $purge = true;
  }
}

require("$PrivateScriptsDir/mysql_connect.php");
require("$BaseDir/common_scripts/dbadmin/widget_types.php");
require("$BaseDir/common_scripts/dbadmin/table_funct.php");
require("$BaseDir/common_scripts/dbadmin/record_funct.php");
require("$BaseDir/common_scripts/dbadmin/view_funct.php");
require("$BaseDir/common_scripts/dbadmin/update_table_data.php");
if (is_file("$CustomPagesPath/$RelativePath/db_funct.php"))
{
  require("$CustomPagesPath/$RelativePath/db_funct.php");
  update_table_data($update_charsets,$optimise,$purge);
}
else
{
  exit("File $CustomPagesPath/$RelativePath/db_funct.php not found.\n");
}

//==============================================================================
?>
