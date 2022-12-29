<?php
//==============================================================================

if (!function_exists('get_base_table'))
{

//==============================================================================

/*
The first three functions are responsible for scanning through the table
hierarchy from a given table/view back to its base table. Typically there
would only be a single iteration or at the very most two, but the definition
of MAX_TABLE_NESTING_LEVEL provides a 'safety net' to prevent these functions
from running into an infinte look in the case of a data error.
*/
if (!defined('MAX_TABLE_NESTING_LEVEL'))
{
	define('MAX_TABLE_NESTING_LEVEL',5);
}

//==============================================================================
/*
Function get_base_table

This function scans the table hierarchy for a given table/view through to its
origin (i.e. the base table)
*/
//==============================================================================

function get_base_table($table,$db=false)
{
	if ($db === false)
	{
		$db = admin_db_connect();
	}
	for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
	{
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			if (empty($row['parent_table']))
			{
				return $table;
			}
			else
			{
				$table = $row['parent_table'];
			}
		}
		else
		{
			return $table;
		}
	}
	return false;  // This should not occur
}

//==============================================================================
/*
Function get_table_for_field

This function scans the table hierarchy from the given table back to the base
table until it finds a record for a given table field in the table fields table.
*/
//==============================================================================

function get_table_for_field($table,$field,$db=false)
{
	if ($db === false)
	{
		$db = admin_db_connect();
	}
	for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
	{
	  $query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
	  if ($row = mysqli_fetch_assoc($query_result))
	  {
	    $query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND field_name='$field'");
	    if ((mysqli_num_rows($query_result2) > 0) || (empty($row['parent_table'])))
	    {
	      return $table;
	    }
	    else
	    {
	      $table = $row['parent_table'];
	    }
	  }
	}
	return false;  // This should not occur
}

//==============================================================================
/*
Function get_table_for_info_field

This function scans the table hierarchy from the given table back to the base
table until it finds non-empty value for a given table info field.
*/
//==============================================================================

function get_table_for_info_field($table,$info_field,$db=false)
{
	if ($db === false)
	{
		$db = admin_db_connect();
	}
	for ($i=MAX_TABLE_NESTING_LEVEL; $i>0; $i--)
	{
	  $query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
	  if ($row = mysqli_fetch_assoc($query_result))
	  {
	    if ((empty($row['parent_table'])) || (!empty($row[$info_field])))
	    {
	      return $table;
	    }
	    else
	    {
	      $table = $row['parent_table'];
	    }
	  }
	}
	return false;  // This should not occur
}

//==============================================================================
/*
Function page_link_url
*/
//==============================================================================

function page_link_url($page_no,$relationships='')
{
	global $BaseURL, $RelativePath;
	global $PageURLTable,$PageURLListSize,$PageURLSearchString,$PageURLSortField,$PageURLSortOrder;
	$page_offset = $PageURLListSize * ($page_no - 1);
	$url = "$BaseURL/$RelativePath/?-table=$PageURLTable&-startoffset=$page_offset&-listsize=$PageURLListSize";
	if (!empty($PageURLSearchString))
	{
		$search_par = urlencode($PageURLSearchString);
		$url .= "&-search=$search_par";
	}
	if (!empty($PageURLSortField))
	{
		$url .= "&-sortfield=$PageURLSortField";
	}
	if (!empty($PageURLSortOrder))
	{
		$url .= "&-sortorder=$PageURLSortOrder";
	}
	if ($relationships == 'show')
	{
		$url .= "&-showrelationships";
	}
	elseif ($relationships == 'hide')
	{
		$url .= "&-hiderelationships";
	}
	return $url;
}

//==============================================================================
/*
Function generate_grid_styles()
*/
//==============================================================================

