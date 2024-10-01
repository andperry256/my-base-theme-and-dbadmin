<?php
/**
 * The template for displaying archive pages
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package My_Base_Theme
 */

get_header();
?>

<div id="primary" class="content-area">
    <?php output_header_links(); ?>
    <main id="main-no-background" class="site-main" role="main">
        <header class="page-header">
            <?php
            the_archive_title( '<h1 class="page-title">', '</h1>' );
            the_archive_description( '<div class="archive-description">', '</div>' );
            ?>
        </header><!-- .page-header -->

        <?php
        // Run the WordPress loop
        if ( have_posts() )
        {
            while ( have_posts() )
            {
                the_post();
                print("<div class=\"article-container\">\n");
                display_post_content(2,200,200);
                print("</div>\n");
            }
            the_posts_navigation();
        }
        else
        {
            get_template_part( 'template-parts/content', 'none' );
        }
        wp_reset_postdata();
        ?>
    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_sidebar();
get_footer();
?>