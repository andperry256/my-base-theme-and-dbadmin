<?php
//==============================================================================
if (!defined('CORE_FUNCT_DEFINED')):
//==============================================================================

if ((isset($base_dir)) && (isset($private_scripts_dir))) {

    // Website specific functionality
    require_once("$base_dir/mysql_connect.php");
    require_once(__DIR__."/session_funct.php");
    require_once(__DIR__."/get_url_content.php");
}

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