function generate_grid_styles($table)
{
	$db = admin_db_connect();
	$grid_coords = array();
	$field_count = 0;
	$auto_count = 0;
	$result = '';
	$cells_used = array();

	$base_table = get_table_for_info_field($table,'grid_columns');
	if ($row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$base_table'")))
	{
		$grid_columns = $row['grid_columns'];
	}

	// Loop through the fields for the given table
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$base_table = get_table_for_field($table,$field_name);

		// Extract and save the grid co-ordinates for the given field.
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name' AND list_mobile=1");
		if ($row2 = mysqli_fetch_assoc($query_result2))
		{
			$grid_coords[$field_name] = $row2['grid_coords'];
			$field_count++;
			if ($grid_coords[$field_name] == 'auto')
			{
				// Co-ordinates set to 'auto' - update the count
				$auto_count++;
			}
			else
			{
				// Custom co-ordinates in use - check that they are valid.
				$row_no = trim(strtok($grid_coords[$field_name],'/'));
				if (!is_numeric($row_no))
				{
					return false;
				}
				else
				{
					$col_no = trim(strtok('/'));
					if (!is_numeric($col_no))
					{
						$col_no = 2;
						$col_span = 1;
					}
					elseif ($col_no == 1)
					{
						return false;
					}
					else
					{
						$col_span = trim(strtok('/'));
						if (!is_numeric($col_span))
						{
							$col_span = 1;
						}
					}
				}

				// Check that fields don't overlap in the layout.
				$end_col = $col_no + $col_span;
				if (!isset($cells_used[$row_no]))
				{
					$cells_used[$row_no] = array();
				}
				for ($i=$col_no; $i<$end_col; $i++)
				{
					if (isset($cells_used[$row_no][$i]))
					{
						return false;
					}
					else
					{
						$cells_used[$row_no][$i] = true;
					}
				}
			}
		}
	}
	$base_table = get_base_table($table);

	if ($auto_count == $field_count)
	{
		/*
		Auto co-ordinates in use. Create styles to place the record fields one per
		row in the grid. The cell containing the select box goes into column 1
		spanning all the rows. The cell containing relationship links goes in a row
		at the bottomn spanning both columns.
		*/
		$result .= "<style>\n";
		if (isset($grid_columns))
		{
			$result .= "div.table-listing {\n";
			$result .= "  grid-template-columns: $grid_columns\n";
			$result .= "}\n";
		}
		$row_no = 1;
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' ORDER BY display_order ASC");
		while ($row = mysqli_fetch_assoc($query_result))
		{
			if (isset($grid_coords[$row['field_name']]))
			{
				$result .= ".field-{$row['field_name']} {\n";
				$result .= "  grid-row: $row_no;\n";
				$result .= "  grid-column: 2;\n";
				$result .= "}\n";
				$row_no++;
			}
		}
		$result .= ".record-select {\n";
		$result .= "  grid-row: 1 / $row_no;\n";
		$result .= "  grid-column: 1;\n";
		$result .= "}\n";
		$result .= ".relationships {\n";
		$result .= "  grid-row: $row_no;\n";
		$result .= "  grid-column: 1 / 3;\n";
		$result .= "}\n";
		$result .= "</style>\n";
		return $result;
	}
	elseif ($auto_count == 0)
	{
		/*
		Custom co-ordinates in use. Create styles to place each record field
		according to its own custom co-ordinates. The cell containing the select box
		goes into column 1 spanning all the rows. The cell containing relationship
		links goes in a row at the bottomn spanning all the columns.
		*/
		$result .= "<style>\n";
		if (isset($grid_columns))
		{
			$result .= "div.table-listing {\n";
			$result .= "  grid-template-columns: $grid_columns\n";
			$result .= "}\n";
		}
		$row_count = 1;
		$col_count = 1;
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' ORDER BY display_order ASC");
		while ($row = mysqli_fetch_assoc($query_result))
		{
			if (isset($grid_coords[$row['field_name']]))
			{
				$row_no = trim(strtok($grid_coords[$row['field_name']],'/'));
				$col_no = trim(strtok('/'));
				if (!is_numeric($col_no))
				{
					$col_no = 2;
					$col_span = 1;
				}
				else
				{
					$col_span = trim(strtok('/'));
					if (!is_numeric($col_span))
					{
						$col_span = 1;
					}
				}
				if ($row_no > $row_count)
				{
					$row_count = $row_no;
				}
				if ($col_no > $col_count)
				{
					$col_count = $col_no;
				}
				$result .= ".field-{$row['field_name']} {\n";
				$result .= "  grid-row: $row_no;\n";
				$col_end = $col_no + $col_span;
				$result .= "  grid-column: $col_no / $col_end;\n";
				$result .= "}\n";
			}
		}
		$result .= ".record-select {\n";
		$row_end = $row_count + 1;
		$result .= "  grid-row: 1 / $row_end;\n";
		$result .= "  grid-column: 1;\n";
		$result .= "}\n";
		$result .= ".relationships {\n";
		$result .= "  grid-row: $row_no;\n";
		$col_end = $col_count + 1;
		$result .= "  grid-column: 1 / $col_end;\n";
		$result .= "}\n";
		$result .= "</style>\n";
		return $result;
	}
	else
	{
		// Mixture of custom and auto co-ordinates (invalid)
		return false;
	}
}

//==============================================================================
/*
Function display_table
*/
//==============================================================================

