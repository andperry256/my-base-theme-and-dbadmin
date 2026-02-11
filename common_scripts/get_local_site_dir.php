<?php
//==============================================================================
/*
This script must be included by any common script which needs to identify the
site by which it is being used. The site is identified by analysing the path to
this script.
*/
//==============================================================================

$dir_elements = explode('/',trim(__DIR__,'/'));
if ($dir_elements[0] == 'home') {
    /*
    Online Server - take path_defs.php from the main domain, even if accessing
    from a subdomain. There should in any case be no requirement to run it on a
    subdomain, as separate from the associated main domain.
    */
    include("/{$dir_elements[0]}/{$dir_elements[1]}/public_html/path_defs.php");
}
else {
    /*
    Local Server - take path_defs.php from the local site path via the main
    'www' directory.
    */
    if (isset($_GET['site'])) {
        /*
        The use of $_GET['site'] is generally no longer mandatory, but the
        option to force a given site has been retained.
        */
        $site_sub_path = $_GET['site'];
    }
    else {
        $uri_elements = explode('/',trim($_SERVER['REQUEST_URI'],'/'));
        $site_sub_path = $uri_elements[0];
    }
    if (!isset($www_root_dir)) {
        include("/Config/linux_pathdefs.php");
    }
    include("$www_root_dir/$site_sub_path/path_defs.php");
}

//==============================================================================
