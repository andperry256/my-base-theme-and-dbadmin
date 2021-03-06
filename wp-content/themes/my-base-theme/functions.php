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

if ( ! function_exists( 'my_base_theme_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
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
endif;
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
	$GLOBALS['content_width'] = apply_filters( 'my_base_theme_content_width', 640 );
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
	wp_enqueue_style( 'my-base-theme-style', get_stylesheet_uri(), '', $link_version );

	wp_enqueue_script( 'my-base-theme-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20151215', true );

	wp_enqueue_script( 'my-base-theme-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20151215', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'my_base_theme_scripts' );

//================================================================================
/*
 * This function is used to output the main title header of the current page.
 * It is dependent upon the installation of the 'Secondary Title' plugin.
 */
function output_page_header()
{
	if (function_exists('get_secondary_title'))
		$secondary_title = get_secondary_title();
	else
		$secondary_title = '';
	if ($secondary_title == '#')
	{
		// No action
	}
	elseif (!empty($secondary_title))
		echo("<h1>$secondary_title</h1>\n");
	else
		the_title( '<h1 class="entry-title">', '</h1>' );
}

//================================================================================
/*
 * This function is used extract and output a given portion of the page content
 * and is for use when the content section of a page is being built using a custom
 * PHP script. A numeric part number is passed as a paramaeter and this indicates
 * that the text is to be extracted from between the following tags in the
 * WordPress page content:-
 *
 * [part<n>]
 * [/part<n>]
 *
 * where <n> is the part number. This allows multiple portions to be extracted
 * from the pages content for use at different points in the page.
 */
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
			return "**** Unable to retrieve part $part_no from page ****";
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
 * This function is used to generate meta tag data in the page header.
 * A number of global variables are referenced by the function to set up the tags
 * as required. These will have been set up previously by running any 'metadata.php'
 * scripts in the page hierachy within the custom scripts folder.
 *
 * N.B. To cancel a meta description from an ancestor page without creating a new one,
 * the meta description must be re-defined for the page as an empty string.
 */
function output_meta_data()
{
	global $meta_description;
	global $meta_robots_noindex;
	global $meta_robots_nofollow;
	global $meta_refresh_interval;
	global $meta_refresh_url;
	global $meta_refresh_url_pars;
	global $Location;

	if ((isset($Location)) && ($Location == 'local'))
		print("<meta name=\"robots\" content=\"noindex,nofollow\">\n");
	else
	{
		if ((isset($meta_description)) && (!empty($meta_description)))
			print("<meta name=\"description\" content=\"$meta_description\">\n");
		$robots_content = '';
		if ((isset($meta_robots_noindex)) && ($meta_robots_noindex))
			$robots_content = 'noindex';
		if ((isset($meta_robots_nofollow)) && ($meta_robots_nofollow))
		{
			if (!empty($robots_content))
				$robots_content .= ',';
			$robots_content .= 'nofollow';
		}
			if (!empty($robots_content))
				print("<meta name=\"robots\" content=\"$robots_content\">\n");
	}

	if ((isset($meta_refresh_interval)) && (isset($meta_refresh_url)) && (!isset($_GET['norefresh'])))
	{
		if (!isset($meta_refresh_url_pars))
			$meta_refresh_url_pars = '';
		print("<meta http-equiv=\"refresh\" content=\"$meta_refresh_interval;URL='$meta_refresh_url/$meta_refresh_url_pars'\" />\n");
	}
}

//================================================================================

function output_stylesheet_link($path,$sub_path)
{
	global $link_version, $BaseDir, $BaseURL;
	$stylesheet_id = str_replace('/','-',$sub_path);
	$dir_path = str_replace($BaseURL,$BaseDir,$path);
	print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-css'  href='$path/$sub_path/styles.css?v=$link_version' type='text/css' media='all' />\n");
	if (($_SESSION['theme_mode'] == 'light') && (is_file("$dir_path/$sub_path/styles-light.css")))
	{
		print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-light-css'  href='$path/$sub_path/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
	}
	elseif (($_SESSION['theme_mode'] == 'dark') && (is_file("$dir_path/$sub_path/styles-dark.css")))
	{
		print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-dark-css'  href='$path/$sub_path/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
	}
}

//================================================================================

function define_supercategory_taxonomy()
{
	global $enable_supercategories;

	if ((!isset($enable_supercategories)) || ($enable_supercategories !== false))
	{
		$labels = array (
			'name' => 'Supercategories',
			'singluar_name' => 'Supercategory',
			'add_new_item' => 'Add New Supercategory',
		);

		$args = array (
			'labels' => $labels,
			'query_var' => true,
			'rewrite' => true,
		);

		register_taxonomy( 'supercategory', 'post', $args );
	}
}
add_action( 'init', 'define_supercategory_taxonomy' );

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
require get_template_directory() . '/shared/functions.php';
add_action( 'init', 'run_session', 1);

//================================================================================

function set_default_header_image_paths()
{
	$image_file_exts = array( 'png', 'jpg' );
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
		if (is_file("$current_theme_dir/header_image.$ext"))
		{
			$desktop_header_image_path = "$current_theme_dir/header_image.$ext";
			$desktop_header_image_url = "$current_theme_url/header_image.$ext";
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
