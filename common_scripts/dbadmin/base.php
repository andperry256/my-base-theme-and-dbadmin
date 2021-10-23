<?php
  require("classes.php");
  require("functions.php");
  require("datepicker_include.php");
  require("widget_types.php");
  require("$RootDir/maintenance/db_master_location.php");
  $return_url = cur_url_par();
  $dummy = '{';  // To remove false positive from syntax checker
?>
<script type="text/javascript" language="javascript">
//==============================================================================

// Function to check/un-check all check boxes in a form in response to
// an action on the designated 'check all' check box.
function checkAll(source)
{
  var checkboxes = new Array();
  checkboxes = document.getElementsByTagName('input');
  for (var i=0; i<checkboxes.length; i++)  {
    if (checkboxes[i].type == 'checkbox')   {
      checkboxes[i].checked = source.checked;
    }
  }
}

// Function to submit the form with no specific option
function submitForm(form)
{
  element = document.getElementById("submitted");
  element.value = '#';
  form.submit();
}

// Functions to select desktop/mobile mode
function selectDesktopMode()
{
  window.location.href = "<?php echo "$DBAdminURL/load_viewing_mode.php?mode=desktop&returnurl=$return_url" ?>";
}
function selectMobileMode()
{
  window.location.href = "<?php echo "$DBAdminURL/load_viewing_mode.php?mode=mobile&returnurl=$return_url" ?>";
}

// Function to apply a search to the table
function applySearch(form)
{
  element = document.getElementById("submitted");
  element.value = 'apply_search';
  form.submit();
}

// Function to confirm submission for a delete action
function confirmDelete(form)
{
  if (confirm("Delete the selected records?")) {
    element = document.getElementById("submitted");
    element.value = 'delete';
    form.submit();
  }
}

// Functions to perform submission for an update action
function selectUpdate(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_update';
  form.submit();
}
function selectUpdateAll(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_update_all';
  form.submit();
}
function runUpdate(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_update';
  form.submit();
}
function runUpdateAll(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_update_all';
  form.submit();
}

// Functions to perform submission for a copy action
function selectCopy(form)
{
  element = document.getElementById("submitted");
  element.value = 'select_copy';
  form.submit();
}
function runCopy(form)
{
  element = document.getElementById("submitted");
  element.value = 'run_copy';
  form.submit();
}

//==============================================================================
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
  global $CustomPagesPath,$CustomPagesURL,$BaseURL,$RelativePath;
  $db = admin_db_connect();

  if (is_file("$CustomPagesPath/$RelativePath/page_logo.php"))
  {
    include("$CustomPagesPath/$RelativePath/page_logo.php");
  }
  elseif (is_file("$CustomPagesPath/$RelativePath/page_logo.png"))
  {
    print("<a href=\"$BaseURL/$RelativePath\"><img src=\"$CustomPagesURL/$RelativePath/page_logo.png\" /></a>\n");
  }
  elseif (is_file("$CustomPagesPath/$RelativePath/page_logo.jpg"))
  {
    print("<a href=\"$BaseURL/$RelativePath\"><img src=\"$CustomPagesURL/$RelativePath/page_logo.jpg\" /></a>\n");
  }
  print("<p class=\"sidebar-item\"><a href=\"$BaseURL/$RelativePath/?-action=main\">Main Page</a>");
  if (is_file("$CustomPagesPath/$RelativePath/custom_sidebar.php"))
  {
    require("$CustomPagesPath/$RelativePath/custom_sidebar.php");
  }
  else
  {
    print("<table class=\"sidebar-table\">");
    $query_result = mysqli_query($db,"SELECT * FROM dba_sidebar_config ORDER BY display_order ASC");
    while ($row = mysqli_fetch_assoc($query_result))
    {
      $label = $row['label'];
      $action_name = $row['action_name'];
      $table_name = $row['table_name'];
      $link = $row['link'];

      if (!empty($link))
      {
        // Sidebar item is a custom link
        print("<tr><td class=\"sidebar-item\"><a href=\"$CustomPagesURL/$RelativePath/$link\"");
        if ($row['new_window'])
        {
            print(" target=\"_blank\"");
        }
        print(">$label</a></td></tr>\n");
      }
      elseif ((!empty($action_name)) || (!empty($table_name)))
      {
        // Sidebar item is an action and/or table reference
        print("<tr><td class=\"sidebar-item\"><a href=\"$BaseURL/$RelativePath/?");
        if (!empty($action_name))
        {
          print("-action=$action_name");
        }
        if (!empty($table_name))
        {
          if (!empty($action_name))
          {
            print("&");
          }
          print("-table=$table_name");
        }
        print("\">$label</a></td></tr>\n");
      }
      else
      {
        // Sidebar is a label only (i.e. no link)
        print("<tr><td class=\"sidebar-item\">$label</td></tr>\n");
      }
    }
    print("</table>");
  }
}

