<?php
if (isset($argc)) {
    require(__DIR__."/../path_defs.php");
    exit($base_dir);
}
else {
    // Do not allow in web mode.
}
