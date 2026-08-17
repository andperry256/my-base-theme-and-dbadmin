<?php
//==============================================================================
/*
This script contains common functionality and is invoked from a site-related
script. On entry the following conditions must be met:-
1. The calling script has already included a valid path_defs.php script.
2. The array $dirs has been set up with a list of the directories to be
   processed for the given site.
*/
//==============================================================================

if (!isset($base_dir)) {
    exit("Path definitions file not found");
}
require_once("$base_dir/keycode.php");
$mode = php_server_mode();
$eol = eol_string();

if ($location == 'local') {
    $links_path = 'links-000000';
}
else {
    $key = password_hash($key,PASSWORD_DEFAULT);
    $key = md5($key);
    $key = substr($key,0,32);
    $links_path = "links-$key";
}

foreach (['paths.php','.htaccess'] as $file) {
    $content = file_get_contents("$base_dir/media_files/$file");
    $content = preg_replace('/links-[0-9a-f]+/',"$links_path",$content);
    file_put_contents("$base_dir/media_files/$file",$content);
}
print("Links URL is now $base_url/media_files/$links_path$eol");
print("Operation completed$eol");

//==============================================================================
