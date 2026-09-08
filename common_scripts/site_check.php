<?php
//==============================================================================

require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
$content = get_url_content("$base_url?sitecheck");
if (strpos($content,'*** SITE CHECK ***') !== false) {
    exit('OK');
}
else {
    exit('ERR');
}
