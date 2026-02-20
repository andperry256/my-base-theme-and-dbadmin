<?php
//==============================================================================
/*
This script is called by each server station on a cron job to say that it is
live and to register its remote IP address.

When called from andperry.com it also checks all web sites registered for
checking.

The timeouts for no activity are specified in the DB tables for each server
station and web site and should be set so that a failure is not reported until
two consecutive updates have missed.
*/
//==============================================================================

namespace MyBaseProject;
use PHPMailer\PHPMailer\PHPMailer;
global $mail_host;

if (!isset($argc)) {
    exit("Script valid in command line mode only\n");
}
require(__DIR__."/../path_defs.php");
if ($location == 'local') {
    exit("Script valid on online server only\n");
}
if (!empty($php_mailer_dir)) {
    include("$base_dir/common_scripts/mail_funct.php");
}

$db = online_itservices_db_connect();
$station_id = $argv[1] ?? null;
$ip_addr = $argv[2] ?? null;
if (empty($station_id)) {
    exit("Station ID not specified\n");
}
elseif (empty($ip_addr)) {
    exit("Server IP not specified\n");
}
$current_time = time();
$current_datetime = date('Y-m-d H:i:s',$current_time);
$where_clause = 'station_id=?';
$where_values = ['s',$station_id];
if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'server_live_updates','*',$where_clause,$where_values,''))) {
    // Update details in table for the given station ID
    $set_fields = 'ip_addr,last_access_time,last_access_datetime';
    $set_values = ['s',$ip_addr,'i',$current_time,'s',$current_datetime];
    $where_clause = "station_id=? AND (last_access_datetime IS NULL OR last_access_datetime NOT LIKE '****%')";
    $where_values = ['s',$station_id];
    mysqli_update_query($db,'server_live_updates',$set_fields,$set_values,$where_clause,$where_values);
    print("Remote IP address set for $station_id\n");
}

/*
Check for inactive stations and sites that fail to load.
Do this when the script is being run for station 'andperry.com' as the cron
for this is running on the same server as this script itself and can therefore
be reasonably assumed as operational.
*/
if ($station_id == 'andperry.com') {
    // Check for inactive stations
    $where_clause = 'reported=0';
    $where_values = [];
    $query_result = mysqli_select_query($db,'server_live_updates','*',$where_clause,$where_values,'');
    while ($row = mysqli_fetch_assoc($query_result)) {
        $station_id = $row['station_id'];
        if ((!empty($row['last_access_time'])) && ((time() - $row['last_access_time']) > $row['access_timeout'])) {
            // Generate email alert
            $message_info = [];
            $message_info['subject'] = "No remote IP update for $station_id";
            $message_info['html_content'] = "Recorded at $current_datetime:-\n";
            $message_info['html_content'] .= "Server station <em>$station_id</em> was last updated at {$row['last_access_datetime']}\n";
            $message_info['message_id'] = 0;
            $message_info['from_addr'] = SEND_ONLY_EMAIL;
            $message_info['from_name'] = 'andperry.com';
            $message_info['to_addr'] = WEBMASTER_EMAIL;
            $result = output_mail($message_info,$mail_host);
            if ($result[0] == 0) {
                // Mark the record as reported
                $set_fields = 'last_access_time,reported';
                $set_values = ['i',0,'i',1];
                $where_clause = 'station_id=?';
                $where_values = ['s',$station_id];
                mysqli_update_query($db,'server_live_updates',$set_fields,$set_values,$where_clause,$where_values);
                print("Update alert generated for $station_id\n");
            }
            else {
                print("Failed to generate update alert for $station_id\n");
            }
        }
    }
}

//==============================================================================
