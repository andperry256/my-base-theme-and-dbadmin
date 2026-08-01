<?php
//==============================================================================
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package My_Base_Theme
 */
//==============================================================================
?>

</div><!-- #content -->

<footer id="colophon" class="site-footer" role="contentinfo">
    <div class="site-info">
        <?php
        $themes_dir = get_theme_root();
        require("$themes_dir/site_path_defs.php");
        if (is_file("$base_dir/wp-custom-scripts/footer.php")) {
            include("$base_dir/wp-custom-scripts/footer.php");
        }
        ?>
    </div><!-- .site-info -->
</footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
