<?php
//==============================================================================
/*
This script is called to determine the used storage for those email mailboxes on
the current site which are listed for checking. It exits as an output string of
format:

<domain>+<mailbox>+<storage>+<domain>+<mailbox>+<storage> ...
*/
//==============================================================================

if ((PHP_SAPI !== 'cli') && (PHP_SAPI !== 'cli-fcgi')) {
    exit("Script valid in command line mode only\n");
}
elseif (substr(__DIR__,0,5) != '/home') {
    exit("Script valid on online server only\n");
}
// Parent directory hierarchy: => bash => common_scripts => public_html
require(__DIR__."/../../../path_defs.php");
$mailbox_data = '';
if ((function_exists('online_itservices_db_connect')) && ($db = itservices_db_connect())) {
    $site_path = $argv[1];
    $where_clause = 'site_path=?';
    $where_values = ['s',$site_path];
    $add_clause = 'ORDER BY domain ASC, mailbox ASC';
    $query_result = mysqli_select_query($db,'email_storage','*',$where_clause,$where_values,$add_clause);
    while ($row = mysqli_fetch_assoc($query_result)) {
        $content = file("$root_dir/mail/{$row['domain']}/{$row['mailbox']}/dovecot-quota");
        if (empty($content)) {
            print("Email account {$row['mailbox']}@{$row['domain']} not found\n");
        }
        else {
            // Add the domain, mailbox and used storage to the mailbox data string,
            // separated with the delimeter '+'.
            $last_line = $content[count($content) -1];
            $used_storage = trim($last_line);
            $mailbox_data .= "{$row['domain']}+{$row['mailbox']}+{$used_storage}+";
        }
    }
    $mailbox_data = rtrim($mailbox_data,'+');
}
exit($mailbox_data);

//==============================================================================
