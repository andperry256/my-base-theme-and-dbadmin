<?php
//================================================================================
/*
This script should be invoked wherever a session_start() is required. It initially
checks for an existing session and sent headers, to eliminate unwanted PHP
warnings. For an online site, it customises the directory path for the session
data.
*/
//================================================================================

if ((!session_id()) && (!headers_sent())) {
    $elements = explode('/',trim(__DIR__,'/'));
    if ($elements[0] == 'home') {
        // Online server - set custom path for session data.
        $php_version = explode('.',phpversion());
        $session_dir_1 = "/{$elements[0]}/{$elements[1]}/session_data";
        $session_dir_2 = "$session_dir_1/php{$php_version[0]}{$php_version[1]}";
        if (!is_dir($session_dir_2)) {
            if (!is_dir($session_dir_1)) {
                mkdir($session_dir_1,0775);
            }
            mkdir($session_dir_2,0775);
        }
        ini_set('session.save_path',$session_dir_2);
    }
    session_start();
}
if (!session_id()) {
    // This should not occur
    exit ("ERROR - Unable to start session");
}

//================================================================================
