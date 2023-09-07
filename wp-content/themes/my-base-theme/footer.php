<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package My_Base_Theme
 */

?>

  </div><!-- #content -->

</div><!-- #page -->
<footer id="colophon" class="site-footer" role="contentinfo">
  <div class="site-info">
    <?php
      global $site_path_defs_path;
      global $custom_footer_script;
      require($site_path_defs_path);
      $home_path = rtrim($BaseDir,'/');
      $page_uri = trim(get_page_uri(get_the_ID()),'/');
      if (is_file("$custom_footer_script"))
      {
        include("$custom_footer_script");
      }
    ?>
  </div><!-- .site-info -->
</footer><!-- #colophon -->

<?php wp_footer(); ?>
</div><!-- #super-container -->

</body>
</html>
