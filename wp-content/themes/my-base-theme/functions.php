<?php
//================================================================================

/*
 * My Base Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package My_Base_Theme
 */

//================================================================================

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */

//================================================================================
if ( ! function_exists( 'my_base_theme_setup' ) ) :
//================================================================================

function my_base_theme_setup()
{
    /*
     * Make theme available for translation.
     * Translations can be filed in the /languages/ directory.
     * If you're building a theme based on My Base Theme, use a find and replace
     * to change 'my-base-theme' to the name of your theme in all the template files.
     */
    load_theme_textdomain( 'my-base-theme', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    /*
     * Let WordPress manage the document title.
     * By adding theme support, we declare that this theme does not use a
     * hard-coded <title> tag in the document head, and expect WordPress to
     * provide it for us.
     */
    add_theme_support( 'title-tag' );

    /*
     * Enable support for Post Thumbnails on posts and pages.
     *
     * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
     */
    add_theme_support( 'post-thumbnails' );

    // This theme uses wp_nav_menu() in one location.
    register_nav_menus( array(
        'menu-1' => esc_html__( 'Primary', 'my-base-theme' ),
    ) );

    /*
     * Switch default core markup for search form, comment form, and comments
     * to output valid HTML5.
     */
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ) );

    // Set up the WordPress core custom background feature.
    add_theme_support( 'custom-background', apply_filters( 'my_base_theme_custom_background_args', array(
        'default-color' => 'ffffff',
        'default-image' => '',
    ) ) );

    // Add theme support for selective refresh for widgets.
    add_theme_support( 'customize-selective-refresh-widgets' );
}
add_action( 'after_setup_theme', 'my_base_theme_setup' );

