<?php
//==============================================================================
// DB admin script for table home_dhcp_settings. This script is used across more
// than one site, and is included by the script table_home_dhcp_settings.php for
// the given site.
//==============================================================================

class tables_home_dhcp_settings
{
    function format_mac_address($mac_address)
    {
        if ($mac_address == '0') {
            return '00:00:00:00:00:00';
        }
        else {
            $mac_address_1 = trim($mac_address);
            $mac_address_1 = str_replace('-','',$mac_address_1);
            $mac_address_1 = str_replace(':','',$mac_address_1);
            $mac_address_1 = strtoupper($mac_address_1);
            $mac_address_2 = substr($mac_address_1,0,2);
            for ($i=2; $i<=10; $i+=2) {
                $mac_address_2 .= ':'.substr($mac_address_1,$i,2);
            }
            return $mac_address_2;
        }
    }

    function ip_element_4__validate($record,$value)
    {
        $db = admin_db_connect();
        if ((!is_numeric($value)) || ($value < 1) || ($value > 256)) {
            return report_error("Invalid IP address element.");
        }
        else {
            $where_clause = 'ip_element_4=? AND ip_element_4<255';
            $where_values = ['i',$value];
            $old_value = $record->OldPKVal('ip_element_4');
            if (!empty($old_value)) {
                $where_clause .= ' AND ip_element_4<>?';
                $where_values = array_merge($where_values,['i',$old_value]);
            }
            if (mysqli_num_rows(mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,'')) > 0) {
                return report_error("Duplicate hostname.");
            }
            else {
                return true;
            }
        }
    }

    function hostname__validate($record,$value)
    {
        $db = admin_db_connect();
        $ip_element_4 = $record->FieldVal('ip_element_4');
        $old_ip_element_4 = $record->OldPKVal('ip_element_4');
        $where_clause = 'ip_element_4<>? AND hostname=?';
        $where_values = ['i',$ip_element_4,'s',$value];
        if (!empty($old_ip_element_4)) {
            $where_clause .= ' AND ip_element_4<>?';
            $where_values = array_merge($where_values,['s',$old_ip_element_4]);
        }
        if (mysqli_num_rows(mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,'')) > 0) {
            return report_error("Duplicate hostname.");
        }
        else {
            return true;
        }
    }

    function mac_address__validate($record,$value)
    {
        $db = admin_db_connect();
        $value = preg_replace('/[^A-Fa-f0-9]/','',$value);
        $ip_element_4 = $record->FieldVal('ip_element_4');
        $old_ip_element_4 = $record->OldPKVal('ip_element_4');
        $hostname = $record->FieldVal('hostname');
        $old_hostname = $record->OldPKVal('hostname');
        if ($value == 0) {
            return true;
        }
        elseif (strlen($value) != 12) {
            return report_error("Invalid MAC address.");
        }
        else {
            $mac_address = $this->format_mac_address($value);
            $where_clause = '(ip_element_4<>? OR hostname<>?) AND mac_address=?';
            $where_values = ['i',$ip_element_4,'s',$hostname,'s',$mac_address];
            if (!empty($old_ip_element_4)) {
                $where_clause .= ' AND (ip_element_4<>? OR hostname<>?)';
                $where_values = array_merge($where_values,['s',$old_ip_element_4,'s',$old_hostname]);
            }
            if (($mac_address != '00:00:00:00:00:00') && (mysqli_num_rows(mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,'')) > 0)) {
                return report_error("Duplicate MAC address.");
            }
            else {
                return true;
            }
        }
    }

    function afterDelete($record)
    {
        global $mirror_hostname;
        if (!empty($mirror_hostname)) {
            $dbname = admin_db_name();
            $mirror_dbname = 'local'.substr($dbname,strpos($dbname,'_'));
            if ($db2 = mysqli_connect($mirror_hostname,REAL_DB_USER,REAL_DB_PASSWD,$mirror_dbname)) {

                // Delete mirror record
                $where_clause = 'ip_element_4=? AND hostname=?';
                $where_values = ['i',$record->FieldVal('ip_element_4'),'s',$record->FieldVal('hostname')];
                mysqli_delete_query($db2,'home_dhcp_settings',$where_clause,$where_values);
            }
        }
    }

    function afterSave($record)
    {
        global $mirror_hostname;
        $db = admin_db_connect();
        $ip_element_4 = $record->FieldVal('ip_element_4');
        $hostname = $record->FieldVal('hostname');
        $vendor = $record->FieldVal('vendor');
        $friendly_name = $record->FieldVal('friendly_name');
        if (empty($friendly_name)) {
            $friendly_name = $hostname;
        }
        $mac_address = $this->format_mac_address($record->FieldVal('mac_address'));
        $set_fields = 'mac_address,friendly_name';
        $set_values = ['s',$mac_address,'s',$friendly_name];
        $where_clause = 'ip_element_4=? AND hostname=?';
        $where_values = ['i',$ip_element_4,'s',$hostname];
        mysqli_update_query($db,'home_dhcp_settings',$set_fields,$set_values,$where_clause,$where_values);

        if (!empty($mirror_hostname)) {
            $dbname = admin_db_name();
            $mirror_dbname = 'local'.substr($dbname,strpos($dbname,'_'));
            if ($db2 = mysqli_connect($mirror_hostname,REAL_DB_USER,REAL_DB_PASSWD,$mirror_dbname)) {

                // Insert mirror record if required
                $fields = 'ip_element_4,mac_address,hostname';
                $values = ['i',$ip_element_4,'s',$mac_address,'s',$hostname];
                $where_clause = 'ip_element_4=? AND hostname=?';
                $where_values = ['i',$ip_element_4,'s',$hostname];
                mysqli_conditional_insert_query($db2,'home_dhcp_settings',$fields,$values,$where_clause,$where_values);

                // Update mirror record
                $fields = 'mac_address,friendly_name';
                $values = ['s',$mac_address,'s',$friendly_name];
                if (!empty($vendor)) {
                    $fields .= ',vendor';
                    $values = array_merge($values,['s',$vendor]);
                }
                mysqli_update_query($db2,'home_dhcp_settings',$fields,$values,$where_clause,$where_values);

                if (($record->OldPKVal('ip_element_4') != $ip_element_4) || ($record->OldPKVal('hostname') != $hostname)) {

                    // Delete old mirror record
                    $where_clause = 'ip_element_4=? AND hostname=?';
                    $where_values = ['i',$record->OldPKVal('ip_element_4'),'s',$record->OldPKVal('hostname')];
                    mysqli_delete_query($db2,'home_dhcp_settings',$where_clause,$where_values);
                }
            }
        }
    }
}

//==============================================================================
