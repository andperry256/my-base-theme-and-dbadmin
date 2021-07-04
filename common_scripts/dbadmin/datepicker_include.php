<?php
  global $CalendarIcon, $NewDateStartYear, $DBAdminURL;
  if (!function_exists('datepicker_widget'))
  {
    if (!isset($CalendarIcon))
    {
      $CalendarIcon = 'calendar-cyan-20px.gif';
    }
    if (!isset($NewDateStartYear))
    {
      $NewDateStartYear = 2010;
    }
    print("<link rel='stylesheet' type=\"text/css\" href=\"$DBAdminURL/datepicker/css/jquery.datepick.css?v=$link_version\">\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/jquery-1.11.0.min.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.plugin.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick.js\"></script>\n");
    print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick-en-GB.js\"></script>\n");
    print("<div style=\"display: none;\"><img id=\"calImg\" src=\"$DBAdminURL/datepicker/img/$CalendarIcon\" alt=\"Popup\" class=\"trigger\"></div>");

    function datepicker_widget($field_name,$field_value)
    {
      global $NewDateStartYear;
      print("<input type=\"text\" id=\"$field_name\" name=\"$field_name\"  value=\"$field_value\" size=\"10\" style=\"margin-right:0.5em\"
             data-datepick=\"showOtherMonths: true, firstDay: 7, dateFormat: 'yyyy-mm-dd', minDate: 'new Date($NewDateStartYear, 1 - 1, 1)'\">\n");
      print("<script>
             $('#$field_name').datepick({showTrigger: '#calImg'});
             $('#$field_name').datepick($.extend( {pickerClass: 'my-picker'}, $.datepick.regionalOptions['en-GB']));
             </script>");
    }
  }
?>