function display_table($params)
{
	global $BaseURL, $RelativePath, $Location;
	global $search_clause;
	global $WidgetTypes;
	global $DBAdminDir;
	global $display_table;
	$db = admin_db_connect();
	$mode = get_viewing_mode();
	print("<style>\n".file_get_contents("$DBAdminDir/page_link_styles.css")."</style>\n");

	//============================================================================
	// Part 1 - Data Initialisation
	//============================================================================

	// Interpret the URL parameters
	if (!isset($_GET['-table']))
	{
		print("<p>Table parameter missing.</p>\n");
		return;
	}
	$table = $_GET['-table'];
	$base_table = get_base_table($table);
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$base_table'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		if (isset($_GET['-listsize']))
		{
			$list_size = $_GET['-listsize'];
		}
		elseif ((isset($_POST['listsize2'])) && ($_POST['listsize2'] > 0))
		{
			$list_size = $_POST['listsize2'];
		}
		else
		{
			$list_size = $row['list_size'];
		}
		if ($list_size < 30)
		{
			$list_size = 30;
		}
		if ($list_size > 800)
		{
			$list_size = 800;
		}
	}
	else
	{
		print("<p>Table record for <em>$base_table</em> not found.</p>\n");
		return;
	}
	if (((isset($_POST['listsize2'])) &&
	    (($_POST['listsize2']) != ($_POST['listsize'])))
		 || (isset($_GET['-reorder'])))
	{
		/*
		Force the start offset to 0 under any of the following conditions:-
		1. A new list size has been requested.
		2. The listing is being re-ordered by a given field.
		*/
		$start_offset = 0;
	}
	elseif (isset($_GET['-startoffset']))
	{
		$start_offset = $_GET['-startoffset'];
	}
	elseif (isset($_POST['startoffset']))
	{
		$start_offset = $_POST['startoffset'];
	}
	else
	{
		$start_offset = 0;
	}
	if (isset($_GET['-showrelationships']))
	{
		update_session_var('show_relationships',true);
	}
	elseif (isset($_GET['-hiderelationships']))
	{
		update_session_var('show_relationships',false);
	}

	/*
	Set up the display filters (for search and sort) apart from the creation of
	a new search filter, which is done later on when processing a post with a
	search string.
	Also if the '-where' URL parameter is set, which happens if the page is
	invoked via a relationship link, the search filter is used for the associated
	WHERE clause,
	*/
	$lc_search_string = '';
	$sort_field = '';
	$sort_order = '';
	if (isset($_GET['-where']))
	{
		$where_clause = stripslashes($_GET['-where']);
		update_session_var('search_clause',"WHERE $where_clause");
	}
	if (!session_var_is_set('filtered_table'))
	{
		update_session_var('filtered_table','');
	}

	// Initialise the search filter if not set
	if (!session_var_is_set('search_clause'))
	{
		update_session_var('search_clause','');
	}

	if ((isset($_GET['-sortfield'])) && (isset($_GET['-sortorder'])))
	{
		// Apply a sort filter
		$sort_field = $_GET['-sortfield'];
		$sort_order = $_GET['-sortorder'];
		update_session_var('sort_clause',"ORDER BY $sort_field ".strtoupper($sort_order));
	}
	elseif (!session_var_is_set('sort_clause'))
	{
		update_session_var('sort_clause','');
	}
	else
	{
		// Leave the existing sort filter in place.
		$tempstr = str_replace('ORDER BY ','',get_session_var('sort_clause'));
		$sort_field = strtok($tempstr,' ');
		$sort_order = strtolower(strtok(' '));
	}
	update_session_var('filtered_table',$table);

	$display_table = true;
	$form_started = false;

	//============================================================================
	// Part 2 - Processing of Actions
	//============================================================================

	if (isset($_POST['submitted']))
	{
		// Not quite sure yet why we need this, but it seems to prevent problems
		// with multiple submissions.
		$submit_option = $_POST['submitted'];
		unset($_POST['submitted']);

		switch ($submit_option)
		{
			case 'apply_search':
				// Apply new search filter
				update_session_var('search_clause','');
				if (!empty($_POST['search_string']))
				{
					$lc_search_string = strtolower($_POST['search_string']);
					$search_clause = "WHERE";
					$field_processed = false;
					$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
					while ($row = mysqli_fetch_assoc($query_result))
					{
						$field_name = $row['Field'];
						$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
						if ($row2 = mysqli_fetch_assoc($query_result2))
						{
							if (($WidgetTypes[$row2['widget_type']]) && (!$row2['exclude_from_search']))
							{
								if ($field_processed)
								{
									$search_clause .= " OR";
								}
								$field_processed = true;
								$search_clause .= " LOWER($field_name) LIKE '%";
								$search_clause .= addslashes($lc_search_string);
								$search_clause .= "%'";
								update_session_var('search_clause',$search_clause);
							}
						}
					}
				}
				break;

			case 'delete':
				$result = delete_record_set($table);
				break;

			case 'select_update';
				$result = select_update($table,'selection');
				if ($result === true)
				{
					$display_table = false;
					$form_started = true;
				}
				break;

			case 'select_update_all';
				$result = select_update($table,'all');
				if ($result === true)
				{
					$display_table = false;
					$form_started = true;
				}
				break;

			case 'run_update';
				$result = run_update($table,'selection');
				break;

			case 'run_update_all';
			if (isset($_POST['confirm_update_all']))
			{
				$result = run_update($table,'all');
			}
			else
			{
				print("<p class=\"highlight-error\">'Update All' confirmation flag was not set - please try again.</p>\n");
			}
			break;

			case 'select_copy';
				$result = select_copy($table);
				if ($result === true)
				{
					$display_table = false;
					$form_started = true;
				}
				break;

			case 'run_copy';
				$result = run_copy($table);
				break;
		}
	}

	//============================================================================
	// Part 3 - Generation of Page
	//============================================================================

	if ($mode == 'mobile')
	{
		$result = generate_grid_styles($table);
		if ($result == false)
		{
			print("<p class=\"highlight-error\">ERROR - Cannot resolve the grid co-ordinates. Please check the table field definitions for possible errors.</p>\n");
			$display_table = false;
		}
		else
		{
			print($result);
		}
	}
	if (!$display_table)
	{
		print("<div style=\"display:none\">\n");
	}

	// Calculate pagination parameters
	$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause'));
	$table_size = mysqli_num_rows($query_result);
	$page_count = ceil($table_size / $list_size);
	$current_page = floor($start_offset / $list_size +1);

	// Generate the page links
	global $PageURLTable,$PageURLListSize,$PageURLSearchString,$PageURLSortField,$PageURLSortOrder;
	$PageURLTable = $table;
	$PageURLListSize = $list_size;
	$PageURLSearchString = $lc_search_string;
	$PageURLSortField = $sort_field;
	$PageURLSortOrder = $sort_order;
	$page_links = page_links($page_count,$current_page,4,'current-page-link','other-page-link','page_link_url');

	// Determine the access level for the table
	$access_level = get_table_access_level($table);

	if (!$form_started)
	{
		/*
		The target URL of the post is to the same script for the same table. A
		post is only performed for update, copy and delete requests, or to apply a
		search, for all of which the next stage is performed by a second iteration
		of the current script.
		*/
		print("<form method=\"post\" action=\"$BaseURL/$RelativePath/?-table=$table\">\n");
	}

	// Ensure that certain parameters are propagated through any subsquent form submission.
	print("<input type=\"hidden\" name=\"startoffset\" value=\"$start_offset\"/>\n");
	print("<input type=\"hidden\" name=\"listsize\" value=\"$list_size\"/>\n");

	// Output top navigation
	print("<h2>Table $table</h2>");
	print("<p class=\"small\">Found $table_size records");
	print("&nbsp;&nbsp;&nbsp;Showing&nbsp;<input class=\"small\" name=\"listsize2\" value=$list_size size=4>&nbsp;results&nbsp;per&nbsp;page");
	print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Apply\" onClick=\"submitForm(this.form)\"/></p>");
	print("<p>$page_links");
	$query_result = mysqli_query($db,"SELECT * FROM dba_relationships WHERE table_name='$base_table' AND UPPER(query) LIKE 'SELECT%'");
	if (mysqli_num_rows($query_result) > 0)
	{
		// One or more select relationships are defined for the given table
		if (get_session_var('show_relationships'))
		{
			print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"".page_link_url($current_page,'hide')."\">Hide Relationships</a></div>\n");
		}
		else
		{
			print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"".page_link_url($current_page,'show')."\">Show Relationships</a></div>\n");
		}
	}
	print("</p>\n");
	if ($access_level == 'full')
	{
		print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=new&-table=$table\">New&nbsp;Record</a></div>\n");
	}
	print("<div class=\"top-navigation-item\"><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-table=$table&-showall\">Show&nbsp;All</a></div>\n");
	if (isset($params['additional_links']))
	{
		print($params['additional_links']);
	}
  print("<div class=\"top-navigation-item\"><input type=\"text\" size=\"24\" name=\"search_string\"/>");
	print("&nbsp;<input type=\"button\" value=\"Search\" onClick=\"applySearch(this.form)\"/></div>\n");
	print("<div style=\"clear:both\">&nbsp;</div>\n");
	// End of top navigation

	// Determine fields to be processed.
	$fields = array();
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];

		// Determine whether the field is to be displayed in the table listing.
		$tab = get_table_for_field($table,$field_name);
		$query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$tab' AND field_name='$field_name'");
		if ($row3 = mysqli_fetch_assoc($query_result3))
		{
			// Table field found
			if ($row3["list_$mode"] == 1)
			{
				$fields[$field_name] = $row3['display_order'];
			}
		}
	}

	// Construct array for primary key data
	$primary_key = array();
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER BY display_order ASC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$primary_key[$row['field_name']] = '';
	}
	if (count($primary_key) == 0)
	{
		exit("ERROR - no primary key defined for table/view $base_table");
	}

	// Output table header
	if ($mode == 'desktop')
	{
		print("<table class=\"table-listing\">\n");
		print("<tr>\n");
		print("<td class=\"table-listing-header\"><input type=\"checkbox\" name=\"select_all\" onclick=\"checkAll(this)\"></td>");
	}
	else
	{
		print("<div class=\"table-listing\">\n");
		print("<div class=\"table-listing-cell record-select table-listing-header\"><input type=\"checkbox\" name=\"select_$record_offset\" onclick=\"checkAll(this)\"></div> <!-- .table-listing-cell -->");
	}
	foreach ($fields as $f => $ord)
	{
		// Output the field name with a sort link
		if ($sort_field == $f)
		{
			// There is already a sort order in force on the given field.
			if (strtolower($sort_order) == 'asc')
			{
				$new_sort_order = 'desc';
			}
			else
			{
				$new_sort_order = 'asc';
			}
		}
		else
		{
			// Applying sort for first time to new field.
			$new_sort_order = 'asc';
		}
		if ($mode == 'desktop')
		{
			print("<td class=\"table-listing-header\"><a href=\"./?-table=$table&-reorder&-sortfield=$f&-sortorder=$new_sort_order\">");
			print(field_label($table,$f)."</a></td>");
		}
		else
		{
			print("<div class=\"table-listing-cell field-$f table-listing-header\"><a href=\"./?-table=$table&-reorder&-sortfield=$f&-sortorder=$new_sort_order\">");
			print(field_label($table,$f)."</a></div <!-- .table-listing-cell -->");
		}
	}
	if ($mode == 'desktop')
	{
		print("\n</tr>\n");
	}
	else
	{
		print("\n</div> <!-- .table-listing -->\n");
	}  // End of table header

	// Process table records
	$record_offset = $start_offset;
	$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause')." LIMIT $start_offset,$list_size");
	$row_no = 0;
	while ($row = mysqli_fetch_assoc($query_result))
	{
		if ($access_level == 'read-only')
		{
			$record_action = 'view';
		}
		else
		{
			$record_action = 'edit';
		}
		if ($mode == 'desktop')
		{
			print("<tr>\n");
		}
		else
		{
			print("<div class=\"table-listing\">\n");
		}
		if (($row_no % 2) == 1)
		{
			$style = 'table-listing-odd-row';
		}
		else
		{
			$style = 'table-listing-even-row';
		}
		$row_no++;
		foreach ($primary_key as $f => $val)
		{
			$primary_key[$f] = $row[$f];
		}
		if ($mode == 'desktop')
		{
			print("<td class=\"$style\"><input type=\"checkbox\" name=\"select_$record_offset\"");
		}
		else
		{
			print("<div class=\"table-listing-cell record-select $style\"><input type=\"checkbox\" name=\"select_$record_offset\"");
		}
		if ((isset($submit_option)) &&
		    (($submit_option == 'select_update') || ($submit_option == 'select_update_all') || ($submit_option == 'select_copy')) &&
				(isset($_POST["select_$record_offset"])))
		{
			/*
			We are currently displaying a select update/copy screen. The table record
			list is present but invisible with a "display:none" style. We are here
			because the given record was checked in the initial screen and needs to
			be checked again to carry it through the next form submission.
			*/
			print(" checked");
		}
		if ($mode == 'desktop')
		{
			print("></td>");
		}
		else
		{
			print("></div> <!-- .table-listing-cell -->");
		}
		$record_offset++;
		$record_id = encode_record_id($primary_key);
		foreach ($fields as $f => $ord)
		{
			if ($mode == 'desktop')
			{
				print("<td class=\"$style\"><a href=\"$BaseURL/$RelativePath/?-table=$table&-action=$record_action&-recordid=$record_id\">{$row[$f]}</a></td>");
			}
			else
			{
				print("<div class=\"table-listing-cell field-$f $style\"><a href=\"$BaseURL/$RelativePath/?-table=$table&-action=$record_action&-recordid=$record_id\">{$row[$f]}</a></div> <!-- .table-listing-cell -->");
			}
		}
		print("\n");
		if ($mode == 'desktop')
		{
			print("</tr>\n");
		}
		else
		{
			// Mobile mode - </div> is output below (after relationships)
		}

		if (get_session_var('show_relationships'))
		{
			if ($mode == 'desktop')
			{
				$colspan = count($fields) + 1;
				print("<tr><td class=\"$style\" style=\"font-size:0.8em\" colspan=\"$colspan\">");
			}
			else
			{
				print("<div class=\"table-listing-cell relationships $style\" style=\"font-size:0.8em\">");
			}
			$query_result2 = mysqli_query($db,"SELECT * FROM dba_relationships WHERE table_name='$base_table' AND UPPER(query) LIKE 'SELECT%'");
			while ($row2 = mysqli_fetch_assoc($query_result2))
			{
				/*
				Add a link for the given relationship. Scan the relationship query for
				variables of type $<field_name> and replace each such variable with the
				corresponding fields value from the current record.
				*/
				$query = $row2['query'];
				$matches = array();
				while (preg_match(RELATIONSHIP_VARIABLE_MATCH_1,$query,$matches))
				{
					$leading_char = substr($matches[0],0,1);
					$field_name = substr($matches[0],2);
					$value = addslashes($row[$field_name]);
					$value = str_replace('$','\\$',$value);
					$query = str_replace($matches[0],"$leading_char$value",$query);
				}
				$query_result3 = mysqli_query($db,$query);
				if (($query_result3 !== false) && (mysqli_num_rows($query_result3) > 0))
				{
					$uc_query = strtoupper($query);
					$pos1 = strpos($uc_query,' FROM ');
					$pos2 = strpos($uc_query,' WHERE ');
					if ($pos2 !== false)
					{
						$tempstr =  trim(substr($query,$pos1+6));
						$target_table = strtok($tempstr,' ');
						$where_clause = trim(substr($query,$pos2+7));
						$where_par = urlencode($where_clause);
					}
					print("&nbsp;&nbsp;<a href=\"./?-table=$target_table&-where=$where_par\" target=\"_blank\">{$row2['relationship_name']}</a>");
				}
			}
			if ($mode == 'desktop')
			{
				print("</td></tr>\n");
			}
			else
			{
				print("</div> <!-- .table-listing-cell -->\n");
			}
		}  // End of processing relationships

		if ($mode == 'mobile')
		{
			print("</div> <!-- .table-listing -->\n");
		}
		else
		{
			// Desktop mode - </tr> was output above (before relationships)
		}
	}  // End of loop for table records

	if ($mode == 'desktop')
	{
		print("</table>\n");
	}

	print("<p>$page_links</p>\n");
	if ($access_level == 'full')
	{
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update Selected\" onClick=\"selectUpdate(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update All\" onClick=\"selectUpdateAll(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Copy Selected\" onClick=\"selectCopy(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Delete Selected\" onClick=\"confirmDelete(this.form)\"/>");
	}
	if (!$display_table)
	{
		print("</div> <!-- display:none -->\n");
	}
	print("<input type=\"hidden\" name=\"submitted\" id=\"submitted\"/>");
	print("</form>\n");
}

