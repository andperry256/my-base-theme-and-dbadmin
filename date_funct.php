<?php
//==============================================================================

if (!defined('TODAY_DATE'))
{
	DEFINE ('TODAY_DATE',date('Y-m-d'));
}
global $BaseDir;
if (!isset($BaseDir))
{
	// Why are we doing this????
	// $local_site_dir = 'andperry.co.uk';
	// $NoAuth = true;
	// require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}

if (is_file("alt_date_funct.php"))
	require("alt_date_funct.php");
elseif (is_file("../alt_date_funct.php"))
	require("../alt_date_funct.php");
elseif (is_file("$BaseDir/alt_date_funct.php"))
	require("$BaseDir/alt_date_funct.php");

//==============================================================================

if (!function_exists('DayName'))
{
	function DayName($day)
	{
		$Name = array("Sunday","Monday","Tuesday","Wednesday",
					  "Thursday","Friday","Saturday");
		return $Name[$day];
	}
}

//==============================================================================

if (!function_exists('ShortDayName'))
{
	function ShortDayName($day)
	{
		$Name = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
		return $Name[$day];
	}
}

//==============================================================================

if (!function_exists('DayNumber'))
{
	function DayNumber($Day)
	{
		$Day = strtolower($Day);
		$ShortName = array("sun" => 0, "mon" => 1, "tue" => 2, "wed" => 3,
		                   "thu" => 4, "fri" => 5, "sat" => 6);
		$LongName = array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3,
		                  "thursday" => 4, "friday" => 5, "saturday" => 6);
		if (isset($ShortName[$Day]))
			return $ShortName[$Day];
		elseif (isset($LongName[$Day]))
			return $LongName[$Day];
		else
			return -1;
	}
}

//==============================================================================

if (!function_exists('MonthName'))
{
	function MonthName($month)
	{
		$Name = array("","January","February","March","April",
						 "May","June","July","August","September",
						 "October","November","December");
		return $Name[$month];
	}
}

//==============================================================================

if (!function_exists('ShortMonthName'))
{
	function ShortMonthName($month)
	{
		$Name = array("","Jan","Feb","Mar","Apr","May","Jun",
						 "Jul","Aug","Sep","Oct","Nov","Dec");
		return $Name[$month];
	}
}

//==============================================================================

if (!function_exists('MonthNumber'))
{
	function MonthNumber($month)
	{
		$month = strtolower($month);
		$ShortName = array("jan" => 1, "feb" => 2, "mar" => 3,
		                   "apr" => 4, "may" => 5, "jun" => 6,
		                   "jul" => 7, "aug" => 8, "sep" => 9,
		                   "oct" => 10, "nov" => 11, "dec" => 12);
		$LongName = array("january" => 1, "february" => 2, "march" => 3,
		                  "april" => 4, "may" => 5, "june" => 6,
		                  "july" => 7, "august" => 8, "september" => 9,
		                  "october" => 10, "november" => 11, "december" => 12);
		if (isset($ShortName[$month]))
			return $ShortName[$month];
		elseif (isset($LongName[$month]))
			return $LongName[$month];
		else
			return 0;
	}
}

//==============================================================================

if (!function_exists('NonLeapYearDays'))
{
	function NonLeapYearDays($month)
	{
		$Days = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
		return $Days[$month];
	}
}

//==============================================================================

if (!function_exists('LeapYearDays'))
{
	function LeapYearDays($month)
	{
		$Days = array(0,31,29,31,30,31,30,31,31,30,31,30,31);
		return $Days[$month];
	}
}

//==============================================================================

if (!function_exists('IsLeapYear'))
{
	function IsLeapYear($year)
	{
		if ((($year % 100) == 0) && (($year % 400) != 0))
			return 0;
		elseif (($year % 4) == 0)
			return 1;
		else
			return 0;
	}
}

//==============================================================================

if (!function_exists('DaysInMonth'))
{
	function DaysInMonth($month,$year)
	{
		if (IsLeapYear($year))
			return LeapYearDays($month);
		else
			return NonLeapYearDays($month);
	}
}

//==============================================================================
/*
The following function 'GegorianDow' is deprecated, but will not be deleted from
the library as the code provides insight into an algorithm for calculating the
day of week.
*/
//==============================================================================

