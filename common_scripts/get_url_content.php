<?php
//==============================================================================
if (!defined('GET_URL_CONTENT_DEFINED')):
//==============================================================================

function get_url_content($url,$debug=false): string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Compatible; PHP/' . PHP_VERSION . ')',
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $content = curl_exec($ch);
    if (curl_errno($ch)) {
        $content = 'cURL Error: '.curl_error($ch);
    }
    if ($debug) {
        exit("(string)$content\n");
    }
    else {
        return (string)$content;
    }
}

//==============================================================================
define('GET_URL_CONTENT_DEFINED',true);
endif;
//==============================================================================
