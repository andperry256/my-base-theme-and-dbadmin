<?php
//==============================================================================

require("classes.php");
require("functions.php");
require("datepicker_include.php");
require("widget_types.php");
require("$root_dir/maintenance/db_master_location.php");
$return_url = cur_url_par();
$dummy = '{';  // To remove false positive from syntax checker
//print("<script type=\"text/javascript\" src=\"$db_admin_url/form_funct.js\"></script>\n");
include_inline_javascript("$db_admin_dir/form_funct.js");

//==============================================================================
?>
<script>
  // Functions to select desktop/mobile mode
  function selectDesktopMode()
  {
    window.location.href = "<?php echo "$db_admin_url/load_viewing_mode.php?mode=desktop&returnurl=$return_url" ?>";
  }
  function selectMobileMode()
  {
    window.location.href = "<?php echo "$db_admin_url/load_viewing_mode.php?mode=mobile&returnurl=$return_url" ?>";
  }
</script>
<?php if (get_session_var('theme_mode') == 'dark'): ?>
  <style>
    /* These styles are intentionally included inline */
    #page a:link, #page a:visited {
      color: #ddd;
    }
    #page a:hover {
      color: #ff9;
    }
  </style>
<?php endif; ?>

<?php
//==============================================================================

function display_sidebar_content()
{
    global $custom_pages_path,$custom_pages_url,$base_url,$relative_path;
    $db = admin_db_connect();

    if (is_file("$custom_pages_path/$relative_path/page_logo.php")) {
        include("$custom_pages_path/$relative_path/page_logo.php");
    }
    elseif (is_file("$custom_pages_path/$relative_path/page_logo.png")) {
        print("<a href=\"$base_url/$relative_path\"><img src=\"$custom_pages_url/$relative_path/page_logo.png\" /></a>\n");
    }
    elseif (is_file("$custom_pages_path/$relative_path/page_logo.jpg")) {
        print("<a href=\"$base_url/$relative_path\"><img src=\"$custom_pages_url/$relative_path/page_logo.jpg\" /></a>\n");
    }
    if (is_file("$custom_pages_path/$relative_path/key_actions.php")) {
        include("$custom_pages_path/$relative_path/key_actions.php");
    }
    print("<div class=\"halfspace\">&nbsp</div>\n");
    print("<table class=\"sidebar-table\">\n");
    print("<tr><td class=\"sidebar-item\"><a href=\"$base_url/$relative_path/?-action=main\">Main Page</td></tr>\n");
    if (is_file("$custom_pages_path/$relative_path/actions/run_cron.php")) {
        print("<tr><td class=\"sidebar-item\"><a href=\"$base_url/$relative_path/?-action=run_cron\">Run Cron</td></tr>\n");
    }
    print("</table>");
    if (is_file("$custom_pages_path/$relative_path/custom_sidebar.php")) {
        require("$custom_pages_path/$relative_path/custom_sidebar.php");
    }
    else {
        $action_filter = '';
        if ((isset($_GET['-action'])) && (!empty($key_actions[$_GET['-action']]))) {
            update_session_var(['dba_key_action',$relative_path],$_GET['-action']);
        }
        elseif ((count($_GET) == 0) && (!empty($key_actions['main']))) {
            update_session_var(['dba_key_action',$relative_path],'main');
        }
        $latest_action = get_session_var(['dba_key_action',$relative_path]);
        print("<table class=\"sidebar-table\">");
        $add_clause = 'ORDER BY display_order ASC';
        $query_result = mysqli_select_query($db,'dba_sidebar_config','*','',[],$add_clause);
        while ($row = mysqli_fetch_assoc($query_result)) {
            $label = $row['label'];
            $action_name = $row['action_name'];
            $table_name = $row['table_name'];
            $link = $row['link'];
            if($label == '@@') {
                // End of action specific section
                $action_filter = '';
            }
            elseif (substr($label,0,1) == '@') {
                // Start of action specific section
                $action_filter = substr($label,1);
            }
            elseif ((empty($action_filter)) || ($action_filter == $latest_action)) {
                // Item can be displayed
                if (!empty($link)) {
                    // Sidebar item is a custom link
                    print("<tr><td class=\"sidebar-item\"><a href=\"$custom_pages_url/$relative_path/$link\"");
                    if ($row['new_window']) {
                          print(" target=\"_blank\"");
                    }
                    print(">$label</a></td></tr>\n");
                }
                elseif ((!empty($action_name)) || (!empty($table_name))) {
                    // Sidebar item is an action and/or table reference
                    print("<tr><td class=\"sidebar-item\"><a href=\"$base_url/$relative_path/?");
                    if (!empty($action_name)) {
                        print("-action=$action_name");
                    }
                    if (!empty($table_name)) {
                        if (!empty($action_name)) {
                            print("&");
                        }
                        print("-table=$table_name");
                    }
                    print("\"");
                    if ($row['new_window']) {
                        print(" target=\"_blank\"");
                    }
                    print(">$label</a></td></tr>\n");
                }
                else {
                    // Sidebar is a label only (i.e. no link)
                    print("<tr><td class=\"sidebar-item\">$label</td></tr>\n");
                }
            }
            else {
                // Action filter in force and not matching current/latest action
            }
        }
        print("</table>");
    }
}

