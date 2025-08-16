<?php
//==============================================================================

print("<link rel='stylesheet' id='dbadmin-styles-css'  href='$base_url/common_scripts/dbadmin/styles.css?v=$link_version' type='text/css' media='all' />\n");
if (get_session_var('theme_mode') == 'dark') {
    print("<link rel='stylesheet' id='dbadmin-styles-dark-css'  href='$base_url/common_scripts/dbadmin/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
    if ($location == 'local') {
        print("<link rel='stylesheet' id='dbadmin-styles-local-dark.css'  href='$base_url/common_scripts/dbadmin/styles-local-dark.css?v=$link_version' type='text/css' media='all' />\n");
    }
}
else {
    print("<link rel='stylesheet' id='dbadmin-styles-light-css'  href='$base_url/common_scripts/dbadmin/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
    if ($location == 'local') {
        print("<link rel='stylesheet' id='dbadmin-styles-local-light.css'  href='$base_url/common_scripts/dbadmin/styles-local-light.css?v=$link_version' type='text/css' media='all' />\n");
    }
}

//==============================================================================
