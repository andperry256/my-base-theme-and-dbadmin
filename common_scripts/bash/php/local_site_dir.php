<?php
if (php_server_mode() == 'command') {
    include(__DIR__.'/include_pathdefs.php');
    exit($local_site_dir);
}
else {
    // Do not allow in web mode.
}
