<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE) {
    session_start();
}
if (is_file('/Config/linux_pathdefs.php')) {
    // Local server
    $subdir = strtok(ltrim($_SERVER['REQUEST_URI'],'/'),'/');
    include("{$_SERVER['DOCUMENT_ROOT']}/{$subdir}/path_defs.php");
}
else {
    // Online server
    include("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
$username = $_SESSION[SV_USER];
put_user('');
if ($location == 'local') {
    // Re-instate the $_SESSION[SV_USER] variable. This enables the user to be kept
    // logged off, which may occasionally be required for testing purposes.
    update_session_var(SV_USER,$username);
}
header("Location: $base_url");
exit;

//==============================================================================
