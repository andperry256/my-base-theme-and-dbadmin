<?php
//==============================================================================
if (!defined('CORE_FUNCT_DEFINED')):
//==============================================================================

if ((isset($base_dir)) && (isset($private_scripts_dir))) {

    // Website specific functionality
    require_once("$base_dir/mysql_connect.php");
    require_once(__DIR__."/session_funct.php");

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
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
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
}

/*
The following functionality is not website specific and may be used in general
system utilities.
*/
require_once(__DIR__."/mysql_funct.php");

//==============================================================================
/*
Function php_server_mode
*/
//==============================================================================

function php_server_mode()
{
    return ((PHP_SAPI === 'cli') || (PHP_SAPI === 'cgi-fcgi')) ? 'command' : 'web';
}

//==============================================================================
/*
Function eol_string
*/
//==============================================================================

function eol_string()
{
    return ((PHP_SAPI === 'cli') || (PHP_SAPI === 'cgi-fcgi')) ? "\n" : "<br />";
}

//==============================================================================
define('CORE_FUNCT_DEFINED',true);
endif;
//==============================================================================