//================================================================================

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function my_base_theme_content_width()
{
    $globals['content_width'] = apply_filters( 'my_base_theme_content_width', 640 );
}
add_action( 'after_setup_theme', 'my_base_theme_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */

//================================================================================

function my_base_theme_widgets_init()
{
    register_sidebar( array(
        'name'          => esc_html__( 'Sidebar', 'my-base-theme' ),
        'id'            => 'sidebar-1',
        'description'   => esc_html__( 'Add widgets here.', 'my-base-theme' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'my_base_theme_widgets_init' );

//================================================================================
/*
 * Enqueue scripts and styles.
 */
function my_base_theme_scripts()
{
    global $link_version;
    if (!isset($link_version)) {
        $link_version = date('ym').'01';
    }
    $stylesheet_uri = get_stylesheet_uri();
    if (function_exists('url_to_static')) {
        $stylesheet_uri = url_to_static($stylesheet_uri);
    }
    wp_enqueue_style( 'my-base-theme-style', $stylesheet_uri, '', $link_version );

    wp_enqueue_script( 'my-base-theme-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );

    wp_enqueue_script( 'my-base-theme-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'my_base_theme_scripts' );

//================================================================================
/*
 * Remove login shake
 */
function wpb_remove_loginshake()
{
remove_action('login_head', 'wp_shake_js', 12);
}
add_action('login_head', 'wpb_remove_loginshake');

//==============================================================================

function output_header_links()
{
    global $base_url;
    global $image_type_1;
    $category_list = [];
    $categories = get_categories();
    foreach ($categories as $cat) {
        $id = (int)$cat->term_id;
        $name = $cat->cat_name;
        $hierarchy = get_category_parents($id, false, '/', true);
        $image_id = get_term_meta($id,'featured_image',true);
        $access_level = get_term_meta($id,'access_level',true);
        $user_access_level =  (session_var_is_set(SV_ACCESS_LEVEL))
            ? get_session_var(SV_ACCESS_LEVEL)
            : 0;
        $top_level_category = get_term_meta($id,'top_level_category',true);
        $blog_home_description = get_term_meta($id,'blog_home_description',true);
        if (($top_level_category) && (!empty($blog_home_description)) && ($user_access_level >= $access_level)) {
            $category_list[$name] = [$hierarchy,$image_id];
        }
    }
    ksort($category_list);
    foreach ($category_list as $name => $info) {
        $category_url = "$base_url/category/{$info[0]}";
        print("<div class=\"category-icon-link\">");
        print("<a href=\"$category_url\">");
        $image_info = wp_get_attachment_image_src($info[1],[480,320]);
        $image_url = get_modified_image_url($image_info[0],$image_type_1);
        print("<img src=\"$image_url\" class=\"category-icon-link-image\" /><br />");
        print("$name</a></div>");
    }
}

//==============================================================================

function authenticate_post($slug,$use_overriding_access_level=false)
{
    if (function_exists('custom_authenticate_post')) {
        return custom_authenticate_post($slug,$use_overriding_access_level);
    }
    else {
        return true;
    }
}

//==============================================================================

function get_top_level_category_for_post()
{
    global $base_url;
    $category_list = [];
    $categories = get_the_category();
    foreach ($categories as $cat) {
        $id = (int)$cat->term_id;
        $name = $cat->cat_name;
        $top_level_category = get_term_meta($id,'top_level_category',true);
        if ($top_level_category) {
            if (isset($retval)) {
                // More than one top level category allocated.
                return '*';
            }
            else {
                $retval = $name;
            }
        }
    }
    return $retval ?? false;
}

//==============================================================================

function display_category_summary($category_name,$category_info,$image_max_width,$image_max_height)
{
    global $base_url, $link_version, $image_type_3;
    $category_url = "$base_url/category/{$category_info[0]}";
    print("<div class=\"post-list-item\">\n");
    print("<div class=\"post-image-holder\">");
    if (!empty($category_info[1])) {
        $image_info = wp_get_attachment_image_src($category_info[1],[$image_max_width,$image_max_height]);
        $image_url = get_modified_image_url($image_info[0],$image_type_3);
        print("<a href=\"$category_url\"><img src=\"$image_url?v=$link_version\"/></a>\n");
    }
    print("</div>\n");
    print("<div class=\"post-text-holder\">");
    print("<p><a href=\"$category_url\">$category_name</a><br /></p>");
    print("<p>{$category_info[2]}</p>");
    print("</div>\n");
    print("</div>\n");
    print("<div class=\"post-list-spacer\">&nbsp;</div>\n");
}

//==============================================================================

function display_post_summary($header_level,$image_max_width,$image_max_height)
{
    global $wpdb;
    global $base_dir;
    global $base_url;
    global $home_ip_addr;
    global $show_author_in_post_summary;
    global $image_type_3;
    $id = get_the_ID();
    $row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID=$id");
    $post_content = $row->post_content;
    $post_date = substr($row->post_date,0,10);
    $post_date = date("d F Y", strtotime($post_date));
    $slug = $row->post_name;
    print("<div class=\"post-list-item\">\n");
    print("<div class=\"post-image-holder\">");
    $image_url = get_modified_image_url(get_the_post_thumbnail_url(),$image_type_3);
    if (!empty($image_url)) {
        print("<div><img src=\"$image_url\" /></div>\n");
    }
    print("</div>\n");
    print("<div class=\"post-text-holder\">");
    echo "<h$header_level>"; the_title(); echo "</h$header_level>\n";
    print("<p>[Posted on: $post_date");
    if (!empty($show_author_in_post_summary)) {
        $author = $row->post_author;
        if ($row2 = $wpdb->get_row("SELECT * FROM wp_users WHERE ID=$author")) {
            print(" by {$row2->display_name}");
        }
    }
    print("]</p>\n");
    the_content();
    if (strpos($post_content,'<!--more-->') === false) {
        print("<a href=\"$base_url/$slug\">Go to Post</a>\n");
    }
    if ($_SERVER['REMOTE_ADDR'] == $home_ip_addr) {
        print("<a href=\"$base_url/post-to-social?slug=$slug\" target=\"_blank\">Post to Social</a>");
    }
    print("</div>\n");
    print("</div>\n");
}

//==============================================================================

function display_post_content($header_level=1,$show_image=true)
{
    global $wpdb;
    global $image_type_3;
    $id = get_the_ID();
    $row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID=$id");
    $post_date = substr($row->post_date,0,10);
    $post_date = date("d F Y", strtotime($post_date));
    echo "<h$header_level>"; the_title(); echo "</h$header_level>\n";
    print("<p>[Posted on: $post_date]</p>\n");
    if ($show_image) {
        $image_url = get_modified_image_url(get_the_post_thumbnail_url(),$image_type_3);
        if (!empty($image_url)) {
            print("<div class=\"right-aligned-image\"><img src=\"$image_url\"></div>\n");
        }
    }
    echo get_content_part(0);
}

//==============================================================================

function navigation_links($option,$filter='category',$filtered_name='')
{
    global $base_url;
    global $theme_url;
    global $wpdb;
    if ($option == 'single') {
        $args = [
            'prev_text' => '%title',
            'next_text' => '%title',
            'in_same_term' => true,
            'taxonomy' => 'category',
        ];
        $navigation = get_the_post_navigation($args);
    }
    elseif ($option == 'multi') {
        $args = [
            'prev_text' => 'Older Posts',
            'next_text' => 'Newer Posts',
        ];
        $navigation = get_the_posts_navigation($args);
    }
    else {
        // This should not occur
        return;
    }
    $nav_info = [
        ['nav-previous','',''],
        ['nav-next','','']
    ];
    $cat_list_array = [];
    foreach ([0,1] as $i) {
        $pos1 = strpos($navigation,"<div class=\"{$nav_info[$i][0]}\">");
        if ($pos1 !== false) {
            $pos2 = strpos($navigation,'href="',$pos1) + 6;
            $pos3 = strpos($navigation,'"',$pos2);
            $nav_info[$i][1] = substr($navigation,$pos2,$pos3-$pos2); // Link
            $pos4 = strpos($navigation,'>',$pos3) + 1;
            $pos5 = strpos($navigation,'<',$pos4);
            $nav_info[$i][2] = substr($navigation,$pos4,$pos5-$pos4);  // Title
        }
    }

    print("<table class=blog-nav-table><tr>");
    if (!empty($nav_info[0][1])) {
        print("<td class=blog-nav-col1><a href=\"{$nav_info[0][1]}\"><img align=\"absmiddle\" src=\"$theme_url/blog_nav_backward.svg\" class=\"blog-nav-button\"></a></td>");
        print("<td class=blog-nav-col2><a class=blog-nav-link href=\"{$nav_info[0][1]}\">{$nav_info[0][2]}</a></td>");
    }
    else {
        print("<td class=blog-nav-col1><img align=\"absmiddle\" src=\"$theme_url/blog_nav_backward_greyed.svg\" class=\"blog-nav-button\"></td>");
        print("<td class=blog-nav-col2></td>");
    }
    print("<td class=blog-nav-col3>");
    switch ($filter) {
        case 'category':
            $cat_name_list = '';
            $categories = get_the_category();
            foreach ($categories as $category) {
                $cat_name_list .= "<a href=\"$base_url/category/{$category->slug}\" class=\"cat-nav-link\">{$category->name}</a> |";
                $cat_list_array[$category->name] = true;
            }
            $cat_name_list = rtrim($cat_name_list,'| ');
            print("$cat_name_list");
            break;

        case 'author':
            $row = $wpdb->get_row("SELECT * FROM wp_users WHERE user_nicename='$filtered_name'");
            $display_name = $row->display_name;
            print("<a href=\"$base_url/author/$filtered_name\" class=\"cat-nav-link\">$display_name</a>");
            break;

    }
    print("</td>");
    if (!empty($nav_info[1][1])) {
        print("<td class=blog-nav-col2><a class=blog-nav-link href=\"{$nav_info[1][1]}\">{$nav_info[1][2]}</a></td>");
        print("<td class=blog-nav-col1><a href=\"{$nav_info[1][1]}\"><img align=\"absmiddle\" src=\"$theme_url/blog_nav_forward.svg\" class=\"blog-nav-button\"></a></td>");
    }
    else {
        print("<td class=blog-nav-col2></td>");
        print("<td class=blog-nav-col1><img align=\"absmiddle\" src=\"$theme_url/blog_nav_forward_greyed.svg\" class=\"blog-nav-button\"></td>");
    }
    print("</tr>\n");
    print("</table>\n");
}

//==============================================================================

function pagination_links($page_count)
{
    if (function_exists('wp_paginate')) {
        if ($page_count == 0) {
            wp_paginate();
        }
        else {
            $pos = strpos($_SERVER['REQUEST_URI'],'/page/');
            if ($pos === false) {
                $page_no = 1;
            }
            else {
                $page_no = strtok(substr($_SERVER['REQUEST_URI'],$pos+6),'/?');
            }
            wp_paginate("page=$page_no&pages=$page_count");
        }
    }
    else {
        navigation_links('multi','');
    }
}

//==============================================================================

function output_post_archive_item($post_id)
{
    global $base_url;
    global $selected_category;
    $db = db_connect(WP_DBID);
    $query_result = mysqli_query($db,"SELECT * FROM wp_posts WHERE ID=$post_id");
    if ($row = mysqli_fetch_assoc($query_result)) {
        print("<tr>\n");
        if ((isset($_GET['postname'])) && ($_GET['postname'] == $row['post_name'])) {
            // The particular post has been selected for display
            print("<td class=\"post-archive-table-cell\" colspan=\"3\">\n");
            print("<a name=\"{$row['post_name']}\"><h1><span style=\"font-size:0.9em\">{$row['post_title']}</span></h1></a>\n");
            $query_result2 = mysqli_query($db,"SELECT * FROM wp_users WHERE ID={$row['post_author']}");
            if ($row2 = mysqli_fetch_assoc($query_result2)) {
                $date = str_replace(' ','&nbsp;',title_date(substr($row['post_date'],0,10)));
                $post_url = "$base_url/{$row['post_name']}";
                $author_url = "$base_url/author/{$row2['user_nicename']}";
                print("<p>Posted on <a href=\"$post_url\">$date</a> by <a href=\"$author_url\">{$row2['display_name']}</a></p>");
            }
            print("{$row['post_content']}\n");
            print("<p>[<a href=\"./#{$row['post_name']}\">Close</a>]</p>\n");
            print("</td>\n");
            $post = get_page_by_path($row['post_name'],OBJECT,'post');
            $post_categories = wp_get_post_categories($post->ID);
            $hierarchy = get_category_parents($post_categories[0], false, '/', true);
            if (is_string($hierarchy)) {
                $selected_category = strtok($hierarchy,'/');
            }
        }
        else {
            // Create links for the post and the associated categories
            print("<td class=\"post-archive-table-cell\"><a name=\"{$row['post_name']}\"><a href=\"./?postname={$row['post_name']}#{$row['post_name']}\">{$row['post_title']}</a></a></td>\n");
            $date = str_replace(' ','&nbsp;',title_date(substr($row['post_date'],0,10)));
            print("<td class=\"post-archive-table-cell post-archive-date_column\">$date</td>\n");
            print("<td class=\"post-archive-table-cell post-archive-category-column\">");
            $post_categories = wp_get_post_categories($row['ID']);
            $count = 0;
            foreach($post_categories as $id) {
                if ($count > 0) {
                    print("<br/>");
                }
                $cat = get_category($id);
                $slug = $cat->slug;
                $name = str_replace(' ','&nbsp;',$cat->name);
                print("<a href=\"$base_url/category/$slug\">$name</a>");
                $count++;
            }
            print("</td>\n");
        }
        print("</tr>\n");
    }
}

//================================================================================

function get_category_access_level($id)
{
    if (function_exists('custom_get_category_access_level')) {
        return custom_get_category_access_level($id);
    }
    else {
        return DEFAULT_ACCESS_LEVEL;
    }
}

//================================================================================
/*
Function check_uncategorised_post

This function needs to be invoked via an action hook on 'save_post'. This hook
must be activated in the child theme functions.php script. Caution needs to be
exercised if there are  multiple actions on 'save_post', as this function itself
will generate a premature exit if the post is uncategorised. In this situation,
it would be best to have a single function on 'save_post', which itself calls this
function at the end.
*/
//================================================================================

function check_uncategorised_post()
{
    global $base_url;
    $post = get_post();
    $id = $post->ID ?? null;
    if ((!empty($id)) && ($post->post_type == 'post')) {
        $uncategorised = true;
        $categories = get_the_category($post->ID);
        foreach ($categories as $key => $dummy) {
            $slug = $categories[$key]->slug;
            if (($slug == 'uncategorised') || ($slug == 'uncategorized')) {
                $uncategorised = true;
                break;
            }
            else {
                $uncategorised = false;
            }
        }
        if ($uncategorised) {
            print("<p><strong>Warning:</strong> You have saved this post with the 'uncategorised' category.</p>\n");
            print("<p><a href=\"$base_url/wp-admin/post.php?post=$id&action=edit\"><button style=\"font-size:$size;\">Continue</button></a></p>\n");
            exit;
        }
    }
}

//================================================================================
/*
Function replace_spaces_at_start

This function searches for two double tildes (~~) in the post content, and coverts
these to HTML tags to force the content in between them to remain on one line with
no automatic breaks;
*/
//================================================================================

function replace_spaces_at_start()
{
    $db = db_connect(WP_DBID);
    $post = get_post();
    $id = $post->ID;
    $where_clause = 'ID=?';
    $where_values = ['i',$id];
    if (($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,''))) &&
        ($row['post_type'] == 'post')) {
        $content = $row['post_content'];
        if (substr_count($content,'~~') == 2) {
            $opening_wspace_tag = '<span style="white-space: pre">';
            $pos1 = strpos($content,'~~',0);
            $pos2 = strpos($content,'~~',$pos1+2);
            $temp_str1 = substr($content,$pos1,$pos2+2-$pos1);
            $temp_str2 = str_replace('&nbsp;',' ',$temp_str1);
            $temp_str2 = str_replace(chr(194).chr(160),' ',$temp_str2);
            $temp_str2 = trim($temp_str2,'~');
            $temp_str2 = $opening_wspace_tag."$temp_str2</span>";
            $content = str_replace($temp_str1,$temp_str2,$content);
            $fields = 'post_content';
            $values = ['s',$content];
            mysqli_update_query($db,'wp_posts',$fields,$values,$where_clause,$where_values);
        }
    }
}

//================================================================================
/*
Function check_spaces_at_start

This function checks for the presence of breaking spaces within a short substring
(default length 15 characters) at the start of the post content. This is to force
the text to a minimum width, thus avoiding the situation where stray words get
wrapped around an image.
*/
//================================================================================

function check_spaces_at_start()
{
    global $base_url;
    global $min_post_content_line_length;
    $post = get_post();
    $id = $post->ID;
    if ($post->post_type == 'post') {
        $db = db_connect(WP_DBID);
        if (!isset($min_post_content_line_length)) {
            $min_post_content_line_length = 15;
        }
        $opening_wspace_tag = '<span style="white-space: pre">';
        $tag_length = strlen($opening_wspace_tag);
        $where_clause = 'ID=?';
        $where_values = ['i',$id];
        if (($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,''))) &&
            ($row['post_type'] == 'post')) {
            // Check for spaces at start of content. Replace non-breaking spaces with normal spaces to test on a
            // substring of the required length, with no distinction between breaking and non-breaking spaces.
            $content = $row['post_content'];
            $content = str_replace('&nbsp;',' ',$content);
            $content = str_replace(chr(194).chr(160),' ',$content);
            $pos1 = strpos($content,$opening_wspace_tag);
            if (($pos1 !== false) &&
                ($pos2 = strpos($content,'</span>',$pos1+$tag_length)) &&
                ($pos2 !== false) &&
                (($pos2 - ($pos1+$tag_length)) >= $min_post_content_line_length)
               ) {
                // There is an appropriate set of tags spanning sufficient characters.
                return true;
            }
            else {
                $content = strip_tags($content);
                if (strpos(substr($content,0,$min_post_content_line_length),' ') === false) {
                    // There are no spaces in the opening substring of the required length.
                    return true;
                }
                else {
                    print("<p><strong>Warning:</strong> You have saved this post with breaking spaces near the beginning.</p>");
                    print("<p>Please re-save the post with a double tilde (~~) at each end of a substring covering at least $min_post_content_line_length characters at the start of the content.");
                    print(" The correct formatting will be generated on saving the post.</p>\n");
                    print("<p>If the above has already been done, then try moving the closing &lt;/span&gt; tag to include more characters.</p>\n");
                    print("<p><a href=\"$base_url/wp-admin/post.php?post=$id&action=edit\"><button style=\"font-size:$size;\">Continue</button></a></p>\n");
                    exit;
                }
            }
        }
    }
}

//================================================================================
/*
Function check_more_directive

The function checks for the presence of a '<!--more-->' directive within the post
content. This warning can be suppressed without needing a 'more' link by including
the tag '<!--no-more-->' within the post content (typically at the end).
*/
//================================================================================

function check_more_directive()
{
    global $base_url;
    $post = get_post();
    $id = $post->ID;
    if ($post->post_type == 'post') {
        $db = db_connect(WP_DBID);
        $where_clause = 'ID=?';
        $where_values = ['i',$id];
        if (($row = mysqli_fetch_assoc(mysqli_select_query($db,'wp_posts','*',$where_clause,$where_values,''))) &&
            ($row['post_type'] == 'post') &&
            (strpos($row['post_content'],'<!--more-->') === false) &&
            (strpos($row['post_content'],'<!--no-more-->') === false)) {
            print("<p><strong>Warning:</strong> You have saved this post without a &lt;!--more--&gt; or &lt;!--no-more--&gt; directive.</p>\n");
            print("<p><a href=\"$base_url/wp-admin/post.php?post=$id&action=edit\"><button style=\"font-size:$size;\">Continue</button></a></p>\n");
            exit;
        }
    }
}

//================================================================================
/*
Function check_blog_template

This function checks for the presence of a template number in the meta data field
'use_template'. If a number is set and a matching template file found, the post
if initialised with the contents of the template. The meta data field is reset
to zero at the end.
*/
//================================================================================

function check_blog_template()
{
    global $custom_theme_path;
    $post = get_post();
    if ($post->post_type != 'post') {
        return;
    }
    $db = db_connect(WP_DBID);
    $post_id = $post->ID;
     $template_no = get_post_meta($post_id,'use_template',true);
    if ((int)$template_no > 0) {
        $template_path = "$custom_theme_path/blog_template_$template_no.txt";
        if (is_file($template_path)) {
            // Load content from template file
            $new_content = file_get_contents($template_path);
            mysqli_query($db,"UPDATE wp_posts SET post_content='$new_content' WHERE ID=$post_id");
        }
    }
    update_post_meta($post_id,'use_template',0);
}

//================================================================================
/*
Function check_local_home_favicon
*/
//================================================================================

function check_local_home_favicon()
{
    global $location, $base_url, $link_version;
    if (($location == 'local') && (is_front_page())) {
        print("<link rel=\"icon\" href=\"$base_url/wp-content/themes/my-base-theme/local_home_favicon.png?v=$link_version\" type=\"image/x-icon\" />\n");
        return true;
    }
    else {
        return false;
    }
}

//================================================================================
/*
Shortcode functions
*/
//================================================================================

if (!defined('NO_COPY_SHORTCODE')) {
    function copy_shortcode($atts,$content=null)
    {
        $content = '&copy;';
        return $content;
    }
    add_shortcode('copy', 'copy_shortcode');
}

if (!defined('NO_NBSP_SHORTCODE')) {
    function nbsp_shortcode($atts,$content=null)
    {
        $content = '&nbsp;';
        return $content;
    }
    add_shortcode('nbsp', 'nbsp_shortcode');
}

if (!defined('NO_POUND_SHORTCODE')) {
    function pound_shortcode($atts,$content=null)
    {
        $content = '&pound;';
        return $content;
    }
    add_shortcode('pound', 'pound_shortcode');
}

if (!defined('NO_SQUOT_SHORTCODE')) {
    function squot_shortcode($atts,$content=null)
    {
        $content = "'";
        return $content;
    }
    add_shortcode('squot', 'squot_shortcode');
}

//================================================================================

/*
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/*
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/*
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/*
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/*
 * Load Jetpack compatibility file.
 */
require get_template_directory() . '/inc/jetpack.php';

/*
 * Load additonal functions file.
 */
require get_template_directory() . '/shared_functions.php';

//==============================================================================
/*
CUSTOM CATEGORIES WIDGET

Functions to create a custom version of the categories widget in which the list
of available categories depends upon the access level for the current user.
*/
//==============================================================================

class custom_categories_widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct(
          'custom_categories_widget',
          __('Categories (Custom)', 'custom_categories_widget_domain'),
          array( 'description' => __( 'Category selection with user authentication', 'custom_categories_widget_domain' ), )
        );
    }

    public function widget( $args, $instance )
    {
        global $base_url;
        if (is_category()) {
            $category = get_queried_object();
            $own_id = $category->term_id;
            $hierarchy = get_category_parents($own_id, false, '/', true);
            $top_level_category = strtok($hierarchy,'/');
            $category_list = array();
            $categories = get_categories();

            // Create array of categories that belong to the current top level category
            foreach ($categories as $cat) {
                $id = (int)$cat->term_id;
                $name = $cat->cat_name;
                $cat_access_level = get_term_meta($id,'access_level',true);
                $is_parent = get_term_meta($id,'is_parent',true);
                $hierarchy = get_category_parents($id, false, '/', true);
                if (session_var_is_set(SV_ACCESS_LEVEL)) {
                    $user_access_level = get_session_var(SV_ACCESS_LEVEL);
                }
                else {
                    $user_access_level = 0;
                }
                if (($user_access_level >= $cat_access_level) && (strtok($hierarchy,'/') == $top_level_category)) {
                    $category_list[$hierarchy] = $name;
                }
            }
            ksort($category_list);

            /*
            Output the sub-category links for the current top level category. Check
            that the array count is greater than 1 as a top level category with no
            sub-categories will still create a single array element.
            */
            if (count($category_list) > 1) {
                $first_item = true;
                foreach ($category_list as $path => $name) {
                    if ($first_item) {
                        $title =" Sub-categories of $name";
                        echo $args['before_widget'];
                        if ( ! empty( $title ) ) {
                            echo $args['before_title'] . $title . $args['after_title'];
                        }
                        $first_item = false;
                    }
                    $nesting_level = substr_count($path,'/');
                    $indent = strval($nesting_level * 20)."px";
                    print("<div class=\"sidebar-category-link\" style=\"padding-left:$indent;\"><a href=\"$base_url/category/$path\">$name</a></div>\n");
                }
                echo $args['after_widget'];
            }
        }
    }

    public function form( $instance )
    {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'custom_categories_widget_domain' );
        }
        // Functionality goes here
    }

    public function update( $new_instance, $old_instance )
    {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

function custom_categories_load_widget()
{
    register_widget( 'custom_categories_widget' );
}
add_action( 'widgets_init', 'custom_categories_load_widget' );

//==============================================================================
/*
CUSTOM RECENT POSTS WIDGET

Functions to create a custom version of the recent posts widget in which the
list of available posts depends upon the access level for the current user.
*/
//==============================================================================

define('RECENT_POSTS_LIST_SIZE',10);
define('RECENT_POSTS_THUMBNAIL_SIZE',70);

class custom_posts_widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct(
          'custom_posts_widget',
          __('Recent Posts (Custom)', 'custom_posts_widget_domain'),
          array( 'description' => __( 'Recent posts with enhanced formatting', 'custom_posts_widget_domain' ), )
        );
    }

    public function widget( $args, $instance )
    {
        global $base_url, $base_dir, $link_version, $local_site_dir, $theme_url, $image_type_1;
        require_once("$base_dir/common_scripts/date_funct.php");
        $thumbnail_size = RECENT_POSTS_THUMBNAIL_SIZE;
        $title = 'Latest Posts';
        echo $args['before_widget'];
        if ( ! empty( $title ) ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $args2 = array( 'post_type' => 'post',
                         'numberposts' => -1,
                        'orderby' => 'date',
                        'order' => 'DESC' );

        if ((function_exists('recent_posts_category')) && (!empty(recent_posts_category()))) {
            $args2['category'] = get_cat_ID(recent_posts_category());
        }

        $postslist = get_posts( $args2 );
        $templist = array();
        foreach ($postslist as $post) {
            if (authenticate_post($post->post_name)) {
                $url = get_the_post_thumbnail_url($post);
                if ($url == false) {
                    $url = "$$theme_url/empty_thumbnail.jpg";
                }
                else {
                    $url = str_replace('.jpg','-150x150.jpg',$url);
                }
                $templist[$post->post_name] = array($post->post_title, $post->post_date, $url);
            }
        }
        if (sizeof($templist) == 0) {
            print("<div class=\"sidebar-post-link\">No recent posts currently available</div>\n");
        }
        else {
            $count = RECENT_POSTS_LIST_SIZE;
            foreach ($templist as $post_name => $info) {
                print("<table class=\"sidebar-post-link\"><tr>\n");
                print("<td class=\"sidebar-post-link-col1\" width=\"$thumbnail_size"."px\">\n");
                $image_url = get_modified_image_url($info[2],$image_type_1);
                print("<a href=\"$base_url/$post_name\"><img src=\"$image_url?v=$link_version\" width=\"$thumbnail_size"."px\" height=\"auto\" /></a><br />\n");
                print("</td>\n");
                print("<td class=\"sidebar-post-link-col2\">\n");
                print("<a href=\"$base_url/$post_name\">{$info[0]}</a><br />\n");
                print("<span class=\"sidebar-post-link-date\">Posted on ".title_date($info[1]."</span>"));
                print("</td>\n");
                print("</tr></table>\n");
                $count--;
                if ($count <= 0) {
                    break;
                }
            }
        }
        echo $args['after_widget'];
    }

    public function form( $instance )
    {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'custom_posts_widget_domain' );
        }
        // Functionality goes here
    }

    public function update( $new_instance, $old_instance )
    {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

function custom_posts_load_widget()
{
    register_widget( 'custom_posts_widget' );
}
add_action( 'widgets_init', 'custom_posts_load_widget' );

//==============================================================================
/*
BLOG HOME LINK WIDGET

Functions to create a widget providing a simple link to the blog home page.
*/

//==============================================================================

class blog_home_widget extends WP_Widget
{
    function __construct()
    {
        parent::__construct(
          'blog_home_widget',
          __('Blog Home Link', 'blog_home_widget_domain'),
          array( 'description' => __( 'Blog home link', 'blog_home_widget_domain' ), )
        );
    }

    public function widget( $args, $instance )
    {
        global $base_url;
        echo $args['before_widget'];
        echo "<a href=\"$base_url/blog\"><button>Blog Home</button></a>";
        echo $args['after_widget'];
    }

    public function form( $instance )
    {
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'blog_home_widget_domain' );
        }
        // Functionality goes here
    }

    public function update( $new_instance, $old_instance )
    {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

function blog_home_load_widget()
{
    register_widget( 'blog_home_widget' );
}
add_action( 'widgets_init', 'blog_home_load_widget' );

//================================================================================

// Auto update of plugins - enable online and disable on local server
if (is_file("/Config/linux_pathdefs.php")) {
    add_filter( 'auto_update_plugin', '__return_false' );
}
else {
    add_filter( 'auto_update_plugin', '__return_true' );
}

// Disable smart quotes
remove_filter('the_content', 'wptexturize');
remove_filter('the_title', 'wptexturize');
remove_filter('the_excerpt', 'wptexturize');

//================================================================================
endif;
//================================================================================
