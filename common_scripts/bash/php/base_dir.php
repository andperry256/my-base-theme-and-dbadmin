<?php
if (isset($argc)) {
    include(__DIR__.'/include_pathdefs.php');
    exit($base_dir);
}
else {
    // Do not allow in web mode.
}
