<?php
if ((PHP_SAPI === 'cli') || (PHP_SAPI === 'cli-fcgi')) {
    include(__DIR__.'/include_pathdefs.php');
    exit($root_dir);
}
else {
    // Do not allow in web mode.
}
