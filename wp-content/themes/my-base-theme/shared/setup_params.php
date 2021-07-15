<?php
//================================================================================

global $my_base_theme_mode;
global $site_path_defs_path;
global $meta_description;
global $meta_robots_noindex;
global $meta_robots_nofollow;
global $meta_refresh_interval;
global $meta_refresh_url;
global $meta_refresh_url_pars;
global $desktop_header_image_path, $desktop_header_image_url;
global $intermediate_header_image_path, $intermediate_header_image_url;
global $mobile_header_image_path, $mobile_header_image_url;
global $PrivateScriptsDir, $DBMode, $Location;
global $custom_footer_script;

$themes_dir = get_theme_root();
$site_path_defs_path = "$themes_dir/site_path_defs.php";
if (!is_file($site_path_defs_path))
{

	//================================================================================
	// SIMPLE MODE
	// Most of the complex functionality is excluded.
	//================================================================================

	$my_base_theme_mode = 'simple';
	set_default_header_image_paths();
	$menu_id = 'Main';
	$menu_description = 'Menu';

	//================================================================================

} else {

	//================================================================================
	// FULL MODE
	// Set up various parameters from data stored in the structure of the
	// 'wp-custom-scripts' directory.
	//================================================================================

	$my_base_theme_mode = 'full';
	require($site_path_defs_path);
	$CustomScriptsPath = "$BaseDir/wp-custom-scripts";
	$CustomScriptsURL = "$BaseURL/wp-custom-scripts";
	$CustomPagesPath = "$CustomScriptsPath/pages";
	$CustomPagesURL = "$CustomScriptsURL/pages";
	$custom_categories_path = "$CustomScriptsPath/categories";
	$custom_categories_url = "$CustomScriptsURL/categories";
	require("$CustomPagesPath/select_menu.php");
	$page_uri = get_page_uri(get_the_ID());
	if (is_file("$CustomScriptsPath/functions.php"))
	{
		include("$CustomScriptsPath/functions.php");
	}

	// Use header image file(s) in top level of the custom scripts directory as the default.
	set_default_header_image_paths();

	//================================================================================

	if (is_file("$CustomScriptsPath/footer.php"))
	{
		$custom_footer_script = "$CustomScriptsPath/footer.php";
	}
	else
	{
		$custom_footer_script = '';
	}

	if (is_page())
	{
		$minimum_access_level = '';
		if (is_file("$CustomPagesPath/init.php"))
		{
			// Run any custom initialisation sequence
			include("$CustomPagesPath/init.php");
		}

		// Move down the page hierarchy to the given address, matching various items along the way.
		$page_uri .= '/';
		$hierarchy = array();
		$tok = strtok($page_uri,'/');
		while ($tok !== false)
		{
			$hierarchy[$tok] = true;
			$tok = strtok('/');
		}
		$uri_sub_path = '';
		foreach ($hierarchy as $key => $val)
		{
			$uri_sub_path .= "/$key";
			$subpath = ltrim($uri_sub_path,'/');
			set_header_image_paths($subpath,'page');
			if (is_file("$CustomPagesPath/$uri_sub_path/footer.php"))
			{
				// Select custom footer script
				$custom_footer_script = "$CustomPagesPath/$uri_sub_path/footer.php";
			}
			if (is_file("$CustomPagesPath/$uri_sub_path/select_menu.php"))
			{
				// Select menu
				include("$CustomPagesPath/$uri_sub_path/select_menu.php");
			}
			if (is_file("$CustomPagesPath/$uri_sub_path/styles.css"))
			{
				// Add stylesheet to hierarchy
				output_stylesheet_link($CustomPagesURL,$uri_sub_path);
			}
			if (is_file("$CustomPagesPath/$uri_sub_path/authentication.php"))
			{
				// Set access level for user authentication
				include("$CustomPagesPath/$uri_sub_path/authentication.php");
			}
			if (is_file("$CustomPagesPath/$uri_sub_path/metadata.php"))
			{
				// Include any meta tag variables
				include("$CustomPagesPath/$uri_sub_path/metadata.php");
			}
			if (is_file("$CustomPagesPath/$uri_sub_path/init.php"))
			{
				// Run any custom initialisation sequence
				include("$CustomPagesPath/$uri_sub_path/init.php");
			}
		}
	}

	//================================================================================

	elseif ((is_single()) || (is_category()))
	{
		if (is_file("$custom_categories_path/select_menu.php"))
		{
			include("$custom_categories_path/select_menu.php");
		}
		$categories = get_the_category();
		foreach ($categories as $key => $dummy)
		{
			$id = $categories[$key]->term_id;
			$slug =  $categories[$key]->slug;
			$hierarchy = get_category_parents($id, false, '/', true);

			/*
			Move down the category hierarchy to the given address, matching various
			items along the way. Set the supercategory to the top level category in
			the hierarchy.
			CAUTION - If the post is allocated to multiple categories and there are
			items to be processed in more than one line of ancestry, then the results
			may be unpredictable as they could depend upon the order in which the
			categories are processed.
			*/
			$tok = strtok($hierarchy,'/');
			if (!empty($tok))
			{
				$supercategory = $tok;
			}
			else
			{
				$supercategory = 'none';
			}
			while ($tok !== false)
			{
				set_header_image_paths($tok,'category');
				if (is_file("$CustomPagesPath/$tok/footer.php"))
				{
					// Select custom footer script
					$custom_footer_script = "$custom_categories_path/$tok/footer.php";
				}
				if (is_file("$custom_categories_path/$tok/select_menu.php"))
				{
					// Select menu
					include("$custom_categories_path/$tok/select_menu.php");
				}
				if (is_file("$custom_categories_path/$uri_sub_path/styles.css"))
				{
					// Add stylesheet to hierarchy
					output_stylesheet_link($custom_categories_url,$tok);
				}
				$tok = strtok('/');
			}
		}

		/*
		This code is temporarily disabled. The call to db_connect_with_params is
		currently being called with a database ID of 1, which may not be correct
		for all sites. The use of supercategories needs to be reviewed anyway.

		if ((is_single()) && (isset($enable_supercategories)) && ($enable_supercategories))
		{
			// Automatically assign required supercategory.
			require("$PrivateScriptsDir/mysql_connect.php");
			$db = db_connect_with_params(1,$DBMode,$Location);
			$query_result = mysqli_query($db,"SELECT term_taxonomy_id FROM wp_term_taxonomy LEFT JOIN wp_terms ON (wp_term_taxonomy.term_id=wp_terms.term_id) WHERE taxonomy='supercategory' AND slug='$supercategory'");
			if ($row = mysqli_fetch_assoc($query_result))
			{
				$post_id = get_the_ID();
				$taxonomy_id = $row['term_taxonomy_id'];
				$query_result2 = mysqli_query($db,"SELECT * FROM wp_term_relationships WHERE object_id='$post_id' AND term_taxonomy_id='$taxonomy_id'");
				if (mysqli_num_rows($query_result2) == 0)
				{
					mysqli_query($db,"INSERT INTO wp_term_relationships VALUES ($post_id, $taxonomy_id, 0)");
				}
			}
		}
		*/
	}

	//================================================================================

	elseif (is_archive())
	{
		set_header_image_paths('','archive');
	}

	//================================================================================


	//================================================================================

	if ((function_exists('GetAccessLevel')) && (isset($minimum_access_level)) && (GetAccessLevel() < $minimum_access_level))
	{
		exit("<p>User authentication failed. Please return to the <a href=\"$BaseURL\">main site home page</a> and log back into the required facility.</p>");
	}

//================================================================================
}  // Endif for simple/full mode
//================================================================================
?>
