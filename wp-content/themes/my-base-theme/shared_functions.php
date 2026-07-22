<?php
//==============================================================================
if (!defined('SHARED_FUNCT_DEFINED')):
//================================================================================
/*
 My Base Theme - Shared Functions

 Includes functions that may need to be accessed:-
 1. By scripts running outside the WordPress environment.
 2. By scripts in the wp-custom-scripts directory.
 3. By child theme scripts.
 */
//================================================================================
/*
* Function set_default_header_image_paths
*/
//================================================================================

function set_default_header_image_paths()
{
    $image_file_exts = [ 'svg', 'png', 'jpg' ];
    global $desktop_header_image_path;
    global $desktop_header_image_url;
    global $intermediate_header_image_path;
    global $intermediate_header_image_url;
    global $mobile_header_image_path;
    global $mobile_header_image_url;
    $current_theme_dir = get_stylesheet_directory();
    $current_theme_url = get_stylesheet_directory_uri();

    $desktop_header_image_path = '';
    $desktop_header_image_url = '';
    foreach ($image_file_exts as $ext) {
        if (is_file("$current_theme_dir/header_image_desktop.$ext")) {
            $desktop_header_image_path = "$current_theme_dir/header_image_desktop.$ext";
            $desktop_header_image_url = "$current_theme_url/header_image_desktop.$ext";
            break;
        }
    }

    $intermediate_header_image_path = $desktop_header_image_path;
    $intermediate_header_image_url = $desktop_header_image_url;
    foreach ($image_file_exts as $ext) {
        if (is_file("$current_theme_dir/header_image_intermediate.$ext")) {
            $intermediate_header_image_path = "$current_theme_dir/header_image_intermediate.$ext";
            $intermediate_header_image_url = "$current_theme_url/header_image_intermediate.$ext";
            break;
        }
    }

    $mobile_header_image_path = $intermediate_header_image_path;
    $mobile_header_image_url = $intermediate_header_image_url;
    foreach ($image_file_exts as $ext) {
        if (is_file("$current_theme_dir/header_image_mobile.$ext")) {
            $mobile_header_image_path = "$current_theme_dir/header_image_mobile.$ext";
            $mobile_header_image_url = "$current_theme_url/header_image_mobile.$ext";
            break;
        }
    }
}

//================================================================================
/*
Function set_header_image_paths
*/
//================================================================================

function set_header_image_paths($slug,$type)
{
    if (function_exists('set_custom_header_image_paths')) {
        // Call child theme function
        set_custom_header_image_paths($slug,$type);
    }
    else {
        // No action - the default paths will apply
    }
}

//================================================================================
/*
Function output_page_header

This function is used to output the main title header of the current page.
*/
//================================================================================

function output_page_header()
{
    if (function_exists('get_secondary_title')) {
        $secondary_title = get_secondary_title();
    }
    else {
        $secondary_title = '';
    }
    if ($secondary_title == '#') {
        // No action
    }
    elseif (!empty($secondary_title)) {
        echo("<h1>$secondary_title</h1>\n");
    }
    else {
        the_title( '<h1 class="entry-title">', '</h1>' );
    }
}

//================================================================================
/*
Function get_content_part

This function is used extract and output a given portion of the page content
and is for use when the content section of a page is being built using a custom
PHP script. A numeric part number is passed as a parameter and this indicates
that the text is to be extracted from between the following tags in the
WordPress page content:-

[part<n>]
[/part<n>]

where <n> is the part number. This allows multiple portions to be extracted
from the pages content for use at different points in the page.
*/
//================================================================================

function get_content_part($part_no)
{
    $post = get_post();
    $content = $post->post_content;
    $content = apply_filters( 'the_content', $content );

    if ($part_no == 0) {
        // Use part number 0 to return whole page content.
    }
    else {
        // Extract required part of page content.
        $pos1 = strpos($content,"[part$part_no]");
        $pos2 = strpos($content,"[/part$part_no]");
        if (($pos1 === false) || ($pos2 === false)) {
            return "**** Unable to retrieve part $part_no from page ****";
        }
        $pos1 += strlen("[part$part_no]");
        $content = substr($content,$pos1,$pos2-$pos1);
    }
    $content = substitute_shortcodes($content);
    return $content;
}

//================================================================================
/*
Function output_meta_data

This function is used to generate meta tag data in the page header.
A number of global variables are referenced by the function to set up the tags
as required. These will have been set up previously by running any 'metadata.php'
scripts in the page hierachy within the custom scripts folder.

N.B. To cancel a meta description from an ancestor page without creating a new one,
the meta description must be re-defined for the page as an empty string.
*/
//================================================================================

