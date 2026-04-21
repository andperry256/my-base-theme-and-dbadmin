<?php
//==============================================================================
// DB admin script for table network_scan_results. This script is used across
// more than one site, and is included by the script
// table_network_sca_results.php for the given site.
//==============================================================================

class tables_network_scan_results
{
    function afterSave($record)
    {
        $db = admin_db_connect();
        date_default_timezone_set('Europe/London');
        $ip_element_4 = $record->FieldVal('ip_element_4');
        $last_seen_time = $record->FieldVal('last_seen_time');
        $last_seen_datetime = $record->FieldVal('last_seen_datetime');
        if ((empty($last_seen_time)) && (!empty($last_seen_datetime))) {
            // Set timestamp to match date & time
            $fields = 'last_seen_time';
            $values = ['i',strtotime($last_seen_datetime)];
        }
        else {
            // Set date & time to match timestamp
            $fields = 'last_seen_datetime';
            $values = ['s',date('Y-m-d H:i',$last_seen_time)];
        }
        $where_clause = 'ip_element_4=?';
        $where_values = ['i',$ip_element_4];
        mysqli_update_query($db,'network_scan_results',$fields,$values,$where_clause,$where_values);
    }
}

//==============================================================================
