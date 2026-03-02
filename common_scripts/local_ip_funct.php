<?php
//==============================================================================
if (!defined('LOCAL_IP_FUNCT_DEFINED')):
//==============================================================================
/*
Function is_local_ip

This function is used to determine whether the remote IP address is on the same
local network as the site.
*/
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
/*
Function is_home_local_ip

This function is similar to 'is_local_ip', except that it also allows for the
remote IP address to be the external IP of the 'Home' network. This is useful
for allowing access from mobile devices on the local network, which would appear
on the external IP if access is made via an internet-based domain name.
*/
//==============================================================================

function is_home_local_ip($ip_addr)
{
    return (defined('HOME_IP_ADDR'))
        ? ((is_local_ip($ip_addr)) || ($ip_addr == HOME_IP_ADDR))
        : false;
}

//==============================================================================
/*
Function get_local_server_ip

This function is used to obtain a local IP address for a local server, given a
domain name or host name.
*/
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
/*
Function is_ip_authorised

This function determines whether there is an active authorised connection from
a given remote IP address, even if it is not within the current session. This
is useful when embedding scripts inside an <iframe>.
*/
//==============================================================================

function is_ip_authorised($ip_addr,$access_level=1)
{
    $db = dbconnect(ADMIN_DBID);
    $where_clause = 'remote_addr=? AND access_level>=?';
    $where_values = ['s',$ip_addr,'i',$access_level];
    return (mysqli_num_rows(mysqli_select_query($db,'login_sessions','*',$where_clause,$where_values,'')) > 0);
}

//==============================================================================
define( 'LOCAL_IP_FUNCT_DEFINED', true );
endif;
//==============================================================================
