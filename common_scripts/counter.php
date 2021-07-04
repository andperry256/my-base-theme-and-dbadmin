<?php

	//==============================================================================
	/*
    The following variables are pre-defined as required:-

		$dbid (mandatory) - Database ID for the 'db_connect' function
    $DisplayCounter (optional) - Indicates that the counter is to be displayed
                                 on the page.
		$StandaloneCounter (optional) - Indicates that the counter is being
		                                displayed on the "web site index" page
																		rather than its native website.
		$MultilingualDates (optional) - Indicates that a multi-lingual version of
		                                the 'ShortMonthName' function is in use.
	*/
	//==============================================================================

	if (!function_exists('DayName'))
	{
		require("$BaseDir/common_scripts/date_funct.php");
	}
	if (!function_exists('db_connect'))
	{
		require("$PrivateScriptsDir/mysql_connect.php");
	}
	if (function_exists('db_connect_with_params'))
	{
		$db = db_connect_with_params($dbid,$DBMode,$Location);
	}
	else
	{
		$db = db_connect($dbid);
	}

	// Determine if the remote user is likely to be a search engine / robot.
	$bot_identifiers = array(
    'bot',
    'slurp',
    'crawler',
    'spider',
    'curl',
    'facebook',
    'fetch',
  );
	$is_bot = false;
	if (isset($_SERVER['HTTP_USER_AGENT']))
	{
		$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		foreach ($bot_identifiers as $identifier)
		{
	    if (strpos($user_agent, $identifier) !== FALSE)
			{
	      $is_bot = true;
				break;
	    }
	  }
	}

	// Determine the current counter year.
	$this_year = (int)date('Y');
	$this_month = (int)date('m');
	$query_result = mysqli_query($db,"SELECT * FROM counter_info WHERE id='start_month'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$start_month = (int)$row['value'];
		if ($this_month >= $start_month)
		{
			$start_year = $this_year;
		}
		else
		{
			$start_year = $this_year - 1;
		}
		if ($start_month == 1)
		{
			$end_year = $start_year;
		}
		else
		{
			$end_year = $start_year + 1;
		}
	}
	else
	{
		// This should not occur.
		exit;
	}

	// Check if a counter exists for the current year.
	$query_result = mysqli_query($db,"SELECT * FROM counter_info WHERE id='$end_year"."_count'");
	if (mysqli_num_rows($query_result) == 0)
	{
		// New counter year - add the required initialised records.
		mysqli_query($db,"INSERT INTO counter_info (id,value) VALUES ('$end_year"."_count','0')");
		mysqli_query($db,"INSERT INTO counter_info (id,value) VALUES ('$end_year"."_daily','0.0')");
		$start_date = $start_year.'-'.sprintf("%02d",$start_month).'-01';
		mysqli_query($db,"INSERT INTO counter_info (id,value) VALUES ('$end_year"."_start','$start_date')");
	}

	// Obtain the current counter start date.
	$query_result = mysqli_query($db,"SELECT * FROM counter_info WHERE id='$end_year"."_start'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$counter_start_date = $row['value'];
	}
	else
	{
		// This should not occur.
		exit;
	}

	$start_year = (int)substr($counter_start_date,0,4);
	$start_month = (int)substr($counter_start_date,5,2);
	$start_day = (int)substr($counter_start_date,8,2);
	$today_date = date('Y-m-d');

	// Read the counter for the current year.
	$query_result = mysqli_query($db,"SELECT * FROM counter_info WHERE id='$end_year"."_count'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$counter_val = (int)$row['value'];
	}
	else
	{
		// This should not occur.
		exit;
	}

	if (((!isset($StandaloneCounter)) || (!$StandaloneCounter)) && (!$is_bot))
	{
		$ip_addr = $_SERVER['REMOTE_ADDR'];
		mysqli_query($db,"DELETE FROM counter_hits WHERE date<'$today_date'");
		$query_result = mysqli_query($db,"SELECT * FROM counter_hits WHERE date='$today_date' AND ip_addr='$ip_addr'");
		if (mysqli_num_rows($query_result) == 0)
		{
			// First visit of the day for the given client, so update the counter
			$counter_val++;
			mysqli_query($db,"UPDATE counter_info SET value='$counter_val' WHERE id='$end_year"."_count'");
			mysqli_query($db,"INSERT INTO counter_hits VALUES('$today_date','$ip_addr',$counter_val)");
		}
		elseif ($row = mysqli_fetch_assoc($query_result))
		{
			// Client has already visited today.
			$own_counter = $row['count'];
		}
	}

	// Calculate and update the daily average for the current counting period.
	$start_date_offset = mktime(1,0,0,$start_month,$start_day,$start_year);
	$today_date_offset = mktime(1,0,0,(int)date('m'),(int)date('d'),(int)date('Y'));
	$days_counting = (($today_date_offset - $start_date_offset) / 86400) + 1;
	$daily_average = sprintf("%01.1f",$counter_val/$days_counting);
	mysqli_query($db,"UPDATE counter_info SET value='$daily_average' WHERE id='$end_year"."_daily'");

	if ((isset($StandaloneCounter)) && ($StandaloneCounter))
	{
		// Generate 'standalone counter' display (i.e. not on native web page).
		$query_result = mysqli_query($db,"SELECT * FROM counter_hits WHERE date='$today_date'");
		$today_count = mysqli_num_rows($query_result);
		print("<style>\n");
		print("td { font-size:12px; line-height:12px; font-family: Verdana, Arial, Helvetica, sans-serif; }");
		print("</style>\n");
		print("<table cellpadding=5>\n");
		print("<tr><td>Count:</td><td>$counter_val</td></tr>\n");
		print(sprintf("<tr><td>Since:</td><td>%02d %s $start_year</td></tr>\n",$start_day, ShortMonthName($start_month,'en')));
		print(sprintf("<tr><td>Daily:</td><td>%01.1f</td></tr>\n",$counter_val/$days_counting));
		print("<tr><td>Today:</td><td>$today_count</td></tr>\n");
		print("</table>\n");
	}
	else
	{
		if (($DisplayCounter) || (isset($_GET['showcount'])))
		{
			// Generate normal counter display (i.e. on native web page).
			print("You are visitor number ");
			if (isset($own_counter))
			{
				$counter_val = $own_counter;
			}
			print(sprintf("<span class=\"counter\">&nbsp;%05d&nbsp;</span>",$counter_val));
			if ((isset($MultilingualDates)) && ($MultilingualDates))
			{
				print(sprintf("<br />since %02d %s $start_year",$start_day, MonthName($start_month,'en')));
			}
			else
			{
				print(sprintf("<br />since %02d %s $start_year",$start_day, MonthName($start_month)));
			}
		}
		if (isset($_GET['showcount']))
		{
			// Add daily average to the display.
			print(sprintf("<br/>Average %01.1f counts per day",$counter_val/$days_counting));
		}
	}
?>
