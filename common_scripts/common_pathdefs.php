<?php
//==============================================================================
//Common path definitions
//==============================================================================
/*
The following variables must be pre-defined by the parent script (normally the
site's own path_defs.php):

$local_site_dir
$cpanel_user
$main_domain
$theme_name

In some contexts (e.g. running on a sub-domain), certain paths may need to be
subsequently updated by the parent script.
*/
//==============================================================================

global $wpdb;
if (!isset($wpdb)) {
    // Outside WP environment
    date_default_timezone_set('Europe/London');
}
ini_set('default_charset','utf-8');
setlocale(LC_ALL, 'en_GB.utf8');

//==============================================================================

if ((!function_exists('user_access_level')) && (!empty($define_user_access_level_funct))) {
    function user_access_level($username)
    {
        global $auth_dbid, $auth_db_table, $auth_db_access_level_field;
        $db = db_connect($auth_dbid);
        $where_clause = 'username=?';
        $where_values = ['s',$username];
        return ($row = mysqli_fetch_assoc(mysqli_select_query($db,$auth_db_table,'*',$where_clause,$where_values,'')))
            ? $row[$auth_db_access_level_field]
            : DEFAULT_ACCESS_LEVEL;
    }
}

//==============================================================================

global $base_dir;
global $base_url;
global $db_mode;
global $location;
global $private_key_path;
global $relative_path;
global $root_dir;

if ((empty($local_site_dir)) || (empty($cpanel_user)) ||
    (empty($main_domain)) || (empty($theme_name))) {
    exit("One or more essential variables not defined");
}

$online_root_dir = "/home/$cpanel_user";
if (is_dir($online_root_dir)) {

    // Online Site
    $location = 'real';
    $db_mode = 'normal';
    $root_dir = $online_root_dir;
    $base_dir = "$root_dir/public_html";
    $base_url = "https://www.$main_domain";
    if (!is_link("$root_dir/common_bash")) {
        symlink("$base_dir/common_scripts/bash","$root_dir/common_bash");
    }
}
elseif (is_file("/Config/linux_pathdefs.php")) {

    // Local Server
    $location = 'local';
    require("/Config/linux_pathdefs.php");
    $db_mode = $site_db_mode[$local_site_dir] ?? 'normal';
    if (is_dir("$www_root_dir/$local_site_dir")) {
        $base_url = "$localhost_root_url/$local_site_dir";
    }
    else {
        $base_url = "$localhost_root_url/Sites/$local_site_dir/public_html";
    }
    $root_dir = "$www_root_dir/Sites/$local_site_dir";
    $base_dir = "$root_dir/public_html";
    $private_key_path = '/var/www/.ssh/sites_rsa';
}
else {
    exit("Valid location not detected");
}

global $auth_dbid;                  $auth_dbid = 2;
global $auth_db_table;              $auth_db_table = "admin_passwords";
global $auth_db_username_field;     $auth_db_username_field = "username";
global $auth_db_access_level_field; $auth_db_access_level_field = "access_level";
global $admin_data_dir;             $admin_data_dir = "$base_dir/admin_data";
global $access_logs_dir;            $access_logs_dir = "$root_dir/access_logs";
global $base_content_url;           $base_content_url = "$base_url/wp-content";
global $custom_scripts_path;        $custom_scripts_path = "$base_dir/wp-custom-scripts";
global $custom_scripts_url;         $custom_scripts_url = "$base_url/wp-custom-scripts";
global $custom_pages_path;          $custom_pages_path = "$custom_scripts_path/pages";
global $custom_pages_url;           $custom_pages_url = "$custom_scripts_url/pages";
global $custom_posts_path;          $custom_posts_path = "$custom_scripts_path/posts";
global $custom_posts_url;           $custom_posts_url = "$custom_scripts_url/posts";
global $custom_categories_path;     $custom_categories_path = "$custom_scripts_path/categories";
global $custom_categories_url;      $custom_categories_path = "$custom_scripts_url/categories";
global $admin_base_dir;             $admin_base_dir = "$custom_pages_path/dbadmin";
global $admin_base_url;             $admin_base_url = "$base_url/dbadmin";
global $base_theme_dir;             $base_theme_dir = "$base_dir/wp-content/themes/my-base-theme";
global $cache_dir;                  $cache_dir = "$root_dir/wp-cache";
global $dbadmin_dir;                $dbadmin_dir = "$base_dir/common_scripts/dbadmin";
global $dbadmin_url;                $dbadmin_url = "$base_url/common_scripts/dbadmin";
global $maintenance_dir;            $maintenance_dir = "$root_dir/maintenance";
global $mail_log_dir;               $mail_log_dir = "$root_dir/mail_logs";
global $menu_css_dir;               $menu_css_dir = "$base_dir/menu_css";
global $menu_css_url;               $menu_css_url = "$base_url/menu_css";
global $online_port;                $online_port = '88';
global $private_scripts_dir;        $private_scripts_dir = "$root_dir/private_scripts";
global $site_mysql_backup_dir;      $site_mysql_backup_dir = "$root_dir/mysql_backup";
global $theme_dir;                  $theme_dir = "$base_dir/wp-content/themes/$theme_name";
global $theme_url;                  $theme_url = "$base_url/wp-content/themes/$theme_name";
global $uploads_dir;                $uploads_dir = "$base_dir/wp-content/uploads";
global $uploads_url;                $uploads_url = "$base_url/wp-content/uploads";

//==============================================================================

require("$base_dir/common_scripts/local_ip_funct.php");
require("$base_dir/common_scripts/core_funct.php");
require("$base_dir/libraries/library_path_defs.php");
require("$base_dir/common_scripts/allowed_hosts.php");
ini_set('display_errors','0');
ini_set('log_errors','1');
ini_set('error_log',"$root_dir/logs/php_error.log");

//==============================================================================
