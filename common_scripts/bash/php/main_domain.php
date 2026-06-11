<?php
if (php_server_mode() == 'command') {
    include(__DIR__.'/include_pathdefs.php');
    exit($main_domain);
}
else {
    // Do not allow in web mode.
}
