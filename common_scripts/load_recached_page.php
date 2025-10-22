<?php
//==============================================================================

$tok1 = strtok(__DIR__,'/');
$tok2 = strtok('/');
$tok3 = strtok('/');
$root_dir = "/$tok1/$tok2";
if ($tok3 != 'public_html') {
    // Extra directory level in special cases
    $root_dir .= "/$tok3";
}
require("$root_dir/public_html/path_defs.php");
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
$dummy = get_url_content("$base_url/common_scripts/force_cache_reload.php");
$uri_path = $_GET['uripath'];
recache_page($uri_path);
header("Location: $base_url/$uri_path/");
exit;

//==============================================================================