//==============================================================================

function display_mobile_close_sidebar_button()
{
    global $base_url,$relative_path;
    $return_url = "$base_url/$relative_path";
    $par_processed = false;
    foreach ($_GET as $key => $value) {
        if ($key != 'showsidebar') {
            if (!$par_processed) {
                $return_url .= '?';
                $par_processed = true;
            }
            else {
                $return_url .= '&';
            }
            $par = urlencode($value);
            $return_url .= "$key=$par";
        }
    }
    print("<p><a href=\"$return_url\"><button>Close</button></a></p>\n");
}

//==============================================================================

function display_main_content($mode)
{
    global $custom_pages_path,$custom_pages_url,$base_url,$relative_path, $relative_sub_path, $alt_include_path;
    $db = admin_db_connect();

    // Process the URL parameters
    if (isset($_GET['-action'])) {
        $action = $_GET['-action'];
    }
    elseif (isset($_POST['-action'])) {
        $action = $_POST['-action'];
    }
    if (isset($_GET['-table'])) {
        $table = $_GET['-table'];
        $base_table = get_base_table($table);
        if (!isset($action)) {
            $action = 'list';
        }
    }
    if (isset($_GET['-startoffset'])) {
        $startoffset = $_GET['-startoffset'];
    }
    if (isset($_GET['-listsize'])) {
        $listsize = $_GET['-listsize'];
    }
    if (isset($_GET['-recordid'])) {
        $record_id = $_GET['-recordid'];
    }

    if ((!isset($action)) || ($action == 'home')) {
        // No action specified so open the default page.
        if (is_file("$custom_pages_path/$relative_path/actions/home.php")) {
            include("$custom_pages_path/$relative_path/actions/home.php");
        }
        elseif (is_file("$custom_pages_path/$relative_path/actions/main.php")) {
            output_page_header();
            include("$custom_pages_path/$relative_path/actions/main.php");
        }
        elseif (is_file("$alt_include_path/actions/home.php")) {
            include("$alt_include_path/actions/home.php");
        }
        elseif (is_file("$alt_include_path/actions/main.php")) {
            output_page_header();
            include("$alt_include_path/actions/main.php");
        }
    }
    elseif (isset($action)) {
        if ($action == 'main') {
            output_page_header();
        }

        // Process the given action.
        if (isset($table)) {
            check_new_action($action,$table);
        }
        else {
            check_new_action($action,'');
        }
        switch ($action) {
            case 'list':
                if ((is_file("$custom_pages_path/$relative_path/tables/$table/$table.php")) &&
                    (!class_exists ("tables_$table",false))) {
                    require("$custom_pages_path/$relative_path/tables/$table/$table.php");
                }
                elseif ((is_file("$alt_include_path/tables/$table/$table.php")) &&
                        (!class_exists ("tables_$table",false))) {
                    require("$alt_include_path/tables/$table/$table.php");
                }
                if (is_file("$custom_pages_path/$relative_path/tables/$table/custom_list.php")) {
                    require("$custom_pages_path/$relative_path/tables/$table/custom_list.php");
                }
                elseif (is_file("$alt_include_path/tables/$base_table/custom_list.php")) {
                    require("$alt_include_path/tables/$base_table/custom_list.php");
                }
                else {
                    $params = [];
                    $params['mode'] = $mode;
                    display_table($params);
                }
                break;

            case 'edit':
                if (is_file("$custom_pages_path/$relative_path/tables/$table/custom_edit.php")) {
                    require("$custom_pages_path/$relative_path/tables/$table/custom_edit.php");
                }
                elseif (is_file("$alt_include_path/tables/$base_table/custom_edit.php")) {
                    require("$alt_include_path/tables/$base_table/custom_edit.php");
                }
                else {
                    $params = [];
                    handle_record('edit',$params);
                }
                break;

            case 'new':
                if (is_file("$custom_pages_path/$relative_path/tables/$table/custom_new.php")) {
                    require("$custom_pages_path/$relative_path/tables/$table/custom_new.php");
                }
                elseif (is_file("$alt_include_path/tables/$base_table/custom_new.php")) {
                    require("$alt_include_path/tables/$base_table/custom_new.php");
                }
                else {
                    $params = [];
                    if (isset($_GET['-presets'])) {
                        $params['presets'] = urlencode($_GET['-presets']);
                    }
                    handle_record('new',$params);
                }
                break;

            case 'view':
                if (is_file("$custom_pages_path/$relative_path/tables/$table/custom_view.php")) {
                    require("$custom_pages_path/$relative_path/tables/$table/custom_view.php");
                }
                elseif (is_file("$alt_include_path/tables/$base_table/custom_view.php")) {
                    require("$alt_include_path/tables/$base_table/custom_view.php");
                }
                else {
                    $params = [];
                    handle_record('view',$params);
                }
                break;

            case 'update_table_data1':
                print("<h1>Update Table Data</h1>\n");
                print("<p><strong>N.B.</strong>This operation will cause a bulk database update.");
                print(" Ticking one or both of the options below may cause adverse performace in web mode and may therefore need to be reserved for command line mode.</p>\n");
                print("<form method=\"post\" action=\"$base_url/$relative_path/?-action=update_table_data2\">\n");
                print("<p><input type=\"checkbox\" name=\"update_charsets\">&nbsp;&nbsp;Update charsets and collation</p>\n");
                print("<p><input type=\"checkbox\" name=\"optimise\">&nbsp;&nbsp;Optimise tables</p>\n");
                print("<input type=\"submit\" value=\"Continue\">\n");
                print("</form>\n");
                break;

            case 'update_table_data2':
                print("<h1>Update Table Data</h1>\n");
                $update_charsets = (isset($_POST['update_charsets']));
                $optimise = (isset($_POST['optimise']));
                update_table_data($update_charsets,$optimise);
                break;

            case 'renumber_records1':
                print("<h1>Renumber Records (All Tables)</h1>\n");
                print("<p>This operation will cause all sequenced tables to be renumbered.<br />");
                print("(To renumber an individual table, go to the associated table editing screen.)</p>\n");
                print("<p><a href=\"$base_url/$relative_path/?-action=renumber_records2\"><button>Continue</button></a></p>\n");
                break;

            case 'renumber_records2':
                print("<h1>Renumber Records (All Tables)</h1>\n");
                $where_clause = 'renumber_enabled=1';
                $query_result = mysqli_select_query($db,'dba_table_info','*',$where_clause,[],'');
                while ($row = mysqli_fetch_assoc($query_result)) {
                    renumber_records($row['table_name']);
                }
                print("<p>Operation completed.</p>\n");
                break;

            case 'dbsync':
                sync_databases(admin_db_name());
                break;

            case 'search_and_replace':
                search_and_replace(admin_db_name());
                break;

            default:
                if (is_file("$custom_pages_path/$relative_path/actions/$action.php")) {
                    include("$custom_pages_path/$relative_path/actions/$action.php");
                }
                elseif (is_file("$alt_include_path/actions/$action.php")) {
                    include("$alt_include_path/actions/$action.php");
                }
                else {
                    print("<p>Script for action <em>$action</em> not found.</p>\n");
                }
                break;
        }
    }
}

