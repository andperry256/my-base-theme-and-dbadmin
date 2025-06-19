<?php
//================================================================================
/*
 * My Base Theme - Shared Functions
 *
 * Includes functions that may need to be accessed:-
 * 1. By scripts running outside the WordPress environment.
 * 2. By scripts in the wp-custom-scripts directory.
 * 3. By child theme scripts.
 */
 //================================================================================
 if (!function_exists('set_default_header_image_paths'))
 {
//================================================================================
/*
* Function set_default_header_image_paths
*/
//================================================================================

function set_default_header_image_paths()
{
    $image_file_exts = array( 'svg', 'png', 'jpg' );
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
    foreach ($image_file_exts as $ext)
    {
        if (is_file("$current_theme_dir/header_image_desktop.$ext"))
        {
            $desktop_header_image_path = "$current_theme_dir/header_image_desktop.$ext";
            $desktop_header_image_url = "$current_theme_url/header_image_desktop.$ext";
            break;
        }
    }

    $intermediate_header_image_path = $desktop_header_image_path;
    $intermediate_header_image_url = $desktop_header_image_url;
    foreach ($image_file_exts as $ext)
    {
        if (is_file("$current_theme_dir/header_image_intermediate.$ext"))
        {
            $intermediate_header_image_path = "$current_theme_dir/header_image_intermediate.$ext";
            $intermediate_header_image_url = "$current_theme_url/header_image_intermediate.$ext";
            break;
        }
    }

    $mobile_header_image_path = $intermediate_header_image_path;
    $mobile_header_image_url = $intermediate_header_image_url;
    foreach ($image_file_exts as $ext)
    {
        if (is_file("$current_theme_dir/header_image_mobile.$ext"))
        {
            $mobile_header_image_path = "$current_theme_dir/header_image_mobile.$ext";
            $mobile_header_image_url = "$current_theme_url/header_image_mobile.$ext";
            break;
        }
    }
}

//================================================================================
/*
* Function set_header_image_paths
*/
//================================================================================

function set_header_image_paths($slug,$type)
{
    if (function_exists('set_custom_header_image_paths'))
    {
        // Call child theme function
        set_custom_header_image_paths($slug,$type);
    }
    else
    {
        // No action - the default paths will apply
    }
}

//================================================================================
/*
* Function output_page_header
*
* This function is used to output the main title header of the current page.
*/
//================================================================================

function output_page_header()
{
    if (function_exists('get_secondary_title'))
    {
        $secondary_title = get_secondary_title();
    }
    else
    {
        $secondary_title = '';
    }
    if ($secondary_title == '#')
    {
        // No action
    }
    elseif (!empty($secondary_title))
    {
        echo("<h1>$secondary_title</h1>\n");
    }
    else
    {
        the_title( '<h1 class="entry-title">', '</h1>' );
    }
}

//================================================================================
/*
* Function get_content_part
*
* This function is used extract and output a given portion of the page content
* and is for use when the content section of a page is being built using a custom
* PHP script. A numeric part number is passed as a parameter and this indicates
* that the text is to be extracted from between the following tags in the
* WordPress page content:-
*
* [part<n>]
* [/part<n>]
*
* where <n> is the part number. This allows multiple portions to be extracted
* from the pages content for use at different points in the page.
*/
//================================================================================

function get_content_part($part_no)
{
    $post = get_post();
    $content = $post->post_content;
    $content = apply_filters( 'the_content', $content );

    if ($part_no == 0)
    {
        // Use part number 0 to return whole page content.
    }
    else
    {
        // Extract required part of page content.
        $pos1 = strpos($content,"[part$part_no]");
        $pos2 = strpos($content,"[/part$part_no]");
        if (($pos1 === false) || ($pos2 === false))
        {
            return "**** Unable to retrieve part $part_no from page ****";
        }
        $pos1 += strlen("[part$part_no]");
        $content = substr($content,$pos1,$pos2-$pos1);
    }
    $content = str_replace( ']]>', ']]&gt;', $content );
    $content = str_replace( '__', '&nbsp;', $content );
    return $content;
}

//================================================================================
/*
* Function output_meta_data
*
* This function is used to generate meta tag data in the page header.
* A number of global variables are referenced by the function to set up the tags
* as required. These will have been set up previously by running any 'metadata.php'
* scripts in the page hierachy within the custom scripts folder.
*
* N.B. To cancel a meta description from an ancestor page without creating a new one,
* the meta description must be re-defined for the page as an empty string.
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

    if ((isset($location)) && ($location == 'local'))
    {
        print("<meta name=\"robots\" content=\"noindex,nofollow\">\n");
    }
    else
    {
        if ((isset($meta_description)) && (!empty($meta_description)))
        {
            print("<meta name=\"description\" content=\"$meta_description\">\n");
        }
        $robots_content = '';
        if ((isset($meta_robots_noindex)) && ($meta_robots_noindex))
        {
            $robots_content = 'noindex';
        }
        if ((isset($meta_robots_nofollow)) && ($meta_robots_nofollow))
        {
            if (!empty($robots_content))
            {
                $robots_content .= ',';
            }
            $robots_content .= 'nofollow';
        }
        if (!empty($robots_content))
        {
            print("<meta name=\"robots\" content=\"$robots_content\">\n");
        }
    }

    if ((isset($meta_refresh_interval)) && (isset($meta_refresh_url)) && (!isset($_GET['norefresh'])))
    {
        if (!isset($meta_refresh_url_pars))
        {
            $meta_refresh_url_pars = '';
        }
        print("<meta http-equiv=\"refresh\" content=\"$meta_refresh_interval;URL='$meta_refresh_url/$meta_refresh_url_pars'\" />\n");
    }
}

//================================================================================
/*
* Function output_stylesheet_link
*
* This function is used to output a stylesheet link in the HTML header when
* the URL hierachy is scanned by setup_params.php. The stylesheet file must be
* named styles.css.
*
* The associated light/dark theme stylesheet will also be linked in if present.
*/
//================================================================================

function output_stylesheet_link($path,$sub_path)
{
    global $link_version, $base_dir, $base_url;
    $stylesheet_id = str_replace('/','-',$sub_path);
    $dir_path = str_replace($base_url,$base_dir,$path);
    print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-css'  href='$path/$sub_path/styles.css?v=$link_version' type='text/css' media='all' />\n");

    if (function_exists('get_session_var'))
    {
        if ((get_session_var('theme_mode') == 'light') && (is_file("$dir_path/$sub_path/styles-light.css")))
        {
            print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-light-css'  href='$path/$sub_path/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
        }
        elseif ((get_session_var('theme_mode') == 'dark') && (is_file("$dir_path/$sub_path/styles-dark.css")))
        {
            print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-dark-css'  href='$path/$sub_path/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
        }
    }
}

//================================================================================
/*
* Function include_inline_stylesheet
*
* This function loads a stylesheet file and outputs its contents within
* <style></style> tags by way of inline styles. It can be called from anywhere
* but is also used by setup_params.php when scanning the URL hierachy. There
* is no constraint on the stylesheet filename, but when called from
* setup_params.php, it will always be inline-styles.css.
*
* The associated light/dark theme stylesheet will also be included if present.
*/
//================================================================================

function include_inline_stylesheet($path)
{
    print("<style>\n");
    if (is_file($path))
    {
        include($path);
    }
    $light_theme_path = str_replace('.css','-light.css',$path);
    $dark_theme_path = str_replace('.css','-dark.css',$path);
    if (function_exists('get_session_var'))
    {
        if ((get_session_var('theme_mode') == 'light') && (is_file($light_theme_path)))
        {
        include($light_theme_path);
    }
        elseif ((get_session_var('theme_mode') == 'dark') && (is_file($dark_theme_path)))
        {
            include($dark_theme_path);
        }
    }
    print("</style>\n");
}

//================================================================================
/*
* Function include_inline_javascript
*
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
* Functions save_php_error_log & restore_php_error_log
*/
//================================================================================

function save_php_error_log()
{
    global $root_dir;
    if (is_file("$root_dir/logs/php_error.log"))
    {
        copy("$root_dir/logs/php_error.log","$root_dir/logs/php_error.log.sav");
    }
}

function restore_php_error_log()
{
    global $root_dir;
    if (is_file("$root_dir/logs/php_error.log"))
    {
        unlink("$root_dir/logs/php_error.log");
    }
    if (is_file("$root_dir/logs/php_error.log.sav"))
    {
        rename("$root_dir/logs/php_error.log.sav","$root_dir/logs/php_error.log");
    }
}

//================================================================================
/*
* Function output_to_access_log
*/
//================================================================================

function output_to_access_log($user='',$add_info='')
{
    global $access_logs_dir;
    global $base_dir;
    include("$base_dir/common_scripts/allowed_hosts.php");
    if (is_dir($access_logs_dir))
    {
        $date = date('Y-m-d');
        $ofp = fopen("$access_logs_dir/$date.log",'a');
        $time = date('H:i:s');
        $addr_str = $_SERVER['REMOTE_ADDR'];
        if (is_local_ip($_SERVER['REMOTE_ADDR']))
        {
            $addr_str = '[Local]';
        }
        elseif (isset($allowed_hosts[$addr_str]))
        {
            $addr_str = "[{$allowed_hosts[$addr_str]}]";
        }
        $addr_str = substr("$addr_str               ",0,15);
        $uri_str = str_replace('%','%%',$_SERVER['REQUEST_URI']);
        fprintf($ofp,"$date $time ".'-'." $addr_str $uri_str");
        if (!empty($user))
        {
            fprintf($ofp," [user = $user]");
        }
        if ((isset($_SERVER['HTTP_REFERER'])) && (!empty($_SERVER['HTTP_REFERER'])))
        {
            $referrer_str = str_replace('%','%%',$_SERVER['HTTP_REFERER']);
            fprintf($ofp,$referrer_str);
        }
        if (!empty($add_info))
        {
            fprintf($ofp," [$add_info]");
        }
        fprintf($ofp,"\n");
        fclose($ofp);
    }
}

//================================================================================
/*
* Function simplify_html_tag
*
* This function is called by the simplify_html function or a site specific
* function that calls the latter.
*
* It reduces all tags of a given type to a simple tag with no options.
*/
//================================================================================

function simplify_html_tag($content,$tag)
{
    $pos1 = strpos($content,"<$tag");
    while ($pos1 !== false)
    {
        $pos2 = strpos($content,'>',$pos1);
        $content = substr($content,0,$pos1+strlen($tag)+1).substr($content,$pos2);
        $pos1 = strpos($content,"<$tag",$pos1+1);
    }
    return $content;
}

//================================================================================
/*
* Function simplify_html
*
* This function is called to simplify a word processor document that has been
* exported as HTML. Its main purpose is to remove any built-in style
* information that is otherwise defined in CSS.
*
* If further edits are required, then it is suggested that this function is
* called from a site specific function with the necessary additional
* functionality.
*/
//================================================================================

function simplify_html($content)
{
    global $allowed_tags, $simplified_tags;
    if (!isset($allowed_tags))
    {
        /*
        The '$allowed_tags' array specifies those tags that are to be retained in the
        generated HTML code. This is the default version but can be overridden by
        declaring a custom version outside the function call.
        */
        $allowed_tags = array('<p>','<br>','<a>','<table>','<th>','<tr>','<td>','<ul>','<ol>','<li>','<b>','<strong>','<i>','<em>','<u>');
    }
    if (!isset($simplified_tags))
    {
        /*
        The '$simplified_tags' array specifies those tag types for which the
        'simplify_html_tag' function is to be run. This is the default version but
        can be overridden by declaring a custom version outside the function call.
        */
        $simplified_tags = array('p');
    }

    // Strip out any <style> tags. This is done long-hand as there have been
    // issues when just relying on the call to strip_tags.
    $pos1 = strpos($content,'<style');
    while ($pos1 !== false)
    {
        $pos2 = strpos($content,'</style>',$pos1);
        $content = substr($content,0,$pos1).substr($content,$pos2+8);
        $pos1 = strpos($content,'<style',$pos1+1);
    }

    // Run the main operation
    $content = strip_tags($content,$allowed_tags);

    // Apply the simplify_html_tag function to selected tag types
    foreach ($simplified_tags as $tag)
    {
        $content = simplify_html_tag($content,$tag);
    }
    $content = str_replace('<li><p>','<li>',$content);
    $content = str_replace('</p></li>','</li>',$content);

    return $content;
}

//================================================================================
/*
* Function recache_page
*
* This function is for use in conjuction with the 'WP Super Cache' plugin. It is
* called to clear any cache files for a given page and then activate the page to
* cause the cache to be regenerated (provided the page is configured to be cached).
* A path to the page is provided as a parameter and this can be one of the
* following:
*
* 1. The WordPress page name (slug). This option works for both posts and pages.
* 2. A full URI sub-path specifying the hierarchy of the page with its ancestors.
*
* An optional parameter specifies a run count, allowing for the re-cache to be run
* more than once (generally twice) in special situations.
*/
//================================================================================

function recache_page($page_path,$run_count=1)
{
    global $cache_dir;
    global $base_url;
    if ((!isset($cache_dir)) || (!defined('WP_DBID')))
    {
        // Return with no action/error.
        return;
    }
    $db = db_connect(WP_DBID);
    if (strpos($page_path,'/') === false)
    {
        // Parameter is a page slug - build full sub-path by looping through page hierarchy.
        $uri_subpath = '';
        $where_clause = 'post_name=?';
        $where_values = array('s',$page_path);
        while($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,'')))
        {
            $uri_subpath = "{$row['post_name']}/$uri_subpath";
            if ($row['post_parent'] == 0)
            {
                break;
            }
            else
            {
                $where_clause = 'ID=?';
                $where_values = array('i',$row['post_parent']);
            }
        }
        if (substr($uri_subpath,0,5) == 'home/')
        {
            $uri_subpath = substr($uri_subpath,5);
        }
        $uri_subpath = trim($uri_subpath,'/');
    }
    else
    {
        // Parameter is a full URI sub-path.
        $uri_subpath = $page_path;
    }

    // Determine path for subdirectory containing cache for given page.
    $pos = strpos($base_url,'//');
    $cache_subdir = "$cache_dir/supercache/".substr($base_url,$pos+2)."/$uri_subpath";
    $cache_subdir = rtrim($cache_subdir,'/');

    // Run operation for prescribed number of times.
    for ($i=1; $i<=$run_count; $i++)
    {
        // Delete cache files for page.
        if (is_dir($cache_subdir))
        {
            $dirlist = scandir($cache_subdir);
            foreach ($dirlist as $file)
            {
                if (is_file("$cache_subdir/$file"))
                {
                    unlink("$cache_subdir/$file");
                }
            }
        }

        // Activate page to regenerate cache.
        $dummy = file_get_contents("$base_url/$uri_subpath");
    }
}

