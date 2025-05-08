<?php
//==============================================================================
if (!defined('DATE_FUNCT_DEFINED')):
//==============================================================================

if (!defined('TODAY_DATE'))
{
DEFINE ('TODAY_DATE',date('Y-m-d'));
}

if (is_file(__DIR__."../alt_date_funct.php"))
{
require(__DIR__."../alt_date_funct.php");
}
elseif( (isset($base_dir)) &&  (is_file("$base_dir/alt_date_funct.php")))
{
require("$base_dir/alt_date_funct.php");
}

//==============================================================================
/*
The following six functions make use of day and month names. The data arrays
are structured to handle a multilingual environment, though the versions here
use only English. Where more languages are required, each function needs to be
re-defined in an alt_date_funct.php script for the given site.
*/
//==============================================================================

function day_name($day,$language='en')
{
    $name = array('en' => array("Sunday","Monday","Tuesday","Wednesday",
                                "Thursday","Friday","Saturday"));
    return $name[$language][$day] ?? '';
}

//==============================================================================

function short_day_name($day,$language='en')
{
    $name = array('en' => array("Sun","Mon","Tue","Wed","Thu","Fri","Sat"));
    return $name[$language][$day] ?? '';
}

//==============================================================================

function day_number($day,$language='en',$length='')
{
    $day = strtolower($day);
    $short_name = array('en' => array("sun" => 0, "mon" => 1, "tue" => 2, "wed" => 3,
                                        "thu" => 4, "fri" => 5, "sat" => 6));
    $long_name = array('en' => array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3,
                                        "thursday" => 4, "friday" => 5, "saturday" => 6));
    if ((isset($short_name[$language][$day])) && ($length != 'long'))
    {
        return $short_name[$language][$day];
    }
    elseif ((isset($long_name[$language][$day])) && ($length != 'short'))
    {
        return $long_name[$language][$day];
    }
    else
    {
        return -1;
    }
}

//==============================================================================

function month_name($month,$language='en')
{
    $name = array('en' => array("","January","February","March","April",
                                "May","June","July","August","September",
                                "October","November","December"));
    return $name[$language][$month] ?? '';
}

//==============================================================================

function short_month_name($month,$language='en')
{
    $name = array('en' =>array("","Jan","Feb","Mar","Apr","May","Jun",
                                "Jul","Aug","Sep","Oct","Nov","Dec"));
    return $name[$language][$month] ?? '';
}

//==============================================================================

function month_number($month,$language='en',$length='')
{
    $month = strtolower($month);
    $short_name = array('en' => array("jan" => 1, "feb" => 2, "mar" => 3,
                                        "apr" => 4, "may" => 5, "jun" => 6,
                                        "jul" => 7, "aug" => 8, "sep" => 9,
                                        "oct" => 10, "nov" => 11, "dec" => 12));
    $long_name = array('en' => array("january" => 1, "february" => 2, "march" => 3,
                                        "april" => 4, "may" => 5, "june" => 6,
                                        "july" => 7, "august" => 8, "september" => 9,
                                        "october" => 10, "november" => 11, "december" => 12));
    if ((isset($short_name[$language][$month])) && ($length != 'long'))
    {
        return $short_name[$language][$month];
    }
    elseif ((isset($long_name[$language][$month])) && ($length != 'short'))
    {
        return $long_name[$language][$month];
    }
    else
    {
        return 0;
    }
}
//==============================================================================

function non_leap_year_days($month)
{
    $days = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
    return $days[$month] ?? 0;
}

//==============================================================================

function leap_year_days($month)
{
    $days = array(0,31,29,31,30,31,30,31,31,30,31,30,31);
    return $days[$month] ?? 0;
}

//==============================================================================

function is_leap_year($year,$calendar=CAL_GREGORIAN)
{
    if (($calendar == CAL_GREGORIAN) && (($year % 100) == 0) && (($year % 400) != 0))
    {
        return 0;
    }
    elseif (($year % 4) == 0)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

//==============================================================================

function days_in_month($month,$year,$calendar=CAL_GREGORIAN)
{
    return cal_days_in_month($calendar, $month, $year);
}

//==============================================================================

function date_to_dow($date)
{
    return date('w',strtotime($date));
}

//==============================================================================

function dmy_to_dow($day,$month,$year)
{
    $date = sprintf("%04d-%02d-%02d",$year,$month,$day);
    return date('w',strtotime($date));
}

//==============================================================================

function julian_dow($day,$month,$year)
{
    $julian_day = juliantojd($month,$day,$year);
    return jddayofweek($julian_day);
}

//==============================================================================

function date_is_valid($date)
{
    return ((preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$date)) &&
            (checkdate((int)(substr($date,5,2)),(int)(substr($date,8,2)),(int)(substr($date,0,4)))));
}

