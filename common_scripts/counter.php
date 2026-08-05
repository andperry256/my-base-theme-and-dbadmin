<?php
//==============================================================================

require_once(__DIR__.'/get_local_site_dir.php');
require_once("$base_dir/path_defs.php");
require_once("$base_dir/wp-content/themes/my-base-theme/shared_functions.php");
$db = db_connect(WP_DBID);

$end_year = current_counter_year(WP_DBID);
$today_date = date('Y-m-d');
$today_count = mysqli_num_rows(mysqli_query($db,"SELECT * FROM counter_hits WHERE date='$today_date'"));
$counter_value = ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM counter_info WHERE id='{$end_year}_count'")))
    ? $row['value']
    : null;
$daily_average = ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM counter_info WHERE id='{$end_year}_daily'")))
    ? $row['value']
    : null;
$start_date = ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM counter_info WHERE id='{$end_year}_start'")))
    ? $row['value']
    : null;
$start_date = date('d M Y',strtotime($start_date));
$start_date = str_replace(' ','&nbsp;',$start_date);
print("<p style=\"line-height:1.8em\">");
print("Count: &nbsp;$counter_value<br />");
print("Since: &nbsp;$start_date<br />");
print(sprintf("Daily: &nbsp;%01.1f<br />",$daily_average));
print("Today: &nbsp;$today_count</p>\n");

//==============================================================================
