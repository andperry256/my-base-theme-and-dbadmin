<?php
//==============================================================================

if (!function_exists('is_local_ip'))
{
    function is_local_ip($ip_addr)
    {
        global $ip_subnet_addr;
        if (!isset($ip_subnet_addr))
        {
            // This will be the case for an online web site.
            return false;
        }
        else
        {
            // Check if the first two elements of the IP address match those of
            // the subnet address defined for the local network.
            $tok1a = strtok($ip_addr,'.');
            $tok2a = strtok('.');
            $tok1b = strtok($ip_subnet_addr,'.');
            $tok2b = strtok('.');
            return (($tok1a == $tok1b) && ($tok2a == $tok2b));
        }
    }
}

//==============================================================================
?>
