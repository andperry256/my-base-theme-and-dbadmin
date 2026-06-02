
<?php
//==============================================================================
/*
  Script to clear all WP cache on a given site.
  Can be run in either command line or web mode.
*/
//==============================================================================

require_once(__DIR__.'/get_local_site_dir.php');
if ($location == 'real') {
    require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
    clear_cache();
}

//==============================================================================
