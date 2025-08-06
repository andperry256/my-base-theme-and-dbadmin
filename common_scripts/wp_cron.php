<?php
//================================================================================

if (is_dir('/media/Data/www')) {
    // Local Server
    $local_site_dir = strtok(ltrim($_SERVER['REQUEST_URI'],'/'),'/');
}
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
if (is_file("$root_dir/maintenance/wp_cron_additions.php")) {
    print("Running WP cron additions for $base_url\n");
    include("$root_dir/maintenance/wp_cron_additions.php");
}

//================================================================================
?>