if (!function_exists('GregorianDoW'))
{
	function GregorianDoW($day,$month,$year)
	{
		$LeapYearMonthAdjust	 = array(0,6,2,3,6,1,4,6,2,5,0,3,5);
		$NonLeapYearMonthAdjust = array(0,0,3,3,6,1,4,6,2,5,0,3,5);
		$GregorianCenturyAdjust = array(2,0,6,4,2,0,6,4,2,0,6);

		if (($year < 1000) || ($year > 2099) || ($month < 1) || ($month > 12) ||
			($day < 1) || ($day > DaysInMonth($month,$year)))
			return -1;

		$result = floor((($year % 100) * 5) / 4);
		$result += $day;
		if (IsLeapYear($year))
			$result += $LeapYearMonthAdjust[$month];
		else
			$result += $NonLeapYearMonthAdjust[$month];
		$result += $GregorianCenturyAdjust[floor($year / 100) - 10];
		$result %= 7;
		return($result);
	}
}

//==============================================================================

if (!function_exists('DateToDoW'))
{
	function DateToDoW($date)
	{
		return date('w',strtotime($date));
	}
}

//==============================================================================

if (!function_exists('DMYToDoW'))
{
	function DMYToDoW($day,$month,$year)
	{
		$date = sprintf("%04d-%02d-%02d",$year,$month,$day);
		return date('w',strtotime($date));
	}
}

//==============================================================================

if (!function_exists('DateIsValid'))
{
	function DateIsValid($date)
	{
		return ((preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) &&
			      (checkdate((int)(substr($date,5,2)),(int)(substr($date,8,2)),(int)(substr($date,0,4)))));
	}
}

//==============================================================================

if (!function_exists('StartWeekOfMonth'))
{
	function StartWeekOfMonth($month,$year)
	{
		$StartDoW = GregorianDoW(1,$month,$year);
		if ($StartDoW == 0)
		{
			// Month starts on a Sunday
			$SoWYear = $year;
			$SoWMonth = $month;
			$SoWDay = 1;
		}
		else
		{
			// Start of week is in previous month
			if ($month==1)
			{
				$SoWYear = $year - 1;
				$SoWMonth = 12;
			}
			else
			{
				$SoWYear = $year;
				$SoWMonth = $month - 1;
			}
			if (IsLeapYear($SoWYear))
				$SoWDay = LeapYearDays($SoWMonth) + 1 - $StartDoW;
			else
				$SoWDay = NonLeapYearDays($SoWMonth) + 1 - $StartDoW;
		}
		return sprintf("%04d-%02d-%02d",$SoWYear,$SoWMonth,$SoWDay);
	}
}

//==============================================================================

if (!function_exists('EndWeekOfMonth'))
{
	function EndWeekOfMonth($month,$year)
	{
		if (IsLeapYear($year))
		{
			$LastDay = LeapYearDays($month);
		}
		else
		{
			$LastDay = NonLeapYearDays($month);
		}

		$EndDoW = GregorianDoW($LastDay,$month,$year);
		$SoWDay = $LastDay - $EndDoW;
		return sprintf("%04d-%02d-%02d",$year,$month,$SoWDay);
	}
}

//==============================================================================

if (!function_exists('PreviousDate'))
{
	function PreviousDate($date)
	{
		$day = (int)substr($date,8,2);
		$month = (int)substr($date,5,2);
		$year = (int)substr($date,0,4);
		$day--;
		if ($day < 1)
		{
			$month--;
			if ($month < 1)
			{
				$year--;
				$month = 12;
			}
			$day = DaysInMonth($month,$year);
		}
		return sprintf("%04d-%02d-%02d",$year,$month,$day);
	}
}

//==============================================================================

if (!function_exists('NextDate'))
{
	function NextDate($date)
	{
		$day = (int)substr($date,8,2);
		$month = (int)substr($date,5,2);
		$year = (int)substr($date,0,4);
		$day++;
		if ($day > DaysInMonth($month,$year))
		{
			$day = 1;
			$month++;
			if ($month > 12)
			{
				$month = 1;
				$year++;
			}
		}
		return sprintf("%04d-%02d-%02d",$year,$month,$day);
	}
}

//==============================================================================