function output_meta_data()
{
    global $meta_description;
    global $meta_robots_noindex;
    global $meta_robots_nofollow;
    global $meta_refresh_interval;
    global $meta_refresh_url;
    global $meta_refresh_url_pars;
    global $location;

    if ((isset($location)) && ($location == 'local')) {
        print("<meta name=\"robots\" content=\"noindex,nofollow\">\n");
    }
    else {
        if ((isset($meta_description)) && (!empty($meta_description))) {
            print("<meta name=\"description\" content=\"$meta_description\">\n");
        }
        $robots_content = '';
        if ((isset($meta_robots_noindex)) && ($meta_robots_noindex)) {
            $robots_content = 'noindex';
        }
        if ((isset($meta_robots_nofollow)) && ($meta_robots_nofollow)) {
            if (!empty($robots_content)) {
                $robots_content .= ',';
            }
            $robots_content .= 'nofollow';
        }
        if (!empty($robots_content)) {
            print("<meta name=\"robots\" content=\"$robots_content\">\n");
        }
    }

    if ((isset($meta_refresh_interval)) && (isset($meta_refresh_url)) && (!isset($_GET['norefresh']))) {
        if (!isset($meta_refresh_url_pars)) {
            $meta_refresh_url_pars = '';
        }
        print("<meta http-equiv=\"refresh\" content=\"$meta_refresh_interval;URL='$meta_refresh_url/$meta_refresh_url_pars'\" />\n");
    }
}

//================================================================================
/*
Function output_stylesheet_link

This function is used to output a stylesheet link in the HTML header when
the URL hierachy is scanned by setup_params.php. The stylesheet file must be
named style.css.

The associated light/dark theme stylesheet will also be linked in if present.
*/
//================================================================================

function output_stylesheet_link($path,$sub_path)
{
    global $link_version, $base_dir, $base_url;
    $stylesheet_id = str_replace('/','-',$sub_path);
    $dir_path = str_replace($base_url,$base_dir,$path);
    print("\n<link rel='stylesheet' id='$stylesheet_id"."-style-css'  href='$path/$sub_path/style.css?v=$link_version' type='text/css' media='all' />\n");

    if (function_exists('get_session_var')) {
        if ((get_session_var('theme_mode') == 'light') && (is_file("$dir_path/$sub_path/style-light.css"))) {
            print("\n<link rel='stylesheet' id='$stylesheet_id"."-style-light-css'  href='$path/$sub_path/style-light.css?v=$link_version' type='text/css' media='all' />\n");
        }
        elseif ((get_session_var('theme_mode') == 'dark') && (is_file("$dir_path/$sub_path/style-dark.css"))) {
            print("\n<link rel='stylesheet' id='$stylesheet_id"."-style-dark-css'  href='$path/$sub_path/style-dark.css?v=$link_version' type='text/css' media='all' />\n");
        }
    }
}

//================================================================================
/*
Function include_inline_stylesheet

This function loads a stylesheet file and outputs its contents within
<style></style> tags by way of inline styles. It can be called from anywhere
but is also used by setup_params.php when scanning the URL hierachy. There
is no constraint on the stylesheet filename, but when called from
setup_params.php, it will always be inline-styles.css.

The associated light/dark theme stylesheet will also be included if present.
*/
//================================================================================

function include_inline_stylesheet($path)
{
    print("<style>\n");
    if (is_file($path)) {
        include($path);
    }
    $light_theme_path = str_replace('.css','-light.css',$path);
    $dark_theme_path = str_replace('.css','-dark.css',$path);
    if (function_exists('get_session_var')) {
        if ((get_session_var('theme_mode') == 'light') && (is_file($light_theme_path))) {
        include($light_theme_path);
    }
        elseif ((get_session_var('theme_mode') == 'dark') && (is_file($dark_theme_path))) {
            include($dark_theme_path);
        }
    }
    print("</style>\n");
}

//================================================================================
/*
Function include_inline_javascript
*/
//================================================================================

function include_inline_javascript($path)
{
    print("<script>\n");
    include($path);
    print("</script>\n");
}

//================================================================================
/*
Functions save_php_error_log & restore_php_error_log
*/
//================================================================================

function save_php_error_log()
{
    global $root_dir;
    if (is_file("$root_dir/logs/php_error.log")) {
        copy("$root_dir/logs/php_error.log","$root_dir/logs/php_error.log.sav");
    }
}

function restore_php_error_log()
{
    global $root_dir;
    if (is_file("$root_dir/logs/php_error.log")) {
        unlink("$root_dir/logs/php_error.log");
    }
    if (is_file("$root_dir/logs/php_error.log.sav")) {
        rename("$root_dir/logs/php_error.log.sav","$root_dir/logs/php_error.log");
    }
}

//================================================================================
/*
Function output_to_access_log
*/
//================================================================================

