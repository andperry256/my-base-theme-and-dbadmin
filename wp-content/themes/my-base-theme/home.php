<?php
//==============================================================================
/**
 * The template for displaying the main blog feed
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package My_Base_Theme
 */
$menu_id = 'blog';
$use_default_blog_image = true;
get_header();

//==============================================================================
?>
<div id="primary" class="content-area">
    <main id="main" class="blog-main" role="main">
        <?php
        $medium_thumbnail_image_type = $thumbnail_image_types['medium'] ?? $default_thumbnail_image_types['medium'];
        $medium_thumbnail_image_width = $thumbnail_image_widths['medium'] ?? $default_thumbnail_image_widths['medium'];
        print("<style>\n");
        print("@media screen and (min-width: 45.01em) {\n");
        print(".post-list-item { grid-template-columns: {$medium_thumbnail_image_width}px 1fr; }\n");
        print("}\n");
        print("</style>\n");

        // Determine the user access level
        if (session_var_is_set(SV_ACCESS_LEVEL)) {
            $user_access_level = get_session_var(SV_ACCESS_LEVEL);
        }
        else {
            $user_access_level = DEFAULT_ACCESS_LEVEL;
        }

        navigation_links('multi');

        // Set up the parameters for the main loop query
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = $_GET['paginate'] ?? POSTS_PER_ARCHIVE_PAGE_STANDARD;
        $args = [ 'paged' => $paged,
                  'posts_per_page' => $posts_per_page,
                  'meta_key' => 'access_level',
                  'meta_value' => $user_access_level,
                  'meta_compare' => '<=',
              ];

        // Run the WordPress loop
        $local_query = new WP_Query($args);
        if ( $local_query->have_posts() ) {
            while ( $local_query->have_posts() ) {
                $local_query->the_post();
                if ($posts_per_page > POSTS_PER_ARCHIVE_PAGE_STANDARD) {
                    display_short_post_summary(128,128);
                }
                else {
                    display_post_summary(2,200,200);
                }
                print("<div class=\"post-list-spacer\">&nbsp;</div>\n");
            }
            navigation_links('multi');
        }
        else {
            get_template_part( 'template-parts/content', 'none' );
        }
        wp_reset_postdata();
        update_session_var('uri_fragment_used',true);

        ?>
    </main><!-- #main -->
</div><!-- #primary -->
<?php
//==============================================================================

get_sidebar();
get_footer();

//==============================================================================