//==============================================================================
/*
Function delete_record_set
*/
//==============================================================================

function delete_record_set($table)
{
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$primary_keys = array();
	$deletions = array();
	foreach ($_POST as $key => $value)
	{
		if (substr($key,0,7) == 'select_')
		{
			$record_offset = substr($key,7);

			// Build up array of deletions indexed by record ID.
			if (is_numeric($record_offset))
			{
				$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause')." LIMIT $record_offset,1");
				if ($row = mysqli_fetch_assoc($query_result))
				{
					$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER by display_order ASC");
					while ($row2 = mysqli_fetch_assoc($query_result2))
					{
						$field_name = $row2['field_name'];
						$primary_keys[$field_name] = $row[$field_name];
					}
					$deletions[$record_offset] = encode_record_id($primary_keys);
				}
			}
		}
	}

	$delete_count = 0;
	foreach ($deletions as $key => $record_id)
	{
		$record = new db_record;
		$record->action = 'delete';
		$record->table = $table;

		// Build the query to select the record
		$primary_keys = fully_decode_record_id($record_id);
		$query = "SELECT * FROM $table WHERE";
		$field_processed = false;
		foreach ($primary_keys as $field => $value)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field='".addslashes($value)."'";
		}

		$query_result = mysqli_query($db,$query);
		if ($row = mysqli_fetch_assoc($query_result))
		{
			// Populate the record fields
			$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' ORDER by display_order ASC");
			while ($row2 = mysqli_fetch_assoc($query_result2))
			{
				$field_name = $row2['field_name'];
				$record->SetField($field_name,$row[$field_name]);
			}
		}

		// Call the delete function on the record
		$result = delete_record($record,$record_id);
		unset($record);
		if ($result == false)
		{
			return;
		}
		else
		{
			$delete_count++;
		}
	}
	print("<p class=\"highlight-success\">$delete_count record(s) deleted.</p>\n");
	return true;
}