function output_to_access_log($user='',$add_info='')
{
    global $access_logs_dir;
    global $base_dir;
    include("$base_dir/common_scripts/allowed_hosts.php");
    if (is_dir($access_logs_dir)) {
        $date = date('Y-m-d');
        $ofp = fopen("$access_logs_dir/$date.log",'a');
        $time = date('H:i:s');
        $addr_str = $_SERVER['REMOTE_ADDR'];
        if (is_local_ip($_SERVER['REMOTE_ADDR'])) {
            $addr_str = '[Local]';
        }
        elseif (isset($allowed_hosts[$addr_str])) {
            $addr_str = "[{$allowed_hosts[$addr_str]}]";
        }
        $addr_str = substr("$addr_str               ",0,15);
        $uri_str = str_replace('%','%%',$_SERVER['REQUEST_URI']);
        fprintf($ofp,"$date $time ".'-'." $addr_str $uri_str");
        if (!empty($user)) {
            fprintf($ofp," [user = $user]");
        }
        if ((isset($_SERVER['HTTP_REFERER'])) && (!empty($_SERVER['HTTP_REFERER']))) {
            $referrer_str = str_replace('%','%%',$_SERVER['HTTP_REFERER']);
            fprintf($ofp,$referrer_str);
        }
        if (!empty($add_info)) {
            fprintf($ofp," [$add_info]");
        }
        fprintf($ofp,"\n");
        fclose($ofp);
    }
}

//================================================================================
/*
Function simplify_html_tag

This function is called by the simplify_html function or a site specific
function that calls the latter.

It reduces all tags of a given type to a simple tag with no options.
*/
//================================================================================

function simplify_html_tag($content,$tag)
{
    $pos1 = strpos($content,"<$tag");
    while ($pos1 !== false) {
        $pos2 = strpos($content,'>',$pos1);
        $content = substr($content,0,$pos1+strlen($tag)+1).substr($content,$pos2);
        $pos1 = strpos($content,"<$tag",$pos1+1);
    }
    return $content;
}

//================================================================================
/*
Function simplify_html

This function is called to simplify a word processor document that has been
exported as HTML. Its main purpose is to remove any built-in style
information that is otherwise defined in CSS.

If further edits are required, then it is suggested that this function is
called from a site specific function with the necessary additional
functionality.
*/
//================================================================================

function simplify_html($content)
{
    global $allowed_tags, $simplified_tags;
    if (!isset($allowed_tags)) {
        /*
        The '$allowed_tags' array specifies those tags that are to be retained in the
        generated HTML code. This is the default version but can be overridden by
        declaring a custom version outside the function call.
        */
        $allowed_tags = ['<p>','<br>','<a>','<table>','<th>','<tr>','<td>','<ul>','<ol>','<li>','<b>','<strong>','<i>','<em>','<u>'];
    }
    if (!isset($simplified_tags)) {
        /*
        The '$simplified_tags' array specifies those tag types for which the
        'simplify_html_tag' function is to be run. This is the default version but
        can be overridden by declaring a custom version outside the function call.
        */
        $simplified_tags = ['p'];
    }

    // Strip out any <style> tags. This is done long-hand as there have been
    // issues when just relying on the call to strip_tags.
    $pos1 = strpos($content,'<style');
    while ($pos1 !== false) {
        $pos2 = strpos($content,'</style>',$pos1);
        $content = substr($content,0,$pos1).substr($content,$pos2+8);
        $pos1 = strpos($content,'<style',$pos1+1);
    }

    // Run the main operation
    $content = strip_tags($content,$allowed_tags);

    // Apply the simplify_html_tag function to selected tag types
    foreach ($simplified_tags as $tag) {
        $content = simplify_html_tag($content,$tag);
    }
    $content = str_replace('<li><p>','<li>',$content);
    $content = str_replace('</p></li>','</li>',$content);

    return $content;
}

//================================================================================
/*
Function clear_cache

This function deletes all the content of the WP cache directory, forcing all pages
and posts to be re-cached.
*/
//================================================================================

function clear_cache($subdir='')
{
    global $cache_dir;
    global $base_url;
    $pos = strpos($base_url,'//');
    if (empty($subdir)) {
        $subdir = "$cache_dir/supercache/".substr($base_url,$pos+2);
    }
    print("$subdir\n");
    $dir = scandir($subdir);
    foreach ($dir as $file) {
        if ((is_dir("$subdir/$file"))&& (substr($file,0,1) != '.')) {
            clear_cache("$subdir/$file");
            rmdir("$subdir/$file");
        }
        elseif (is_file("$subdir/$file")) {
            unlink("$subdir/$file");
        }
    }
}

//================================================================================
/*
Function trigger_cache_generation

This function is called to access a given page or post in such a way that it will
force it to be cached in the WP cache if not already there. The setting of the
user agent to be browser based is key to successful operation.
*/
//================================================================================

function trigger_cache_generation($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($http_code === 200);
}

//================================================================================
/*
Function recache_page

This function is for use in conjuction with the 'WP Super Cache' plugin. It is
called to clear any cache files for a given page and then activate the page to
cause the cache to be regenerated (provided the page is configured to be cached).

This and the 'recache_all_pages' have been rewritten with the help of AI. To refer
back to the previous versions, please see the backup archive for May 2026.
*/
//================================================================================

