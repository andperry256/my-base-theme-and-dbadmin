<?php
  global $CalendarIcon;
  if (!function_exists('datepicker_widget'))
  {
    if (!isset($CalendarIcon))
    {
      $CalendarIcon = 'calendar-cyan-20px.gif';
    }
    print("<link rel='stylesheet' type=\"text/css\" href=\"$DBAdminURL/datepicker/css/jquery.datepick.css?v=$link_version\">\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/jquery-1.11.0.min.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.plugin.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick-en-GB.js\"></script>\n");
    print("<div style=\"display: none;\"><img id=\"calImg\" src=\"$DBAdminURL/datepicker/img/$CalendarIcon\" alt=\"Popup\" class=\"trigger\"></div>");

    function datepicker_widget($field_name,$field_value)
    {
      global $new_date_start_year, $new_date_start_month;
      if (!isset($new_date_start_year))
      {
        $new_date_start_year = 2010;
      }
      if (!isset($new_date_start_month))
      {
        $new_date_start_month = 1;
      }
      print("<input type=\"text\" id=\"$field_name\" name=\"$field_name\"  value=\"$field_value\" size=\"10\" style=\"margin-right:0.5em\"
             data-datepick=\"showOtherMonths: true, firstDay: 7, dateFormat: 'yyyy-mm-dd', minDate: 'new Date($new_date_start_year, $new_date_start_month -1, 1)'\">\n");
      print("<script>
             $('#$field_name').datepick({showTrigger: '#calImg'});
             $('#$field_name').datepick($.extend( {pickerClass: 'my-picker'}, $.datepick.regionalOptions['en-GB']));
             </script>");
    }
  }
?>