//==============================================================================

$db = admin_db_connect();

// Temporary code
mysqli_query_normal($db,"DROP TABLE dba_master_location");

if (!isset($db_master_location)) {
    exit("ERROR - Master location cannot be determined");
}
if ((!isset($support_mobile)) || (!($support_mobile))) {
    print("<div class=\"no-mobile-support\"><strong>N.B. </strong>This page is not optimised for mobile viewing. For a better user experience please use a computer or tablet.</div>\n");
}
$db_sub_path = str_replace('dbadmin/','',$relative_path);
update_session_var("dbauth-$db_sub_path","1");
if (($db_master_location[$db_sub_path] != $location) &&
    ((!isset($override_db_sync_warning[$db_sub_path])) || (!$override_db_sync_warning[$db_sub_path]))) {
    // Output warning(s) about not using the master copy of the database
    print("<p class=\"small\"><span class=\"highlight-warning\">WARNING</span> - You are not using the master copy of the database. Any changes are liable to be lost on the next database synchronisation.");
    if ((isset($_GET['-table'])) && ($db_itservices = itservices_db_connect())) {
        $table = get_base_table($_GET['-table']);
        $where_clause = 'site_path=? AND table_name=?';
        $where_values = ['s',$local_site_dir,'s',$table];
        if (function_exists('admin_sub_path')) {
            $sub_path = admin_sub_path();
            $where_clause .= ' AND sub_path=?';
            $where_values = array_merge($where_values,['s',$sub_path]);
        }
        if (mysqli_num_rows(mysqli_select_query($db_itservices,'nosync_tables','*',$where_clause,$where_values,'')) > 0) {
            print("<br /><span class=\"highlight-success\">BUT NOTE</span> - The current table appears to be designated as 'no sync', which means that data may not be lost.\n");
        }
    }
    print("</p>\n");
}
create_view_structure('_view_dba_table_fields','dba_table_fields','table_name IS NOT NULL');
mysqli_query_normal($db,"CREATE OR REPLACE VIEW _view_dba_table_fields AS SELECT * FROM dba_table_fields ORDER BY table_name ASC, display_order ASC");