//================================================================================
/*
* Function recache_all_pages
*
* This function is called to execute the recache_page function on all published
* pages/posts within the site.
*/
//================================================================================

function recache_all_pages($type='page')
{
    if (defined('WP_DBID'))
    {
        $eol = (!empty($_SERVER['REMOTE_ADDR'])) ? "<br />\n" : "\n";
        $db = db_connect(WP_DBID);
        $where_clause = "post_type='$type' AND post_status='publish'";
        $where_values = array();
        $add_clause = "ORDER BY post_name ASC";
        $query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            set_time_limit(30);
            print("Re-caching $type [{$row['post_name']}]$eol");
            recache_page($row['post_name']);
        }
    }
}

//================================================================================
/*
* Function create_cache_reload_link
*
* This function needs to be included in the theme header.php script. It will cause
* the page to be immediately redirected a script which recaches and reloads the
* page, on the condition that a 'recache indicator' has been supplied as a URL
* parameter.
*/
//================================================================================

function create_cache_reload_link()
{
    global $base_url;
    $recache_indicator = (defined('RECACHE_INDICATOR')) ? RECACHE_INDICATOR : 'recache';
    if (isset($_GET[$recache_indicator]))
    {
        $uri_path = $_SERVER['REQUEST_URI'];
        $pos1 = strpos($uri_path,"?$recache_indicator");
        $pos2 = strpos($uri_path,"&$recache_indicator");
        if ($pos1 !== false)
        {
            $uri_path = substr($uri_path,0,$pos1);
        }
        elseif ($pos2 !== false)
        {
            $uri_path = substr($uri_path,0,$pos2);
        }
        $uri_path = trim($uri_path,'/');
        if (empty($uri_path))
        {
            $uri_path = 'home';
        }
        print("<meta http-equiv=\"refresh\" content=\"0;URL='$base_url/common_scripts/load_recached_page.php?uripath=$uri_path'\" />\n");
    }
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
    if (session_var_is_set(SV_USER))
    {
        // User logged in
        return get_session_var(SV_USER);
    }
    else
    {
        // No user logged in
        return '';
    }
}