//==============================================================================
/*
Function select_update
*/
//==============================================================================

function select_update($table,$option)
{
	global $BaseURL, $RelativePath;
	$db = admin_db_connect();
	$mode = get_viewing_mode();
	$base_table = get_base_table($table);
	if ($option == 'selection')
	{
		$item_found = false;
		foreach ($_POST as $key => $value)
		{
			if (substr($key,0,7) == 'select_')
			{
				$item_found = true;
				break;
			}
		}
		if (!$item_found)
		{
			print("<p class=\"highlight-error\">No records selected.</p>\n");
			return false;
		}
		print("<h2>Update Records</h2>\n");
	}
	elseif ($option == 'all')
	{
		print("<h2>Update All Records</h2>\n");
	}

	print("<form method=\"post\" action=\"$BaseURL/$RelativePath/?-table=$table\">\n");
	if ($option == 'all')
	{
		print("<p><strong>Important</strong> - You are updating all records in the table - please check to confirm&nbsp;&nbsp;<input type=\"checkbox\" name=\"confirm_update_all\"></p>\n");
	}
	$last_display_group = '';
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
		if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] != 'file'))
		{
			$display_group = $row2['display_group'];
			if ($display_group != $last_display_group)
			{
				if (!empty($last_display_group))
				{
					if ($mode == 'desktop')
					{
						print("</table>\n");
					}
					if ($display_group != '-default-')
					{
						print("<strong>$display_group</strong>\n");
					}
				}
				if ($mode == 'desktop')
				{
					print("<table class=\"update-selection\">\n");
					print("<tr><td class=\"update-selection-header\">Select</td>");
					print("<td class=\"update-selection-header\">Field</td>");
					print("<td class=\"update-selection-header\">Value</td></tr>\n");
				}
				$last_display_group = $display_group;
			}
			if ($mode == 'desktop')
			{
				print("<tr>");
				print("<td class=\"update-selection\">");
			}
			else
			{
				print("<div class=\"update-field\">");
				print("<div class=\"update-field-cell update-field-select\">");
			}
			if (($row2['widget_type'] != 'auto-increment') && ($row2['widget_type'] != 'static'))
			{
				print("<input type=\"checkbox\" name=\"select_$field_name\">");
			}
			if ($mode == 'desktop')
			{
				print("</td>");
			}
			else
			{
				print("</div>");
			}
			$label = field_label($table,$field_name);
			if ($mode == 'desktop')
			{
				print("<td class=\"update-selection\">$label</td>");
				print("<td class=\"update-selection\">");
				generate_widget($table,$field_name,false);
				print("</td></tr>\n");
			}
			else
			{
				print("<div class=\"update-field-cell update-field-name\">$label</div>");
				print("<div class=\"update-field-cell update-field-value\">");
				generate_widget($table,$field_name,false);
				print("</div></div>\n");
			}
		}
	}
	if ($mode == 'desktop')
	{
		print("</table>\n");
	}
	print("<p>&nbsp;&nbsp;<input type=\"checkbox\" name=\"show_progress\">&nbsp;Show&nbsp;Progress</p>\n");
	if ($option == 'selection')
	{
		print("&nbsp;&nbsp;<input type=\"button\" value=\"Update\" onClick=\"runUpdate(this.form)\"/>");
	}
	elseif ($option == 'all')
	{
		print("&nbsp;&nbsp;<input type=\"button\" value=\"Update\" onClick=\"runUpdateAll(this.form)\"/>");
	}

	// N.B. Do not generate the "submitted" input tag or the closing </form> tag
	// at this point.
	return true;
}