// Load the table class if applicable
if ((isset($_GET['-table'])) && (is_file("$custom_pages_path/$relative_path/tables/{$_GET['-table']}/{$_GET['-table']}.php"))) {
    require("$custom_pages_path/$relative_path/tables/{$_GET['-table']}/{$_GET['-table']}.php");
}

print("<div id=\"dbadmin-main\">\n");

if ((!isset($hide_dbadmin)) || (!$hide_dbadmin)) {
    // Mobile sidebar (hidden in desktop mode)
    print("<div id=\"dbadmin-mobile-sidebar\">\n");
    if (isset($_GET['showsidebar'])) {
        display_sidebar_content();
        display_mobile_close_sidebar_button();
    }
    else {
        $sidebar_url = './?showsidebar';
        foreach ($_GET as $key => $value) {
            $par = urlencode($value);
            $sidebar_url .= "&$key=$par";
        }
        print("<p><a href=\"$sidebar_url\"><button>Shortcuts</button></a></p>\n");
    }
    print("</div> <!--#dbadmin-mobile-sidebar-->\n");

    // Desktop sidebar (hidden in mobile mode)
    print("<div id=\"dbadmin-desktop-sidebar\">\n");
    display_sidebar_content();
    print("</div> <!--#dbadmin-desktop-sidebar-->\n");
}

// Main content
print("<div id=\"dbadmin-content\">\n");

// Display button to select desktop or mobile mode
$mode = get_viewing_mode();
if ($mode == 'mobile') {
    print("<div id=select-desktop>\n");
    print("<p><button style=\"background-color:gold;\" onclick=\"selectDesktopMode()\">Select Desktop Mode</button><br />");
    print("<span style=\"font-size:0.8em\">(uses a cookie)</span></p>\n");
    print("</div> <!--#select-desktop -->\n");
    print("<div style=\"clear:both\"></div>\n");
}
else {
    print("<div id=select-mobile>\n");
    print("<p><button style=\"background-color:gold;\" onclick=\"selectMobileMode()\">Select Mobile Mode</button><br />");
    print("<span style=\"font-size:0.8em\">(uses a cookie)</span><p>\n");
    print("</div> <!--#select-mobile -->\n");
    print("<div style=\"clear:both\"></div>\n");
}
display_main_content($mode);

print("</div> <!--#dbadmin-content-->\n");

print("</div> <!--#dbadmin-main-->\n");

// Output common links at foot of page
if ((!isset($hide_dbadmin)) || (!$hide_dbadmin)) {
    print("<p class=\"small\"><a href=\"$base_url/$relative_path/?-table=dba_sidebar_config\">Sidebar&nbsp;Config</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-table=dba_table_info\">Table&nbsp;Info</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-table=_view_dba_table_fields\">Table&nbsp;Fields</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-table=dba_relationships\">Relationships</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-table=dba_change_log\">Change Log</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-action=update_table_data1\">Update&nbsp;Table&nbsp;Data</a>");
    print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-action=renumber_records1\">Renumber&nbsp;All</a>");
    if ($location == 'local') {
        print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-action=dbsync\">Sync&nbsp;Databases</a>");
        print("&nbsp;&nbsp; <a href=\"$base_url/$relative_path/?-action=search_and_replace\">Search&nbsp;&amp;&nbsp;Replace</a>");
    }
    if ((is_file("$base_dir/admin_logout.php")) && (!is_file("$custom_pages_path/$relative_path/logout.php"))) {
        print("&nbsp;&nbsp; <a href=\"$base_url/admin_logout.php\">Logout</a>");
    }
    print("</p>\n");
}
print("<script type=\"text/javascript\" src=\"$db_admin_url/no_resubmit.js\"></script>\n");
//include_inline_javascript("$db_admin_url/no_resubmit.js");

//==============================================================================
?>
