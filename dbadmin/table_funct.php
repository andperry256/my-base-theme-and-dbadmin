<?php
//==============================================================================
if (!function_exists('get_base_table'))
{
//==============================================================================
/*
Function get_base_table
*/
//==============================================================================

function get_base_table($table)
{
	$db = admin_db_connect();
	/*
	  In practice the following loop should execute 2 times at the most. It is
		controlled by a counter to prevent infinite loop execution in the case of
		data error.
	*/
	for ($i=5; $i>0; $i--)
	{
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			if (empty($row['parent_table']))
			{
				break;
			}
			else
			{
				$table = $row['parent_table'];
			}
		}
	}
	return ($table);
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
	$db = admin_db_connect();

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

	/*
	Set up the display filters (for search and sort) apart from the creation of
	a new search filter, which is done later on when processing a post with a 
	search string.
	*/
	if (!isset($_SESSION['filtered_table']))
	{
		$_SESSION['filtered_table'] = '';
	}
	if ((isset($_GET['-showall'])) || ($table != $_SESSION['filtered_table']))
	{
		// Clear all filters
		$_SESSION['search_clause'] = '';
		$_SESSION['sort_clause'] = '';
	}
	else
	{
		// Initialise the search filter if not set
		if (!isset($_SESSION['search_clause']))
		{
			$_SESSION['search_clause'] = '';
		}

		if ((isset($_GET['-sortfield'])) && (isset($_GET['-sortorder'])))
		{
			// Apply a sort filter
			$sort_field = $_GET['-sortfield'];
			$sort_order = $_GET['-sortorder'];
			$_SESSION['sort_clause'] = "ORDER BY $sort_field ".strtoupper($sort_order);
		}
		elseif (!isset($_SESSION['sort_clause']))
		{
			$_SESSION['sort_clause'] = '';
		}
		else
		{
			// Leave the existing sort filter in place.
			$tempstr = str_replace('ORDER BY ','',$_SESSION['sort_clause']);
			$sort_field = strtok($tempstr,' ');
			$sort_order = strtolower(strtok(' '));
		}
	}
	$_SESSION['filtered_table'] = $table;

	$display_table = true;
	$form_started = false;
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
				$_SESSION['search_clause'] = '';
				if (!empty($_POST['search_string']))
				{
					$lc_search_string = strtolower($_POST['search_string']);
					$_SESSION['search_clause'] = "WHERE";
					$field_processed = false;
					$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
					while ($row = mysqli_fetch_assoc($query_result))
					{
						$field_name = $row['Field'];
						$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
						if ($row2 = mysqli_fetch_assoc($query_result2))
						{
							if ($WidgetTypes[$row2['widget_type']])
							{
								if ($field_processed)
								{
									$_SESSION['search_clause'] .= " OR";
								}
								$field_processed = true;
								$_SESSION['search_clause'] .= " LOWER($field_name) LIKE '%";
								$_SESSION['search_clause'] .= addslashes($lc_search_string);
								$_SESSION['search_clause'] .= "%'";
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

	// Calculate pagination parameters
	$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']}");
	$table_size = mysqli_num_rows($query_result);
	$page_count = ceil($table_size / $list_size);
	$current_page = floor($start_offset / $list_size +1);

	if (!$display_table)
	{
		print("<div style=\"display:none\">\n");
	}

	// Generate the page links
	$page_links = '';
	$add_pars = '';
	if (!empty($search_string))
	{
		$search_par = urlencode($search_string);
		$add_pars .= "&-search=$search_par";
	}
	if (!empty($sort_field))
	{
		$add_pars .= "&-sortfield=$sort_field";
	}
	if (!empty($sort_order))
	{
		$add_pars .= "&-sortorder=$sort_order";
	}
	if ($page_count > 1)
	{
		if ($current_page == 1)
		{
			$page_links .= "<span style=\"color:silver\">Prev</span>";
		}
		else
		{
			$offset = $start_offset - $list_size;
			$page_links .= "<a href=\"$BaseURL/$RelativePath/?-table=$table&-startoffset=$offset&-listsize=$list_size$add_pars\">Prev</a>";
		}
		for ($page = 1; $page <= $page_count; $page++)
		{
			$page_links .= " |&nbsp;";
			if ($page == $current_page)
			{
				$page_links .= "<span style=\"color:silver\">$page</span>";
			}
			else
			{
				$page_offset = ($page - 1) * $list_size;
				$page_links .= "<a href=\"$BaseURL/$RelativePath/?-table=$table&-startoffset=$page_offset&-listsize=$list_size$add_pars\">$page</a>";
			}
		}
		if ($current_page == $page_count)
		{
			$page_links .= " |&nbsp;<span style=\"color:silver\">Next</span>";
		}
		else
		{
			$offset = $start_offset + $list_size;
			$page_links .= " |&nbsp;<a href=\"$BaseURL/$RelativePath/?-table=$table&-startoffset=$offset&-listsize=$list_size$add_pars\">Next</a>";
		}
	}

	// Determine the access level for the table
	$access_level = get_table_access_level($table);

	if (!$form_started)
	{
		print("<form method=\"post\" action=\"$BaseURL/$RelativePath/?-table=$table\">\n");
	}

	// Ensure that certain parameters are propagated through any subsquent form submission.
	if (!empty($search_string))
	{
		print("<input type=\"hidden\" name=\"search_string2\" value=\"$search_string\"/>\n");
	}
	print("<input type=\"hidden\" name=\"startoffset\" value=\"$start_offset\"/>\n");
	print("<input type=\"hidden\" name=\"listsize\" value=\"$list_size\"/>\n");

	// Output top navigation
	print("<p class=\"small\">Found $table_size records");
	print("&nbsp;&nbsp;&nbsp;Showing&nbsp;<input class=\"small\" name=\"listsize2\" value=$list_size size=4>&nbsp;results&nbsp;per&nbsp;page");
	print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Apply\" onClick=\"submitForm(this.form)\"/>");
	print("<br/>$page_links");
	print("<br />\n");
	print("<table class=\"table-top-navigation\"><tr>\n");
	if ($access_level == 'full')
	{
		print("<td><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=new&-table=$table\">New&nbsp;Record</a></td>");
	}
	print("<td><a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-table=$table&-showall\">Show&nbsp;All</a></td>");
	if (isset($params['additional_links']))
	{
		print($params['additional_links']);
	}
	print("<td><input type=\"text\" size=\"24\" name=\"search_string\"/>");
	print("&nbsp;<input type=\"button\" value=\"Search\" onClick=\"applySearch(this.form)\"/></td>");
	print("</tr></table>\n");

	// Determine fields to be processed.
	$fields = array();
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		if (isset($params['mode']))
		{
			$mode = $params['mode'];
		}
		else
		{
			$mode = 'desktop';
		}

		/*
			Determine whether the field is to be displayed in the table listing.
			Search for a table field record going from the current table back to the
			base table.
		*/
		$tab = $table;
		for ($i=5; $i>0; $i--)
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$tab'");
			if ($row2 = mysqli_fetch_assoc($query_result2))
			{
				$query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$tab' AND field_name='$field_name'");
				if ($row3 = mysqli_fetch_assoc($query_result3))
				{
					// Table field found
					if ($row3["list_$mode"] == 1)
					{
						$fields[$field_name] = $row3['display_order'];
					}
					break;
				}
				elseif (empty($row2['parent_table']))
				{
					// This should not normally occur as the previous condition should
					// have been met if the base table has been reached.
					break;
				}
				else
				{
					$tab = $row2['parent_table'];
				}
			}
		}
	}

	// Construct array for primary key data
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER BY display_order ASC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$primary_key[$row['field_name']] = '';
	}

	// Output table header
	print("<table class=\"table-listing\">\n");
	print("<tr>\n");
	print("<td class=\"table-listing-header\"><input type=\"checkbox\" name=\"select_all\"  onclick=\"checkAll(this)\"></td>");
	foreach ($fields as $f => $ord)
	{
		if ($sort_field == $f)
		{
			if (strtolower($sort_order) == 'asc')
			{
				$new_sort_order = 'desc';
			}
			else
			{
				$new_sort_order = 'asc';
			}
		}
		elseif (!empty($sort_field))
		{
			// There is currently a custom sort in use on a differen field.
			$new_sort_order = 'asc';
		}
		else
		{
			// There is no custom sort currently in use.
			$new_sort_order = 'desc';
		}
		print("<td class=\"table-listing-header\"><a href=\"./?-table=$table&-reorder&-sortfield=$f&-sortorder=$new_sort_order\">");
		print(field_label($table,$f)."</a></td>");
	}
	print("\n</tr>\n");

	// Process table records
	$record_offset = $start_offset;
	$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']} LIMIT $start_offset,$list_size");
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
		print("<tr>\n");
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
		print("<td class=\"$style\"><input type=\"checkbox\" name=\"select_$record_offset\"");
		if ((isset($submit_option)) &&
		    (($submit_option == 'select_update') || ($submit_option == 'select_update_all') || ($submit_option == 'select_copy')) &&
				(isset($_POST["select_$record_offset"])))
		{
			// We are currently processing a select update/copy screen and need to
			// preserve the record selection for the next form submission.
			print(" checked");
		}
		print("></td>");
		$record_offset++;
		$record_id = encode_record_id($primary_key);
		foreach ($fields as $f => $ord)
		{
			print("<td class=\"$style\"><a href=\"$BaseURL/$RelativePath/?-table=$table&-action=$record_action&-recordid=$record_id\">{$row[$f]}</a></td>");
		}
		print("\n</tr>\n");
	}

	print("</table>\n");
	print("$page_links<br /><br />");
	if ($access_level == 'full')
	{
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update Selected\" onClick=\"selectUpdate(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Update All\" onClick=\"selectUpdateAll(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Copy Selected\" onClick=\"selectCopy(this.form)\"/>");
		print("&nbsp;&nbsp;&nbsp;<input type=\"button\" value=\"Delete Selected\" onClick=\"confirmDelete(this.form)\"/>");
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
			$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']} LIMIT $record_offset,1");
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
					print("</table>\n");
					if ($display_group != '-default-')
					{
						print("<strong>$display_group</strong>\n");
					}
				}
				print("<table class=\"update-selection\">\n");
				print("<tr><td class=\"update-selection-header\">Select</td><td class=\"update-selection-header\">Field</td><td class=\"update-selection-header\">Value</td></tr>\n");
				$last_display_group = $display_group;
			}
			print("<tr>");
			print("<td class=\"update-selection\"><input type=\"checkbox\" name=\"select_$field_name\"></td>");
			$label = field_label($table,$field_name);
			print("<td class=\"update-selection\">$label</td>");
			print("<td class=\"update-selection\">");
			generate_widget($table,$field_name,false);
			print("</td><tr>\n");
		}
	}
	print("</table>\n");
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
				$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']} LIMIT $record_offset,1");
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
	elseif ($option == 'all')
	{
		$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']}");
		$record_count = mysqli_num_rows($query_result);
		for ($record_offset=0; $record_offset<$record_count; $record_offset++)
		{
			$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']} LIMIT $record_offset,1");
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
	print("<p class=\"highlight-success\">$success_count record(s) updated, $failure_count record(s) not updated.</p>\n");
	if (!$display_table)
	{
		print("</div>\n");
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
	print("<p class=\"small\">* = Primary key field - at least one must be selected.</p>\n");

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
					print("</table>\n");
					if ($display_group != '-default-')
					{
						print("<strong>$display_group</strong>\n");
					}
				}
				print("<table class=\"update-selection\">\n");
				print("<tr><td class=\"update-selection-header\">Select</td><td class=\"update-selection-header\">Field</td><td class=\"update-selection-header\">Value</td></tr>\n");
				$last_display_group = $display_group;
			}
			print("<tr>");
			print("<td class=\"update-selection\"><input type=\"checkbox\" name=\"select_$field_name\">");
			if ($row2['is_primary'])
			{
				print("&nbsp;*");
			}
			print("</td>");
			$label = field_label($table,$field_name);
			print("<td class=\"update-selection\">$label</td>");
			print("<td class=\"update-selection\">");
			generate_widget($table,$field_name,false);
			print("</td><tr>\n");
		}
	}
	print("</table>\n");
	print("&nbsp;&nbsp;<input type=\"button\" value=\"Update\" onClick=\"runCopy(this.form)\"/>");

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
			$query_result = mysqli_query($db,"SELECT * FROM $table {$_SESSION['search_clause']} {$_SESSION['sort_clause']} LIMIT $record_offset,1");
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

  // Check the number of primary key fields being updated
	$primary_key_count = 0;
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name' AND is_primary=1");
		if (($row2 = mysqli_fetch_assoc($query_result2)) && (isset($_POST["select_$field_name"])))
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
	print("<p class=\"highlight-success\">$success_count record(s) copied, $failure_count record(s) not copied.</p>\n");
	if (!$display_table)
	{
		print("</div>\n");
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

	// Search for sequencing information in table dba_table_info, starting with
	// the table itself and working back to the base table.
	for ($i=5; $i>0; $i--)
	{
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			if ((empty($row['parent_table'])) || (!empty($row['seq_no_field'])))
			{
				break;
			}
			else
			{
				$table = $row['parent_table'];
			}
		}
	}

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
/*
Function export_table
*/
//==============================================================================

