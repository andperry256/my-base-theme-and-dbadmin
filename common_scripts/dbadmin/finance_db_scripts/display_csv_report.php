<?php
//==============================================================================

if (is_file("/Config/linux_pathdefs.php")) {
    // Local server
    $local_site_dir = 'andperry.com';
}
require_once("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
require_once("$base_dir/common_scripts/session_funct.php");
run_session();
$link_version = date('ym').'01';
require("$base_dir/last_preset_link_version.php");
if ($link_version < $last_preset_link_version) {
    $link_version = $last_preset_link_version;
}

//==============================================================================
?>
<html>
    <head>
        <?php if (isset($title)) print("<title>$title</title>\n"); ?>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php
        print("<link rel='stylesheet' href='$base_url/non_wp_styles.css?v=$link_version' type='text/css' media='all' />\n");
        ?>
    </head>
    <body>
<?php
//==============================================================================

if (isset($_SESSION['csv_report'])) {
    $line = 1;
    while (true) {
        $line_index = sprintf("%06d",$line++);
        if (isset($_SESSION['csv_report'][$line_index])) {
            $line_content = $_SESSION['csv_report'][$line_index];
            $line_content = str_replace("\n","<br />\n",$line_content);
            print("$line_content<br>\n");
        }
        else {
            break;
        }
    }
}
else {
    print("<p>CSV report not found.</p>");
}

//==============================================================================
?>
    </body>
</html>