function recache_page($uri_subpath)
{
    global $cache_dir;
    global $base_url;
    $pos = strpos($base_url,'//');
    $cache_subdir = "$cache_dir/supercache/".substr($base_url,$pos+2)."/$uri_subpath";
    $cache_subdir = rtrim($cache_subdir,'/');

    // Delete old cache files
    if (is_dir($cache_subdir)) {
        $files = glob("$cache_subdir/*");
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
    }

    // Activate page to regenerate cache.
    trigger_cache_generation("$base_url/$uri_subpath");
}

//================================================================================
/*
Function recache_all_pages

This function is called to execute the recache_page function on all published
pages/posts within the site.
*/
//================================================================================

function recache_all_pages($type='page')
{
    if (!defined('WP_DBID')) return;
    $db = db_connect(WP_DBID);
    $eol = eol_string();

    switch ($type) {
        case 'page':
            print("Re-caching front page$eol");
            recache_page('');
            // No break

        case 'post':
            $all_posts = [];
            $where_clause = "post_type='$type' AND post_status='publish'";
            $where_values = [];
            $add_clause = "ORDER BY post_name ASC";
            $query_result = mysqli_select_query($db,'wp_posts','ID,post_name,post_parent',$where_clause,$where_values,$add_clause);
            while ($row = mysqli_fetch_assoc($query_result)) {
                $all_posts[$row['ID']] = $row;
            }
            foreach ($all_posts as $post) {
                set_time_limit(30);
                $uri_subpath = build_uri_path($post, $all_posts);
                print("Re-caching $type [{$post['post_name']}]$eol");
                recache_page($uri_subpath);
            }
            break;

        case 'category':
            $query = "SELECT * FROM wp_terms LEFT JOIN wp_term_taxonomy ON wp_terms.term_id=wp_term_taxonomy.term_ID WHERE taxonomy='category' ORDER BY slug ASC";
            $query_result = mysqli_query($db,$query);
            while ($row = mysqli_fetch_assoc($query_result)) {
                print("Re-caching category [{$row['slug']}]$eol");
                empty_cache_dir("category/{$row['slug']}");
                recache_page("category/{$row['slug']}");
            }
            break;
    }
}

// Sub-functions for use by recache_all_pages

function build_uri_path($post, &$all_posts)
{
    $path = $post['post_name'];
    $parent_id = $post['post_parent'];

    while ($parent_id > 0 && isset($all_posts[$parent_id])) {
        $path = $all_posts[$parent_id]['post_name'] . '/' . $path;
        $parent_id = $all_posts[$parent_id]['post_parent'];
    }
    return ($path === 'home') ? '' : $path;
}

function empty_cache_dir($uri_subpath)
{
    global $cache_dir;
    global $base_url;
    $directory = "$cache_dir/supercache/";
    $pos = strpos($base_url,'//');
    $cache_subdir = $directory.substr($base_url,$pos+2)."/$uri_subpath";
    $cache_subdir = rtrim($cache_subdir,'/');
    if (!is_dir($directory)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $path = $fileinfo->getRealPath();

        if ($fileinfo->isDir()) {
            rmdir($path);
        } else {
            unlink($path);
        }
    }
}

//================================================================================
/*
Function create_cache_reload_link

This function needs to be included in the theme header.php script. It will cause
the page to be immediately redirected a script which recaches and reloads the
page, on the condition that a 'recache indicator' has been supplied as a URL
parameter.
*/
//================================================================================

function create_cache_reload_link()
{
    global $base_url;
    $recache_indicator = (defined('RECACHE_INDICATOR')) ? RECACHE_INDICATOR : 'recache';
    if (isset($_GET[$recache_indicator])) {
        $uri_path = $_SERVER['REQUEST_URI'];
        $pos1 = strpos($uri_path,"?$recache_indicator");
        $pos2 = strpos($uri_path,"&$recache_indicator");
        if ($pos1 !== false) {
            $uri_path = substr($uri_path,0,$pos1);
        }
        elseif ($pos2 !== false) {
            $uri_path = substr($uri_path,0,$pos2);
        }
        $uri_path = trim($uri_path,'/');
        if (empty($uri_path)) {
            $uri_path = 'home';
        }
        print("<meta http-equiv=\"refresh\" content=\"0;URL='$base_url/common_scripts/load_recached_page.php?uripath=$uri_path'\" />\n");
    }
}

//================================================================================
/*
Function load_updated_page_content

This function checks all HTML files in the page content directory, and if a file
timestamp is found to be changed, a forced recache is performed on the
associated page.
*/
//================================================================================

function load_updated_page_content($page_content_dir)
{
    $db = db_connect(WP_DBID);
    $filetimes = [];
    $dirlist = scandir($page_content_dir);
    foreach ($dirlist as $file) {
        if (pathinfo($file,PATHINFO_EXTENSION) == 'html') {
            $filetimes[$file] = filemtime("$page_content_dir/$file");
        }
    }
    foreach ($filetimes as $filename => $filetime) {
        $where_clause = 'filename=?';
        $where_values = ['s',$filename];
        if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'file_timestamps','*',$where_clause,$where_values,''))) {
            if ($row['filetime'] != $filetime) {
                // File timestamp has changed.
                $set_fields = 'filetime';
                $set_values = ['i',$row['filetime']];
                mysqli_update_query($db,'file_timestamps',$set_fields,$set_values,$where_clause,$where_values);
                recache_page(pathinfo($filename,PATHINFO_FILENAME));
            }
        }
        else {
            // Add entry to file timestamps table.
            $fields = 'filename,filetime';
            $values = ['s',$filename,'i',$filetime];
            mysqli_insert_query($db,'file_timestamps',$fields,$values);
            recache_page(pathinfo($filename,PATHINFO_FILENAME));
        }
    }
}

