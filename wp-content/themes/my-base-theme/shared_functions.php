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

function get_content_part($part_no,$option='')
{
    $page_id = get_the_ID();
    $page_object = get_page($page_id);
    $content = $page_object->post_content;
    $dummy = "[[[[[[[[";  // To prevent false positive in PHP code checker
    if ($part_no == 0)
    {
        // Use part number 0 to return whole page content
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
    }
    else
    {
        $pos1 = strpos($content,"[part$part_no]");
        $pos2 = strpos($content,"[/part$part_no]");
        if (($pos1 === false) || ($pos2 === false))
        {
            return "**** Unable to retrieve part $part_no from page ****";
        }
        $pos1 += strlen("[part$part_no]");
        $content = substr($content,$pos1,$pos2-$pos1);
        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
        $content = str_replace( '__', '&nbsp;', $content );
    }
    if ($option == 'strip_paras')
    {
        $content = str_replace('<p>','',$content);
        $content = str_replace('</p>','',$content);
    }
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
* Function output_font_stylesheet_links
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

    // List of available fonts.
    $main_fonts = array (
        'IBM Plex Sans' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap',
        'Lato' => 'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap',
        'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap',
        'Noto Sans' => 'https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap',
        'Open Sans' => 'https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap',
        'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap',
    );
    $mono_fonts = array (
        'IBM Plex Mono' => 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&family=IBM+Plex+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;1,100;1,200;1,300;1,400;1,500;1,600;1,700&display=swap',
        'Source Code Pro' => 'https://fonts.googleapis.com/css2?family=Roboto+Mono:ital,wght@0,100..700;1,100..700&family=Source+Code+Pro:ital,wght@0,200..900;1,200..900&display=swap',
    );

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

    for ($i=1; $i<=$run_count; $i++)
    {
        // Delete cache files for page.
        $dirlist = scandir($cache_subdir);
        foreach ($dirlist as $file)
        {
            if (is_file("$cache_subdir/$file"))
            {
                unlink("$cache_subdir/$file");
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
    global $argc;
    if (defined('WP_DBID'))
    {
        $eol = (isset($argc)) ? "\n" : "<br />\n";
        $db = db_connect(WP_DBID);
        $where_clause = "post_type='$type' AND post_status='publish'";
        $where_values = array();
        $add_clause = "ORDER BY post_name ASC";
        $query_result = mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,$add_clause);
        while ($row = mysqli_fetch_assoc($query_result))
        {
            print("Re-caching $type [{$row['post_name']}]$eol");
            recache_page($row['post_name']);
        }
    }
}

//================================================================================
}
//================================================================================
?>
