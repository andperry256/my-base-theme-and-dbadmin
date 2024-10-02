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
    if (!isset($link_version))
    {
        $link_version = date('ym').'01';
    }
    $stylesheet_uri = get_stylesheet_uri();
    if (function_exists('url_to_static'))
    {
        $stylesheet_uri = url_to_static($stylesheet_uri);
    }
    wp_enqueue_style( 'my-base-theme-style', $stylesheet_uri, '', $link_version );
  
    wp_enqueue_script( 'my-base-theme-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );
  
    wp_enqueue_script( 'my-base-theme-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );
  
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
    {
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
    $category_list = array();
    $categories = get_categories();
    foreach ($categories as $cat)
    {
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
        if (($top_level_category) && (!empty($blog_home_description)) && ($user_access_level >= $access_level))
        {
            $category_list[$name] = array($hierarchy,$image_id);
        }
    }
    ksort($category_list);
    foreach ($category_list as $name => $info)
    {
        $category_url = "$base_url/category/{$info[0]}";
        print("<div class=\"category-icon-link\">");
        print("<a href=\"$category_url\">");
        $image_info = wp_get_attachment_image_src($info[1],array(480,320));
        if (function_exists('url_to_static'))
        {
            $image_url = url_to_static($image_info[0]);
        }
        else
        {
            $image_url = $image_info[0];
        }
        $image_url = str_replace('.jpg','-80px.jpg',$image_url);
        $image_url = str_replace('.png','-80px.png',$image_url);
        print("<img src=\"$image_url\" class=\"category-icon-link-image\" /><br />");
        print("$name</a></div>");
    }
}

//==============================================================================

function display_post_summary($header_level,$image_max_width,$image_max_height)
{
    global $wpdb;
    global $base_url;
    global $home_ip_addr;
    $id = get_the_ID();
    $row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID=$id");
    $post_date = substr($row->post_date,0,10);
    $post_date = date("d F Y", strtotime($post_date));
    print("<tr>\n");
    print("<td class=\"post-list-cell post-list-col1-cell\">");
    $featured_image = get_the_post_thumbnail();
    if (!empty($featured_image))
    {
        print("<div>$featured_image</div>\n");
    }
    print("</td>\n");
    print("<td class=\"post-list-cell post-list-col2-cell\">");
    echo "<h$header_level>"; the_title(); echo "</h$header_level>\n";
    print("<p>[Posted on: $post_date]</p>\n");
    the_content();
    if ($_SERVER['REMOTE_ADDR'] == $home_ip_addr)
    {
        $post = get_post($id); 
        $slug = $post->post_name;
        print("<a href=\"$base_url/post-to-social?slug=$slug\" target=\"_blank\">Post to Social</a>");
    }
    print("</td>\n");
    print("</tr>\n");
}

//==============================================================================

function display_post_content($header_level,$image_max_width,$image_max_height)
{
    global $wpdb;
    $id = get_the_ID();
    $row = $wpdb->get_row("SELECT * FROM wp_posts WHERE ID=$id");
    $post_date = substr($row->post_date,0,10);
    $post_date = date("d F Y", strtotime($post_date));
    echo "<h$header_level>"; the_title(); echo "</h$header_level>\n";
    print("<p>[Posted on: $post_date]</p>\n");
    $featured_image = get_the_post_thumbnail();
    $featured_image = adjust_featured_image_size($featured_image,$image_max_width,$image_max_height);
    if (!empty($featured_image))
    {
        $featured_image = adjust_featured_image_size($featured_image,$image_max_width,$image_max_height);
        print("<div class=\"right-aligned-image\">$featured_image</div>\n");
    }
    the_content();
}

//==============================================================================

function adjust_featured_image_size($image_spec,$max_width=200,$max_height=200)
{
    $matches = array();
    if (preg_match('/width="[0-9]+"/',$image_spec,$matches))
    {
        $width_spec = $matches[0];
        $tok = strtok($width_spec,'"');
        $width = (int)strtok('"');
    }
    if (preg_match('/height="[0-9]+"/',$image_spec,$matches))
    {
        $height_spec = $matches[0];
        $tok = strtok($height_spec,'"');
        $height = (int)strtok('"');
    }
    if ((isset($width_spec)) && (isset($height_spec)))
    {
        if ($width > $height)
        {
            // Landscape
            if ($width < $max_width)
            {
                $new_width = $width;
            }
            else
            {
                $new_width = $max_width;
            }
            $new_height = (int)($height * $new_width / $width);
        }
        else
        {
            // Portrait
            if ($height < $max_height)
            {
                $new_height = $height;
            }
            else
            {
                $new_height = $max_height;
            }
            $new_width = (int)($width * $new_height/ $height);
        }
        if ((isset($new_width)) && (isset($new_height)))
        {
            $new_width_spec = "width=\"$new_width\"";
            $new_height_spec = "height=\"$new_height\"";
            $image_spec = str_replace($width_spec,$new_width_spec,$image_spec);
            $image_spec = str_replace($height_spec,$new_height_spec,$image_spec);
        }
    }
    return $image_spec;
}

//==============================================================================

function navigation_links($option,$taxonomy='category')
{
    global $base_url;
    global $theme_url;
    if ($option == 'single')
    {
        $args = array (
            'prev_text' => '%title',
            'next_text' => '%title',
            'in_same_term' => true,
            'taxonomy' => $taxonomy,
        );
        $navigation = get_the_post_navigation($args);
    }
    elseif ($option == 'multi')
    {
        $args = array (
            'prev_text' => 'Older Posts',
            'next_text' => 'Newer Posts',
        );
        $navigation = get_the_posts_navigation($args);
    }
    else
    {
        // This should not occur
        return;
    }
    $nav_info = array ( 
        array('nav-previous','',''),
        array('nav-next','','')
    );
    $cat_list_array = array();
    foreach (array(0,1) as $i)
    {
        $pos1 = strpos($navigation,"<div class=\"{$nav_info[$i][0]}\">");
        if ($pos1 !== false)
        {
            $pos2 = strpos($navigation,'href="',$pos1) + 6;
            $pos3 = strpos($navigation,'"',$pos2);
            $nav_info[$i][1] = substr($navigation,$pos2,$pos3-$pos2); // Link
            $pos4 = strpos($navigation,'>',$pos3) + 1;
            $pos5 = strpos($navigation,'<',$pos4);
            $nav_info[$i][2] = substr($navigation,$pos4,$pos5-$pos4);  // Title
        }
    }

    /*
    Options for creating the navigation links with a grid or a table are currently
    defined. This is a temporary measure while CSS issues are resolved. To switch
    option, please alter the true/false directive in the next line.
    */
    if (true)
    {
        // Using table
        print("<table class=blog-nav-table><tr>");
        if (!empty($nav_info[0][1]))
        {
            print("<td class=blog-nav-col1><a href=\"{$nav_info[0][1]}\"><img align=\"absmiddle\" src=\"$theme_url/blog_nav_backward.svg\" class=\"blog-nav-button\"></a></td>");
            print("<td class=blog-nav-col2><a class=blog-nav-link href=\"{$nav_info[0][1]}\">{$nav_info[0][2]}</a></td>");
        }
        else
        {
            print("<td class=blog-nav-col1><img align=\"absmiddle\" src=\"$theme_url/blog_nav_backward_greyed.svg\" class=\"blog-nav-button\"></td>");
            print("<td class=blog-nav-col2></td>");
        }
        print("<td class=blog-nav-col3>");
        if ($taxonomy == 'category')
        {
            $cat_name_list = '';
            $categories = get_the_category();
            foreach ($categories as $category)
            {
                $cat_name_list .= "<a href=\"$base_url/category/{$category->slug}\" class=\"cat-nav-link\">{$category->name}</a> |";
                $cat_list_array[$category->name] = true;
            }
            $cat_name_list = rtrim($cat_name_list,'| ');
            print("$cat_name_list");
        }
        print("</td>");
        if (!empty($nav_info[1][1]))
        {
            print("<td class=blog-nav-col2><a class=blog-nav-link href=\"{$nav_info[1][1]}\">{$nav_info[1][2]}</a></td>");
            print("<td class=blog-nav-col1><a href=\"{$nav_info[1][1]}\"><img align=\"absmiddle\" src=\"$theme_url/blog_nav_forward.svg\" class=\"blog-nav-button\"></a></td>");
        }    
        else
        {
            print("<td class=blog-nav-col2></td>");
            print("<td class=blog-nav-col1><img align=\"absmiddle\" src=\"$theme_url/blog_nav_forward_greyed.svg\" class=\"blog-nav-button\"></td>");
        }
        print("</tr>\n");
        print("</table>\n");
    }
    else
    {
        // Using Grid
        print("<div class=\"blog-navigation\">");
        if (!empty($nav_info[0][1]))
        {
            print("<div class=left-arrow><a href=\"{$nav_info[0][1]}\"><img src=\"$theme_url/blog_nav_backward.svg\" class=\"blog-nav-button\"></a></div>");
            print("<div class=prev-link><a class=blog-nav-link href=\"{$nav_info[0][1]}\">{$nav_info[0][2]}</a></div>");
        }
        else
        {
            print("<div class=left-arrow><img src=\"$theme_url/blog_nav_backward_greyed.svg\" class=\"blog-nav-button\"></div>");
            print("<div class=prev-link></div>");
        }
        print("<div class=nav-type>");
        if ($taxonomy = 'category')
        {
            $cat_name_list = '';
            $categories = get_the_category();
            foreach ($categories as $category)
            {
                $cat_name_list .= $category->name.' | ';
                $cat_list_array[$category->name] = true;
            }
            $cat_name_list = rtrim($cat_name_list,'| ');
            print("<div style=\"display:block\">$cat_name_list</div>");
        }
        print("</div>");
        if (!empty($nav_info[1][1]))
        {
            print("<div class=next-link><a class=blog-nav-link href=\"{$nav_info[1][1]}\">{$nav_info[1][2]}</a></div>");
            print("<div class=right-arrow><a href=\"{$nav_info[1][1]}\"><img src=\"$theme_url/blog_nav_forward.svg\" class=\"blog-nav-button\"></a></div>");
        }    
        else
        {
            print("<div class=next-link></div>");
            print("<div class=right-arrow><img src=\"$theme_url/blog_nav_forward_greyed.svg\" class=\"blog-nav-button\"></div>");
        }
        print("</div>");
    }
}

//==============================================================================

function pagination_links($page_count)
{
    if (function_exists('wp_paginate'))
    {
        if ($page_count == 0)
        {
            wp_paginate();
        }
        else
        {
            $pos = strpos($_SERVER['REQUEST_URI'],'/page/');
            if ($pos === false)
            {
                $page_no = 1;
            }
            else
            {
                $page_no = strtok(substr($_SERVER['REQUEST_URI'],$pos+6),'/?');
            }
            wp_paginate("page=$page_no&pages=$page_count");
        }
    }
    else
    {
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
    if ($row = mysqli_fetch_assoc($query_result))
    {
        print("<tr>\n");
        if ((isset($_GET['postname'])) && ($_GET['postname'] == $row['post_name']))
        {
            // The particular post has been selected for display
            print("<td class=\"post-archive-table-cell\" colspan=\"3\">\n");
            print("<a name=\"{$row['post_name']}\"><h1><span style=\"font-size:0.9em\">{$row['post_title']}</span></h1></a>\n");
            $query_result2 = mysqli_query($db,"SELECT * FROM wp_users WHERE ID={$row['post_author']}");
            if ($row2 = mysqli_fetch_assoc($query_result2))
            {
                $date = str_replace(' ','&nbsp;',title_date(substr($row['post_date'],0,10)));
                $post_url = "$base_url/{$row['post_name']}";
                $author_url = "$base_url/author/{$row2['user_nicename']}";
                print("<p>Posted on <a href=\"$post_url\">$date</a> by <a href=\"$author_url\">${row2['display_name']}</a></p>");
            }
            print("{$row['post_content']}\n");
            print("<p>[<a href=\"./#{$row['post_name']}\">Close</a>]</p>\n");
            print("</td>\n");
            $post = get_page_by_path($row['post_name'],OBJECT,'post');
            $post_categories = wp_get_post_categories($post->ID);
            $hierarchy = get_category_parents($post_categories[0], false, '/', true);
            if (is_string($hierarchy))
            {
                $selected_category = strtok($hierarchy,'/');
            }
        }
        else
        {
            // Create links for the post and the associated categories
            print("<td class=\"post-archive-table-cell\"><a name=\"{$row['post_name']}\"><a href=\"./?postname={$row['post_name']}#{$row['post_name']}\">{$row['post_title']}</a></a></td>\n");
            $date = str_replace(' ','&nbsp;',title_date(substr($row['post_date'],0,10)));
            print("<td class=\"post-archive-table-cell post-archive-date_column\">$date</td>\n");
            print("<td class=\"post-archive-table-cell post-archive-category-column\">");
            $post_categories = wp_get_post_categories($row['ID']);
            $count = 0;
            foreach($post_categories as $id)
            {
                if ($count > 0)
                {
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
        if (is_category())
        {
            $category = get_queried_object();
            $own_id = $category->term_id;
            $hierarchy = get_category_parents($own_id, false, '/', true);
            $top_level_category = strtok($hierarchy,'/');
            $category_list = array();
            $categories = get_categories();
      
            // Create array of categories that belong to the current top level category
            foreach ($categories as $cat)
            {
                $id = (int)$cat->term_id;
                $name = $cat->cat_name;
                $cat_access_level = get_term_meta($id,'access_level',true);
                $is_parent = get_term_meta($id,'is_parent',true);
                $hierarchy = get_category_parents($id, false, '/', true);
                if (session_var_is_set(SV_ACCESS_LEVEL))
                {
                    $user_access_level = get_session_var(SV_ACCESS_LEVEL);
                }
                else
                {
                    $user_access_level = 0;
                }
                if (($user_access_level >= $cat_access_level) && (strtok($hierarchy,'/') == $top_level_category))
                {
                    $category_list[$hierarchy] = $name;
                }
            }
            ksort($category_list);
      
            /*
            Output the sub-category links for the current top level category. Check
            that the array count is greater than 1 as a top level category with no
            sub-categories will still create a single array element.
            */
            if (count($category_list) > 1)
            {
                $first_item = true;
                foreach ($category_list as $path => $name)
                {
                    if ($first_item)
                    {
                        $title =" Sub-categories of $name";
                        echo $args['before_widget'];
                        if ( ! empty( $title ) )
                        {
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
        if ( isset( $instance[ 'title' ] ) )
        {
            $title = $instance[ 'title' ];
        }
        else
        {
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
        global $base_url, $base_dir, $link_version, $local_site_dir, $theme_url;
        require_once("$base_dir/common_scripts/date_funct.php");
        $thumbnail_size = RECENT_POSTS_THUMBNAIL_SIZE;
        $title = 'Latest Posts';
        echo $args['before_widget'];
        if ( ! empty( $title ) )
        {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        $args2 = array( 'post_type' => 'post',
                         'numberposts' => -1,
                        'orderby' => 'date',
                        'order' => 'DESC' );

        if ((function_exists('recent_posts_category')) && (!empty(recent_posts_category())))
        {
            $args2['category'] = get_cat_ID(recent_posts_category());
        }

        $postslist = get_posts( $args2 );
        $templist = array();
        foreach ($postslist as $post)
        {
            if (authenticate_post($post->post_name))
            {
                $url = get_the_post_thumbnail_url($post);
                if ($url == false)
                {
                    $url = "$$theme_url/empty_thumbnail.jpg";
                }
                else
                {
                    $url = str_replace('.jpg','-150x150.jpg',$url);
                }
                $templist[$post->post_name] = array($post->post_title, $post->post_date, $url);
            }
        }
        if (sizeof($templist) == 0)
        {
            print("<div class=\"sidebar-post-link\">No recent posts currently available</div>\n");
        }
        else
        {
            $count = RECENT_POSTS_LIST_SIZE;
            foreach ($templist as $post_name => $info)
            {
                print("<table class=\"sidebar-post-link\"><tr>\n");
                print("<td class=\"sidebar-post-link-col1\" width=\"$thumbnail_size"."px\">\n");
                if (function_exists('url_to_static'))
                {
                    $image_url = url_to_static($info[2]);
                }
                else
                {
                    $image_url = $info[2];
                }
                print("<a href=\"$base_url/$post_name\"><img src=\"$image_url?v=$link_version\" width=\"$thumbnail_size"."px\" height=\"auto\" /></a><br />\n");
                print("</td>\n");
                print("<td class=\"sidebar-post-link-col2\">\n");
                print("<a href=\"$base_url/$post_name\">{$info[0]}</a><br />\n");
                print("<span class=\"sidebar-post-link-date\">Posted on ".title_date($info[1]."</span>"));
                print("</td>\n");
                print("</tr></table>\n");
                $count--;
                if ($count <= 0)
                {
                    break;
                }
            }
        }
        echo $args['after_widget'];
    }
  
    public function form( $instance )
    {
        if ( isset( $instance[ 'title' ] ) )
        {
            $title = $instance[ 'title' ];
        }
        else
        {
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
        if ( isset( $instance[ 'title' ] ) )
        {
            $title = $instance[ 'title' ];
        }
        else
        {
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
endif;
//================================================================================
