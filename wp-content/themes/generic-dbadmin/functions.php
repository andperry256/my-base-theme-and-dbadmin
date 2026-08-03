<?php
//==============================================================================

require ("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
add_action( 'init', 'run_session', 1);
if (!defined('WP_POST_REVISIONS')) {
    define( 'WP_POST_REVISIONS', 2 );
}

//==============================================================================

function is_local()
{
    global $location;
    return ($location == 'local');
}

//==============================================================================
