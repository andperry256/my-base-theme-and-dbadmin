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

if (!function_exists('run_session'))
{

//================================================================================
/*
 * Function run_session
 *
 * This function is used both inside and outside the WordPress environment.
 * When used inside WordPress it is invoked through the inclusion of this file
 * within the main functions.php file for the theme. The latter must run the
 * following statement immediately after the file inclusion:-
 *
 * add_action( 'init', 'run_session', 1);
 */
//================================================================================

function run_session()
{
	global $wpdb;
	global $GlobalSessionVars;
	global $GlobalSessionID;
	if ((!session_id()) && (!headers_sent()))
	{
		session_start();
	}
	if (!session_id())
	{
		// This should not occur
		exit ("ERROR - Unable to start session");
	}
	$GlobalSessionID = session_id();

	if ((isset($wpdb)) && (function_exists('have_posts')))
	{
		// Running inside the WP environment
		if (is_file('/var/www/html/user_authentication.php'))
		{
			include('/var/www/html/user_authentication.php');
		}
		if (!isset($_SESSION['theme_mode']))
		{
			$_SESSION['theme_mode'] = 'light';
		}
		$env = 'wp';
	}
	elseif (function_exists('wp_db_connect'))
	{
		// Running outside the WP environment
		wp_db_connect();
		$env = 'non-wp';
	}
	if (!isset($wpdb))
	{
		// This should not occur
		exit("ERROR - Unable to connect to the WP database.");
	}

	/*
	The following query checks whether the table wp_session_updates is present.
	If it is then the current session variables are all transferred to the array
	$GlobalSessionVars and the PHP session is closed.
	If the table is not present then no action is performed and the PHP session
	left permanently open (not recommended).
	*/
	$query_result = $wpdb->query("SELECT * FROM wp_session_updates");
	if ($query_result !== false)
	{
		// Transfer all updates for the current session from the database to the
		// appropriate $_SESSION variables.
		if ($env == 'wp')
		{
			// Inside the WordPress environment
			$query_result2 = $wpdb->get_results("SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID'");
			foreach ($query_result2 as $row2)
			{
				if ($row2->type == 'update')
				{
					// Update is a variable assignment
					if ($row2->name2 == '#')
					{
						$_SESSION[$row2->name] = $row2->value;
					}
					else
					{
						if (!isset($_SESSION[$row2->name]))
						{
							$_SESSION[$row2->name] = array();
						}
						$_SESSION[$row2->name][$row2->name2] = $row2->value;
					}
				}
				elseif (($row2->type == 'delete') && (isset($_SESSION[$row2->name])))
				{
					// Update is a variable deletion (unset)
					if ($row2->name2 == '#')
					{
						unset($_SESSION[$row2->name]);
					}
					else
					{
						unset($_SESSION[$row2->name][$row2->name2]);
					}
				}
			}
		}
		else
		{
			// Outside the WordPress environment
			$query_result2 = $wpdb->query("SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID'");
			while ($row2 = $query_result2->fetch_assoc())
			{
				if ($row2['type'] == 'update')
				{
					// Update is a variable assignment
					if ($row2['name2'] == '#')
					{
						$_SESSION[$row2['name']] = $row2['value'];
					}
					else
					{
						if (!isset($_SESSION[$row2['name']]))
						{
							$_SESSION[$row2['name']] = array();
						}
						$_SESSION[$row2['name']][$row2['name2']] = $row2['value'];
					}
				}
				elseif (($row2['type'] == 'delete') && (isset($_SESSION[$row2['name']])))
				{
					// Update is a variable deletion (unset)
					if ($row2['name2'] == '#')
					{
						unset($_SESSION[$row2['name']]);
					}
					else
					{
						unset($_SESSION[$row2['name']][$row2['name2']]);
					}
				}
			}
		}
		$wpdb->query("DELETE FROM wp_session_updates WHERE session_id='$GlobalSessionID'");

		// Transfer all $_SESSION variables into the $GlobalSessionVars array.
		$GlobalSessionVars = array();
		foreach($_SESSION as $name => $value)
		{
			if (is_array($_SESSION[$name]))
			{
				$GlobalSessionVars[$name] = array();
				foreach($_SESSION[$name] as $name2 => $value2)
				{
					$GlobalSessionVars[$name][$name2] = $value2;
				}
			}
			else
			{
				$GlobalSessionVars[$name] = $value;
			}
		}

		// Close the session.
		session_write_close();
	}
}

//================================================================================
/*
 * Session variable handling functions
 *
 * This section includes the following functions:-
 * 1. session_var_is_set
 * 2. get_session_var
 * 3. update_session_var
 * 4. delete_session_var
 *
 * Each function determines whether actual $_SESSION variables are in force
 * or whether they have been saved to $GlobalSessionVars and the PHP session
 * closed.
 *
 * Each function takes a parameter '$name' which can be one of:-
 * 1. A single name to index a simple array.
 * 2. An array of two names to index a 2-dimensional array.
 */
 //================================================================================

 function session_var_is_set($name)
 {
 	global $GlobalSessionVars;
 	if (!is_array($name))
 	{
 		$name = array($name,'');
 	}

 	if (count($name) != 2)
 	{
 		return false;
 	}
 	elseif (isset($GlobalSessionVars))
 	{
 		if (empty($name[1]))
 		{
 			return isset($GlobalSessionVars[$name[0]]);
 		}
 		else
 		{
 			return isset($GlobalSessionVars[$name[0]][$name[1]]);
 		}
 	}
 	elseif (empty($name[1]))
 	{
 		return isset($_SESSION[$name[0]]);
 	}
 	else
 	{
 		return isset($_SESSION[$name[0]][$name[1]]);
 	}
 }

 //================================================================================

 function get_session_var($name,$check=true)
 {
 	global $GlobalSessionVars;
 	if (!is_array($name))
 	{
 		$name = array($name,'');
 	}

 	if (count($name) != 2)
 	{
 		return false;
 	}
 	elseif (($check) && (!session_var_is_set($name[0],$name[1])))
 	{
 		return false;
 	}
 	elseif (isset($GlobalSessionVars))
 	{
 		if (empty($name[1]))
 		{
 			return $GlobalSessionVars[$name[0]];
 		}
 		else
 		{
 			return $GlobalSessionVars[$name[0]][$name[1]];
 		}
 	}
 	elseif (empty($name[1]))
 	{
 		return $_SESSION[$name[0]];
 	}
 	else
 	{
 		return $_SESSION[$name[0]][$name[1]];
 	}
 }

 //================================================================================

 function update_session_var($name,$value)
 {
 	global $GlobalSessionVars;
 	global $GlobalSessionID;
 	global $wpdb;
 	if (!isset($wpdb))
 	{
 		if (function_exists('wp_db_connect'))
 		{
 			wp_db_connect();
 		}
 		else
 		{
 			exit("ERROR - Unable to connect to WP database.");
 		}
 	}
 	if (!is_array($name))
 	{
 		$name = array($name,'');
 	}

 	if (count($name) != 2)
 	{
 		return false;
 	}
 	elseif (isset($GlobalSessionVars))
 	{
 		$timestamp = time();
 		$old_timestamp = $timestamp - 86400;  // 24 hours ago
 		$wpdb->query("DELETE FROM wp_session_updates WHERE timestamp<$old_timestamp");
 		$value_par = addslashes($value);
 		if (empty($name[1]))
 		{
 			$GlobalSessionVars[$name[0]] = $value;
 			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
 			$insert_query = "INSERT INTO wp_session_updates (session_id,name,value,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','$value_par','update',$timestamp)";
 			$update_query = "UPDATE wp_session_updates SET value='$value_par',type='update' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
 		}
 		else
 		{
 			if (!isset($GlobalSessionVars[$name[0]]))
 			{
 				$GlobalSessionVars[$name[0]] = array();
 			}
 			$GlobalSessionVars[$name[0]][$name[1]] = $value;
 			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
 			$insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,value,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','{$name[1]}','$value_par','update',$timestamp)";
 			$update_query = "UPDATE wp_session_updates SET value='$value_par',type='update' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
 		}
 		$query_result = $wpdb->query($select_query);
 		if (isset($query_result->num_rows))
 		{
 			$num_rows = $query_result->num_rows;
 		}
 		elseif(isset($wpdb->num_rows))
 		{
 			$num_rows = $wpdb->num_rows;
 		}
 		else
 		{
 			// This should not occur
 			$num_rows = -1;
 		}
 		if ($num_rows == 0)
 		{
 			$wpdb->query($insert_query);
 		}
 		else
 		{
 			$wpdb->query($update_query);
 		}
 	}
 	elseif (empty($name[1]))
 	{
 		$_SESSION[$name[0]] = $value;
 	}
 	else
 	{
 		if (!isset($_SESSION[$name[0]]))
 		{
 			$_SESSION[$name[0]] = array();
 		}
 		$_SESSION[$name[0]][$name[1]] = $value;
 	}
 }

 //================================================================================

 function delete_session_var($name)
 {
 	global $GlobalSessionVars;
 	global $GlobalSessionID;
 	global $wpdb;
 	if (!isset($wpdb))
 	{
 		if (function_exists('wp_db_connect'))
 		{
 			wp_db_connect();
 		}
 		else
 		{
 			exit("ERROR - Unable to connect to WP database.");
 		}
 	}
 	if (!is_array($name))
 	{
 		$name = array($name,'');
 	}

 	if (count($name) != 2)
 	{
 		return false;
 	}
 	elseif (isset($GlobalSessionVars))
 	{
 		$timestamp = time();
 		if (empty($name[1]))
 		{
 			if (isset($GlobalSessionVars[$name[0]]))
 			{
 				unset($GlobalSessionVars[$name[0]]);
 			}
 			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
 			$insert_query = "INSERT INTO wp_session_updates (session_id,name,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','delete',$timestamp)";
 			$update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}'";
 		}
 		else
 		{
 			if (isset($GlobalSessionVars[$name[0]][$name[1]]))
 			{
 				unset($GlobalSessionVars[$name[0]][$name[1]]);
 			}
 			$select_query = "SELECT * FROM wp_session_updates WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
 			$insert_query = "INSERT INTO wp_session_updates (session_id,name,name2,type,timestamp) VALUES ('$GlobalSessionID','{$name[0]}','{$name[1]}','delete',$timestamp)";
 			$update_query = "UPDATE wp_session_updates SET type='delete' WHERE session_id='$GlobalSessionID' AND name='{$name[0]}' AND name2='{$name[1]}'";
 		}
 		$query_result = $wpdb->query($select_query);
 		if (isset($query_result->num_rows))
 		{
 			$num_rows = $query_result->num_rows;
 		}
 		elseif(isset($wpdb->num_rows))
 		{
 			$num_rows = $wpdb->num_rows;
 		}
 		else
 		{
 			// This should not occur
 			$num_rows = -1;
 		}
 		if ($num_rows == 0)
 		{
 			$wpdb->query($insert_query);
 		}
 		else
 		{
 			$wpdb->query($update_query);
 		}
 	}
 	elseif ((empty($name[1])) && (isset($_SESSION[$name[0]])))
 	{
 		unset($_SESSION[$name[0]]);
 	}
 	elseif ((!empty($name[1])) && (isset($_SESSION[$name[0]][$name[1]])))
 	{
 		unset($_SESSION[$name[0]][$name[1]]);
 	}
 }

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
	if ((get_session_var('theme_mode') == 'light') && (is_file("$dir_path/$sub_path/styles-light.css")))
	{
		print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-light-css'  href='$path/$sub_path/styles-light.css?v=$link_version' type='text/css' media='all' />\n");
	}
	elseif ((get_session_var('theme_mode') == 'dark') && (is_file("$dir_path/$sub_path/styles-dark.css")))
	{
		print("\n<link rel='stylesheet' id='$stylesheet_id"."-styles-dark-css'  href='$path/$sub_path/styles-dark.css?v=$link_version' type='text/css' media='all' />\n");
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
	if ((get_session_var('theme_mode') == 'light') && (is_file($light_theme_path)))
	{
		include($light_theme_path);
	}
	elseif ((get_session_var('theme_mode') == 'dark') && (is_file($dark_theme_path)))
	{
		include($dark_theme_path);
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
 * Function coded_text_to_html
 *
 * This function coverts a coded text file into readable HTML. The following
 * syntax is used in the source:-
 *
 *   #*#<Text>#*#                        H1 Header
 *   ##<Text>##                          H2 Header
 *   ###<Text>###                        H3 Header
 *   ####<Text>####                      H4 Header
 *   **<Text>**                          Bold
 *   ///<Text>///                        Italic
 *   __<Text>__                          Underline
 *   @@<Link Address>@@<Link Text>@@     Hyperlink (same tab/window)
 *   @@<Link Address>@@@<Link Text>@@    Hyperlink (new tab/window)
 *   #BL#                                Start of bulleted list
 *   #*BL#                               End of bulleted list
 *   #NL#                                Start of numbered list
 *   #*NL#                               End of numbered list
 *   *                                   Create new list item (when at start of
 *                                       line inside a list)
 *   <Blank Line>                        Create paragraph break (unless in
 *                                       a list)
 *   \                                   Escape character to override other
 *                                       syntax
 */
//================================================================================

function coded_text_to_html($url)
{
	/*
	Define standard tag substitions.

	The array $tag_substitutions defines string substitutions that are made to
	generate HTML tags.

	Each generated tag alternates between offsets 0 & 1 of the sub-array as
	controlled by the corresponding value in the array $tag_status (0 or 1).

	Offset 2 of the sub-array defines the pattern to be used in preg_replace.

	The header tags must appear in descending order so that substitutions are
	made in the correct order.
	*/
	$tag_substitutions = array (
		'####' => array ('<h4>','</h4>','####'),
		'###' => array ('<h3>','</h3>','###'),
		'##' => array ('<h2>','</h2>','##'),
		'#*#' => array ('<h1>','</h1>','#\*#'),
		'**' => array ('<strong>','</strong>','\*\*'),
		'///' => array ('<em>','</em>','\/\/\/'),
		'__' => array ('<u>','</u>','__'),
		'@@' => array ('<a>','<a>','@@'),
		'#BL#' => array ('<ul>','<ul>','#BL#'),
		'#*BL#' => array ('</ul>','</ul>','#\*BL#'),
		'#NL#' => array ('<ol>','<ol>','#NL#'),
		'#*NL#' => array ('</ol>','/<ol>','#\*NL#'),
		'#TB#' => array ('<table>','<table>','#TB#'),
		'#*TB#' => array ('</table>','</table>','#\*TB#'),
	);
	$tag_status = array (
		'####' => 0,
		'###' => 0,
		'##' => 0,
		'#*#' => 0,
		'**' => 0,
		'///' => 0,
		'__' => 0,
		'@@' => 0,
		'#BL#' => 0,
		'#*BL#' => 0,
		'#NL#' => 0,
		'#*NL#' => 0,
		'#TB#' => 0,
		'#*TB#' => 0,
	);
	$in_paragraph = false;
	$list_level = 0;
	$new_list = false;
	$in_table = false;
	$new_table = false;

	$content = file($url);
	$result = '';
	if (empty($content))
	{
		return("###### INPUT TEXT NOT FOUND ######");
	}
	foreach ($content as $line)
	{
		$line = trim($line);
		$original_line = $line;

		// Perform standard tag substitutions
		foreach ($tag_substitutions as $key => $values)
		{
			while (strpos($line,$key) !== false)
			{
				if ($key == '@@')
				{
					// ====== Hyperlink ======
					$pos1 = strpos($line,'@@');
					if (($pos2 = strpos($line,'@@',$pos1+2)) && ($pos3 = strpos($line,'@@',$pos2+2)))
					{
						$link_address = substr($line,$pos1+2,$pos2-$pos1-2);
						if (substr($line,$pos2+2,1) == '@')
						{
							// An extra '@' symbol inthe middle indicates open link in new tab/window
							$options = ' target="_blank"';
							$link_text = substr($line,$pos2+3,$pos3-$pos2-3);
							$old_string = "@@$link_address@@@$link_text@@";
						}
						else
						{
							$options = '';
							$link_text = substr($line,$pos2+2,$pos3-$pos2-2);
							$old_string = "@@$link_address@@$link_text@@";
						}
					}
					$line = str_replace($old_string,"<a href=\"$link_address\"$options>$link_text</a>",$line);
				}
				elseif (($key == '#BL#') || ($key == '#NL#'))
				{
					// ====== Start of List ======
					$list_level++;
					$new_list = true;
					if ($in_paragraph)
					{
						// Close the previous paragraph
						$result .= "</p>\n";
						$in_paragraph = false;
					}
				}
				elseif ($key == '#TB#')
				{
					// ====== Start of Table ======
					$in_table = true;
					$new_table = true;
					$col_count_tok = '';
					$style_tok = '';
					$tok = strtok(substr($line,strpos($line,$key)+4),'#');
					$col_count = 2; // Set default
					while ($tok !== false)
					{
						if ((substr($tok,0,2) == 'C=') && (is_numeric(substr($tok,2))))
						{
							// Extract required column count
							$col_count_tok = $tok;
							$col_count = substr($tok,2);
						}
						elseif (substr($tok,0,2) == 'S=')
						{
							// Extract style definition
							$style_tok = $tok;
							$style = substr($tok,2);
						}
						$tok = strtok('#');
					}
					if (!empty($col_count_tok))
					{
						$line = str_replace("$col_count_tok#",'',$line);
					}
					if (!empty($style_tok))
					{
						$line = str_replace("#TB#$style_tok#","<table style=\"$style\">",$line);
					}
					else
					{
						$line = str_replace("#TB#","<table>",$line);
					}
					$col_no = 0;
				}
				else
				{
					if (($list_level > 0) && (($key == '#*BL#') || ($key == '#*NL#')))
					{
						// ====== End of List ======
						$result .= "</li>\n";
						$list_level--;
					}
					if (($in_table) && ($key == '#*TB#'))
					{
						// ====== End of Table ======
						$result .= "</td></tr>\n";
						$in_table = false;
					}

					// Substitute the tag with the required HTML.
					$line = preg_replace("/{$values[2]}/",$values[$tag_status[$key]],$line,1);
					$tag_status[$key] = ($tag_status[$key] + 1 & 1);
				}
			}
		}

		if (($list_level > 0) && (substr($line,0,1) == '*'))
		{
			// ====== Start of List Item ======
			if (!$new_list)
			{
				// Close the last list item
				$result .= "</li>\n";
			}
			$new_list = false;
			$line = preg_replace("/\*/",'<li>',$line,1);
		}
		elseif (($in_table) && (substr($line,0,2) == '[]'))
		{
			// ====== Start of Table Cell ======
			if ($new_table)
			{
				// Start of new table
				$line = preg_replace('/\[\]/',"<tr><td>",$line,1);
			}
			elseif ($col_no == 0)
			{
				// Start of new row
				$line = preg_replace('/\[\]/',"</td></tr>\n<tr><td>",$line,1);
			}
			else
			{
				// Next cell on same row
				$line = preg_replace('/\[\]/',"</td><td>",$line,1);
			}
			$new_table = false;
			$col_no = ($col_no + 1) % $col_count;
		}
		elseif ((empty($line)) && ($in_paragraph))
		{
			// Handle blank line as a paragraph break
			$line = "</p>";
			$in_paragraph = false;
		}
		elseif (substr($original_line,0,1) != '#')
		{
			// Line contains ordinary text
			if ($in_paragraph)
			{
				$result .= "<br />\n";
			}
			elseif ($list_level == 0)
			{
				$result .= "<p>";
				$in_paragraph = true;
			}
		}

		$result .= "$line\n";
	}
	if ($in_paragraph)
	{
		$result .= "</p>\n";
	}
	return stripslashes($result);
}

//================================================================================
}
//================================================================================
?>
