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
endif;
//==============================================================================
