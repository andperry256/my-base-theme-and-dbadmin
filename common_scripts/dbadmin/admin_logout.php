<?php
//==============================================================================
//
// N.B. The associated path_defs.php script must have been included by the 
// calling script.
//
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_dir)) {
    exit("Path definitions file not included.");
}
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
$username = get_session_var(SV_USER);
put_user('');
if ($location == 'local') {
    // Re-instate the $_SESSION[SV_USER] variable. This enables the user to be kept
    // logged off, which may occasionally be required for testing purposes.
    update_session_var(SV_USER,$username);
}
header("Location: $base_url");
exit;

//==============================================================================
