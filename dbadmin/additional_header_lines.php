<?php
  print("<link rel='stylesheet' id='dbadmin-styles-css'  href='$BaseURL/_link_to_common/dbadmin/styles.css?v=$link_version' type='text/css' media='all' />\n");
  if ($_SESSION['theme_mode'] == 'dark')
  {
    print("<link rel='stylesheet' id='dbadmin-styles-dark-css'  href='$BaseURL/_link_to_common/dbadmin/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
  }
  else
  {
    print("<link rel='stylesheet' id='dbadmin-styles-light-css'  href='$BaseURL/_link_to_common/dbadmin/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
  }
  print("<link rel='stylesheet' type=\"text/css\" href=\"$DBAdminURL/datepicker/css/jquery.datepick.css?v=$link_version\">\n");
  print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/jquery-1.11.0.min.js\"></script>\n");
  print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.plugin.js\"></script>\n");
  print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick.js\"></script>\n");
  print("<script type=\"text/javascript\" src=\"$DBAdminURL/datepicker/js/jquery.datepick-en-GB.js\"></script>\n");
?>
