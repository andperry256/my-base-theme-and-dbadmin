<?php
include(__DIR__.'/../../../common_scripts/session_start.php'); // Parent Dirs: themes => wp-content => public_html
if (isset($_GET['mode'])) {
    $_SESSION['theme_mode'] = $_GET['mode'];
}
if (isset($_GET['returnurl'])) {
    header("Location: {$_GET['returnurl']}");
    exit;
}
else {
    print("ERROR - Return URL not specified");
    exit;
}
