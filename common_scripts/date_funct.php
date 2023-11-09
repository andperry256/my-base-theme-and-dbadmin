<?php
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

if (!function_exists('DayName'))
{
    function DayName($day)
    {
        $name = array("Sunday","Monday","Tuesday","Wednesday",
                      "Thursday","Friday","Saturday");
        return $name[$day];
    }
}

//==============================================================================

if (!function_exists('ShortDayName'))
{
    function ShortDayName($day)
    {
        $name = array("Sun","Mon","Tue","Wed","Thu","Fri","Sat");
        return $name[$day];
    }
}

//==============================================================================

if (!function_exists('DayNumber'))
{
    function DayNumber($day)
    {
        $day = strtolower($day);
        $short_name = array("sun" => 0, "mon" => 1, "tue" => 2, "wed" => 3,
                           "thu" => 4, "fri" => 5, "sat" => 6);
        $long_name = array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3,
                          "thursday" => 4, "friday" => 5, "saturday" => 6);
        if (isset($short_name[$day]))
        {
            return $short_name[$day];
        }
        elseif (isset($long_name[$day]))
        {
            return $long_name[$day];
        }
        else
        {
            return -1;
        }
    }
}

//==============================================================================

if (!function_exists('MonthName'))
{
    function MonthName($month)
    {
        $name = array("","January","February","March","April",
                      "May","June","July","August","September",
                      "October","November","December");
        return $name[$month];
    }
}

//==============================================================================

if (!function_exists('ShortMonthName'))
{
    function ShortMonthName($month)
    {
        $name = array("","Jan","Feb","Mar","Apr","May","Jun",
                      "Jul","Aug","Sep","Oct","Nov","Dec");
        return $name[$month];
    }
}

//==============================================================================

if (!function_exists('MonthNumber'))
{
    function MonthNumber($month)
    {
        $month = strtolower($month);
        $short_name = array("jan" => 1, "feb" => 2, "mar" => 3,
                           "apr" => 4, "may" => 5, "jun" => 6,
                           "jul" => 7, "aug" => 8, "sep" => 9,
                           "oct" => 10, "nov" => 11, "dec" => 12);
        $long_name = array("january" => 1, "february" => 2, "march" => 3,
                          "april" => 4, "may" => 5, "june" => 6,
                          "july" => 7, "august" => 8, "september" => 9,
                          "october" => 10, "november" => 11, "december" => 12);
        if (isset($short_name[$month]))
        {
            return $short_name[$month];
        }
        elseif (isset($long_name[$month]))
        {
            return $long_name[$month];
        }
        else
        {
            return 0;
        }
    }
}

//==============================================================================

if (!function_exists('NonLeapYearDays'))
{
    function NonLeapYearDays($month)
    {
        $days = array(0,31,28,31,30,31,30,31,31,30,31,30,31);
        return $days[$month];
    }
}

//==============================================================================

if (!function_exists('LeapYearDays'))
{
    function LeapYearDays($month)
    {
        $days = array(0,31,29,31,30,31,30,31,31,30,31,30,31);
        return $days[$month];
    }
}

//==============================================================================

if (!function_exists('IsLeapYear'))
{
    function IsLeapYear($year,$calendar=CAL_GREGORIAN)
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
}

//==============================================================================

