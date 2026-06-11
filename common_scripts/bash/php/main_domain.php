<?php
if ((PHP_SAPI === 'cli') || (PHP_SAPI === 'cli-fcgi')) {
    include(__DIR__.'/include_pathdefs.php');
    exit($main_domain);
}
else {
    // Do not allow in web mode.
}
