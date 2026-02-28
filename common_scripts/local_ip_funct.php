<?php
//==============================================================================
if (!function_exists('is_local_ip')):
//==============================================================================

function is_local_ip($ip_addr)
{
    global $ip_subnet_addr;
    if (!isset($ip_subnet_addr)) {
        // This will be the case for an online web site.
        return false;
    }
    else {
        // Check if the first two elements of the IP address match those of
        // the subnet address defined for the local network.
        $ip_elements = explode('.',$ip_addr);
        $ip_subnet_elements = explode('.',$ip_subnet_addr);
        return (($ip_elements[0] == $ip_subnet_elements[0]) && ($ip_elements[1] == $ip_subnet_elements[1]));
    }
}

//==============================================================================

function is_home_local_ip($ip_addr)
{
    return (defined('HOME_IP_ADDR'))
        ? ((is_local_ip($ip_addr)) || ($ip_addr == HOME_IP_ADDR))
        : false;
}

//==============================================================================

function get_local_server_ip($hostname)
{
    if (is_file('/Config/location.php')) {
        include('/Config/location.php');
    }
    else {
        return null;
    }
    $db = itservices_db_connect();
    $where_clause = 'source=?';
    $where_values = ['s',$hostname];
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'server_aliases','*',$where_clause,$where_values,''))) {
        $hostname = $row['target'];
    }
    $where_clause = 'location=? AND computer_name=?';
    $where_values = ['s',$my_location,'s',$hostname];
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'computer_addresses','*',$where_clause,$where_values,''))) {
        return ($row['address']);
    }
    else {
        return null;
    }
}

//==============================================================================
endif;
//==============================================================================
