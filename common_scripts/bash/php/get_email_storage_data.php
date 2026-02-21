<?php
//==============================================================================

if (!isset($argc)) {
    exit("Script valid in command line mode only\n");
}
elseif (substr(__DIR__,0,5) != '/home') {
    exit("Script valid on online server only\n");
}
require(__DIR__."/../../../path_defs.php");
$domain = $argv[1];
$mailbox_list = explode('+',$argv[2]);
$mailbox_data = '';
foreach ($mailbox_list as $mailbox) {
    $content = file("$root_dir/mail/$domain/$mailbox/dovecot-quota");
    if (empty($content)) {
        print("Email account $mailbox@$domain not found\n");
    }
    else {
        $last_line = $content[count($content) -1];
        $used_storage = trim($last_line);
        $mailbox_data .= $mailbox.'+'.$used_storage.'+';
    }
}
$mailbox_data = rtrim($mailbox_data,'+');
exit($mailbox_data);

//==============================================================================
