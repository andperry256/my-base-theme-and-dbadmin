<html>
    <?php
    if ((!isset($title)) || (!isset($base_dir)) || (!isset($base_url))) {
        exit("One or more variables not preset.");
    }
    print("<title>$title</title>\n");
    ?>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php
        print("<link rel='stylesheet' href='$base_url/non_wp_styles.css' type='text/css' media='all' />\n");
        ?>
    </head>
    <body>
        <div id="main">
<?php
//==============================================================================

require("$base_dir/common_scripts/allowed_hosts.php");
if (!isset($allowed_hosts[$_SERVER['REMOTE_ADDR']])) {
    exit("Authentication failure.");
}
if (isset($_POST['submitted'])) {
    file_put_contents("$base_dir/maint_mode.php","<?php\n\$maint_mode = {$_POST['maint_mode']};\n?>\n");
}

if (is_file("$base_dir/maint_mode.php")) {
    include("$base_dir/maint_mode.php");
}
else {
    $maint_mode = false;
}

print("<form method=\"post\">");
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


//==============================================================================
?>
        <input type="submit" value="Update">
        <input type="hidden" name="submitted">
    </form>
</body>
</html>
