<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE) {
    session_start();
}
include("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
$username = $_SESSION[SV_USER];
put_user('');
print("<p>User logged out</p>\n");
print("<p><a href=\"$base_url\">Home</a></p>\n");
exit;

//==============================================================================
