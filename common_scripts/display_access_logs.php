<html><head>
<script>
	function selectFile(dropdown,site,mode)
	{
		var option_value = dropdown.options[dropdown.selectedIndex].value;
		location.href = './display_access_logs.php?site=' + site + '&file=' + encodeURIComponent(option_value) + '&mode=' + mode;
	}
</script>
<style>
	html {
		font-size: 16px;
		font-family: Arial,Helvetica,Sans-serif;
	}
	table {
	  border-spacing: 0;
	  border-collapse: collapse;
	}
	td {
		padding: 0.7em;
		border: solid 1px #ccc;
	}
	a:link,
	a:visited {
		color: steelblue;
	}
</style>
</head>
<body>
<?php
//==============================================================================

require("allowed_hosts.php");
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (substr($_SERVER['REMOTE_ADDR'],0,8) != '192.168.'))
{
	exit("Authentication failure");
}
if (isset($_GET['site']))
{
	$local_site_dir = $_GET['site'];
}
if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php"))
{
	require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
else
{
	exit("Path definitions script not found");
}
if (!isset($local_site_dir))
{
	exit("Site not specified");
}
if ((!isset($AccessLogsDir)) || (!is_dir($AccessLogsDir)))
{
	exit("Access log directory not found");
}

require("date_funct.php");
$today_date = date('Y-m-d');
if (isset($_GET['file']))
{
	$current_file = $_GET['file'];
}
else
{
	$current_file = "$today_date.log";
}
if (isset($_GET['mode']))
{
	$display_mode = $_GET['mode'];
}
else
{
	$display_mode = "count_summary";
}
print("<h1>Display Access Logs</h1>\n");

// Output the date selector
$files = scandir($AccessLogsDir);
arsort($files);
$select1 = "<select name=\"file1\" onchange=\"selectFile(this,'$local_site_dir','count_summary')\">\n";
$first_item_processed = false;
foreach($files as $file)
{
	$fileext = strtolower(pathinfo($file,PATHINFO_EXTENSION));
	if ($fileext == 'log')
	{
		$date = substr($file,0,10);
		if ((!$first_item_processed) && ($date != $today_date))
		{
			$select1 .= "<option value=\"mail-$today_date.log\" selected>Today</option>\n";
		}
		if ($date == $today_date)
		{
			$description = 'Today';
		}
		else
		{
			$description = title_date($date);
		}
		$select1 .= "<option value=\"$file\"";
		if ($file == $current_file)
		{
			$select1 .= " selected";
		}
		$select1 .= ">$description</option>\n";
	}
	$first_item_processed = true;
}
$select1 .= "</select>\n";
$select2 = str_replace('$file1','$file2',$select1);
$select2 = str_replace('count_summary','full_log',$select2);
print("<table>\n");
print("<tr><td>Count Summary:</td><td>$select1</td>");
print("<td><a href=\"$BaseURL/common_scripts/display_access_logs.php?site=$local_site_dir&file=$current_file&mode=count_summary\">Select</a></td></tr>\n");
print("<tr><td>Full Log:</td><td>$select2</td>");
print("<td><a href=\"$BaseURL/common_scripts/display_access_logs.php?site=$local_site_dir&file=$current_file&mode=full_log\">Select</a></td></tr>\n");
print("</table>\n");

// Output the access data for the given date
$date = substr($current_file,0,10);
print("<h2>".long_title_date($date)."</h2>\n");
$content = file("$AccessLogsDir/$current_file");

if ($display_mode == 'count_summary')
{
	$page_accesses = array();
	$page_counts = array();
	$output_list = array();

	// Record each IP/page combination as a unique page access
	foreach($content as $line)
	{
		$ip = substr($line,22,15);
		$page = strtok(substr($line,38),' ');
		$page_accesses["$ip $page"] = true;
		$page_counts[$page] = 0;
	}

	// Count the accesses for each page address
	foreach ($page_accesses as $key => $value)
	{
		$page = substr($key,16);
		$page_counts[$page]++;
	}

	// Organise the data as a 2-dimensional array, grouping first by count
	foreach ($page_counts as $page => $count)
	{
		if (!isset($output_list[$count]))
		{
			$output_list[$count] = array();
		}
		$output_list[$count][$page] = true;
	}

	// Output the data - ordered by count descending then page address ascending
	krsort($output_list);
	print("<table>\n");
	foreach ($output_list as $count => $data)
	{
		ksort($data);
		foreach($data as $page => $value)
		{
			if (substr($page,0,2) != '/.')
			{
				print("<tr><td>$page</td><td>$count</td></tr style=\"align:right\">\n");
			}
		}
	}
	print("</table>\n");
}
else
{
	print("<table>\n");
	foreach($content as $line)
	{
		print("<tr><td>$line</td></tr>\n");
	}
	print("</table>\n");
}

//==============================================================================
?>
</body></html>