//================================================================================
/*
Function substitute_shortcodes

This function replicates the functionality for the shortcode handling functions
in functions.php, and is available for use in situations where the normal
WordPress loop is not in operation.
*/
//================================================================================

function substitute_shortcodes($text)
{
    if (!defined('NO_COPY_SHORTCODE')) {
        $text = str_replace('[copy]','&copy;',$text);
    }
    if (!defined('NO_NBSP_SHORTCODE')) {
        $text = str_replace('[nbsp]','&nbsp;',$text);
    }
    if (!defined('NO_POUND_SHORTCODE')) {
        $text = str_replace('[pound]','&pound;',$text);
    }
    if (!defined('NO_SQUOT_SHORTCODE')) {
        $text = str_replace('[squot]',"'",$text);
    }
    return $text;
}

//================================================================================
/*
Functions for handling user login status

When these functions are to be used, the following constants must be pre-defined:

SV_USER              - Session variable name for the username.
SV_ACCESS_LEVEL      - Session variable name for the user access level.
DEFAULT_ACCESS_LEVEL - Default access level when there is no logged in user.
USER_LOGIN_TIMEOUT   - Idle timeout for a login session (in seconds).
LOGIN_COOKIE_ID      - Variable name for the login cookie. Used to distinguish sites
                       on the local server. Typically set to 'login_id' on a live
                       site.
LOGIN_COOKIE_PATH    - Server path for the login cookie. Used to distinguish sites
                       on the local server. Should always be set to '/' on a live
                       site.

The function 'user_access_level' needs to be created as a site-specific function.

The function 'put_user_additions' can optionally be created to perform any
site-specific functions required when updating a user.
*/
//================================================================================

/*
Please note that get__user is so named because 'get_user' now clashes with a
built-in function introduced in WordPress 6.7.
*/
function get__user()
{
    if (session_var_is_set(SV_USER)) {
        // User logged in
        return get_session_var(SV_USER);
    }
    else {
        // No user logged in
        return '';
    }
}

//================================================================================

function put_user($username)
{
    if (empty($username)) {
        // Perform operations for user logout
        $db = db_connect(WP_DBID);
        update_session_var(SV_USER,'');
        update_session_var(SV_ACCESS_LEVEL,DEFAULT_ACCESS_LEVEL);
        setcookie(LOGIN_COOKIE_ID,'',time()-3600,LOGIN_COOKIE_PATH);
        $session_id = session_id();
        if (!empty($session_id)) {
            $where_clause = 'session_id=? AND (name=? OR name=?)';
            $where_values = ['s',$session_id,'s',SV_USER,'s',SV_ACCESS_LEVEL];
            mysqli_delete_query($db,'wp_session_updates',$where_clause,$where_values);
        }
    }
    else {
        // Perform operations for user login
        update_session_var(SV_USER,$username);
        update_session_var(SV_ACCESS_LEVEL,user_access_level($username));
    }
    // Perform any additional site-specific operations
    if (function_exists('put_user_additions')) {
        put_user_additions($username);
    }
}

//================================================================================

function get_access_level()
{
    if (session_var_is_set(SV_ACCESS_LEVEL)) {
        return get_session_var(SV_ACCESS_LEVEL);
    }
    else {
        return DEFAULT_ACCESS_LEVEL;
    }
}

//================================================================================

function check_login_status($db)
{
    $username = get_session_var(SV_USER);
    $access_level = get_session_var(SV_ACCESS_LEVEL);
    $current_time = time();
    $purge_time = $current_time-USER_LOGIN_TIMEOUT-3600;
    $expiry_time = $current_time+USER_LOGIN_TIMEOUT;
    $date_and_time = date('Y-m-d H:i:s',$current_time);
    mysqli_query($db,"DELETE FROM login_sessions WHERE update_time<$purge_time");
    if (isset($_COOKIE[LOGIN_COOKIE_ID])) {
        $login_id = $_COOKIE[LOGIN_COOKIE_ID];
        if ((!empty($login_id)) && ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM login_sessions WHERE id='$login_id'")))) {
            // Update user, login cookie and DB record
            put_user($row['username']);
            mysqli_query($db,"UPDATE login_sessions SET update_time=$current_time,date_and_time='$date_and_time',remote_addr='{$_SERVER['REMOTE_ADDR']}' WHERE id='$login_id'");
            setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
        }
    }
    elseif ((!empty($username)) && (!empty($access_level))) {
        // New login - create cookie and associated login session record
        $login_id = md5($username.date('YmdHis'));
        $query = "INSERT INTO login_sessions (id,username,access_level,update_time,date_and_time,remote_addr)";
        $query .= " VALUES ('$login_id','$username','$access_level',$current_time,'$date_and_time','{$_SERVER['REMOTE_ADDR']}')";
        mysqli_query($db,$query);
        setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
    }
}

