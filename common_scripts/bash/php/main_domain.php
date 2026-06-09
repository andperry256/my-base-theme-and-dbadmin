<?php
if (PHP_SAPI === 'cli') {
    include(__DIR__.'/include_pathdefs.php');
    exit($main_domain);
}
else {
    // Do not allow in web mode.
}
