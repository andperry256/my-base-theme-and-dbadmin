<?php
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

        // Determine the required date range from the calling URL
        $year = get_query_var('year');
        $monthnum = get_query_var('monthnum');
        $month_par = sprintf("%02d",$monthnum);
        $month_name = $month[$month_par];
        $day = get_query_var('day');
        $day_par = sprintf("%02d",$day);
        if (empty($month_name))
        {
            print("<h1>Year: $year</h1>\n");
            $sub_url = "$year";
            $date_par = $year;
        }
        elseif ($day == 0)
        {
            print("<h1>Month: $month_name $year</h1>\n");
            $sub_url = "$year/$month_par";
            $date_par = $year.'-'.$month_par;
        }
        else
        {
            print("<h1>Date: $day_par $month_name $year</h1>\n");
            $sub_url = "$year/$month_par/$day_par";
            $date_par = $year.'-'.$month_par.'-'.$day_par;
        }

        // Determine the user access level
        if (session_var_is_set(SV_ACCESS_LEVEL))
        {
            $user_access_level = get_session_var(SV_ACCESS_LEVEL);
        }
        else
        {
            $user_access_level = DEFAULT_ACCESS_LEVEL;
        }

        // Set up the parameters for the main loop query
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array ( 'year' => $year,
                        'paged' => $paged,
                        'posts_per_page' => POSTS_PER_ARCHIVE_PAGE,
                        'meta_key' => 'access_level',
                        'meta_value' => $user_access_level,
                        'meta_compare' => '<=',
                        );
        if (!empty($month_name))
        {
            $args['monthnum'] = $monthnum;
            if ($day != 0)
            {
                $args['day'] = $day;
            }
        }

        // Handle the sort order for the posts
        if (isset($_GET['orderby']))
        {
            update_session_var('orderby',$_GET['orderby']);
        }
        elseif (!session_var_is_set('orderby'))
        {
            update_session_var('orderby','date');
        }
        if (get_session_var('orderby') == 'title')
        {
            print("<a href=\"$base_url/$sub_url\?orderby=date\">Sort by Date</a><br />&nbsp;\n");
            $args['orderby'] = 'name';
            $args['order'] = 'ASC';
        }
        else
        {
            print("<a href=\"$base_url/$sub_url\?orderby=title\">Sort by Title</a><br />&nbsp;\n");
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        // Calculate the page count
        $local_query = new WP_Query($args);
        $post_count = $local_query->found_posts;
        $page_count = ceil($post_count/POSTS_PER_ARCHIVE_PAGE);

        // Run the WordPress loop
        if ( $local_query->have_posts() )
        {
            pagination_links($page_count);
            print("<table>\n");
            while ( $local_query->have_posts() )
            {
                $local_query->the_post();
                output_post_archive_item($post->ID);
            }
            print("</table>\n");
            pagination_links($page_count);
        }
        else
        {
            get_template_part( 'template-parts/content', 'none' );
        }
        wp_reset_postdata();

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
