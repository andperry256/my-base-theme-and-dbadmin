<?php
//==============================================================================
if (!defined('GET_URL_CONTENT_DEFINED')):
//==============================================================================

function get_url_content($url, $post_data = [], $debug = false): string
{
    $ch = curl_init();

    $options = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Compatible; PHP/' . PHP_VERSION . ')',
        CURLOPT_SSL_VERIFYPEER => true,
    ];
    if (!empty($post_data)) {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = is_array($post_data) ? http_build_query($post_data) : $post_data;
    }

    curl_setopt_array($ch, $options);

    $content = curl_exec($ch);
    if (curl_errno($ch)) {
        $content = 'cURL Error: ' . curl_error($ch);
    }

    if ($debug) {
        exit((string)$content . "\n");
    } else {
        return (string)$content;
    }
}

//==============================================================================
define('GET_URL_CONTENT_DEFINED',true);
endif;
//==============================================================================
