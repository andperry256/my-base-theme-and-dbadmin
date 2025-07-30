
<?php
//==============================================================================
/*
  Script to recache all pages/posts on a given site.
  Can be run in either command line or web mode.
*/
//==============================================================================

$tok1 = strtok(__DIR__,'/');
if ($tok1 == 'home') {
    $tok2 = strtok('/');
    $tok3 = strtok('/');
    $root_dir = "/$tok1/$tok2";
    if ($tok3 != 'public_html') {
        // Extra directory level in special cases
        $root_dir .= "/$tok3";
    }
    require("$root_dir/public_html/path_defs.php");
    require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
    recache_all_pages('page');
    recache_all_pages('post');
    if (function_exists('recache_additional_pages')) {
        recache_additional_pages();
    }
}
else {
    // Invalid environment - exit with no action.
}

//==============================================================================
?>
