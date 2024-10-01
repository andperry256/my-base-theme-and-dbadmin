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
        <?php
        $category = get_queried_object();
        $own_id = $category->term_id;
        $own_slug = $category->slug;
        $access_level = get_category_access_level($own_id);
        $hierarchy = get_category_parents($own_id, false, '/', true);
        $image_id = get_term_meta($own_id,'featured_image',true);
        if (session_var_is_set(SV_ACCESS_LEVEL))
        {
            $user_access_level = get_session_var(SV_ACCESS_LEVEL);
        }
        else
        {
            $user_access_level = 0;
        }

        if ($user_access_level < $access_level)
        {
            print("<p>Authentication Failure</p>");
        }
        else
        {
            navigation_links('multi');
            print("<table class=\"post-list-table\">\n");
            print("<header class=\"page-header\">\n");
            $page_no = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $blog_home_description = get_term_meta($own_id,'blog_home_description',true);
            if (($page_no == 1) && (!empty($blog_home_description)))
            {
                // Output relevant section of blog home page
                print("<tr>\n");
                print("<td class=\"post-list-cell post-list-col1-cell\">");
                if (!empty($image_id))
                {
                    $image_info = wp_get_attachment_image_src($image_id,array(480,320));
                    if (function_exists('url_to_static'))
                    {
                        $image_url = url_to_static($image_info[0]);
                    }
                    else
                    {
                        $image_url = $image_info[0];
                    }
                    print("<br /><img src=\"$image_url\"/>\n");
                }
                print("</td>\n");
                print("<td class=\"post-list-cell post-list-col2-cell\">");
                the_archive_title( '<h1 class="page-title">', '</h1>' );
                print("$blog_home_description");
                print("</td>\n");
                print("</tr>\n");
                print("<tr><td class=\"post-list-spacer-cell\" colspan=\"2\"> </td></tr>\n");
            }
            else
            {
                // Output link to parent category if applicable
                the_archive_title( '<h1 class="page-title">', '</h1>' );
                $parent_slug = '';
                $tok = strtok($hierarchy,'/');
                while ($tok !== false)
                {
                    if ($tok == $own_slug)
                    {
                        break;
                    }
                    $parent_slug = $tok;
                    $tok = strtok('/');
                }
                if (!empty($parent_slug))
                {
                    $parent_cat = get_category_by_slug($parent_slug);
                    $parent_name = $parent_cat->name;
                    print("<p><a href=\"$base_url/category/$parent_slug\">Parent Category - $parent_name</a></p>\n");
                }
            }
            print("</header>\n");

            // Set up the parameters for the main loop query
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array ( 'cat' => $own_id,
                            'paged' => $paged,
                            array('key' => 'access_level',
                                    'value' => $user_access_level,
                                    'compare' => '<=',
                                    'type' => 'NUMERIC')
                        );

            // Run the WordPress loop
            $local_query = new WP_Query($args);
            if ( $local_query->have_posts() )
            {
                while ( $local_query->have_posts() )
                {
                    print("<tr><td class=\"post-list-spacer-cell\" colspan=\"2\"></td></tr>\n");
                    $local_query->the_post();
                    display_post_summary(2,200,200);
                    print("<tr><td class=\"post-list-spacer-cell\" colspan=\"2\"> </td></tr>\n");
                }
                navigation_links('multi');
            }
            else
            {
                get_template_part( 'template-parts/content', 'none' );
            }
            wp_reset_postdata();
            print("</table>\n");
            update_session_var('uri_fragment_used',true);
        }
        ?>
    </main><!-- #main -->
</div><!-- #primary -->
<?php
get_sidebar();
get_footer();
?>