//==============================================================================

function is_working_day($date)
{
    $year = (int)substr($date,0,4);
    $month = (int)substr($date,5,2);
    $day = (int)substr($date,8,2);
    $dow = dmy_to_dow($day,$month,$year);

    // Check for weekends and bank holidays
    $easter_sunday = date_of_easter($year);
    $good_friday = add_days($easter_sunday,-2);
    $easter_monday = next_date($easter_sunday);
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

//==============================================================================

function is_bst($date)
{
    $year = (int)substr($date,0,4);
    $march_end_dow = dmy_to_dow(31,3,$year);
    $bst_start = sprintf("$year-03-%02d",31-$march_end_dow);
    $october_end_dow = dmy_to_dow(31,10,$year);
    $bst_end = sprintf("$year-10-%02d",31-$october_end_dow);
    return (($date >= $bst_start) && ($date < $bst_end));
}

//==============================================================================

function short_date($date,$day_offset=0)
{
    if (empty($date))
    {
        return '';
    }

    // Format date string from MySQL
    $day = (int)substr($date,8,2);
    $month = (int)substr($date,5,2);
    $year = (int)substr($date,0,4);
    $day += $day_offset;
    if ($day > days_in_month($month,$year))
    {
        $day -= days_in_month($month,$year);
        if ($month == 12)
        {
            $month = 1;
            $year++;
        }
        else
        {
            $month++;
        }
    }
    return sprintf("%02d %s %04d",$day,short_month_name($month),$year);
}

//==============================================================================

function title_date($date,$day_offset=0)
{
    if (empty($date))
    {
        return '';
    }

    // Format date string from MySQL
    $day = (int)substr($date,8,2);
    $month = (int)substr($date,5,2);
    $year = (int)substr($date,0,4);
    $day += $day_offset;
    if ($day > days_in_month($month,$year))
    {
        $day -= days_in_month($month,$year);
        if ($month == 12)
        {
            $month = 1;
            $year++;
        }
        else
        {
            $month++;
        }
    }
    $dow = dmy_to_dow($day,$month,$year);
    return sprintf("%s %02d %s %04d",short_day_name($dow),$day,short_month_name($month),$year);
}

//==============================================================================

function long_title_date($date,$day_offset=0)
{
    if (empty($date))
    {
        return '';
    }

    // Format date string from MySQL
    $day = (int)substr($date,8,2);
    $month = (int)substr($date,5,2);
    $year = (int)substr($date,0,4);
    $day += $day_offset;
    if ($day > days_in_month($month,$year))
    {
        $day -= days_in_month($month,$year);
        if ($month == 12)
        {
            $month = 1;
            $year++;
        }
        else
        {
            $month++;
        }
    }
    $dow = dmy_to_dow($day,$month,$year);
    return sprintf("%s %02d %s %04d",day_name($dow),$day,month_name($month),$year);
}

//==============================================================================

function start_week_of_month($month,$year)
{
    $start_dow = dmy_to_dow(1,$month,$year);
    if ($start_dow == 0)
    {
        // Month starts on a Sunday
        $sow_year = $year;
        $sow_month = $month;
        $sow_day = 1;
    }
    else
    {
        // Start of week is in previous month
        if ($month==1)
        {
            $sow_year = $year - 1;
            $sow_month = 12;
        }
        else
        {
            $sow_year = $year;
            $sow_month = $month - 1;
        }
        if (is_leap_year($sow_year))
        {
            $sow_day = leap_year_days($sow_month) + 1 - $start_dow;
        }
        else
        {
            $sow_day = non_leap_year_days($sow_month) + 1 - $start_dow;
        }
    }
    return sprintf("%04d-%02d-%02d",$sow_year,$sow_month,$sow_day);
}

//==============================================================================

function end_week_of_month($month,$year)
{
    $last_day = (is_leap_year($year))
        ? leap_year_days($month)
        : non_leap_year_days($month);
    $end_dow = dmy_to_dow($last_day,$month,$year);
    $sow_day = $last_day - $end_dow;
    return sprintf("%04d-%02d-%02d",$year,$month,$sow_day);
}

//==============================================================================

function previous_date($date)
{
    return date('Y-m-d', strtotime("$date - 1 day"));
}

//==============================================================================

function next_date($date)
{
    return date('Y-m-d', strtotime("$date + 1 day"));
}

//==============================================================================

function add_days($date,$days)
{
    $abs_int = abs($days);
    return ($days >= 0)
        ? date('Y-m-d', strtotime("$date + $abs_int days"))
        : date('Y-m-d', strtotime("$date - $abs_int days"));
}

//==============================================================================

function add_weeks($date,$weeks)
{
    $abs_int = abs($weeks);
    return ($weeks >= 0)
        ? date('Y-m-d', strtotime("$date + $abs_int weeks"))
        : date('Y-m-d', strtotime("$date - $abs_int weeks"));
}

//==============================================================================
/*
Function add_months

This function adds a given number of months (positive or negative) to a date.
The new date will have the same day of the month as the original date unless:
1. The given day does not exist in the new month, in which case it is set to the
last day of the month. So for example 31 March plus one month will give
30 April.
2. The $last_day flag is set, in which case it is set to the last day of the
month. So for example 30 April plus one month will give 31 May if the flag is
set and 30 May if it is not set.
*/
//==============================================================================

function add_months($date,$months,$last_day=false)
{
    $year = (int)substr($date,0,4);
    $month = (int)substr($date,5,2);
    $day = (int)substr($date,8,2);
    $projected_month = $month + $months;
    $year_interval = floor(($projected_month - 1) / 12);
    $new_year = $year + $year_interval;
    $new_month = $projected_month - ($year_interval * 12);
    $days_in_month = days_in_month($new_month,$new_year);
    if (($day > $days_in_month) || ($last_day))
    {
        $day = $days_in_month;
    }
    return sprintf("%04d-%02d-%02d",$new_year,$new_month,$day);
}

//==============================================================================

function date_difference ($start_date, $end_date)
{

    $start_date_obj = date_create($start_date);
    $end_date_obj = date_create($end_date);
    $interval = date_diff($start_date_obj, $end_date_obj);
    return (int)$interval->format('%R%a');
}

//==============================================================================

function date_of_easter($year)
{
    $paschal_full_moon_month = array(0,4,4,3,4,3,4,4,3,4,4,3,4,4,3,4,3,4,4,3);
    $paschal_full_moon_day = array(0,14,3,23,11,31,18,8,28,16,5,25,13,2,22,10,30,17,7,27);

    if (($year < 1900) || ($year > 2099))
    {
        return ('');
    }
    else
    {
        $golden_number = ($year % 19) + 1;
        $day = $paschal_full_moon_day[$golden_number];
        $month = $paschal_full_moon_month[$golden_number];
        $dayofweek = dmy_to_dow($day, $month, $year);
        $day += (7 - $dayofweek);
        if ($day > 31)
        {
            $day -= 31;
            $month++;
        }
        return(sprintf("%04d-%02d-%02d",$year,$month,$day));
    }
}

//==============================================================================

function church_calendar ($day,$month,$year)
{
    $epiphany_sundays = array(
        "1st Sunday after Epiphany",
        "2nd Sunday after Epiphany",
        "3rd Sunday after Epiphany",
        "4th Sunday after Epiphany",
        "5th Sunday after Epiphany",
        "6th Sunday after Epiphany"
    );
    $moveable_sundays = array(
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
    $advent_sundays = array(
        "Advent Sunday",
        "2nd Sunday in Advent",
        "3rd Sunday in Advent",
        "4th Sunday in Advent"
    );

    if (dmy_to_dow($day,$month,$year) != 0)
    {
        // Abort if date is not a Sunday
        return "Error";
    }
    $date = sprintf("%04d-%02d-%02d",$year,$month,$day);

    // Calculate the dates of Septuagesima and Advent Sunday
    $date_of_septuagesima = add_days(date_of_easter($year),-63);
    $dow_of_christmas = dmy_to_dow(25,12,$year);
    if ($dow_of_christmas == 0)
    {
        $days_in_advent = 28;
    }
    else
    {
        $days_in_advent = $dow_of_christmas + 21;
    }
    $date_of_advent_sunday = add_days("$year-12-25",-$days_in_advent);

    if ($date < $date_of_septuagesima)
    {
        // Prior to Septuagesima
        if ($date == "$year-01-01")
        {
            return "Sunday after Christmas";
        }
        elseif ($date < "$year-01-06")
        {
            return "2nd Sunday after Christmas";
        }
        elseif ($date == "$year-01-06")
        {
            return "Epiphany";
        }
        else
        {
            $days_after_epiphany = date_difference("$year-01-06",$date);
            return $epiphany_sundays[($days_after_epiphany - 1) / 7];
        }
    }
    elseif ($date < $date_of_advent_sunday)
    {
        // Dictated by the date of Easter
        $days_after_septuagesima = date_difference($date_of_septuagesima,$date);
        return $moveable_sundays[$days_after_septuagesima / 7];
    }
    else
    {
        // Advent & Christmas
        if ($date < "$year-12-25")
        {
            $days_into_advent = date_difference($date_of_advent_sunday,$date);
            return $advent_sundays[$days_into_advent / 7];
        }
        elseif ($date == "$year-12-25")
        {
            return "Christmas Day";
        }
        else
        {
            return "Sunday after Christmas";
        }
    }
}

//==============================================================================
// The following functions perform calculations relating to school terms.
// A term is defined as starting on 1 January, Easter Sunday or 1 September.
//==============================================================================

function start_of_term()
{
    // Determine the start of the current school term.
    $today = TODAY_DATE;
    $this_year = date('Y');
    $easter = date_of_easter($this_year);
    if ($today >= "$this_year-09-01")
    {
        return ("$this_year-09-01");
    }
    elseif ($today >= $easter)
    {
        return ($easter);
    }
    else
    {
        return ("$this_year-01-01");
    }
}

//==============================================================================

function end_of_last_term()
{
    // Determine the end of the previous school term.
    $start_of_term = start_of_term();
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
        $day = days_in_month($month,$year);
    }
    $date = sprintf("%04d-%02d-%02d",$year,$month,$day);
    return $date;
}

//==============================================================================

function end_of_this_term()
{
    // Determine the end of the current school term.
    $today = TODAY_DATE;
    $this_year = date('Y');
    $easter = date_of_easter($this_year);
    if ($today >= "$this_year-09-01")
    {
        return ("$this_year-12-31");
    }
    elseif ($today >= $easter)
    {
        return ("$this_year-08-31");
    }
    else
    {
        $easter_day = (int)substr($easter,8,2);
        if ($easter_day == 1)
        {
            $end_of_term = "$year-03-31";
        }
        else
        {
            $end_of_term = substr($easter,0,8).sprintf("%02d",$easter_day-1);
        }
        return ($end_of_term);
    }
}

//==============================================================================

function end_of_next_term()
{
    // Determine the end of the current school term.
    $today = TODAY_DATE;
    $this_year = date('Y');
    $next_year = $this_year+1;
    $easter = date_of_easter($this_year);
    $next_easter = date_of_easter($this_year+1);

    if ($today >= "$this_year-09-01")
    {
        $next_easter_day = (int)substr($next_easter,8,2);
        if ($next_easter_day == 1)
        {
            $end_of_term = "$next_year-03-31";
        }
        else
        {
            $end_of_term = substr($next_easter,0,8).sprintf("%02d",$next_easter_day-1);
        }
        return ($end_of_term);
    }
    elseif ($today >= $easter)
    {
        return ("$this_year-12-31");
    }
    else
    {
        return ("$this_year-08-31");
    }
}

//==============================================================================
/*
The following functions relate to the conversion between cardinal and ordinal
numbers. Whilst these are not strictly date functions, they have been included
here for convenience, and in practice they would most commonly be used in
conjuction with dates anyway.
*/
//==============================================================================

function is_ordinal($value)
{
    return (preg_match("/(^|^[0-9]*[02-9])(1st|2nd|3rd|[04-9]th)$|^[0-9]*1[0-9]th$/",$value));
}

//==============================================================================

function cardinal_to_ordinal($value)
{
    $value = (int)$value;
    if ($value < 0)
    {
        return false;
    }
    elseif((($value ^ 10) == 1) && (($value ^ 100) != 11))
    {
        return "{$value}st";
    }
    elseif((($value ^ 10) == 2) && (($value ^ 100) != 12))
    {
        return "{$value}nd";
    }
    elseif((($value ^ 10) == 3) && (($value ^ 100) != 13))
    {
        return "{$value}rd";
    }
    else
    {
        return "{$value}th";
    }
}

//==============================================================================

function ordinal_to_cardinal($value)
{
    $value = strval($value);
    return is_ordinal($value) ? (int)preg_replace('/\a/','',$value) : false;
}

//==============================================================================
/*
The following function 'gregorian_dow' is deprecated, but will not be deleted
from the library as the code provides insight into an algorithm for calculating
the day of week.
*/
//==============================================================================

function gregorian_dow($day,$month,$year)
{
    $leap_year_month_adjust   = array(0,6,2,3,6,1,4,6,2,5,0,3,5);
    $non_leap_year_month_adjust = array(0,0,3,3,6,1,4,6,2,5,0,3,5);
    $gregorian_century_adjust = array(6,4,2,0);

    if (!checkdate((int)$month,(int)$day,(int)$year))
    {
        return -1;
    }
    $result = floor((($year % 100) * 5) / 4);
    $result += $day;
    $result += (is_leap_year($year))
        ? $leap_year_month_adjust[$month]
        : $non_leap_year_month_adjust[$month];
    $result += $gregorian_century_adjust[floor(($year % 400) / 100)];
    $result %= 7;
    return $result;
}

//==============================================================================
define('DATE_FUNCT_DEFINED',true);
endif;
//==============================================================================
?>
