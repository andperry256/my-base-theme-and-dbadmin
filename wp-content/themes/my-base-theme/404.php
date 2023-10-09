<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package My_Base_Theme
 */

get_header();
$page = get_page_by_path("404-not-found");
$content = $page->post_content;
$content = apply_filters( 'the_content', $content );
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title">Page not Found</h1>
            </header><!-- .page-header -->

            <div class="page-content">
                <?php echo $content; ?>
            </div><!-- .page-content -->
        </section><!-- .error-404 -->

    </main><!-- #main -->
</div><!-- #primary -->

<?php
get_footer();