if (!function_exists('DateOfEaster'))
{
	function DateOfEaster($year)
	{
		$PaschalFullMoonMonth = array(0,4,4,3,4,3,4,4,3,4,4,3,4,4,3,4,3,4,4,3);
		$PaschalFullMoonDay = array(0,14,3,23,11,31,18,8,28,16,5,25,13,2,22,10,30,17,7,27);

		if (($year < 1900) || ($year > 2099))
			return ("");
		else
		{
			$GoldenNumber = ($year % 19) + 1;
			$day = $PaschalFullMoonDay[$GoldenNumber];
			$month = $PaschalFullMoonMonth[$GoldenNumber];
			$dayofweek = GregorianDoW($day, $month, $year);
			$day += (7 - $dayofweek);
			if ($day > 31)
			{
				$day -= 31;
				$month++;
			}
			return(sprintf("%04d-%02d-%02d",$year,$month,$day));
		}
	}
}

//==============================================================================

if (!function_exists('IsWorkingDay'))
{
	function IsWorkingDay($date)
	{
		$year = (int)substr($date,0,4);
		$month = (int)substr($date,5,2);
		$day = (int)substr($date,8,2);
		$dow = GregorianDoW($day,$month,$year);

		// Check for weekends and bank holidays
		$easter_sunday = DateOfEaster($year);
		$good_friday = AddDays($easter_sunday,-2);
		$easter_monday = NextDate($easter_sunday);
		if (($dow == 6) ||  // Saturday
		    ($dow == 0) ||  // Sunday
				(($month == 1) && ($day == 1)) ||  // New Year's Day
				(($month == 1) && ($dow == 1) && ($day <= 3)) ||  // New Year in lieu
				($date == $good_friday) ||  // Good Friday
				($date == $easter_monday) ||  // Easter Monday
				(($month == 5) && ($dow == 1) && ($day <= 7)) ||  // May Day BH
				(($month == 5) && ($dow == 1) && ($day >= 25)) ||  // Late May BH
				(($month == 8) && ($dow == 1) && ($day >= 25)) ||  // August BH
				(($month == 12) && ($day == 25)) ||  // Christmas Day
				(($month == 12) && ($day == 26)) ||  // Boxing Day
				(($month == 12) && ($dow <= 2) && ($day >= 27) && ($day <= 28))  // Christmas in lieu
			 )
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}

//==============================================================================

if (!function_exists('IsBST'))
{
	function IsBST($date)
	{
		$year = (int)substr($date,0,4);
		$march_end_dow = GregorianDoW(31,3,$year);
		$bst_start = sprintf("$year-03-%02d",31-$march_end_dow);
		$october_end_dow = GregorianDoW(31,10,$year);
		$bst_end = sprintf("$year-10-%02d",31-$october_end_dow);
		return (($date >= $bst_start) && ($date < $bst_end));
	}
}

//==============================================================================
// The following functions perform calculations relating to school terms.
// A term is defined as starting on 1 January, Easter Sunday or 1 September.
//==============================================================================

if (!function_exists('StartOfTerm'))
{
	function StartOfTerm()
	{
		// Determine the start of the current school term.
		$Today = TODAY_DATE;
		$ThisYear = date('Y');
		$Easter = DateOfEaster($ThisYear);
		if ($Today >= "$ThisYear-09-01")
			return ("$ThisYear-09-01");
		elseif ($Today >= $Easter)
			return ($Easter);
		else
			return ("$ThisYear-01-01");
	}
}

//==============================================================================

if (!function_exists('EndOfLastTerm'))
{
	function EndOfLastTerm()
	{
		// Determine the end of the previous school term.
		$start_of_term = StartOfTerm();
		$year = (int)substr($start_of_term,0,4);
		$month = (int)substr($start_of_term,5,2);
		$day = (int)substr($start_of_term,8,2);
		$day--;
		if ($day == 0)
		{
			$month--;
			if ($month == 0)
			{
				$year--;
				$month = 12;
			}
			$day = DaysInMonth($month,$year);
		}
		$date = sprintf("%04d-%02d-%02d",$year,$month,$day);
		return $date;
	}
}

//==============================================================================

if (!function_exists('EndOfThisTerm'))
{
	function EndOfThisTerm()
	{
		// Determine the end of the current school term.
		$Today = TODAY_DATE;
		$ThisYear = date('Y');
		$Easter = DateOfEaster($ThisYear);
		if ($Today >= "$ThisYear-09-01")
			return ("$ThisYear-12-31");
		elseif ($Today >= $Easter)
			return ("$ThisYear-08-31");
		else
		{
			$EasterDay = (int)substr($Easter,8,2);
			if ($EasterDay == 1)
				$EndOfTerm = "$Year-03-31";
			else
				$EndOfTerm = substr($Easter,0,8).sprintf("%02d",$EasterDay-1);
			return ($EndOfTerm);
		}
	}
}

//==============================================================================

if (!function_exists('EndOfNextTerm'))
{
	function EndOfNextTerm()
	{
		// Determine the end of the current school term.
		$Today = TODAY_DATE;
		$ThisYear = date('Y');
		$NextYear = $ThisYear+1;
		$Easter = DateOfEaster($ThisYear);
		$NextEaster = DateOfEaster($ThisYear+1);

		if ($Today >= "$ThisYear-09-01")
		{
			$NextEasterDay = (int)substr($NextEaster,8,2);
			if ($NextEasterDay == 1)
				$EndOfTerm = "$NextYear-03-31";
			else
				$EndOfTerm = substr($NextEaster,0,8).sprintf("%02d",$NextEasterDay-1);
			return ($EndOfTerm);
		}
		elseif ($Today >= $Easter)
			return ("$ThisYear-12-31");
		else
			return ("$ThisYear-08-31");
	}
}

//==============================================================================

if (!function_exists('AddDays'))
{
	function AddDays($date,$days)
	{
		// Add a given number of days to (or substract from) a given date
		$year = (int)substr($date,0,4);
		$month = (int)substr($date,5,2);
		$day = (int)substr($date,8,2);
		$day += $days;

		while ($day > DaysInMonth($month,$year))
		{
			$day -= DaysInMonth($month,$year);
			$month ++;
			if ($month > 12)
			{
				$year ++;
				$month = 1;
			}
		}
		while ($day < 1)
		{
			$month --;
			if ($month < 1)
			{
				$year --;
				$month = 12;
			}
			$day += DaysInMonth($month,$year);
		}

		return sprintf("%04d-%02d-%02d",$year,$month,$day);
	}
}

//==============================================================================

if (!function_exists('DateDifference'))
{
	function DateDifference ($start_date, $end_date)
	{
		$start_year = (int)substr($start_date,0,4);
		$start_month = (int)substr($start_date,5,2);
		$start_day = (int)substr($start_date,8,2);
		$start_date_offset = mktime(1,0,0,$start_month,$start_day,$start_year);
		$end_year = (int)substr($end_date,0,4);
		$end_month = (int)substr($end_date,5,2);
		$end_day = (int)substr($end_date,8,2);
		$end_date_offset = mktime(1,0,0,$end_month,$end_day,$end_year);

		// Round the difference to avoid problems with DST change.
		$difference = ($end_date_offset - $start_date_offset) / 86400;
		return (int)round($difference,0);
	}
}

//==============================================================================

if (!function_exists('short_date'))
{
	function short_date($date,$day_offset=0)
	{
		// Format date string from MySQL
		$day = (int)substr($date,8,2);
		$month = (int)substr($date,5,2);
		$year = (int)substr($date,0,4);
		$day += $day_offset;
		if ($day > DaysInMonth($month,$year))
		{
			$day -= DaysInMonth($month,$year);
			if ($month == 12)
			{
				$month = 1;
				$year++;
			}
			else
				$month++;
		}
		return sprintf("%02d %s %04d",$day,ShortMonthName($month),$year);
	}
}

//==============================================================================

if (!function_exists('title_date'))
{
	function title_date($date,$day_offset=0)
	{
		// Format date string from MySQL
		$day = (int)substr($date,8,2);
		$month = (int)substr($date,5,2);
		$year = (int)substr($date,0,4);
		$day += $day_offset;
		if ($day > DaysInMonth($month,$year))
		{
			$day -= DaysInMonth($month,$year);
			if ($month == 12)
			{
				$month = 1;
				$year++;
			}
			else
				$month++;
		}
		$dow = GregorianDoW($day,$month,$year);
		return sprintf("%s %02d %s %04d",ShortDayName($dow),$day,ShortMonthName($month),$year);
	}
}

//==============================================================================

if (!function_exists('long_title_date'))
{
	function long_title_date($date,$day_offset=0)
	{
		// Format date string from MySQL
		$day = (int)substr($date,8,2);
		$month = (int)substr($date,5,2);
		$year = (int)substr($date,0,4);
		$day += $day_offset;
		if ($day > DaysInMonth($month,$year))
		{
			$day -= DaysInMonth($month,$year);
			if ($month == 12)
			{
				$month = 1;
				$year++;
			}
			else
				$month++;
		}
		$dow = GregorianDoW($day,$month,$year);
		return sprintf("%s %02d %s %04d",DayName($dow),$day,MonthName($month),$year);
	}
}

//==============================================================================

if (!function_exists('ChurchCalendar'))
{
	function ChurchCalendar ($day,$month,$year)
	{
		$epiphany_sundays = array (
			"1st Sunday after Epiphany",
			"2nd Sunday after Epiphany",
			"3rd Sunday after Epiphany",
			"4th Sunday after Epiphany",
			"5th Sunday after Epiphany",
			"6th Sunday after Epiphany"
		);
		$moveable_sundays = array (
			"Septuagesima",
			"Sexagesima",
			"Quinquagesima",
			"1st Sunday in Lent",
			"2nd Sunday in Lent",
			"3rd Sunday in Lent",
			"4th Sunday in Lent",
			"5th Sunday in Lent",
			"Palm Sunday",
			"Easter Sunday",
			"1st Sunday after Easter",
			"2nd Sunday after Easter",
			"3rd Sunday after Easter",
			"4th Sunday after Easter",
			"5th Sunday after Easter",
			"Sunday after Ascension",
			"Whit Sunday",
			"Trinity Sunday",
			"1st Sunday after Trinity",
			"2nd Sunday after Trinity",
			"3rd Sunday after Trinity",
			"4th Sunday after Trinity",
			"5th Sunday after Trinity",
			"6th Sunday after Trinity",
			"7th Sunday after Trinity",
			"8th Sunday after Trinity",
			"9th Sunday after Trinity",
			"10th Sunday after Trinity",
			"11th Sunday after Trinity",
			"12th Sunday after Trinity",
			"13th Sunday after Trinity",
			"14th Sunday after Trinity",
			"15th Sunday after Trinity",
			"16th Sunday after Trinity",
			"17th Sunday after Trinity",
			"18th Sunday after Trinity",
			"19th Sunday after Trinity",
			"20th Sunday after Trinity",
			"21st Sunday after Trinity",
			"22nd Sunday after Trinity",
			"23rd Sunday after Trinity",
			"24th Sunday after Trinity",
			"25th Sunday after Trinity",
			"26th Sunday after Trinity",
			"27th Sunday after Trinity"
		);
		$advent_sundays = array (
			"Advent Sunday",
			"2nd Sunday in Advent",
			"3rd Sunday in Advent",
			"4th Sunday in Advent"
		);

		if (GregorianDoW($day,$month,$year) != 0)
		{
			// Abort if date is not a Sunday
			return "Error";
		}
		$date = sprintf("%04d-%02d-%02d",$year,$month,$day);

		// Calculate the dates of Septuagesima and Advent Sunday
		$date_of_septuagesima = AddDays(DateOfEaster($year),-63);
		$dow_of_christmas = GregorianDoW(25,12,$year);
		if ($dow_of_christmas == 0)
			$days_in_advent = 28;
		else
			$days_in_advent = $dow_of_christmas + 21;
		$date_of_advent_sunday = AddDays("$year-12-25",-$days_in_advent);

		if ($date < $date_of_septuagesima)
		{
			// Prior to Septuagesisma
			if ($date == "$year-01-01")
				return "Sunday after Christmas";
			else if ($date < "$year-01-06")
				return "2nd Sunday after Christmas";
			else if ($date == "$year-01-06")
				return "Epiphany";
			else
			{
				$days_after_epiphany = DateDifference("$year-01-06",$date);
				return $epiphany_sundays[($days_after_epiphany - 1) / 7];
			}
		}
		elseif ($date < $date_of_advent_sunday)
		{
			// Dictated by the date of Easter
			$days_after_septuagesima = DateDifference($date_of_septuagesima,$date);
			return $moveable_sundays[$days_after_septuagesima / 7];
		}
		else
		{
			// Advent & Christmas
			if ($date < "$year-12-25")
			{
				$days_into_advent = DateDifference($date_of_advent_sunday,$date);
				return $advent_sundays[$days_into_advent / 7];
			}
			elseif ($date == "$year-12-25")
				return "Christmas Day";
			else
				return "Sunday after Christmas";
		}
	}
}

//==============================================================================
?>