//==============================================================================
/*
Function run_update
*/
//==============================================================================

function run_update($table,$option)
{
	$_POST = array_map( 'stripslashes_deep', $_POST );
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$primary_keys = array();
	$updates = array();

	// Build up array of updates indexed by record ID.
	if ($option == 'selection')
	{
		foreach ($_POST as $key => $value)
		{
			if (substr($key,0,7) == 'select_')
			{
				$record_offset = substr($key,7);
				if (is_numeric($record_offset))
				{
					$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause'). "LIMIT $record_offset,1");
					if ($row = mysqli_fetch_assoc($query_result))
					{
						$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER by display_order ASC");
						while ($row2 = mysqli_fetch_assoc($query_result2))
						{
							$field_name = $row2['field_name'];
							$primary_keys[$field_name] = $row[$field_name];
						}
						$updates[$record_offset] = encode_record_id($primary_keys);
					}
				}
			}
		}
	}
	elseif ($option == 'all')
	{
		$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause'));
		$record_count = mysqli_num_rows($query_result);
		for ($record_offset=0; $record_offset<$record_count; $record_offset++)
		{
			$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause')." LIMIT $record_offset,1");
			if ($row = mysqli_fetch_assoc($query_result))
			{
				$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER by display_order ASC");
				while ($row2 = mysqli_fetch_assoc($query_result2))
				{
					$field_name = $row2['field_name'];
					$primary_keys[$field_name] = $row[$field_name];
				}
				$updates[$record_offset] = encode_record_id($primary_keys);
			}
		}
	}

  // Count number of fields being updated
	$field_count = 0;
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name' AND is_primary=0");
		if ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if (isset($_POST["select_$field_name"]))
			{
				$field_count++;
			}
		}
	}

	// Main loop to process the updates
	$success_count = 0;
	$failure_count = 0;
	foreach ($updates as $key => $record_id)
	{
		$record = new db_record;
		$record->action = 'update';
		$record->table = $table;

		// Build the query to select the record
		$primary_keys = fully_decode_record_id($record_id);
		$pk_values = '^';
		$query = "SELECT * FROM $table WHERE";
		$field_processed = false;
		foreach ($primary_keys as $field => $value)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field='".addslashes($value)."'";
			$pk_values .= "$value^";
		}
		if (isset($_POST['show_progress']))
		{
			set_time_limit(30);
			print("Processing record $pk_values<br />\n");
		}

		$query_result = mysqli_query($db,$query);
		if ($row = mysqli_fetch_assoc($query_result))
		{
			// Populate the record fields
			$query_result2 = mysqli_query($db,"SHOW COLUMNS FROM $table");
			while ($row2 = mysqli_fetch_assoc($query_result2))
			{
				$field_name = $row2['Field'];
				$query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
				if ($row3 = mysqli_fetch_assoc($query_result3))
				{
					if (isset($_POST["select_$field_name"]))
					{
						// Field is being updated
						if ($row3['widget_type'] == 'checkbox')
						{
							if (isset($_POST["field_$field_name"]))
							{
								$field_value = 1;
							}
							else
							{
								$field_value = 0;
							}
						}
						elseif ($row3['widget_type'] == 'checklist')
						{
							$field_value = '^';
							foreach ($_POST as $key => $value)
							{
								if (strpos($key,"item_$field_name"."___") !== false)
								{
									$item = urldecode(substr($key,strlen("item_$field_name"."___")));
									$field_value .= "$item^";
								}
							}
						}
						else
						{
							$field_value = $_POST["field_$field_name"];
						}
					}
					else
					{
						// Field is not being updated
						$field_value = $row[$field_name];
					}
					$record->SetField($field_name,$field_value);
				}
			}
		}

		// Call the save function on the record
		$result = save_record($record,$record_id,$record_id);
		unset($record);
		if ($result == false)
		{
			$failure_count++;
		}
		else
		{
			$success_count++;
		}
	}
	$alert_message = "$success_count record(s) updated, $failure_count record(s) not updated.";
	if (isset($_POST['show_progress']))
	{
		print("<script>alert(\"$alert_message\")</script>\n");
	}
	else
	{
		print("<p class=\"highlight-success\">$alert_message</p>\n");
	}
	return true;
}

