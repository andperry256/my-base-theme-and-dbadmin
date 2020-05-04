<?php
	/**
	 * The header for our theme
	 *
	 * This is the template that displays all of the <head> section and everything up until <div id="content">
	 *
	 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
	 *
	 * @package My_Base_Theme
	 */
	 global $link_version;
	 $link_version = date('ym').'01';
	 if ((isset($last_preset_link_version)) && ($link_version < $last_preset_link_version))
	 {
		 $link_version = $last_preset_link_version;
	 }
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<?php
	wp_head();
	$themes_dir = get_theme_root();
	require("$themes_dir/my-base-theme/shared/setup_params.php");
	output_meta_data();
?>
</head>

<body <?php body_class(); ?>>

<div id="super-container">
<header id="masthead" class="site-header" role="banner">
	<div class="site-branding">
		<?php
			if (is_file($desktop_header_image_path))
			{
				echo("<div class=\"desktop-only-item\"><img src=\"$desktop_header_image_url?v=$link_version\" /></div>");
			}
			if (is_file($intermediate_header_image_path))
			{
				echo("<div class=\"intermediate-width-item\"><img src=\"$intermediate_header_image_url?v=$link_version\" /></div>");
			}
			if (is_file($mobile_header_image_path))
			{
				echo("<div class=\"mobile-only-item\"><img src=\"$mobile_header_image_url?v=$link_version\" /></div>");
			}
		?>
		<?php if ( true ) : ?>
			<!-- Force title not to display in this implementation -->
		<?php elseif ( is_front_page() && is_home() ) : ?>
			<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
		<?php else : ?>
			<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
		<?php
			endif;

			$description = get_bloginfo( 'description', 'display' );
			if ( $description || is_customize_preview() ) : ?>
				<p class="site-description"><?php echo $description; /* WPCS: xss ok. */
		?>
		</p>
		<?php endif; ?>
	</div><!-- .site-branding -->

	<?php
		if ((is_file("$CustomThemePath/style-light.css")) && ($_SESSION['theme_mode'] == 'light'))
		{
			print("<link rel='stylesheet' id='-home-styles-css'  href='$CustomThemeURL/style-light.css?v=$link_version' type='text/css' media='all' />");
		}
		elseif ((is_file("$CustomThemePath/style-dark.css")) && ($_SESSION['theme_mode'] == 'dark'))
		{
			print("<link rel='stylesheet' id='-home-styles-css'  href='$CustomThemeURL/style-dark.css?v=$link_version' type='text/css' media='all' />");
		}
	?>

	<?php if ($menu_id != 'none'): ?>
	<nav id="site-navigation" class="main-navigation" role="navigation">
		<div id="main-menu">
		<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( $menu_description, 'my-base-theme' ); ?></button>
		<?php wp_nav_menu( array( 'menu' => $menu_id,  'menu_class' => 'main-navigation', 'theme_location' => 'menu-1', 'menu_id' => 'primary-menu' ) ); ?>
		</div>
	</nav><!-- #site-navigation -->
	<?php endif; ?>
</header><!-- #masthead -->
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'my-base-theme' ); ?></a>
	<div id="content" class="site-content">