//================================================================================

function log_user_out($db)
{
    // Clear all user info, including the cookie and login session record.
    put_user('');
    if (isset($_COOKIE[LOGIN_COOKIE_ID])) {
        $login_id = $_COOKIE[LOGIN_COOKIE_ID];
        mysqli_query($db,"DELETE FROM login_sessions WHERE id='$login_id'");
        $expiry_time = time() - 3600;
        setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
    }
}

//==============================================================================
/*
Function authenticate_user_in_path

This function checks whether the user is authenicated for access to the post or
page referenced by the current URL.

Posts - A check is made against the 'access level' post meta value if present.
Pages - The directory hierarchy is checked, running the authentication.php
        script if found at any level.
*/
//==============================================================================

function authenticate_user_in_path()
{
    global $location;
    global $custom_pages_path;
    $db = db_connect(WP_DBID);
    $hierarchy = explode('/',ltrim($_SERVER['REQUEST_URI'],'/'));
    if ($location == 'local') {
        unset($hierarchy[0]);
    }
    $user_authenticated = true;  // Default setting - can be overridden.
    $path = $custom_pages_path;
    foreach ($hierarchy as $element) {
        $path .= "/$element";
        $where_clause = "post_name=? AND post_type='post'";
        $where_values = ['s',$element];
        if ((!empty($element)) && ($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,'')))) {
            /*
            Element is a post name, which means that the current URI refers to a post. Check for the
            presence of a post access level, and if found, check this against the current user access
            level. Always break out of the loop here, as it is a final result.
            */
            $where_clause = 'post_id=? AND meta_key=?';
            $where_values = ['i',$row['ID'],'s','access_level'];
            $post_access_level = ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'wp_postmeta','*',$where_clause,$where_values,'')))
                ? $row2['meta_value']
                : null;
	        $user_access_level = $_SESSION[SV_ACCESS_LEVEL] ?? DEFAULT_ACCESS_LEVEL;
            $user_authenticated = ($user_access_level >= $post_access_level);
            break;
        }
        elseif (is_dir($path)) {
            /*
            Element is directory on a page path. Run the authentication.php script in the given
            directory (if found), which is expected to set the $user_authenticated variable according
            to its local criteria. Break out of the loop if authentication has failed at this point.
            */
            if (is_file("$path/authentication.php")) {
                include("$path/authentication.php");
            }
            if (!$user_authenticated) {
                break;
            }
        }
        else {
            /*
            Element not a directory or post name - presumably a filename and/or parameter string at
            the end of the URI.
            */
            break;
        }
    }
    return $user_authenticated;
}

//==============================================================================

