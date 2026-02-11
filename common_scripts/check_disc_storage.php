<?php
//==============================================================================

if (!isset($argc)) {
    exit("Script valid in command line mode only\n");
}
elseif (substr(__DIR__,0,5) != '/home') {
    exit("Script valid on online server only\n");
}
require(__DIR__.'/get_local_site_dir.php');
$content = file("$root_dir/maintenance/disc_storage.txt");
if (empty($content)) {
    exit("Disc storage data not found\n");
}
elseif (!isset($local_site_dir)) {
    exit("Local site directory not set\n");
}
$last_line = $content[count($content) -1];
$used_storage = strtok($last_line," \t");
$date_and_time = date('YmdHis');
$temp = get_url_content("https://remote.andperry.com/report_disc_storage.php?site_path=$local_site_dir&datetime=$date_and_time&used_storage=$used_storage");
print($temp);

//==============================================================================
