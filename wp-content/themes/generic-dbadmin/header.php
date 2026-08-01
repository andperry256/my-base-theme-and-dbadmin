<?php
//==============================================================================
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package My_Base_Theme
 */
//==============================================================================

if (is_front_page()) {
    // Auto-load main dashboard
    header("Location: ./dbadmin/db-main");
    exit;
}

global $link_version;

require("{$_SERVER['DOCUMENT_ROOT']}/path_defs.php");
$link_version = get_last_preset_link_version();

$db = db_connect(2);
check_login_status($db);

//==============================================================================
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> >
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <?php
        //==============================================================================
        wp_head();
        $themes_dir = get_theme_root();
        require("$themes_dir/my-base-theme/setup_params.php");
        check_local_home_favicon();
        output_meta_data();
	    create_cache_reload_link();
        //==============================================================================
        ?>
    </head>
    <!-- *** SITE CHECK *** DO NOT DELETE THIS LINE *** -->
    <body <?php body_class(); ?>>
        <div id="page" class="site" >
            <a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'my-base-theme' ); ?></a>
            <header id="masthead" class="site-header" role="banner">
                <div class="site-branding">
                  <?php
                    //==============================================================================
                    if ((isset($header_image_path)) && (is_file($header_image_path))) {
                        echo("<img src=\"$header_image_url\" />");
                    }
                    $description = get_bloginfo( 'description', 'display' );
                    if ( $description || is_customize_preview() ) :
                    //==============================================================================
                    ?>
                        <p class="site-description"><?php echo $description; /* WPCS: xss ok. */?> </p>
                  <?php endif; ?>
                </div><!-- .site-branding -->
            </header><!-- #masthead -->

            <nav id="site-navigation" class="main-navigation" role="navigation">
                <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( $menu_description, 'my-base-theme' ); ?></button>
                <?php wp_nav_menu( [ 'menu' => $menu_id,  'menu_class' => 'main-navigation', 'theme_location' => 'menu-1', 'menu_id' => 'primary-menu' ] ); ?>
            </nav><!-- #site-navigation -->

            <div id="content" class="site-content">
