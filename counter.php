<?php
	//==============================================================================
	//    The following variables are defined in path_defs.php:-
	//
	//    $CounterStartMonth (mandatory) - month (integer) in which new year starts.
	//    $CounterDir (mandatory) - path to directory containing counter file(s).
	//    $CounterStartDate (optional) - original start date of counter if part
	//                                   way through a normal counter year.
	//    $DisplayCounter (optional) - indicates if counter is to be displayed
	//                                 on page.
	//
	//    The variable $dbid must be defined in the calling script
	//==============================================================================

	// Determine if remote user is likely to be a search engine / robot
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

	if (!function_exists('DayName'))
	{
		require("$BaseDir/_link_to_common/date_funct.php");
	}
	if (!function_exists('db_connect'))
	{
		require("$PrivateScriptsDir/mysql_connect.php");
	}

	// Determine current counter year
	$year = (int)date('Y');
	$month = (int)date('m');
	if ($month >= $CounterStartMonth)
	{
		$start_year = $year;
	}
	else
	{
		$start_year = $year - 1;
	}
	if ($CounterStartMonth == 1)
	{
		$end_year = $start_year;
	}
	else
	{
		$end_year = $start_year + 1;
	}

	$counter_file = "$CounterDir/counter-$end_year.txt";
	$daily_average_file = "$CounterDir/daily-average-$end_year.txt";
	$today_date = date('Y-m-d');
	if (function_exists('db_connect_with_params'))
	{
		$db = db_connect_with_params($dbid,$DBMode,$Location);
	}
	else
	{
		$db = db_connect($dbid);
	}

	// Check if counter text file exists. If not create one and initialize it to zero.
	if (!is_dir($CounterDir))
	{
		mkdir($CounterDir);
	}
	if (!file_exists($counter_file))
	{
	  $f = fopen($counter_file, "w");
	  fwrite($f,"0");
	  fclose($f);
	}

	// Read the current value from the counter file
	$f = fopen($counter_file,"r");
	$counter_val = fread($f, filesize($counter_file));
	if (empty($counter_val))
	{
		$counter_val = 0;
	}
	fclose($f);

	if ((!isset($StandaloneCounter)) && (!$is_bot))
	{
		$ip_addr = $_SERVER['REMOTE_ADDR'];
		mysqli_query($db,"DELETE FROM counter_hits WHERE date<'$today_date'");
		$query_result = mysqli_query($db,"SELECT * FROM counter_hits WHERE date='$today_date' AND ip_addr='$ip_addr'");
		if (mysqli_num_rows($query_result) == 0)
		{
			$counter_val++;
			$f = fopen($counter_file, "w");
			fwrite($f, $counter_val);
			fclose($f);
			mysqli_query($db,"INSERT INTO counter_hits VALUES('$today_date','$ip_addr',$counter_val)");
		}
		elseif ($row = mysqli_fetch_assoc($query_result))
		{
			$own_counter = $row['count'];
		}
	}

	// Determine start date of current count period
	$year_start_date = sprintf("%04d-%02d-01",$start_year,$CounterStartMonth);
	if ((isset($CounterStartDate)) && ($CounterStartDate > $year_start_date))
	{
		$start_year = substr($CounterStartDate,0,4);
		$start_month = (int)substr($CounterStartDate,5,2);
		$start_day = (int)substr($CounterStartDate,8,2);
	}
	else
	{
		$start_year = $start_year;
		$start_month = $CounterStartMonth;
		$start_day = 1;
	}

	// Calculate daily average
	$start_date_offset = mktime(1,0,0,$start_month,$start_day,$start_year);
	$today_date_offset = mktime(1,0,0,(int)date('m'),(int)date('d'),(int)date('Y'));
	$days_counting = (($today_date_offset - $start_date_offset) / 86400) + 1;
	$daily_average = sprintf("%01.1f",$counter_val/$days_counting);
	$f = fopen($daily_average_file, "w");
	fwrite($f,"$daily_average");
	fclose($f);

	if (isset($StandaloneCounter))
	{
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
		if (((isset($DisplayCounter)) && ($DisplayCounter === true)) || (isset($_GET['showcount'])))
		{
			// Display counter on web page
			print("You are visitor number ");
			if (isset($own_counter))
			{
				$counter_val = $own_counter;
			}
			print(sprintf("<span class=\"counter\">&nbsp;%05d&nbsp;</span>",$counter_val));
			if (isset($MultilingualDates))
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
			// Display daily average on web page
			print(sprintf("<br/>Average %01.1f counts per day",$counter_val/$days_counting));
		}
	}
?>
