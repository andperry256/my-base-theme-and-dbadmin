<?php
//==============================================================================

if (!isset($argc)) {
    exit("Script allowed in command line mode only\n");
}
$tok1 = strtok(__DIR__,'/');
$tok2 = strtok('/');
$tok3 = strtok('/');
$root_dir = "/$tok1/$tok2";
if ($tok3 != 'public_html') {
    // Extra directory level in special cases
    $root_dir .= "/$tok3";
}
$domain = $argv[1];
$mailbox_data = '';
require("$root_dir/public_html/path_defs.php");
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