function sync_post_data($source_dbid,$source_user,$target_dbid,$target_user,$option,$category='')
{
    global $dbinfo, $location;
    $db1 = db_connect($source_dbid,'p',$source_user);
    $db2 = db_connect($target_dbid,'p',$target_user);

    /*
    Define list of meta fields to be processed, specifying each as a string ('s') or integer ('i').
    Only include the fields for short address groups/numbers if the relavant tables are present in
    the target database.
    */
    $meta_fields = [
        'pinterest_title' => 's',
        'pinterest_description' => 's',
        'facebook_text' => 's',
        'twitter_text' => 's',
    ];
    $target_dbname = ($location == 'local') ? $dbinfo[$target_dbid][0] : $dbinfo[$target_dbid][1];
    if (mysqli_num_rows(mysqli_query_normal($db2,"SHOW FULL TABLES FROM `$target_dbname` WHERE `Tables_in_$target_dbname`='short_address_groups'")) > 0) {
        $meta_fields = array_merge($meta_fields, [
                'short_address_group' => 'i',
                'short_address_number' => 'i',
            ]);
    }

    $where_clause = "post_type='post' AND post_status='publish'";
    $where_values = [];
    $query_result = mysqli_select_query($db1,'wp_posts','*',$where_clause,$where_values,'');

    // Loop through posts in source database.
    while ($row1 = mysqli_fetch_assoc($query_result)) {
        $post_name = $row1['post_name'];
        $where_clause = "post_name=? AND post_status='publish'";
        $where_values = ['s',$row1['post_name']];
        if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_posts','*',$where_clause,$where_values,''))) {
            // Matching post name found in target database.
            $query = "SELECT * FROM wp_terms LEFT JOIN wp_term_taxonomy ON wp_terms.term_id=wp_term_taxonomy.term_ID WHERE slug='$category' AND taxonomy='category'";
            $category_match = false;
            if (empty($category)) {
                $category_match = true;
            }
            elseif (($row3 = mysqli_fetch_assoc(mysqli_query($db1,$query))) &&
                    ($row4 = mysqli_fetch_assoc(mysqli_query($db2,$query)))) {
                // Category exists in both DBs. Now check if both posts are in that category.
                if (($row5 = mysqli_fetch_assoc(mysqli_query($db1,"SELECT * FROM wp_term_relationships WHERE object_id={$row1['ID']} AND term_taxonomy_id={$row3['term_taxonomy_id']}"))) &&
                    ($row6 = mysqli_fetch_assoc(mysqli_query($db2,"SELECT * FROM wp_term_relationships WHERE object_id={$row2['ID']} AND term_taxonomy_id={$row4['term_taxonomy_id']}")))) {
                    $category_match = true;
                }
            }
            if ($category_match) {
                $where_clause = "post_id=? AND meta_key='inhibit_sync'";
                $where_values = ['post_id',$row2['ID']];
                if ($row3 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_postmeta','*',$where_clause,$where_values,''))); {
                    $inhibit_sync = ($row3['meta_value']) ?? false;
                }
                if (empty($inhibit_sync)) {
                    if (($option == 'timestamp') && ($row1['post_date'] != $row2['post_date'])) {
                        // Synchronise post timestamp.
                        echo "Synchronising timestamp for post $post_name\n";
                        $where_clause = "post_name=?";
                        $where_values = ['s',$post_name];
                        $fields = ('post_date,post_date_gmt');
                        $values = ['s',$row1['post_date'],'s',$row1['post_date_gmt']];
                        mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                    }
                    elseif ($option == 'content') {
                        if ($row1['post_content'] != $row2['post_content']) {
                            // Synchronise post content.
                            echo "Synchronising content for post $post_name\n";
                            $where_clause = "post_name=?";
                            $where_values = ['s',$post_name];
                            $fields = ('post_content');
                            $values = ['s',$row1['post_content']];
                            mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                        }
                        // Synchronise any meta values.
                        foreach ($meta_fields as $field => $type) {
                            $where_clause = 'post_id=? AND meta_key=?';
                            $where_values_1 = ['',$row1['ID'],'s',$field];
                            $where_values_2 = ['',$row2['ID'],'s',$field];
                            /*
                            Copy the meta value if:
                            1. It is present in the source
                            2. It is either absent in the target or not equal to the source value.
                            */
                            if (($row3 = mysqli_fetch_assoc(mysqli_select_query($db1,'wp_postmeta','*',$where_clause,$where_values_1,''))) &&
                                ((($row4 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_postmeta','*',$where_clause,$where_values_2,''))) &&
                                  ($row3['meta_value'] != $row4['meta_value'])) ||
                                 (empty($row4)))
                               ) {
                                echo "Synchronising meta value for post $post_name => $field\n";
                                $value = $row3['meta_value'];
                                if ($field == 'short_address_group') {

                                    // Map the short address group from the source to the target.
                                    $where_clause_3 = 'source_group=?';
                                    $where_values_3 = ['i',$value];
                                    if ($row7 = mysqli_fetch_assoc(mysqli_select_query($db2,'short_address_groups','*',$where_clause_3,$where_values_3,''))) {
                                        $value = $row7['group_no'];
                                    }
                                }

                                $fields = 'post_id,meta_key,meta_value';
                                $values = ['i',$row2['ID'],'s',$field,$type,$value];
                                if (mysqli_conditional_insert_query($db2,'wp_postmeta',$fields,$values,$where_clause,$where_values_2) === NOINSERT) {
                                    $fields = 'meta_value';
                                    $values = [$type,$value];
                                    mysqli_update_query($db2,'wp_postmeta',$fields,$values,$where_clause,$where_values_2);
                                }

                            }
                        }
                    }
                }
            }
        }
    }
}

//==============================================================================

function rebuild_short_address_list()
{
    $db = db_connect(WP_DBID);
    mysqli_delete_query($db,'short_addresses','',[]);
    $where_clause = "(post_type='post' OR post_type='page') AND post_status='publish'";
    $query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,[],'');
    while ($row = mysqli_fetch_assoc($query_result)) {
        /*
        Create a short address if all the following conditions are true:
        1. A meta value is defined for the short address group.
        2. A meta value is defined for the short address number.
        3. A matching entry is present in the short address groups table.
        */
        $where_clause_1 = "post_id=? AND meta_key='short_address_group'";
        $where_clause_2 = "post_id=? AND meta_key='short_address_number'";
        $where_clause_3 = "group_no=?";
        $where_values_1 = ['i',$row['ID']];
        if (($row1 = mysqli_fetch_assoc(mysqli_select_query($db,'wp_postmeta','*',$where_clause_1,$where_values_1,''))) &&
            (!empty($row1['meta_value'])) &&
            ($row2 = mysqli_fetch_assoc(mysqli_select_query($db,'wp_postmeta','*',$where_clause_2,$where_values_1,''))) &&
            (!empty($row2['meta_value'])) &&
            ($where_values_2 = ['i',$row1['meta_value']]) &&
            ($row3 = mysqli_fetch_assoc(mysqli_select_query($db,'short_address_groups','*','',[],'')))) {
            /*
            Use a conditional insert in case there is a clash of addresses, though this should
            not normally occur.
            */
            $post_number = $row3['base_offset'] + $row2['meta_value'];
            $fields = 'post_number,post_name,category';
            $values = ['i',$post_number,'s',$row['post_name'],'s',$row3['category']];
            $where_clause = 'post_number=?';
            $where_values = ['i',$post_number];
            mysqli_conditional_insert_query($db,'short_addresses',$fields,$values,$where_clause,$where_values);
        }
    }
}

