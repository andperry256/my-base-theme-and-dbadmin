<?php
if (isset($argc)) {
    include(__DIR__.'/include_pathdefs.php');
    exit($cpanel_user);
}
else {
    // Do not allow in web mode.
}
