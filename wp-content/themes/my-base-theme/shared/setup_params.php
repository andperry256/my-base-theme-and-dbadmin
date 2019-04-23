<?php
//================================================================================

// Set up various parameters from data stored in the structure of the
// 'wp-custom-scripts' directory.

//================================================================================

function set_header_image_paths($dir,$url)
{
	global $desktop_header_image_path;
	global $desktop_header_image_url;
	global $mobile_header_image_path;
	global $mobile_header_image_url;

	if (is_file("$dir/header_image.png"))
	{
		// Select top level desktop header image file (PNG)
		$desktop_header_image_path = "$dir/header_image.png";
		$desktop_header_image_url = "$url/header_image.png";
	}
	elseif (is_file("$dir/header_image.jpg"))
	{
		// Select top level desktop header image file (JPG)
		$desktop_header_image_path = "$dir/header_image.jpg";
		$desktop_header_image_url = "$url/header_image.jpg";
	}
	if (is_file("$dir/header_image_mobile.png"))
	{
		// Select top level mobile header image file (PNG)
		$mobile_header_image_path = "$dir/header_image_mobile.png";
		$mobile_header_image_url = "$url/header_image_mobile.png";
	}
	elseif (is_file("$dir/header_image_mobile.jpg"))
	{
		// Select top level mobile header image file (JPG)
		$mobile_header_image_path = "$dir/header_image_mobile.jpg";
		$mobile_header_image_url = "$url/header_image_mobile.jpg";
	}
}

//================================================================================

global $meta_description;
global $meta_robots_noindex;
global $meta_robots_nofollow;
global $meta_refresh_interval;
global $meta_refresh_url;
global $meta_refresh_url_pars;
global $desktop_header_image_path, $desktop_header_image_url;
global $mobile_header_image_path, $mobile_header_image_url;
global $PrivateScriptsDir, $DBMode, $Location;
global $site_path_defs_path;
global $custom_footer_script;

$themes_dir = get_theme_root();
$site_path_defs_path = "$themes_dir/site_path_defs.php";
if (is_file($site_path_defs_path))
	require($site_path_defs_path);
else
	die("Unable to set site path definitions.");
$CustomScriptsPath = "$BaseDir/wp-custom-scripts";
$CustomScriptsURL = "$BaseURL/wp-custom-scripts";
$CustomPagesPath = "$CustomScriptsPath/pages";
$CustomPagesURL = "$CustomScriptsURL/pages";
$custom_categories_path = "$CustomScriptsPath/categories";
$custom_categories_url = "$CustomScriptsURL/categories";
require("$CustomPagesPath/select_menu.php");
if (!isset($_SESSION['header_no']))
	$_SESSION['header_no'] = 1;
$page_uri = get_page_uri(get_the_ID());
if (is_file("$CustomScriptsPath/functions.php"))
{
	include("$CustomScriptsPath/functions.php");
}

// Use header image file(s) in top level of the custom scripts directory as the default.
set_header_image_paths($CustomScriptsPath,$CustomScriptsURL);

//================================================================================

if (is_file("$CustomScriptsPath/footer.php"))
{
	$custom_footer_script = "$CustomScriptsPath/footer.php";
}
else
{
	$custom_footer_script = '';
}

if ((is_page()) || (is_404()))
{
	set_header_image_paths($CustomPagesPath,$CustomPagesURL);
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
		set_header_image_paths("$CustomPagesPath/$uri_sub_path","$CustomPagesURL/$uri_sub_path");
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
	$categories = get_the_category();
	if (!empty($categories))
	{
		$id = $categories[0]->term_id;
		$slug =  $categories[0]->slug;
		$hierarchy = get_category_parents($id, false, '/', true);

		// Move down the category hierarchy to the given address, matching various items along the way.
		// Set the cupercategory to the top level category in the hierarchy.
		$tok = strtok($hierarchy,'/');
		if (!empty($tok))
			$supercategory = $tok;
		else
			$supercategory = 'none';
		$uri_sub_path = $tok;
		while ($tok !== false)
		{
			set_header_image_paths("$custom_categories_path/$uri_sub_path","$custom_categories_url/$uri_sub_path");
			if (is_file("$CustomPagesPath/$uri_sub_path/footer.php"))
			{
				// Select custom footer script
				$custom_footer_script = "$custom_categories_path/$uri_sub_path/footer.php";
			}
			if (is_file("$custom_categories_path/$uri_sub_path/styles.css"))
			{
				// Add stylesheet to hierarchy
				output_stylesheet_link($custom_categories_url,$uri_sub_path);
			}
			$tok = strtok('/');
			$uri_sub_path = $tok;
		}
	}

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
}

//================================================================================

elseif (is_archive())
{
	set_header_image_paths("$CustomScriptsPath/archives","$CustomScriptsURL/archives");
}

//================================================================================

if (!isset($_SESSION['header_no']))
	$_SESSION['header_no'] = 1;
$header_no = $_SESSION['header_no'];
$next_header_no = $header_no + 1;
$alt_desktop_header_image_path = str_replace(".png","_$header_no.png",$desktop_header_image_path);
$alt_desktop_header_image_path = str_replace(".jpg","_$header_no.jpg",$alt_desktop_header_image_path);
$alt_desktop_header_image_url = str_replace(".png","_$header_no.png",$desktop_header_image_url);
$alt_desktop_header_image_url = str_replace(".jpg","_$header_no.jpg",$alt_desktop_header_image_url);
$alt_mobile_header_image_path = str_replace(".png","_$header_no.png",$mobile_header_image_path);
$alt_mobile_header_image_path = str_replace(".jpg","_$header_no.jpg",$alt_mobile_header_image_path);
$alt_mobile_header_image_url = str_replace(".png","_$header_no.png",$mobile_header_image_url);
$alt_mobile_header_image_url = str_replace(".jpg","_$header_no.jpg",$alt_mobile_header_image_url);
$next_alt_desktop_header_image_path = str_replace("_$header_no","_$next_header_no",$alt_desktop_header_image_path);
if (is_file($alt_desktop_header_image_path))
{
	// Select alternative desktop header image
	$desktop_header_image_path = $alt_desktop_header_image_path;
	$desktop_header_image_url = $alt_desktop_header_image_url;
}
if (is_file($alt_mobile_header_image_path))
{
	// Select alternative mobile header image
	$mobile_header_image_path = $alt_mobile_header_image_path;
	$mobile_header_image_url = $alt_mobile_header_image_url;
}
if (is_file($next_alt_desktop_header_image_path))
{
	// Next image found - increment header number
	$_SESSION['header_no']++;
}
else
{
	// Next image not found - reset header number to 1
	$_SESSION['header_no'] = 1;
}

// Set mobile header image to desktop header image if separate item not found.
if (!isset($mobile_header_image_path))
{
	$mobile_header_image_path = $desktop_header_image_path;
	$mobile_header_image_url = $desktop_header_image_url;
}

//================================================================================

if ((function_exists('GetAccessLevel')) && (GetAccessLevel() < $minimum_access_level))
{
	die("<p>User authentication failed. Please return to the <a href=\"$BaseURL\">main site home page</a> and log back into the required facility.</p>");
}

//================================================================================
?>