function export_table($table='')
{
	global $TableExportDir;
	$db = admin_db_connect();
	if (empty($table))
	{
		print("<h1>Export Table to CSV</h1>\n");
	}
	if ((empty($table)) && (isset($_POST['submitted'])) && (!empty($_POST['table'])))
	{
		$table = $_POST['table'];
	}

	if (!empty($table))
	{
		$pk_fields = '';
		$field_added = false;
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND is_primary=1 ORDER BY display_order ASC");
		if (mysqli_num_rows($query_result) == 0)
		{
			print("<p class=\"highlight-error\">ERROR - table <em>$table</em> not found.</p>\n");
			return;
		}
		while ($row = mysqli_fetch_assoc($query_result))
		{
			if ($field_added)
			{
				$pk_fields .= ',';
			}
			$pk_fields .= ($row['field_name']);
			$field_added = true;
		}
		$order_clause = "$pk_fields ASC";
		if (isset($TableExportDir))
		{
			if ((isset($TableExportRootDir)) && (!is_dir($TableExportRootDir)))
			{
				mkdir($TableExportRootDir,0755);
			}
			if (!is_dir($TableExportDir))
			{
				mkdir($TableExportDir,0755);
			}
			if (is_dir($TableExportDir))
			{
				export_table_to_csv("$TableExportDir/table_$table.csv",$db,$table,'','long','',$order_clause);
				print("<p>Table <em>$table</em> exported to <em>$TableExportDir/table_$table.csv</em></p>\n");
				return;
			}
			else
			{
				print("<p class=\"highlight-error\">ERROR - Unable to create export directory.</p>\n");
				return;
			}
		}
		else
		{
			print("<p class=\"highlight-error\">ERROR - Export directory not defined.</p>\n");
			return;
		}
	}
	else
	{
		print("<p></p>\n");
		print("<form method=\"post\">\n");
		print("<table cellpadding=\"8\">\n");
		print("<tr><td>Table:</td><td><select name=\"table\">\n");
		print("<option value=\"\">Please select...</option>\n");
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info ORDER BY table_name ASC");
		while ($row = mysqli_fetch_assoc($query_result))
		{
			print("<option value=\"{$row['table_name']}\">{$row['table_name']}</option>\n");
		}
		print("</select>\n</td></tr>");
		print("<tr><td colspan=\"2\"><input value=\"Export\" type=\"submit\"></td></tr>\n");
		print("</table>\n");
		print("<input type=\"hidden\" name=\"submitted\" value=\"TRUE\" />\n");
		print("</form>\n");
	}
}

//==============================================================================
/*
Function export_multiple_tables
*/
//==============================================================================

function export_multiple_tables()
{
	print("<h1>Export Tables</h1>\n");
	$db = admin_db_connect();
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE auto_dump=1 ORDER BY table_name ASC");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		export_table($row['table_name']);
	}
	print("<p>Operation completed.</p>\n");
}

//==============================================================================
}
//==============================================================================
?>
