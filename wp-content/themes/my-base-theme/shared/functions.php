<?php
//================================================================================
/*
 * My Base Theme - Additional Functions
 *
 * Includes functions that may need to be accessed:-
 * 1. By scripts running outside the WordPress environment.
 * 2. By scripts in the wp-custom-scripts directory.
 * 3. By child theme scripts.
 */
 //================================================================================
 if (!function_exists('set_default_header_image_paths'))
 {
  //================================================================================
  /*
   * Function set_default_header_image_paths
   */
  //================================================================================
  
  function set_default_header_image_paths()
  {
      $image_file_exts = array( 'svg', 'png', 'jpg' );
      global $desktop_header_image_path;
      global $desktop_header_image_url;
      global $intermediate_header_image_path;
      global $intermediate_header_image_url;
      global $mobile_header_image_path;
      global $mobile_header_image_url;
      $current_theme_dir = get_stylesheet_directory();
      $current_theme_url = get_stylesheet_directory_uri();
    
      $desktop_header_image_path = '';
      $desktop_header_image_url = '';
      foreach ($image_file_exts as $ext)
      {
          if (is_file("$current_theme_dir/header_image_desktop.$ext"))
          {
              $desktop_header_image_path = "$current_theme_dir/header_image_desktop.$ext";
              $desktop_header_image_url = "$current_theme_url/header_image_desktop.$ext";
              break;
          }
      }
    
      $intermediate_header_image_path = $desktop_header_image_path;
      $intermediate_header_image_url = $desktop_header_image_url;
      foreach ($image_file_exts as $ext)
      {
          if (is_file("$current_theme_dir/header_image_intermediate.$ext"))
          {
              $intermediate_header_image_path = "$current_theme_dir/header_image_intermediate.$ext";
              $intermediate_header_image_url = "$current_theme_url/header_image_intermediate.$ext";
              break;
          }
      }
    
      $mobile_header_image_path = $intermediate_header_image_path;
      $mobile_header_image_url = $intermediate_header_image_url;
      foreach ($image_file_exts as $ext)
      {
          if (is_file("$current_theme_dir/header_image_mobile.$ext"))
          {
              $mobile_header_image_path = "$current_theme_dir/header_image_mobile.$ext";
              $mobile_header_image_url = "$current_theme_url/header_image_mobile.$ext";
              break;
          }
      }
  }
  
  //================================================================================
  /*
   * Function set_header_image_paths
   */
  //================================================================================
  
  function set_header_image_paths($slug,$type)
  {
      if (function_exists('set_custom_header_image_paths'))
      {
          // Call child theme function
          set_custom_header_image_paths($slug,$type);
      }
      else
      {
          // No action - the default paths will apply
      }
  }
  
  //================================================================================
  /*
   * Function output_page_header
   *
   * This function is used to output the main title header of the current page.
   */
  //================================================================================
  
  function output_page_header()
  {
      if (function_exists('get_secondary_title'))
      {
          $secondary_title = get_secondary_title();
      }
      else
      {
          $secondary_title = '';
      }
      if ($secondary_title == '#')
      {
          // No action
      }
      elseif (!empty($secondary_title))
      {
          echo("<h1>$secondary_title</h1>\n");
      }
      else
      {
          the_title( '<h1 class="entry-title">', '</h1>' );
      }
  }
  
  //================================================================================
  /*
   * Function get_content_part
   *
   * This function is used extract and output a given portion of the page content
   * and is for use when the content section of a page is being built using a custom
   * PHP script. A numeric part number is passed as a parameter and this indicates
   * that the text is to be extracted from between the following tags in the
   * WordPress page content:-
   *
   * [part<n>]
   * [/part<n>]
   *
   * where <n> is the part number. This allows multiple portions to be extracted
   * from the pages content for use at different points in the page.
   */
  //================================================================================
  
  function get_content_part($part_no,$option='')
  {
      $page_id = get_the_ID();
      $page_object = get_page($page_id);
      $content = $page_object->post_content;
      $dummy = "[[[[[[[[";  // To prevent false positive in PHP code checker
      if ($part_no == 0)
      {
          // Use part number 0 to return whole page content
          $content = apply_filters( 'the_content', $content );
          $content = str_replace( ']]>', ']]&gt;', $content );
      }
      else
      {
          $pos1 = strpos($content,"[part$part_no]");
          $pos2 = strpos($content,"[/part$part_no]");
          if (($pos1 === false) || ($pos2 === false))
          {
              return "**** Unable to retrieve part $part_no from page ****";
          }
          $pos1 += strlen("[part$part_no]");
          $content = substr($content,$pos1,$pos2-$pos1);
          $content = apply_filters( 'the_content', $content );
          $content = str_replace( ']]>', ']]&gt;', $content );
          $content = str_replace( '__', '&nbsp;', $content );
      }
      if ($option == 'strip_paras')
      {
          $content = str_replace('<p>','',$content);
          $content = str_replace('</p>','',$content);
      }
      return $content;
  }
  
  //================================================================================
  /*
   * Function output_meta_data
   *
   * This function is used to generate meta tag data in the page header.
   * A number of global variables are referenced by the function to set up the tags
   * as required. These will have been set up previously by running any 'metadata.php'
   * scripts in the page hierachy within the custom scripts folder.
   *
   * N.B. To cancel a meta description from an ancestor page without creating a new one,
   * the meta description must be re-defined for the page as an empty string.
   */
  //================================================================================
  
  function output_meta_data()
  {
      global $meta_description;
      global $meta_robots_noindex;
      global $meta_robots_nofollow;
      global $meta_refresh_interval;
      global $meta_refresh_url;
      global $meta_refresh_url_pars;
      global $Location;
    
      if ((isset($Location)) && ($Location == 'local'))
      {
          print("<meta name=\"robots\" content=\"noindex,nofollow\">\n");
      }
      else
      {
          if ((isset($meta_description)) && (!empty($meta_description)))
          {
              print("<meta name=\"description\" content=\"$meta_description\">\n");
          }
          $robots_content = '';
          if ((isset($meta_robots_noindex)) && ($meta_robots_noindex))
          {
              $robots_content = 'noindex';
          }
          if ((isset($meta_robots_nofollow)) && ($meta_robots_nofollow))
          {
              if (!empty($robots_content))
              {
                  $robots_content .= ',';
              }
              $robots_content .= 'nofollow';
          }
            if (!empty($robots_content))
            {
                print("<meta name=\"robots\" content=\"$robots_content\">\n");
            }
      }
    
      if ((isset($meta_refresh_interval)) && (isset($meta_refresh_url)) && (!isset($_GET['norefresh'])))
      {
          if (!isset($meta_refresh_url_pars))
          {
              $meta_refresh_url_pars = '';
          }
          print("<meta http-equiv=\"refresh\" content=\"$meta_refresh_interval;URL='$meta_refresh_url/$meta_refresh_url_pars'\" />\n");
      }
  }
  
  //================================================================================
  /*
   * Function output_stylesheet_link
   *
   * This function is used to output a stylesheet link in the HTML header when
   * the URL hierachy is scanned by setup_params.php. The stylesheet file must be
   * named styles.css.
   *
   * The associated light/dark theme stylesheet will also be linked in if present.
   */
  //================================================================================
  
  function output_stylesheet_link($path,$sub_path)
  {
      global $link_version, $BaseDir, $BaseURL;
      $stylesheet_id = str_replace('/','-',$sub_path);
      $dir_path = str_replace($BaseURL,$BaseDir,$path);
      print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-css'  href='$path/$sub_path/styles.css?v=$link_version' type='text/css' media='all' />\n");
    
      if (function_exists('get_session_var'))
      {
          if ((get_session_var('theme_mode') == 'light') && (is_file("$dir_path/$sub_path/styles-light.css")))
          {
              print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-light-css'  href='$path/$sub_path/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
          }
          elseif ((get_session_var('theme_mode') == 'dark') && (is_file("$dir_path/$sub_path/styles-dark.css")))
          {
              print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-dark-css'  href='$path/$sub_path/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
          }
      }
  }
  
  //================================================================================
  /*
   * Function include_inline_stylesheet
   *
   * This function loads a stylesheet file and outputs its contents within
   * <style></style> tags by way of inline styles. It can be called from anywhere
   * but is also used by setup_params.php when scanning the URL hierachy. There
   * is no constraint on the stylesheet filename, but when called from
   * setup_params.php, it will always be inline-styles.css.
   *
   * The associated light/dark theme stylesheet will also be included if present.
   */
  //================================================================================
  
  function include_inline_stylesheet($path)
  {
      print("<style>\n");
      if (is_file($path))
      {
          include($path);
      }
      $light_theme_path = str_replace('.css','-light.css',$path);
      $dark_theme_path = str_replace('.css','-dark.css',$path);
      if (function_exists('get_session_var'))
      {
          if ((get_session_var('theme_mode') == 'light') && (is_file($light_theme_path)))
          {
              require_once("$BaseDir/common_scripts/core_funct.php");
          }
          elseif ((get_session_var('theme_mode') == 'dark') && (is_file($dark_theme_path)))
          {
              include($dark_theme_path);
          }
      }
      print("</style>\n");
  }
  
  //================================================================================
  /*
   * Function include_inline_javascript
   *
   */
  //================================================================================
  
  function include_inline_javascript($path)
  {
      print("<script>\n");
      include($path);
      print("</script>\n");
  }
  
  //================================================================================
  /*
   * Functions save_php_error_log & restore_php_error_log
   */
  //================================================================================
  
  function save_php_error_log()
  {
      global $RootDir;
      if (is_file("$RootDir/logs/php_error.log"))
      {
          copy("$RootDir/logs/php_error.log","$RootDir/logs/php_error.log.sav");
      }
  }
  
  function restore_php_error_log()
  {
      global $RootDir;
      if (is_file("$RootDir/logs/php_error.log"))
      {
          unlink("$RootDir/logs/php_error.log");
      }
      if (is_file("$RootDir/logs/php_error.log.sav"))
      {
          rename("$RootDir/logs/php_error.log.sav","$RootDir/logs/php_error.log");
      }
  }
  
  //================================================================================
  /*
   * Function output_to_access_log
   */
  //================================================================================
  
  function output_to_access_log($user='',$add_info='')
  {
      global $AccessLogsDir;
      global $BaseDir;
      include("$BaseDir/common_scripts/allowed_hosts.php");
      if (is_dir($AccessLogsDir))
      {
          $date = date('Y-m-d');
          $ofp = fopen("$AccessLogsDir/$date.log",'a');
          $time = date('H:i:s');
          $addr_str = $_SERVER['REMOTE_ADDR'];
          if (is_local_ip($_SERVER['REMOTE_ADDR']))
          {
              $addr_str = '[Local]';
          }
          elseif (isset($allowed_hosts[$addr_str]))
          {
              $addr_str = "[{$allowed_hosts[$addr_str]}]";
          }
          $addr_str = substr("$addr_str               ",0,15);
          $uri_str = str_replace('%','%%',$_SERVER['REQUEST_URI']);
          fprintf($ofp,"$date $time ".'-'." $addr_str $uri_str");
          if (!empty($user))
          {
              fprintf($ofp," [user = $user]");
          }
          if ((isset($_SERVER['HTTP_REFERER'])) && (!empty($_SERVER['HTTP_REFERER'])))
          {
              $referrer_str = str_replace('%','%%',$_SERVER['HTTP_REFERER']);
              fprintf($ofp,$referrer_str);
          }
          if (!empty($add_info))
          {
              fprintf($ofp," [$add_info]");
          }
          fprintf($ofp,"\n");
          fclose($ofp);
      }
  }
  
  //================================================================================
  /*
   * Function readable_markup
   *
   * This function is used to display markup code (HTML/XML) visibly in the
   * browser window when setting a debug point.
   */
  //================================================================================
  
  function readable_markup($str)
  {
      $str = str_replace("<","&lt;",$str);
      $str = str_replace(">","&gt;",$str);
      $str = str_replace("\n","<br />\n",$str);
      return $str;
  }
  
  //================================================================================
  /*
   * Function simpify_html_tag
   *
   * This function is called by the simplify_html function or a site specific
   * function that calls the latter.
   *
   * It reduces all tags of a given type to a simple tag with no options.
   */
  //================================================================================
  
  function simpify_html_tag($content,$tag)
  {
      $pos1 = strpos($content,"<$tag");
      while ($pos1 !== false)
      {
          $pos2 = strpos($content,'>',$pos1);
          $content = substr($content,0,$pos1+strlen($tag)+1).substr($content,$pos2);
          $pos1 = strpos($content,"<$tag",$pos1+1);
      }
      return $content;
  }
  
  //================================================================================
  /*
   * Function simplify_html
   *
   * This function is called to simplify a word processor document that has been
   * exported as HTML. Its main purpose is to remove any built-in style
   * information that is otherwise defined in CSS.
   *
   * If further edits are required, then it is suggested that this function is
   * called from a site specific function with the necessary additional
   * functionality.
   */
  //================================================================================
  
  function simplify_html($content)
  {
      global $allowed_tags, $simplified_tags;
      if (!isset($allowed_tags))
      {
          /*
           The '$allowed_tags' array specifies those tags that are to be retained in the
           generated HTML code. This is the default version but can be overridden by 
           declaring a custom version outside the function call.
           */
          $allowed_tags = array('<p>','<br>','<a>','<table>','<th>','<tr>','<td>','<ul>','<ol>','<li>','<b>','<i>','<u>');
      }
      if (!isset($simplified_tags))
      {
          /*
           The '$simplified_tags' array specifies those tag types for which the
           'simplify_html_tag' function is to be run. This is the default version but
           can be overridden by declaring a custom version outside the function call.
           */
          $simplified_tags = array('p');
      }
    
      // Strip out any <style> tags. This is done long-hand as there have been
      // issues when just relying on the call to strip_tags.
      $pos1 = strpos($content,'<style');
      while ($pos1 !== false)
      {
          $pos2 = strpos($content,'</style>',$pos1);
          $content = substr($content,0,$pos1).substr($content,$pos2+8);
          $pos1 = strpos($content,'<style',$pos1+1);
      }
    
      // Run the main operation
      $content = strip_tags($content,$allowed_tags);
    
      // Apply the simplify_html_tag function to selected tag types
      foreach ($simplified_tags as $tag)
      {
          $content = simpify_html_tag($content,$tag);
      }
    
      return $content;
  }
  
  //================================================================================
}
//================================================================================
?>
