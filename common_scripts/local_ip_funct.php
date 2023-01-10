<?php
//==============================================================================

if (!function_exists('is_local_ip'))
{
	function is_local_ip($ip_addr)
	{
		global $IP_Subnet_Addr;
		if (!isset($IP_Subnet_Addr))
		{
			return false;
		}
		else
		{
			$tok1a = strtok($ip_addr,'.');
			$tok2a = strtok('.');
			$tok1b = strtok($IP_Subnet_Addr,'.');
			$tok2b = strtok('.');
			return (($tok1a == $tok1b) && ($tok2a == $tok2b));
		}
	}
}

//==============================================================================
?>
