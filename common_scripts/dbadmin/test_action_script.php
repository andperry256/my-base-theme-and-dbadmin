<?php
//==============================================================================

ini_set('error_reporting','E_ALL');
ini_set('error_reporting','E_ALL');
if (isset($_GET['site']))
{
    $local_site_dir = $_GET['site'];
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
else
{
    exit("Site parameter not specified");
}
if (isset($_GET['action']))
{
    $action = $_GET['action'];
}
else
{
    exit("Action parameter not specified");
}
if (isset($_GET['subpath']))
{
    $sub_path = "db-".$_GET['subpath'];
    $file_path = "$CustomPagesPath/dbadmin/$sub_path/actions/$action.php";
    $RelativePath = "dbadmin/$sub_path";
}
else
{
    $file_path = "$CustomPagesPath/dbadmin/actions/$action.php";
    $RelativePath = "dbadmin";
}
if (is_file($file_path))
{
    require($file_path);
}
else
{
    exit("File not found");
}
exit ("End of script");

//==============================================================================
?>
