<?php
//==============================================================================

require("allowed_hosts.php");
if (isset($_GET['site'])) {
    $local_site_dir = $_GET['site'];
}
if (is_file("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php")) {
    require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
}
else {
    exit("Path definitions script not found");
}
print("<style>\n");
print("html { font-size: 100%; font-family: Arial, Helvetica, sans-serif; }\n");
print("</style>\n");
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication failure");
}
if (!isset($local_site_dir)) {
    exit("Site not specified");
}
if (!isset($base_dir)) {
    exit("Site structure not present");
}
$log_file_path = "$root_dir/logs/wp_cron.log";

// Clear log if required
$clear_time_file_path = "$root_dir/logs/wp_cron_log_clear_time.txt";
if (((isset($_POST['clear'])) || (isset($_GET['clear']))) && (is_file($log_file_path))) {
    unlink($log_file_path);
    $ofp = fopen($clear_time_file_path,'w');
    fprintf($ofp,date('Y-m-d H:i:s'));
    fclose($ofp);
}
?>

<fieldset>
<form method="post">
<p>Clear&nbsp;Log:&nbsp;<input type="checkbox" name="clear" />
    <?php
    if (is_file($clear_time_file_path)) {
        print("&nbsp;&nbsp;&nbsp; [Last cleared ".trim(file_get_contents($clear_time_file_path)."]"));
    }
    ?>
</p>
<p><input type="submit" value="Update/Reload"></p>
<input type="hidden" name="submitted" />
</form>
</fieldset>
<?php
$files_found = false;
print("<br />\n");
if (is_file($log_file_path)) {
    $content = file($log_file_path);
    foreach ($content as $line) {
        print("$line<br />\n");
    }
    $files_found = true;
}
else {
    print("No WP cron log found\n");
}

//==============================================================================
?>
