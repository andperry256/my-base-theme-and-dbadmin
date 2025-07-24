<?php
//================================================================================
/*
This script is called to update the DHCP configuration data for the various
possible DHCP servers.

On a local server, it is normally called as part of the procedure performed by
the 'update_local_hosts' cron job.

On an online server, it would normally be called with the 'php' command as a
standalone cron job.

A single command line parameter (local server only) specifies the local site
directory for the main server site:
    andperry.com on Server1
    longcroft on LC-Server1
*/
//================================================================================

if (is_file("/Config/linux_pathdefs.php"))
{
    // Local Server
    $local_site_dir = $argv[1];
    require_once("/Config/linux_pathdefs.php");
    if (!is_dir("$www_root_dir/Sites/$local_site_dir/public_html"))
    {
        exit("Local site directory not found.\n");
    }
    require_once("$www_root_dir/Sites/$local_site_dir/public_html/path_defs.php");
    $source_dir = "/media/Data/Links/Linux_Config/";
    $target_dir = "/home/andrew/Linux_Config/dhcp";
}
else
{
    require_once(__DIR__."/../path_defs.php");
    $source_dir = "$root_dir/dhcp";
    $target_dir = "$root_dir/dhcp";
}
$db = itservices_db_connect();

// ###### Configuration for ISC DHCP Server ######
$content1 = file("$source_dir/dhcpd_active.conf");
$content1 = str_replace('{subnet}',$ip_subnet_addr,$content1);
$content1 = str_replace('{router}',$router_node,$content1);
$content1 = str_replace('{dhcp_start}',$dhcp_start_node,$content1);
$content1 = str_replace('{dhcp_end}',$dhcp_end_node,$content1);
$content2 = '';
foreach ($content1 as $line)
{
    if (strpos($line,'#### ADD FIXED') !== false)
    {
        $where_clause = "ip_element_4>=1 AND ip_element_4<=254";
        $where_values = [];
        $add_clause = 'ORDER BY ip_element_4 ASC';
        $query_result = mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $content2 .= "  host {$row['hostname']} {\n";
            $content2 .= "    hardware ethernet {$row['mac_address']};\n";
            $content2 .= "    fixed-address $ip_subnet_addr.{$row['ip_element_4']};\n";
            $content2 .= "  }\n";
        }
    }
    else
    {
        $content2 .= $line;
    }
}
file_put_contents("$target_dir/dhcpd.conf",$content2);

// ###### Configuration for Kea Server ######
$content1 = file("$source_dir/kea-dhcp4_active.conf");
$content1 = str_replace('{interface}',$local_ethernet_if,$content1);
$content1 = str_replace('{subnet}',$ip_subnet_addr,$content1);
$content1 = str_replace('{router}',$router_node,$content1);
$content1 = str_replace('{dhcp_start}',$dhcp_start_node,$content1);
$content1 = str_replace('{dhcp_end}',$dhcp_end_node,$content1);
$content2 = '';
foreach ($content1 as $line)
{
    if (strpos($line,'#### ADD FIXED') !== false)
    {
        $where_clause = "ip_element_4>=1 AND ip_element_4<=254";
        $where_values = [];
        $add_clause = 'ORDER BY ip_element_4 ASC';
        $query_result = mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $content2 .= "          {\n";
            $content2 .= "            // {$row['hostname']}\n";
            $content2 .= "            \"hw-address\": \"{$row['mac_address']}\",\n";
            $content2 .= "            \"ip-address\": \"$ip_subnet_addr.{$row['ip_element_4']}\"\n";
            $content2 .= "          },\n";
        }
        $content2 = rtrim($content2,",\n")."\n";
    }
    else
    {
        $content2 .= $line;
    }
}
file_put_contents("$target_dir/kea-dhcp4.conf",$content2);

// ###### Configuration for Dnsmasq ######
$content1 = file("$source_dir/dnsmasq_active.conf");
$content1 = str_replace('{interface}',$local_ethernet_if,$content1);
$content1 = str_replace('{subnet}',$ip_subnet_addr,$content1);
$content1 = str_replace('{router}',$router_node,$content1);
$content1 = str_replace('{dhcp_start}',$dhcp_start_node,$content1);
$content1 = str_replace('{dhcp_end}',$dhcp_end_node,$content1);
$content2 = '';
foreach ($content1 as $line)
{
    if (strpos($line,'#### DHCP RANGE') !== false)
    {
        $content2 .= "dhcp-range=$ip_subnet_addr.$dhcp_start_node,$ip_subnet_addr.$dhcp_end_node,3h\n";
    }
    elseif (strpos($line,'#### ADD FIXED') !== false)
    {
        $where_clause = "ip_element_4>=1 AND ip_element_4<=254";
        $where_values = [];
        $add_clause = 'ORDER BY ip_element_4 ASC';
        $query_result = mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            $content2 .= "dhcp-host={$row['mac_address']},{$row['hostname']},$ip_subnet_addr.{$row['ip_element_4']}\n";
        }
    }
    else
    {
        $content2 .= $line;
    }
}
file_put_contents("$target_dir/dnsmasq.conf",$content2);

//==============================================================================
?>
