<?php
//==============================================================================

if (!isset($argc))
{
    exit("Script allowed in command line mode only\n");
}
$tok1 = strtok(__DIR__,'/');
$tok2 = strtok('/');
$tok3 = strtok('/');
$root_dir = "/$tok1/$tok2";
if ($tok3 != 'public_html')
{
    // Extra directory level in special cases
    $root_dir .= "/$tok3";
}
require("$root_dir/public_html/path_defs.php");
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
recache_all_pages();

//==============================================================================
?>
