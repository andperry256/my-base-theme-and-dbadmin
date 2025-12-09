<html>
    <?php
    print("<title>Maintenance Mode</title>\n");
    ?>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php
        print("<link rel='stylesheet' href='https://www.andperry.com/non_wp_styles.css' type='text/css' media='all' />\n");
        print("<meta http-equiv=\"refresh\" content=\"300;URL='{$_SERVER['PHP_SELF']}'\">\n");
        ?>
    </head>
    <body>
        <div id="main">
<?php
//==============================================================================

if (is_file('/Config/linux_pathdefs.php')) {
    include('/Config/linux_pathdefs.php');
    $uri_elements = explode('/',ltrim($_SERVER['REQUEST_URI'],'/'));
    $base_dir = "/media/Data/www/{$uri_elements[0]}";
    $site_location = 'local';
}
else {
    $path_elements = explode('/',ltrim(__DIR__,'/'));
    $base_dir = "/{$path_elements[0]}/{$path_elements[1]}/public_html";
    $site_location = 'online';
}
require("$base_dir/path_defs.php");
require("$base_dir/common_scripts/allowed_hosts.php");
require("$base_dir/common_scripts/local_ip_funct.php");
if ((!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) && (!is_local_ip($_SERVER['REMOTE_ADDR']))) {
    exit("Authentication failure.");
}
if (isset($_POST['submitted'])) {
    if (is_file("$base_dir/maint_mode.php")) {
        file_put_contents("$base_dir/maint_mode.php","<?php\n\$maint_mode = {$_POST['maint_mode']};\n?>\n");
    }
    if ((is_file("$base_dir/.maintenance")) && (isset($_POST['clear_wp_maint_mode']))) {
        unlink ("$base_dir/.maintenance");
    }
}

print("<p>Maintenance Mode for site [$local_site_dir/$site_location]");
print("<form method=\"post\">");
if (is_file("$base_dir/maint_mode.php")) {
    include("$base_dir/maint_mode.php");
    print("<input type=\"radio\" name=\"maint_mode\" value=\"false\"");
    if (!$maint_mode) {
        print(" checked");
    }
    print(">&nbsp;Disabled&nbsp; ");
    print("<input type=\"radio\" name=\"maint_mode\" value=\"true\"");
    if ($maint_mode) {
        print(" checked");
    }
    print(">&nbsp;");
    print ($maint_mode) ? "<span style=\"color:red\">Enabled</span>" : "Enabled";
    print("<br /><br />\n");
}
if (is_file("$base_dir/.maintenance")) {
    print("<input type=\"checkbox\" name=\"clear_wp_maint_mode\"> <span style=\"color:red\">Clear WP Maintenance Mode</span>");
    print("<br /><br />\n");
}

//==============================================================================
?>
        <input type="submit" value="Update">
        <input type="hidden" name="submitted">
    </form>
</body>
</html>
