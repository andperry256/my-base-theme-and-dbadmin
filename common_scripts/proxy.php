<?php
//==============================================================================

require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
$path = $_GET['file'] ?? '';
if (empty($path) || strpos($path, '..') !== false) {
    header("HTTP/1.1 400 Bad Request");
    exit('Invalid path');
}
$type = $_GET['type'] ?? 'r2';
$bases = [
    'r2'    => $r2_base ?? '',
    'local' => "$base_url/media_files" ?? '',
];
$target_url = "{$bases[$type]}/" . ltrim($path, '/');

$ch = curl_init($target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($http_code === 200) {
    header("Content-Type: " . $content_type);
    header("Cache-Control: public, max-age=86400");
    echo $data;
} else {
    header("HTTP/1.1 404 Not Found");
    echo "File not found.";
}

//==============================================================================
