<?php
//================================================================================

/*
 * My Base Theme functions and definitions.
 *
 * Additional functions that may need to be exported to or accessed by an
 * independent custom theme.
 */

//================================================================================

/*
 * This function is used to output the main title header of the current page.
 * It is dependent upon the installation of the 'Secondary Title' plugin.
 */

function output_page_header()
{
	if (function_exists('get_secondary_title'))
		$secondary_title = get_secondary_title();
	else
		$secondary_title = '';
	if ($secondary_title == '#')
	{
		// No action
	}
	elseif (!empty($secondary_title))
		echo("<h1>$secondary_title</h1>\n");
	else
		the_title( '<h1 class="entry-title">', '</h1>' );
}

//================================================================================

/*
 * This function is used extract and output a given portion of the page content
 * and is for use when the content section of a page is being built using a custom
 * PHP script. A numeric part number is passed as a paramaeter and this indicates
 * that the text is to be extracted from between the following tags in the
 * WordPress page content:-
 *
 * [part<n>]
 * [/part<n>]
 *
 * where <n> is the part number. This allows multiple portions to be extracted
 * from the pages content for use at different points in the page.
 */

function get_content_part($part_no)
{
	$page_id = get_the_ID();
	$page_object = get_page($page_id);
	$content = $page_object->post_content;
	if ($part_no == 0)
	{
		// Use part number 0 to return whole page content
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		return $content;
	}
	else
	{
		$pos1 = strpos($content,"[part$part_no]");
		$pos2 = strpos($content,"[/part$part_no]");
		if (($pos1 === false) || ($pos2 === false))
			return "**** Unable to retrieve part $part_no from page ****";
		$pos1 += strlen("[part$part_no]");
		$content = substr($content,$pos1,$pos2-$pos1);
		$content = apply_filters( 'the_content', $content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		$content = str_replace( '__', '&nbsp;', $content );
		return $content;
	}
}

//================================================================================

/*
 * This function is used to generate meta tag data in the page header.
 * A number of global variables are referenced by the function to set up the tags
 * as required. These will have been set up previously by running any 'metadata.php'
 * scripts in the page hierachy within the custom scripts folder.
 *
 * N.B. To cancel a meta description from an ancestor page without creating a new one,
 * the meta description must be re-defined for the page as an empty string.
 */

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
		print("<meta name=\"robots\" content=\"noindex,nofollow\">\n");
	else
	{
		if ((isset($meta_description)) && (!empty($meta_description)))
			print("<meta name=\"description\" content=\"$meta_description\">\n");
		$robots_content = '';
		if ((isset($meta_robots_noindex)) && ($meta_robots_noindex))
			$robots_content = 'noindex';
		if ((isset($meta_robots_nofollow)) && ($meta_robots_nofollow))
		{
			if (!empty($robots_content))
				$robots_content .= ',';
			$robots_content .= 'nofollow';
		}
			if (!empty($robots_content))
				print("<meta name=\"robots\" content=\"$robots_content\">\n");
	}

	if ((isset($meta_refresh_interval)) && (isset($meta_refresh_url)) && (!isset($_GET['norefresh'])))
	{
		if (!isset($meta_refresh_url_pars))
			$meta_refresh_url_pars = '';
		print("<meta http-equiv=\"refresh\" content=\"$meta_refresh_interval;URL='$meta_refresh_url/$meta_refresh_url_pars'\" />\n");
	}
}

//================================================================================

function output_stylesheet_link($path,$sub_path)
{
	global $link_version;
	$stylesheet_id = str_replace('/','-',$sub_path);
	print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-css'  href='$path/$sub_path/styles.css?v=$link_version' type='text/css' media='all' />\n");
}

//================================================================================

function define_supercategory_taxonomy()
{
	global $enable_supercategories;

	if ((!isset($enable_supercategories)) || ($enable_supercategories !== false))
	{
		$labels = array (
			'name' => 'Supercategories',
			'singluar_name' => 'Supercategory',
			'add_new_item' => 'Add New Supercategory',
		);

		$args = array (
			'labels' => $labels,
			'query_var' => true,
			'rewrite' => true,
		);

		register_taxonomy( 'supercategory', 'post', $args );
	}
}
add_action( 'init', 'define_supercategory_taxonomy' );

//================================================================================

function wpb_remove_loginshake()
{
    remove_action('login_head', 'wp_shake_js', 12);
}
add_action('login_head', 'wpb_remove_loginshake');

//================================================================================
?>