//================================================================================

function put_user($username)
{
    if (empty($username))
    {
        // Perform operations for user logout
        $db = db_connect(WP_DBID);
        update_session_var(SV_USER,'');
        update_session_var(SV_ACCESS_LEVEL,DEFAULT_ACCESS_LEVEL);
        setcookie(LOGIN_COOKIE_ID,'',time()-3600,LOGIN_COOKIE_PATH);
        $session_id = session_id();
        if (!empty($session_id))
        {
            $where_clause = 'session_id=? AND (name=? OR name=?)';
            $where_values = array('s',$session_id,'s',SV_USER,'s',SV_ACCESS_LEVEL);
            mysqli_delete_query($db,'wp_session_updates',$where_clause,$where_values);
        }
    }
    else
    {
        // Perform operations for user login
        update_session_var(SV_USER,$username);
        update_session_var(SV_ACCESS_LEVEL,user_access_level($username));
    }
    // Perform any additional site-specific operations
    if (function_exists('put_user_additions'))
    {
        put_user_additions($username);
    }
}

//================================================================================

function get_access_level()
{
    if (session_var_is_set(SV_ACCESS_LEVEL))
    {
        return get_session_var(SV_ACCESS_LEVEL);
    }
    else
    {
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
    if (isset($_COOKIE[LOGIN_COOKIE_ID]))
    {
        $login_id = $_COOKIE[LOGIN_COOKIE_ID];
        if ((!empty($login_id)) && ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM login_sessions WHERE id='$login_id'"))))
        {
            // Update user, login cookie and DB record
            put_user($row['username']);
            setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
            mysqli_query($db,"UPDATE login_sessions SET update_time=$current_time,date_and_time='$date_and_time' WHERE id='$login_id'");
        }
    }
    elseif ((!empty($username)) && (!empty($access_level)))
    {
        // New login - create cookie and associated login session record
        $login_id = md5($username.date('YmdHis'));
        $query = "INSERT INTO login_sessions (id,username,access_level,update_time,date_and_time)";
        $query .= " VALUES ('$login_id','$username','$access_level',$current_time,'$date_and_time')";
        mysqli_query($db,$query);
        setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
    }
}

//================================================================================

function log_user_out($db)
{
    // Clear all user info, including the cookie and login session record.
    put_user('');
    if (isset($_COOKIE[LOGIN_COOKIE_ID]))
    {
        $login_id = $_COOKIE[LOGIN_COOKIE_ID];
        mysqli_query($db,"DELETE FROM login_sessions WHERE id='$login_id'");
        $expiry_time = time() - 3600;
        setcookie(LOGIN_COOKIE_ID,$login_id,$expiry_time,LOGIN_COOKIE_PATH);
    }
}

//==============================================================================

function sync_post_data($source_dbid,$source_user,$target_dbid,$target_user,$option,$category='')
{
    $db1 = db_connect($source_dbid,'p',$source_user);
    $db2 = db_connect($target_dbid,'p',$target_user);
    $meta_fields = array('pinterest_title',
                         'pinterest_description',
                         'facebook_text',
                         'twitter_text');
    $where_clause = "post_type='post' AND post_status='publish'";
    $where_values = array();
    $query_result = mysqli_select_query($db1,'wp_posts','*',$where_clause,$where_values,'');

    // Loop through posts in source database.
    while ($row1 = mysqli_fetch_assoc($query_result))
    {
        $post_name = $row1['post_name'];
        $where_clause = "post_name=? AND post_status='publish'";
        $where_values = array('s',$row1['post_name']);
        if ($row2 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_posts','*',$where_clause,$where_values,'')))
        {
            // Matching post name found in target database.
            $query = "SELECT * FROM wp_terms LEFT JOIN wp_term_taxonomy ON wp_terms.term_id=wp_term_taxonomy.term_ID WHERE slug='$category' AND taxonomy='category'";
            $category_match = false;
            if (empty($category))
            {
                $category_match = true;
            }
            elseif (($row3 = mysqli_fetch_assoc(mysqli_query($db1,$query))) &&
                    ($row4 = mysqli_fetch_assoc(mysqli_query($db2,$query))))
            {
                // Category exists in both DBs. Now check if both posts are in that category.
                if (($row5 = mysqli_fetch_assoc(mysqli_query($db1,"SELECT * FROM wp_term_relationships WHERE object_id={$row1['ID']} AND term_taxonomy_id={$row3['term_taxonomy_id']}"))) &&
                    ($row6 = mysqli_fetch_assoc(mysqli_query($db2,"SELECT * FROM wp_term_relationships WHERE object_id={$row2['ID']} AND term_taxonomy_id={$row4['term_taxonomy_id']}"))))
                {
                    $category_match = true;
                }
            }
            if ($category_match)
            {
                $where_clause = "post_id=? AND meta_key='inhibit_sync'";
                $where_values = array('post_id',$row2['ID']);
                if ($row3 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_postmeta','*',$where_clause,$where_values,'')));
                {
                    $inhibit_sync = ($row3['meta_value']) ?? false;
                }
                if (empty($inhibit_sync))
                {
                    if (($option == 'timestamp') && ($row1['post_date'] != $row2['post_date']))
                    {
                        // Synchronise post timestamp.
                        echo "Synchronising timestamp for post $post_name\n";
                        $where_clause = "post_name=?";
                        $where_values = array('s',$post_name);
                        $fields = ('post_date,post_date_gmt');
                        $values = array ('s',$row1['post_date'],'s',$row1['post_date_gmt']);
                        mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                    }
                    elseif ($option == 'content')
                    {
                        if ($row1['post_content'] != $row2['post_content'])
                        {
                            // Synchronise post content.
                            echo "Synchronising content for post $post_name\n";
                            $where_clause = "post_name=?";
                            $where_values = array('s',$post_name);
                            $fields = ('post_content');
                            $values = array ('s',$row1['post_content']);
                            mysqli_update_query($db2,'wp_posts',$fields,$values,$where_clause,$where_values);
                        }
                        // Synchronise any meta values.
                        foreach ($meta_fields as $field)
                        {
                            $where_clause = 'post_id=? AND meta_key=?';
                            $where_values_1 = array('',$row1['ID'],'s',$field);
                            $where_values_2 = array('',$row2['ID'],'s',$field);
                            if (($row3 = mysqli_fetch_assoc(mysqli_select_query($db1,'wp_postmeta','*',$where_clause,$where_values_1,''))) &&
                                ($row4 = mysqli_fetch_assoc(mysqli_select_query($db2,'wp_postmeta','*',$where_clause,$where_values_2,''))) &&
                                ($row3['meta_value'] != $row4['meta_value']))
                            {
                                echo "Synchronising meta value for post $post_name => $field\n";
                                $fields = 'meta_value';
                                $values = array('s',$row3['meta_value']);
                                mysqli_update_query($db2,'wp_postmeta',$fields,$values,$where_clause,$where_values_2);
                            }
                        }
                    }
                }
            }
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
* Function output_font_stylesheet_links
*
* ******** THIS FUNCTION IS DEPRECATED ********
*
* This function is used to generate the stylesheet links for the main and header
* fonts assigned within the given theme. It relies on access to Google Fonts.
*
* It generates the necessary CSS files in the root directory of the Base Theme if
* not already present. To force regeneration of all such files, load any page on
* the site with the parameter 'reloadfonts' appended to the URL.
*/
//================================================================================

function output_font_stylesheet_links()
{

    global $base_theme_dir;
    global $base_theme_url;
    global $link_version;
    global $main_font;
    global $header_font;
    $main_elements = 'html, body, div, p, li, td, select, input';
    $default_main_font = 'Noto Sans';
    $header_elements = 'h1, h2, h3, h4, h5, h6';
    $default_header_font = 'Roboto';
    $mono_elements = 'pre, code, textarea';
    $default_mono_font = 'Source Code Pro';
    require(__DIR__.'/font_lists.php');

    // Set up variables for main, header and mono fonts.
    if ((!isset($main_font)) || (!isset($main_fonts[$main_font])))
    {
        $main_font = $default_main_font;
    }
    $lc_main_font = strtolower($main_font);
    $lc_main_font = str_replace(' ','_',$lc_main_font);
    if ((!isset($header_font)) || (!isset($main_fonts[$header_font])))
    {
        $header_font = $default_header_font;
    }
    $lc_header_font = strtolower($header_font);
    $lc_header_font = str_replace(' ','_',$lc_header_font);
    if ((!isset($mono_font)) || (!isset($main_fonts[$mono_font])))
    {
        $mono_font = $default_mono_font;
    }
    $lc_mono_font = strtolower($mono_font);
    $lc_mono_font = str_replace(' ','_',$lc_mono_font);

    if (isset($_GET['reloadfonts']))
    {
        // Regenerate CSS files for all available fonts.
        $dirlist = scandir($base_theme_dir);
        foreach($dirlist as $file)
        {
            if ((strpos($file,'_font_') !== false) && (pathinfo($file,PATHINFO_EXTENSION) == 'css'))
            {
                unlink("$base_theme_dir/$file");
            }
        }
        foreach ($main_fonts as $font => $link)
        {
            $lc_font = strtolower($font);
            $lc_font = str_replace(' ','_',$lc_font);
            $ofp = fopen("$base_theme_dir/main_font_$lc_font.css",'w');
            fprintf($ofp,"$main_elements {\nfont-family: '$font', sans-serif;\n}\n");
            fclose($ofp);
            $ofp = fopen("$base_theme_dir/header_font_$lc_font.css",'w');
            fprintf($ofp,"$header_elements {\nfont-family: '$font', sans-serif;\n}\n");
            fclose($ofp);
        }
        foreach ($mono_fonts as $font => $link)
        {
            $lc_font = strtolower($font);
            $lc_font = str_replace(' ','_',$lc_font);
            $ofp = fopen("$base_theme_dir/mono_font_$lc_font.css",'w');
            fprintf($ofp,"$mono_elements {\nfont-family: '$font', sans-serif;\n}\n");
            fclose($ofp);
        }
    }
    else
    {
        if (!is_file("$base_theme_dir/main_font_$lc_main_font.css"))
        {
            // Regenerate CSS file for assigned main font.
            $ofp = fopen("$base_theme_dir/main_font_$lc_main_font.css",'w');
            fprintf($ofp,"$main_elements {\nfont-family: '$main_font', sans-serif;\n}\n");
            fclose($ofp);
        }
        if (!is_file("$base_theme_dir/header_font_$lc_header_font.css"))
        {
            // Regenerate CSS file for assigned header font.
            $ofp = fopen("$base_theme_dir/header_font_$lc_header_font.css",'w');
            fprintf($ofp,"$header_elements {\nfont-family: '$header_font', sans-serif;\n}\n");
            fclose($ofp);
        }
        if (!is_file("$base_theme_dir/mono_font_$lc_mono_font.css"))
        {
            // Regenerate CSS file for assigned mono font.
            $ofp = fopen("$base_theme_dir/mono_font_$lc_mono_font.css",'w');
            fprintf($ofp,"$mono_elements {\nfont-family: '$mono_font', sans-serif;\n}\n");
            fclose($ofp);
        }
    }

    // Create links to Google fonts.
    print("\n");
    print("<link rel='preconnect' href='https://fonts.googleapis.com'>\n");
    print("<link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>\n");
    print("<link rel='stylesheet' id='main-font-css1'  href='{$main_fonts[$main_font]}' type='text/css' media='all' />\n");
    if ($header_font != $main_font)
    {
        print("<link rel='stylesheet' id='header-font-css1'  href='{$main_fonts[$header_font]}' type='text/css' media='all' />\n");
    }
    print("<link rel='stylesheet' id='mono-font-css1'  href='{$mono_fonts[$mono_font]}' type='text/css' media='all' />\n");

    // Create link to CSS for main font.
    if (is_file("$base_theme_dir/main_font_$lc_main_font.css"))
    {
        print("<link rel='stylesheet' id='main-font-css2'  href='$base_theme_url/main_font_$lc_main_font.css?v=$link_version' media='all' />\n");
    }
    else
    {
        // This should not occur unless folder permissions prevent creation of CSS file.
        print("<style>\n$main_elements {\nfont-family: '$main_font', sans-serif;\n</style>\n");
    }

    // Create link to CSS for header font.
    if ($header_font != $main_font)
    {
        if (is_file("$base_theme_dir/header_font_$lc_header_font.css"))
        {
            print("<link rel='stylesheet' id='header-font-css2'  href='$base_theme_url/header_font_$lc_header_font.css?v=$link_version' media='all' />\n");
        }
        else
        {
            // This should not occur unless folder permissions prevent creation of CSS file.
            print("<style>\n$header_elements {\nfont-family: '$header_font', sans-serif;\n</style>\n");
        }
    }

    // Create link to CSS for mono font.
    if (is_file("$base_theme_dir/mono_font_$lc_mono_font.css"))
    {
        print("<link rel='stylesheet' id='mono-font-css2'  href='$base_theme_url/mono_font_$lc_mono_font.css?v=$link_version' media='all' />\n");
    }
    else
    {
        // This should not occur unless folder permissions prevent creation of CSS file.
        print("<style>\n$mono_elements {\nfont-family: '$mono_font', sans-serif;\n</style>\n");
    }
}

//================================================================================
}
//================================================================================
?>
