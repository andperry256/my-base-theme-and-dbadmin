<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site may use a
 * different template.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package My_Base_Theme
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        if ($my_base_theme_mode == 'full')
        {
            require($site_path_defs_path);
            $page_uri = trim(get_page_uri(get_the_ID()),'/');
            if (is_file("$custom_pages_path/$page_uri/_home.php"))
            {
                $custom_script = "$custom_pages_path/$page_uri/_home.php";
            }
            else
            {
                $custom_script = "$custom_pages_path/$page_uri.php";
            }
        }
        if ((isset($custom_script)) && (is_file($custom_script)))
        {
            // Display page with custom PHP script
            include($custom_script);
        }
        else
        {
            // Display page using normal WordPress mechanism
            while ( have_posts() ) : the_post();

              get_template_part( 'template-parts/content', 'page' );

              /*** Edit out the following code as comments are not used on this site ***
                // If comments are open or we have at least one comment, load up the comment template.
                if ( comments_open() || get_comments_number() ) :
                  comments_template();
                endif;
              */

            endwhile; // End of the loop.
        }
      ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php

// *** Edit out the sidebar inclusion ***
//get_sidebar();

get_footer();
