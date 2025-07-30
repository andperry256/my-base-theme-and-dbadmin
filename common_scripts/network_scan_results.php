<?php
//==============================================================================

$local_site_dir = strtok(substr($_SERVER['REQUEST_URI'],1),'/');
require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require("$base_dir/non_wp_header.php");
$db = local_itservices_db_connect();

$mac_addresses = [];
$vendors = [];
$content = file_get_contents($network_scan_output);

// Loop through all host specifications in the XML script
$pos1 = strpos($content,'<host>');
while ($pos1 !== false) {
    // Extract data for given host
    $pos2 = strpos($content,'</host>',$pos1);
    $pos3 = strpos($content,'<',$pos2);
    $host_data = explode("\n",substr($content,$pos1,$pos3-$pos1));
    $up = false;
    $ip_address = '';
    $mac_address = '';
    $vendor = '';
    foreach($host_data as $line) {
        if (strpos($line,'status state="up"') !== false) {
            $up = true;
        }
        elseif (strpos($line,'addrtype="ipv4"') !== false) {
            $pos4 = strpos($line,'addr=') + 6;
            $pos5 = strpos($line,'"',$pos4);
            $ip_address = substr($line,$pos4,$pos5-$pos4);
        }
        elseif (strpos($line,'addrtype="mac"') !== false) {
            $pos4 = strpos($line,'addr=') + 6;
            $pos5 = strpos($line,'"',$pos4);
            $mac_address = substr($line,$pos4,$pos5-$pos4);
            if (strpos($line,'vendor=') !== false) {
                $pos4 = strpos($line,'vendor=') + 8;
                $pos5 = strpos($line,'"',$pos4);
                $vendor = substr($line,$pos4,$pos5-$pos4);
            }
        }
        if (($up) && (!empty($ip_address))) {
            $mac_addresses[$ip_address] = $mac_address;
            $vendors[$ip_address] = $vendor;
        }
    }
    $pos1 = strpos($content,'<host>',$pos3);
}

print("<table>\n");
print("<tr><td>IP Address</td><td>Name</td><td>MAC Address</td><td>Vendor</td></tr>\n");

// Loop through all possible IP addresses on the network
for ($sub_addr=1; $sub_addr<=254; $sub_addr++) {
    $ip_address = "$ip_subnet_addr.$sub_addr";
    if (!empty($mac_addresses[$ip_address])) {
        // IP address found on scan (online)
        $mac_address = $mac_addresses[$ip_address];
        $where_clause = 'mac_address=?';
        $where_values = ['s',$mac_addresses[$ip_address]];
        if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,''))) {
            // Device matches item in DHCP settings table
            $name = (empty($row['friendly_name'])) ? $row['hostname'] : $row['friendly_name'];
        }
        else {
            // No match in DHCP settings table
            $name = '';
        }
        $vendor = $vendors[$ip_address];
        $status = 'online';
    }
    elseif (($where_clause = 'ip_element_4=?') &&
            ($where_values = ['i',$sub_addr]) &&
      ($row = mysqli_fetch_assoc(mysqli_select_query($db,'home_dhcp_settings','*',$where_clause,$where_values,'')))) {
  // Item in DHCP settings table not found on scan (offline)
  $mac_address = $row['mac_address'];
  $name = (empty($row['friendly_name'])) ? $row['hostname'] : $row['friendly_name'];
  $vendor = '';
  $status = 'offline';
    }
    else {
  $status = '';
    }
    if (!empty($status)) {
  print("<tr><td class=\"td-$status\">$ip_address</td><td class=\"td-$status\">$name</td><td class=\"td-$status\">$mac_address</td><td class=\"td-$status\">$vendor</td></tr>");
        print("\n");
    }
}

print("</table>\n");

//==============================================================================
?>
