<?php
//================================================================================

global $base_theme_dir;
global $base_theme_url;
global $my_base_theme_mode;
global $site_path_defs_path;
global $meta_description;
global $meta_robots_noindex;
global $meta_robots_nofollow;
global $meta_refresh_interval;
global $meta_refresh_url;
global $meta_refresh_url_pars;
global $desktop_header_image_path, $desktop_header_image_url;
global $intermediate_header_image_path, $intermediate_header_image_url;
global $mobile_header_image_path, $mobile_header_image_url;
global $private_scripts_dir;
global $custom_footer_script;
global $wpdb;
global $light_theme_css_list, $light_theme_newest_timestamp, $light_theme_filesize_total;
global $dark_theme_css_list, $dark_theme_newest_timestamp, $dark_theme_filesize_total;

$themes_dir = get_theme_root();
$site_path_defs_path = "$themes_dir/site_path_defs.php";
$base_theme_dir = get_template_directory();
$base_theme_url = get_template_directory_uri();

//================================================================================
// Set up various parameters from data stored in the structure of the
// 'wp-custom-scripts' directory.
//================================================================================

$my_base_theme_mode = 'full';
require($site_path_defs_path);
if (!isset($custom_scripts_path)) {
    $custom_scripts_path = "$base_dir/wp-custom-scripts";
}
if (!isset($custom_scripts_url)) {
    $custom_scripts_url = "$base_url/wp-custom-scripts";
}
$custom_pages_url = "$custom_scripts_url/pages";
$custom_pages_path = "$custom_scripts_path/pages";
$custom_categories_path = "$custom_scripts_path/categories";
$custom_categories_url = "$custom_scripts_url/categories";
require("$custom_pages_path/select_menu.php");
$page_uri = get_page_uri(get_the_ID());
if (is_file("$custom_scripts_path/functions.php")) {
    include("$custom_scripts_path/functions.php");
}
set_default_header_image_paths();

if (is_file("$custom_scripts_path/footer.php")) {
    $custom_footer_script = "$custom_scripts_path/footer.php";
}
else {
    $custom_footer_script = '';
}
/*
The following constants are set to default values if site related values were
not set when the path_defs.php script was invoked.
*/
if (!defined('SV_USER')) {
    define('SV_USER', 'user');
}
if (!defined('SV_ACCESS_LEVEL')) {
    define('SV_ACCESS_LEVEL', 'access_level');
}
if (!defined('DEFAULT_ACCESS_LEVEL')) {
    define('DEFAULT_ACCESS_LEVEL', 0);
}
if (!defined('SUPER_ACCESS_LEVEL')) {
    define('SUPER_ACCESS_LEVEL', 99);
}
if (!defined('POSTS_PER_ARCHIVE_PAGE_STANDARD')) {
    define('POSTS_PER_ARCHIVE_PAGE_STANDARD', 10);
}
if (!defined('POSTS_PER_ARCHIVE_PAGE_LONG')) {
    define('POSTS_PER_ARCHIVE_PAGE_LONG', 50);
}

//================================================================================

if (is_page()) {
    $minimum_access_level = '';
    if (is_file("$custom_pages_path/init.php")) {
        // Run any custom initialisation sequence
        include("$custom_pages_path/init.php");
    }

    // Move down the page hierarchy to the given address, matching various items along the way.
    $hierarchy = explode('/',ltrim($page_uri,'/'));
    $uri_sub_path = '';
    foreach ($hierarchy as $element) {
        $uri_sub_path .= "/$element";
        $uri_sub_path = ltrim($uri_sub_path,'/');
        set_header_image_paths($uri_sub_path,'page');
        if (is_file("$custom_pages_path/$uri_sub_path/footer.php")) {
            // Select custom footer script
            $custom_footer_script = "$custom_pages_path/$uri_sub_path/footer.php";
        }
        if (is_file("$custom_pages_path/$uri_sub_path/select_menu.php")) {
            // Select menu
            include("$custom_pages_path/$uri_sub_path/select_menu.php");
        }
        if (is_file("$custom_pages_path/$uri_sub_path/inline-styles.css")) {
            // Add inline stylesheet
            include_inline_stylesheet("$custom_pages_path/$uri_sub_path/inline-styles.css");
        }
        if (is_file("$custom_pages_path/$uri_sub_path/metadata.php")) {
            // Include any meta tag variables
            include("$custom_pages_path/$uri_sub_path/metadata.php");
        }
        if (is_file("$custom_pages_path/$uri_sub_path/init.php")) {
            // Run any custom initialisation sequence
            include("$custom_pages_path/$uri_sub_path/init.php");
        }
    }
}

//================================================================================

elseif (is_single()) {
    if (is_file("$custom_categories_path/select_menu.php")) {
        include("$custom_categories_path/select_menu.php");
    }
    $categories = get_the_category();
    foreach ($categories as $key => $dummy) {
        $id = $categories[$key]->term_id;
        $slug =  $categories[$key]->slug;
        $hierarchy = get_category_parents($id, false, '/', true);

        /*
        Move down the category hierarchy to the given address, matching various
        items along the way.
        CAUTION - If the post is allocated to multiple categories and there are
        items to be processed in more than one line of ancestry, then the results
        may be unpredictable as they could depend upon the order in which the
        categories are processed.
        */
        $hierarchy_elements = explode('/',ltrim($hierarchy,'/'));
        $uri_sub_path = '';
        foreach ($hierarchy_elements as $element) {
            $uri_sub_path .= "/$element";
            $uri_sub_path = ltrim($uri_sub_path,'/');
            set_header_image_paths($element,'category');
            if (is_file("$custom_pages_path/$element/footer.php")) {
                // Select custom footer script
                $custom_footer_script = "$custom_categories_path/$element/footer.php";
            }
            if (is_file("$custom_categories_path/$element/select_menu.php")) {
                // Select menu
                include("$custom_categories_path/$element/select_menu.php");
            }
            if (is_file("$custom_categories_path/$element/inline-styles.css")) {
                // Add inline stylesheet
                include_inline_stylesheet("$custom_categories_path/$element/inline-styles.css");
            }
        }
    }
}

//================================================================================

elseif (is_category()) {
    $category = get_queried_object();
    $id = $category->term_id;
    $hierarchy = get_category_parents($id, false, '/', true);
    $hierarchy_elements = explode('/',ltrim($hierarchy,'/'));
    $uri_sub_path = '';
    foreach ($hierarchy_elements as $element) {
        $uri_sub_path .= "/$element";
        $uri_sub_path = ltrim($uri_sub_path,'/');
        set_header_image_paths($element,'category');
        if (is_file("$custom_pages_path/$element/footer.php")) {
            // Select custom footer script
            $custom_footer_script = "$custom_categories_path/$element/footer.php";
        }
        if (is_file("$custom_categories_path/$element/select_menu.php")) {
            // Select menu
            include("$custom_categories_path/$element/select_menu.php");
        }
        if (is_file("$custom_categories_path/$element/inline-styles.css")) {
            // Add inline stylesheet
            include_inline_stylesheet("$custom_categories_path/$element/inline-styles.css");
        }
    }
}

//================================================================================

elseif (is_archive()) {
    set_header_image_paths('','archive');
}

//================================================================================