//================================================================================

function copyright_notice($owner,$start_year)
{
    $start_year = strval($start_year);
    $this_year = date('Y');
    $date = ($start_year == $this_year)
        ? $this_year
        : sprintf("%04d-%02d",$start_year,strval((int)$this_year % 100));
    return "Copyright &copy; $date $owner, all rights reserved.";
}

//================================================================================
/*
Function get_modified_image_url

This function is called to determine whether an image in the WP uploads directory
has an equivalent image in a given subdirectory, and returns the URL of this in
place of the original image URL.
*/
//================================================================================

function get_modified_image_url($image_url,$type='webp300',$image_type='webp')
{
    global $base_dir, $base_url;
    $image_path = str_replace($base_url,$base_dir,$image_url);
    $file_ext = pathinfo($image_path,PATHINFO_EXTENSION);
    $alt_image_path = str_replace('/uploads/',"/uploads/$type/",$image_path);
    $alt_image_path = str_replace(".$file_ext",".$image_type",$alt_image_path);
    if (is_file($alt_image_path)) {
        $image_url = str_replace($base_dir,$base_url,$alt_image_path);
    }
    if (function_exists('url_to_static')) {
        $image_url = url_to_static($image_url);
    }
    return $image_url;
}

//================================================================================
/*
Function load_codemirror

This function is called to include the links for the CodeMirror library, if they
are required in the current context. It relies on the settings in a global array
$user_codemirror, which has the following formmat:

$use_codemirror = [
    '<subpath1>' => [
        'dbid' => '<dbid>',
        '<item1>' => true,
        '<item2>' => true,
        ...
    ],
    '<subpath2>' => [
        'dbid' => '<dbid>',
        '<item1>' => true,
        '<item2>' => true,
        ...
    ],
    ...
]

where:
* <subpath1>, <subpath2> etc. are database sub-paths on the main DB admin
  directory, and are matched to the $sub_path parameter passed to the function.
* There is one 'dbid' element for each sub-path, which specifies the DB ID that is
  passed to the db_connect function for the associated database.
* <item1>, <item2> etc. are the various tables and actions associated with the
  given sub-path for which the CodeMirror library needs to be loaded.

N.B. This function relies on the inclusion of the table_funct.php script by the
calling software.
*/
//================================================================================

function load_codemirror($sub_path)
{
    global $use_codemirror, $base_dir;
    if ((isset($use_codemirror[$sub_path]['dbid'])) && (function_exists('get_base_table'))) {
        if (isset($_GET['-table'])) {
            // Do a lookup on the current table.
            $option = get_base_table($_GET['-table'],db_connect($use_codemirror[$sub_path]['dbid']));
        }
        else {
            // Do a lookup on the current action.
            $option = $_GET['-action'] ?? '';
        }
        if (isset($use_codemirror[$sub_path][$option])) {
            include("$base_dir/libraries/codemirror_links.php");
        }
    }
}

//==============================================================================
/*
Function set_last_preset_link_version

This function sets the last preset link version to the next value. This is in
the format 'yymmdd-nn', with the current date and a 2 digit sequence number.
*/
//==============================================================================

function set_last_preset_link_version()
{
    global $base_dir;
    $txt_file = "$base_dir/last_preset_link_version.txt";
    if (!is_file($txt_file)) {
        // Create file with today's date / sequence number 01
        file_put_contents($txt_file,date('ymd').'-01');
    }
    else {
        $content = file_get_contents($txt_file);
        $elements = explode('-',$content);
        if ($elements[0] != date('ymd')) {
            // Update to today's date /sequence number 01
            file_put_contents($txt_file,date('ymd').'-01');
        }
        else {
            // Increment sequence number for today
            $new_seq = (int)$elements[1] + 1;
            file_put_contents($txt_file,sprintf("{$elements[0]}-%02d",$new_seq));
        }
    }
}

//==============================================================================
/*
Function get_last_preset_link_version

This function retrieves the stored value of the last preset link version.
*/
//==============================================================================

function get_last_preset_link_version()
{
    global $base_dir;
    $txt_file = "$base_dir/last_preset_link_version.txt";
    if (!is_file($txt_file)) {
        file_put_contents($txt_file,date('ymd').'-01');
    }
    $content = trim(file_get_contents($txt_file));
    return $content;
}

//==============================================================================
define('SHARED_FUNCT_DEFINED',true);
endif;
//==============================================================================
