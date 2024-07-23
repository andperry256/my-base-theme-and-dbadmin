<?php
//==============================================================================

if (session_status() ==  PHP_SESSION_NONE)
{
    session_start();
}
$username = get_session_var(SV_USER);
put_user('');
if ($location == 'local')
{
    // Re-instate the $_SESSION[SV_USER] variable. This enables the user to be kept
    // logged off, which may occasionally be required for testing purposes.
    update_session_var(SV_USER,$username);
}
header("Location: $base_url");
exit;

//==============================================================================
?>