//==============================================================================
/*
Function select_copy
*/
//==============================================================================

function select_copy($table)
{
	global $BaseURL, $RelativePath;
	$db = admin_db_connect();
	$mode = get_viewing_mode();
	$base_table = get_base_table($table);
	$item_found = false;
	foreach ($_POST as $key => $value)
	{
		if (substr($key,0,7) == 'select_')
		{
			$item_found = true;
			break;
		}
	}
	if (!$item_found)
	{
		print("<p class=\"highlight-error\">No records selected.</p>\n");
		return false;
	}
	print("<h2>Copy Records</h2>\n");
	print("<p class=\"small\">* = Primary key field - at least one must be selected (unless there is an auto-increment field).</p>\n");

	print("<form method=\"post\" action=\"$BaseURL/$RelativePath/?-table=$table\">\n");
	$last_display_group = '';
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
		if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] != 'file'))
		{
			$display_group = $row2['display_group'];
			if ($display_group != $last_display_group)
			{
				if (!empty($last_display_group))
				{
					if ($mode == 'desktop')
					{
						print("</table>\n");
					}
					if ($display_group != '-default-')
					{
						print("<strong>$display_group</strong>\n");
					}
				}
				if ($mode == 'desktop')
				{
					print("<table class=\"update-selection\">\n");
					print("<tr><td class=\"update-selection-header\">Select</td>");
					print("<td class=\"update-selection-header\">Field</td>");
					print("<td class=\"update-selection-header\">Value</td></tr>\n");
				}
				$last_display_group = $display_group;
			}
			if ($mode == 'desktop')
			{
				print("<tr>");
				print("<td class=\"update-selection\">");
			}
			else
			{
				print("<div class=\"update-field\">");
				print("<div class=\"update-field-cell update-field-select\">");
			}
			if (($row2['widget_type'] != 'auto-increment') && ($row2['widget_type'] != 'static'))
			{
				print("<input type=\"checkbox\" name=\"select_$field_name\">");
				if ($row2['is_primary'])
				{
					print("&nbsp;*");
				}
			}
			if ($mode == 'desktop')
			{
				print("</td>");
			}
			else
			{
				print("</div>");
			}
			$label = field_label($table,$field_name);
			if ($mode == 'desktop')
			{
				print("<td class=\"update-selection\">$label</td>");
				print("<td class=\"update-selection\">");
				generate_widget($table,$field_name,false);
				print("</td></tr>\n");
			}
			else
			{
				print("<div class=\"update-field-cell update-field-name\">$label</div>");
				print("<div class=\"update-field-cell update-field-value\">");
				generate_widget($table,$field_name,false);
				print("</div></div>\n");
			}
		}
	}
	if ($mode == 'desktop')
	{
		print("</table>\n");
	}
	print("<p>&nbsp;&nbsp;<input type=\"checkbox\" name=\"show_progress\">&nbsp;Show&nbsp;Progress</p>\n");
	print("&nbsp;&nbsp;<input type=\"button\" value=\"Copy\" onClick=\"runCopy(this.form)\"/>");

	// N.B. Do not generate the "submitted" input tag or the closing </form> tag
	// at this point.
	return true;
}

//==============================================================================
/*
Function run_copy
*/
//==============================================================================

