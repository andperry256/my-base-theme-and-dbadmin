<?php
//==============================================================================
if (!defined('CORE_FUNCT_DEFINED')):
//==============================================================================

if ((isset($base_dir)) && (isset($private_scripts_dir))) {
    require_once("$base_dir/mysql_connect.php");
    require_once("$base_dir/common_scripts/mysql_funct.php");
    require_once("$base_dir/common_scripts/session_funct.php");
}
else {
    exit("Directory paths not found\n");
}

//==============================================================================
/*
Function get_url_content
*/
//==============================================================================

function get_url_content($url,$debug=false)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    if (curl_errno($ch)) {
        $content = 'cURL Error: '.curl_error($ch);
    }
    if ($debug) {
        exit("$content\n");
    }
    else {
        curl_close($ch);
        return $content;
    }
}

//==============================================================================
define('CORE_FUNCT_DEFINED',true);
endif;
//==============================================================================
