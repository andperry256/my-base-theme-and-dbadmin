<?php
if (!empty($argv[1])) {
    # Normal case for local server
    include('/Config/linux_pathdefs.php');
    include("$www_root_dir/{$argv[1]}/path_defs.php");
}
else {
    # Normal case for online server
    include(__DIR__."/../path_defs.php");
}