function run_copy($table)
{
	$_POST = array_map( 'stripslashes_deep', $_POST );
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$primary_keys = array();
	$updates = array();

	// Build up array of updates indexed by record ID.
	foreach ($_POST as $key => $value)
	{
		if (substr($key,0,7) == 'select_')
		{
			$record_offset = substr($key,7);
			if (is_numeric($record_offset))
			{
				$query_result = mysqli_query($db,"SELECT * FROM $table ".get_session_var('search_clause').' '.get_session_var('sort_clause')." LIMIT $record_offset,1");
				if ($row = mysqli_fetch_assoc($query_result))
				{
					$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER by display_order ASC");
					while ($row2 = mysqli_fetch_assoc($query_result2))
					{
						$field_name = $row2['field_name'];
						$primary_keys[$field_name] = $row[$field_name];
					}
					$updates[$record_offset] = encode_record_id($primary_keys);
				}
			}
		}
	}

	// Check the number of primary key fields being updated
	$primary_key_count = 0;
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name' AND is_primary=1");
		if (($row2 = mysqli_fetch_assoc($query_result2)) &&
		    ((isset($_POST["select_$field_name"])) || ($row2['widget_type'] == 'auto-increment')))
		{
			$primary_key_count++;
		}
	}
	if ($primary_key_count == 0)
	{
		print("<p class=\"highlight-error\">No primary key field selected<p>\n");
		return false;
	}

	// Main loop to process the updates
	$success_count = 0;
	$failure_count = 0;
	foreach ($updates as $key => $record_id)
	{
		$record = new db_record;
		$record->action = 'copy';
		$record->table = $table;

		// Build the query to select the record
		$primary_keys = fully_decode_record_id($record_id);
		$query = "SELECT * FROM $table WHERE";
		$field_processed = false;
		foreach ($primary_keys as $field => $value)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field='".addslashes($value)."'";
			$pk_values .= "$value^";
		}
		if (isset($_POST['show_progress']))
		{
			set_time_limit(30);
			print("Processing record $pk_values<br />\n");
		}

		$query_result = mysqli_query($db,$query);
		if ($row = mysqli_fetch_assoc($query_result))
		{
			// Populate the record fields
			$query_result2 = mysqli_query($db,"SHOW COLUMNS FROM $table");
			while ($row2 = mysqli_fetch_assoc($query_result2))
			{
				$field_name = $row2['Field'];
				$query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
				if ($row3 = mysqli_fetch_assoc($query_result3))
				{
					if (isset($_POST["select_$field_name"]))
					{
						// Field is being updated
						if ($row3['widget_type'] == 'checkbox')
						{
							if (isset($_POST["field_$field_name"]))
							{
								$field_value = 1;
							}
							else
							{
								$field_value = 0;
							}
						}
						else
						{
							$field_value = $_POST["field_$field_name"];
						}
					}
					else
					{
						// Field is not being updated
						$field_value = $row[$field_name];
					}
					$record->SetField($field_name,$field_value);
				}
			}
		}

		// Call the save function on the record
		$result = save_record($record,$record_id,$record_id);
		unset($record);
		if ($result == false)
		{
			$failure_count++;
		}
		else
		{
			$success_count++;
		}
	}
	$alert_message = "$success_count record(s) copied, $failure_count record(s) not copied.";
	if (isset($_POST['show_progress']))
	{
		print("<script>alert(\"$alert_message\")</script>\n");
	}
	else
	{
		print("<p class=\"highlight-success\">$alert_message</p>\n");
	}
	return true;
}

//==============================================================================
/*
Function renumber_records

This function renumbers the records of a given table in increments of 10.
*/
//==============================================================================

function renumber_records($table)
{
	$db = admin_db_connect();
	set_time_limit(30);
	$table = get_table_for_info_field($table,'seq_no_field');
	$row = mysqli_fetch_assoc(mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'"));
	if ((isset($row['seq_no_field'])) && (!empty($row['seq_no_field'])) && ($row['renumber_enabled']))
	{
		// Set up basic query string according to sort method
		if (!empty($row['sort_1_field']))
		{
			$select_query = "SELECT * FROM $table ORDER BY {$row['sort_1_field']},{$row['seq_no_field']}";
			mysqli_query($db,"ALTER TABLE $table ORDER BY {$row['sort_1_field']},{$row['seq_no_field']}");
			$level_1_sort = true;
		}
		else
		{
			$select_query = "SELECT * FROM $table ORDER BY {$row['seq_no_field']}";
			mysqli_query($db,"ALTER TABLE $table ORDER BY {$row['seq_no_field']}");
			$row['seq_method'] = 'continuous';   // Force continuous method if no first-level sort
			$level_1_sort = false;
		}

		// Renumber records to temporary range (outside existing range)
		$query_result = mysqli_query($db,"SELECT * FROM $table");
		$record_count = mysqli_num_rows($query_result);
		$query_result2 = mysqli_query($db,"SELECT * FROM $table ORDER BY {$row['seq_no_field']} DESC LIMIT 1");
		if ($row2 = mysqli_fetch_assoc($query_result2))
		{
			$max_rec_id = $row2[$row['seq_no_field']];
		}
		else
		{
			$max_rec_id = 0;
		}
		$temp_rec_id = $max_rec_id + 10;
		$query_result2 = mysqli_query($db,$select_query);
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if ($level_1_sort)
			{
				mysqli_query($db,"UPDATE $table SET {$row['seq_no_field']}=$temp_rec_id WHERE {$row['sort_1_field']}='{$row2[$row['sort_1_field']]}' AND {$row['seq_no_field']}={$row2[$row['seq_no_field']]}");
			}
			else
			{
				mysqli_query($db,"UPDATE $table SET {$row['seq_no_field']}=$temp_rec_id WHERE {$row['seq_no_field']}={$row2[$row['seq_no_field']]}");
			}
			$temp_rec_id += 10;
		}

		// Renumber records to final values
		$new_id = 0;
		$first_sort_prev_value = '';
		$query_result2 = mysqli_query($db,$select_query);
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			if (($row['seq_method'] == 'repeat') && ($row2[$row['sort_1_field']] != $first_sort_prev_value))
			{
				$new_id = 10;
			}
			else
			{
				$new_id += 10;
			}
			mysqli_query($db,"UPDATE $table SET {$row['seq_no_field']}=$new_id WHERE {$row['seq_no_field']}={$row2[$row['seq_no_field']]}");
			if ($row['seq_method'] == 'repeat')
			{
				$first_sort_prev_value = $row2[$row['sort_1_field']];
			}
		}
		print("<p>Records renumbered for table <i>$table</i>.</p>\n");
	}
}

//==============================================================================
}
//==============================================================================
?>
