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
?>