//==============================================================================

function display_mobile_close_sidebar_button()
{
  global $BaseURL,$RelativePath;
  $return_url = "$BaseURL/$RelativePath";
  $par_processed = false;
  foreach ($_GET as $key => $value)
  {
    if ($key != 'showsidebar')
    {
      if (!$par_processed)
      {
        $return_url .= '?';
        $par_processed = true;
      }
      else
      {
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
  global $CustomPagesPath,$CustomPagesURL,$BaseURL,$RelativePath;
  $db = admin_db_connect();

  // Process the URL parameters
  if (isset($_GET['-action']))
  {
    $action = $_GET['-action'];
  }
  elseif (isset($_POST['-action']))
  {
    $action = $_POST['-action'];
  }
  if (isset($_GET['-table']))
  {
    $table = $_GET['-table'];
    if (!isset($action))
    {
      $action = 'list';
    }
  }
  if (isset($_GET['-startoffset']))
  {
    $startoffset = $_GET['-startoffset'];
  }
  if (isset($_GET['-listsize']))
  {
    $listsize = $_GET['-listsize'];
  }
  if (isset($_GET['-recordid']))
  {
    $record_id = $_GET['-recordid'];
  }

  if ((!isset($action)) || ((isset($action)) && ($action == 'home')))
  {
    // No action specified so open the default page
    if (is_file("$CustomPagesPath/$RelativePath/actions/home.php"))
    {
      include("$CustomPagesPath/$RelativePath/actions/home.php");
    }
    elseif (is_file("$CustomPagesPath/$RelativePath/actions/main.php"))
    {
      output_page_header();
      include("$CustomPagesPath/$RelativePath/actions/main.php");
    }
  }
  elseif (isset($action))
  {
    if ($action == 'main')
    {
      output_page_header();
    }

    // Process the given action
    if (isset($table))
    {
      check_new_action($action,$table);
    }
    else
    {
      check_new_action($action,'');
    }
    switch ($action)
    {
      case 'list':
        if (is_file("$CustomPagesPath/$RelativePath/tables/$table/custom_list.php"))
        {
          require("$CustomPagesPath/$RelativePath/tables/$table/custom_list.php");
        }
        else
        {
          $params = array();
          $params['mode'] = $mode;
          display_table($params);
        }
        break;

      case 'edit':
        if (is_file("$CustomPagesPath/$RelativePath/tables/$table/custom_edit.php"))
        {
          require("$CustomPagesPath/$RelativePath/tables/$table/custom_edit.php");
        }
        else
        {
          $params = array();
          handle_record('edit',$params);
        }
        break;

      case 'new':
        if (is_file("$CustomPagesPath/$RelativePath/tables/$table/custom_new.php"))
        {
          require("$CustomPagesPath/$RelativePath/tables/$table/custom_new.php");
        }
        else
        {
          $params = array();
          if (isset($_GET['-presets']))
          {
            $params['presets'] = urlencode($_GET['-presets']);
          }
          handle_record('new',$params);
        }
        break;

      case 'view':
        if (is_file("$CustomPagesPath/$RelativePath/tables/$table/custom_view.php"))
        {
          require("$CustomPagesPath/$RelativePath/tables/$table/custom_view.php");
        }
        else
        {
          $params = array();
          handle_record('view',$params);
        }
        break;

      case 'update_table_data1':
        print("<h1>Update Table Data</h1>\n");
        print("<p><strong>N.B.</strong>This operation will cause a bulk database update.");
        print(" Ticking one or both of the options below may cause adverse performace in web mode and may therefore need to be reserved for command line mode.</p>\n");
        print("<form method=\"post\" action=\"$BaseURL/$RelativePath/?-action=update_table_data2\">\n");
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
        print("<h1>Renumber Records</h1>\n");
        print("<p>This operation will cause a bulk database update.</p>\n");
        print("<p><a href=\"$BaseURL/$RelativePath/?-action=renumber_records2\"><button>Continue</button></a></p>\n");
        break;

      case 'renumber_records2':
        print("<h1>Renumber Records</h1>\n");
        $query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE renumber_enabled=1 ORDER BY table_name ASC");
        while ($row = mysqli_fetch_assoc($query_result))
        {
          renumber_records($row['table_name']);
        }
        print("<p>Operation completed.</p>\n");
        break;

      case 'dbsync':
        sync_databases(admin_db_name());
        break;

      case 'export_table':
        export_table();
        print("<p><a href=\"./?-action=multi_export\">Multiple Export</a> (export all tables with the auto dump flag set)</p>\n");
        break;

      case 'multi_export':
        export_multiple_tables();
        break;

      default:
        if (is_file("$CustomPagesPath/$RelativePath/actions/$action.php"))
        {
          include("$CustomPagesPath/$RelativePath/actions/$action.php");
        }
        else
        {
          print("<p>Script for action <em>$action</em> not found.</p>\n");
        }
        break;
    }
  }
}

//==============================================================================

$db = admin_db_connect();

// Temporary code
mysqli_query($db,"DROP TABLE dba_master_location");

if (!isset($db_master_location))
{
  exit("ERROR - Master location cannot be determined");
}
if ((!isset($SupportMobile)) || (!($SupportMobile)))
{
  print("<div class=\"no-mobile-support\"><strong>N.B. </strong>This page is not optimised for mobile viewing. For a better user experience please use a computer or tablet.</div>\n");
}
$db_sub_path = str_replace('dbadmin/','',$RelativePath);
if (($db_master_location[$db_sub_path] != $Location) &&
    ((!isset($override_db_sync_warning[$db_sub_path])) || (!$override_db_sync_warning[$db_sub_path])))
{
  print("<p class=\"small\"><span class=\"highlight-warning\">WARNING</span> - You are not using the master copy of the database. Any changes are liable to be lost on the next database synchronisation.<p>\n");
}
create_view_structure('_view_dba_table_fields','dba_table_fields','table_name IS NOT NULL');
mysqli_query($db,"CREATE OR REPLACE VIEW _view_dba_table_fields AS SELECT * FROM dba_table_fields ORDER BY table_name ASC, display_order ASC");

// Load the table class if applicable
if ((isset($_GET['-table'])) && (is_file("$CustomPagesPath/$RelativePath/tables/{$_GET['-table']}/{$_GET['-table']}.php")))
{
  require("$CustomPagesPath/$RelativePath/tables/{$_GET['-table']}/{$_GET['-table']}.php");
}

print("<div id=\"dbadmin-main\">\n");

// Mobile sidebar (hidden in desktop mode)
print("<div id=\"dbadmin-mobile-sidebar\">\n");
if (isset($_GET['showsidebar']))
{
  display_sidebar_content();
  display_mobile_close_sidebar_button();
}
else
{
  $sidebar_url = './?showsidebar';
  foreach ($_GET as $key => $value)
  {
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

// Main content
print("<div id=\"dbadmin-content\">\n");

// Display button to select desktop or mobile mode
$mode = get_viewing_mode();
if ($mode == 'mobile')
{
  print("<div id=select-desktop>\n");
  print("<p><button onclick=\"selectDesktopMode()\">Select Desktop Mode</button><br />");
  print("<span style=\"font-size:0.8em\">(uses a cookie)</span></p>\n");
  print("</div> <!--#select-desktop -->\n");
  print("<div style=\"clear:both\"></div>\n");
}
else
{
  print("<div id=select-mobile>\n");
  print("<p><button onclick=\"selectMobileMode()\">Select Mobile Mode</button><br />");
  print("<span style=\"font-size:0.8em\">(uses a cookie)</span><p>\n");
  print("</div> <!--#select-mobile -->\n");
  print("<div style=\"clear:both\"></div>\n");
}
display_main_content($mode);

print("</div> <!--#dbadmin-content-->\n");

print("</div> <!--#dbadmin-main-->\n");

// Output common links at foot of page
print("<p class=\"small\"><a href=\"$BaseURL/$RelativePath/?-table=dba_sidebar_config\">Sidebar&nbsp;Config</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-table=dba_table_info\">Table&nbsp;Info</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-table=_view_dba_table_fields\">Table&nbsp;Fields</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-table=dba_relationships\">Relationships</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-action=update_table_data1\">Update&nbsp;Table&nbsp;Data</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-action=renumber_records1\">Renumber&nbsp;Records</a>");
print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-action=export_table\">Export&nbsp;Table(s)</a>");
if ($Location == 'local')
{
  print("&nbsp;&nbsp; <a href=\"$BaseURL/$RelativePath/?-action=dbsync\">Sync&nbsp;Databases</a>");
}
if ((is_file("$BaseDir/admin_logout.php")) && (!is_file("$CustomPagesPath/$RelativePath/logout.php")))
{
  print("&nbsp;&nbsp; <a href=\"$BaseURL/admin_logout.php\">Logout</a>");
}
print("</p>\n");

//==============================================================================
?>
<script type="text/javascript" language="javascript">
//==============================================================================

// The following code prevents a form from re-submitting on a page refresh.
if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}

//==============================================================================
</script>
