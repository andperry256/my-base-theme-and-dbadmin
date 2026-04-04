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

    function afterSave($record)
    {
        $db = admin_db_connect();
        $ip_element_4 = $record->FieldVal('ip_element_4');
        $hostname = $record->FieldVal('hostname');
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
    }
}

//==============================================================================
