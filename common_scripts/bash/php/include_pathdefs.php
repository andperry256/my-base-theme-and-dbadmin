<?php
if (!empty($argv[1])) {
    // Normal case for local server
    include('/Config/linux_pathdefs.php');
    include("$www_root_dir/{$argv[1]}/path_defs.php");
}
else {
    /*
    Normal case for online server.
    Parent directory hierarchy: bash => common_scripts => public_html
    */
    include(__DIR__."/../../../path_defs.php");
}
