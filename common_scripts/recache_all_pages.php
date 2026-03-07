
<?php
//==============================================================================
/*
  Script to recache all pages/posts on a given site.
  Can be run in either command line or web mode.
*/
//==============================================================================

require_once(__DIR__.'/get_local_site_dir.php');
if ($location == 'real') {
    require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
    recache_all_pages('page');
    recache_all_pages('post');
    if (function_exists('recache_additional_pages')) {
        recache_additional_pages();
    }
}

//==============================================================================
