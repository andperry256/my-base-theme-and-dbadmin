<?php
//==============================================================================

require_once(__DIR__.'/get_local_site_dir.php');
require_once("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");

if (isset($_POST['submitted'])) {
    include(__DIR__.'/recache_all_pages.php');
}

if (($location == 'real') && (isset($page_content_dir)) && is_dir($page_content_dir)) {
    load_updated_page_content($page_content_dir);
}

set_last_preset_link_version();
$link_version = get_last_preset_link_version();

print("<p>Last preset link version set to <em>$link_version</em></p>\n");
print("<p>Site =  <em>$local_site_dir</em></p>\n");
print("<p>Location =  <em>$location</em></p>\n");
if ($location == 'real') {
    print("<form method=\"post\">\n");
    print("<input type=\"submit\" style=\"font-size: 1.1em;\" value=\"Re-cache all pages/posts\"><span style=\"font-size:0.85em\"><br />");
    print("N.B. This may take a minute or so to complete, so please be patient.</span>\n");
    print("<input type=\"hidden\" name=\"submitted\">\n");
    print("</form>\n");
}

//==============================================================================
