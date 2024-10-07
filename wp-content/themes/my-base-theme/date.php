<?php
//==============================================================================
//
//### Temporary version - copy of archive.php (extra functionality to be added)
//
//==============================================================================
/**
 * The template for displaying archive pages
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
        //==============================================================================

        // Determine the user access level
        if (session_var_is_set(SV_ACCESS_LEVEL))
        {
            $user_access_level = get_session_var(SV_ACCESS_LEVEL);
        }
        else
        {
            $user_access_level = DEFAULT_ACCESS_LEVEL;
        }
        

        navigation_links('multi','');
        // Set up the parameters for the main loop query
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array ( 'paged' => $paged,
                        'posts_per_page' => POSTS_PER_ARCHIVE_PAGE_STANDARD,
                        'meta_key' => 'access_level',
                        'meta_value' => $user_access_level,
                        'meta_compare' => '<=',
                    );

        // Run the WordPress loop
        $local_query = new WP_Query($args);
        if ( $local_query->have_posts() )
        {
            while ( $local_query->have_posts() )
            {
                $local_query->the_post();
                display_post_summary(2,200,200);
                print("<div class=\"post-list-spacer\">&nbsp;</div>\n");
            }
            navigation_links('multi','');
        }
        else
        {
            get_template_part( 'template-parts/content', 'none' );
        }
        wp_reset_postdata();
        update_session_var('uri_fragment_used',true);

        //==============================================================================
        ?>
    </main><!-- #main -->
</div><!-- #primary -->
<?php
//==============================================================================

get_sidebar();
get_footer();

//==============================================================================
?>
