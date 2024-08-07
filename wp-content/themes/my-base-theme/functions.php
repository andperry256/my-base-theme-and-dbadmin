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

//================================================================================
endif;
//================================================================================