if (!function_exists('DaysInMonth'))
{
  
    function DaysInMonth($month,$year,$calendar=CAL_GREGORIAN)
    {
        return cal_days_in_month($calendar, $month, $year);
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
        $leap_year_month_adjust   = array(0,6,2,3,6,1,4,6,2,5,0,3,5);
        $non_leap_year_month_adjust = array(0,0,3,3,6,1,4,6,2,5,0,3,5);
        $gregorian_century_adjust = array(6,4,2,0);
    
        if (!checkdate((int)$month,(int)$day,(int)$year))
        {
            return -1;
        }
        $result = floor((($year % 100) * 5) / 4);
        $result += $day;
        $result += (IsLeapYear($year))
             ? $leap_year_month_adjust[$month]
             : $non_leap_year_month_adjust[$month];
        $result += $gregorian_century_adjust[floor(($year % 400) / 100)];
        $result %= 7;
        return $result;
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

if (!function_exists('JulianDoW'))
{
    function JulianDoW($day,$month,$year)
    {
        $julian_day = juliantojd($month,$day,$year);
        return jddayofweek($julian_day);
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
        $start_do_w = DMYToDoW(1,$month,$year);
        if ($start_do_w == 0)
        {
            // Month starts on a Sunday
            $so_wyear = $year;
            $so_wmonth = $month;
            $so_wday = 1;
        }
        else
        {
            // Start of week is in previous month
            if ($month==1)
            {
                $so_wyear = $year - 1;
                $so_wmonth = 12;
            }
            else
            {
                $so_wyear = $year;
                $so_wmonth = $month - 1;
            }
            if (IsLeapYear($so_wyear))
            {
                $so_wday = LeapYearDays($so_wmonth) + 1 - $start_do_w;
            }
            else
            {
                $so_wday = NonLeapYearDays($so_wmonth) + 1 - $start_do_w;
            }
        }
        return sprintf("%04d-%02d-%02d",$so_wyear,$so_wmonth,$so_wday);
    }
}

//==============================================================================

if (!function_exists('EndWeekOfMonth'))
{
    function EndWeekOfMonth($month,$year)
    {
        $last_day = (IsLeapYear($year))
            ? LeapYearDays($month)
            : NonLeapYearDays($month);
        $end_do_w = DMYToDoW($last_day,$month,$year);
        $so_wday = $last_day - $end_do_w;
        return sprintf("%04d-%02d-%02d",$year,$month,$so_wday);
    }
}

//==============================================================================

if (!function_exists('PreviousDate'))
{
    function PreviousDate($date)
    {
        return date('Y-m-d', strtotime("$date - 1 day"));
    }
}

//==============================================================================

if (!function_exists('NextDate'))
{
    function NextDate($date)
    {
        return date('Y-m-d', strtotime("$date + 1 day"));
    }
}

//==============================================================================

if (!function_exists('AddDays'))
{
    function AddDays($date,$days)
    {
        $abs_int = abs($days);
         return ($days >= 0)
            ? date('Y-m-d', strtotime("$date + $abs_int days"))
            : date('Y-m-d', strtotime("$date - $abs_int days"));
    }
}

//==============================================================================

if (!function_exists('AddWeeks'))
{
    function AddWeeks($date,$weeks)
    {
        $abs_int = abs($weeks);
         return ($weeks >= 0)
            ? date('Y-m-d', strtotime("$date + $abs_int weeks"))
            : date('Y-m-d', strtotime("$date - $abs_int weeks"));
    }
}

//==============================================================================
/*
Function AddMonths

This function adds a given number of months (positive or negative) to a date.
The new date will have the same day of the month as the original date unless:-
1. The given day does not exist in the new month, in which case it is set to the
   last day of the month. So for example 31 March plus one month will give
   30 April.
2. The $last_day flag is set, in which case it is set to the last day of the
   month. So for example 30 April plus one month will give 31 May if the flag is
   set and 30 May if it is not set.
*/
//==============================================================================

if (!function_exists('AddMonths'))
{
    function AddMonths($date,$months,$last_day=false)
    {
        $year = (int)substr($date,0,4);
        $month = (int)substr($date,5,2);
        $day = (int)substr($date,8,2);
        $projected_month = $month + $months;
        $year_interval = floor(($projected_month - 1) / 12);
        $new_year = $year + $year_interval;
        $new_month = $projected_month - ($year_interval * 12);
        $days_in_month = DaysInMonth($new_month,$new_year);
        if (($day > $days_in_month) || ($last_day))
        {
            $day = $days_in_month;
        }
        return sprintf("%04d-%02d-%02d",$new_year,$new_month,$day);
    }
}

//==============================================================================

if (!function_exists('DateDifference'))
{
    function DateDifference ($start_date, $end_date)
    {
    
        $start_date_obj = date_create($start_date);
        $end_date_obj = date_create($end_date);
        $interval = date_diff($start_date_obj, $end_date_obj);
        return (int)$interval->format('%R%a');
    }
}

//==============================================================================

if (!function_exists('DateOfEaster'))
{
    function DateOfEaster($year)
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
            $dayofweek = DMYToDoW($day, $month, $year);
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
        $dow = DMYToDoW($day,$month,$year);
    
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
        $march_end_dow = DMYToDoW(31,3,$year);
        $bst_start = sprintf("$year-03-%02d",31-$march_end_dow);
        $october_end_dow = DMYToDoW(31,10,$year);
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
        $today = TODAY_DATE;
        $this_year = date('Y');
        $easter = DateOfEaster($this_year);
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
        $today = TODAY_DATE;
        $this_year = date('Y');
        $easter = DateOfEaster($this_year);
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
}

//==============================================================================

if (!function_exists('EndOfNextTerm'))
{
    function EndOfNextTerm()
    {
        // Determine the end of the current school term.
        $today = TODAY_DATE;
        $this_year = date('Y');
        $next_year = $this_year+1;
        $easter = DateOfEaster($this_year);
        $next_easter = DateOfEaster($this_year+1);
    
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
}

//==============================================================================

if (!function_exists('short_date'))
{
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
        if ($day > DaysInMonth($month,$year))
        {
            $day -= DaysInMonth($month,$year);
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
        return sprintf("%02d %s %04d",$day,ShortMonthName($month),$year);
    }
}

//==============================================================================

if (!function_exists('title_date'))
{
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
        if ($day > DaysInMonth($month,$year))
        {
            $day -= DaysInMonth($month,$year);
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
        $dow = DMYToDoW($day,$month,$year);
        return sprintf("%s %02d %s %04d",ShortDayName($dow),$day,ShortMonthName($month),$year);
    }
}

//==============================================================================

if (!function_exists('long_title_date'))
{
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
        if ($day > DaysInMonth($month,$year))
        {
            $day -= DaysInMonth($month,$year);
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
        $dow = DMYToDoW($day,$month,$year);
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
    
        if (DMYToDoW($day,$month,$year) != 0)
        {
            // Abort if date is not a Sunday
            return "Error";
        }
        $date = sprintf("%04d-%02d-%02d",$year,$month,$day);
    
        // Calculate the dates of Septuagesima and Advent Sunday
        $date_of_septuagesima = AddDays(DateOfEaster($year),-63);
        $dow_of_christmas = DMYToDoW(25,12,$year);
        if ($dow_of_christmas == 0)
        {
            $days_in_advent = 28;
        }
        else
        {
            $days_in_advent = $dow_of_christmas + 21;
        }
        $date_of_advent_sunday = AddDays("$year-12-25",-$days_in_advent);
    
        if ($date < $date_of_septuagesima)
        {
            // Prior to Septuagesisma
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
            {
                return "Christmas Day";
            }
            else
            {
                return "Sunday after Christmas";
            }
        }
    }
}

//==============================================================================
?>
