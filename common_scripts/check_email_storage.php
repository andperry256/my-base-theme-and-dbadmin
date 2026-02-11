<?php
//==============================================================================

if (!isset($argc)) {
    exit("Script valid in command line mode only\n");
}
elseif (substr(__DIR__,0,5) != '/home') {
    exit("Script valid on online server only\n");
}
require(__DIR__.'/get_local_site_dir.php');
$domain = $argv[1];
$mailbox_data = '';
$mailbox = strtok($argv[2],'+');
while ($mailbox !== false) {
    $content = file("$root_dir/mail/$domain/$mailbox/dovecot-quota");
    if (empty($content)) {
        print("Email account $mailbox@$domain not found\n");
    }
    else {
        $last_line = $content[count($content) -1];
        $used_storage = trim($last_line);
        $mailbox_data .= $mailbox.'+'.$used_storage.'+';
    }
    $mailbox = strtok('+');
}
$mailbox_data = rtrim($mailbox_data,'+');
$date_and_time = date('YmdHis');
$temp = get_url_content("https://remote.andperry.com/report_email_storage.php?domain=$domain&mailbox_data=$mailbox_data&datetime=$date_and_time");
print($temp);

//==============================================================================
