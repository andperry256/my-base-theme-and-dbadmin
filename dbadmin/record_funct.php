<?php
//==============================================================================
if (!function_exists('field_label'))
{
//==============================================================================
/*
Function field_label
*/
//==============================================================================

function field_label($table,$field)
{
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field'");
	if (($row=mysqli_fetch_assoc($query_result)) && (!empty($row['alt_label'])))
	{
		return $row['alt_label'];
	}
	$field = str_replace('-',' ',$field);
	$field = str_replace('_',' ',$field);
	$field = ucwords($field);
	return $field;
}

//==============================================================================
/*
Function check_field_status
*/
//==============================================================================

function check_field_status($table,$field,$value)
{
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$value = trim($value);
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$widget_type = $row['widget_type'];
		if (is_numeric($value))
		{
			return true;
		}
		elseif (($widget_type == 'input-num') && (!empty($value)))
		{
			return report_error("Attempt to set numeric field <em>$field</em> to a non-numeric value.");
		}
		elseif (($row['required']) && (empty($value)))
		{
			return report_error("Field <em>$field</em> is required but not set.");
		}
	}
	return true;
}

//==============================================================================
/*
Function generate_widget
*/
//==============================================================================

function generate_widget($table,$field_name,$field_value)
{
	global $BaseDir, $BaseURL;
	$db = admin_db_connect();
	/*
	Although in the vast majority of cases, the widget information will come from
	the base table for the given table, we nevertheless need to work backwards
	from the table itself in case there are any overriding definitions.
	*/
	$base_table = $table;
	for ($i=5; $i>0; $i--)
	{
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$base_table'");
		if ($row = mysqli_fetch_assoc($query_result))
		{
			$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
			if ((mysqli_num_rows($query_result2) > 0) || (empty($row['parent_table'])))
			{
				break;
			}
			else
			{
				$base_table = $row['parent_table'];
			}
		}
	}

	if ($field_value === false)
	{
			// No field value - indicates a new record. Find default.
			$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table where Field='$field_name'");
			if ($row = mysqli_fetch_assoc($query_result))
			{
					$field_value = $row['Default'];
			}
			else
			{
				return '';  // This should not occur
			}
	}
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
	if ($row=mysqli_fetch_assoc($query_result))
	{
		switch ($row['widget_type'])
		{
			case 'date':
				print("<input type=\"date\" name=\"field_$field_name\" value=\"$field_value\">");
				break;

			case 'input-text':
				print("<input type=\"text\" name=\"field_$field_name\" value=\"$field_value\" size=\"64\"");
				if ((!empty($row['vocab_table'])) && (!empty($row['vocab_field'])))
				{
					print("list=\"list_$field_name\">");
					print("<datalist id=\"list_$field_name\">\n");
					$vocab_table = $row['vocab_table'];
					$vocab_field = $row['vocab_field'];
					$query_result2 = mysqli_query($db,"SELECT $vocab_field FROM $vocab_table ORDER BY $vocab_field ASC");
					while ($row2 = mysqli_fetch_assoc($query_result2))
					{
						print("<option value=\"{$row2[$vocab_field]}\"></option>\n");
					}
					print("</datalist>");
				}
				else
				{
					print(">");
				}
				break;

			case 'input-num':
				print("<input type=\"text\" name=\"field_$field_name\" size=\"7\" value=\"$field_value\">");
				break;

			case 'password':
				print("<input type=\"password\" name=\"field_$field_name\" value=\"$field_value\">");
				break;

			case 'enum':
				print("<select name=\"field_$field_name\">\n");
				print("<option value=\"\">Please select ...</option>\n");
				$query_result2 = mysqli_query($db,"SHOW COLUMNS FROM $table where Field='$field_name' AND Type LIKE 'enum(%'");
				if ($row2 = mysqli_fetch_assoc($query_result2))
				{
						$options = substr($row2['Type'],5);
						$options = rtrim($options,')');
						$options = str_replace("'","",$options);
						$tok = strtok($options,",");
						while ($tok !== false)
						{
							print("<option value=\"$tok\"");
							if ($tok == $field_value)
							{
								print(" selected");
							}
							print(">$tok</option>\n");
							$tok = strtok(",");
						}
				}
				print("</select>");
				break;

			case 'select':
				print("<select name=\"field_$field_name\">\n");
				print("<option value=\"\">Please select ...</option>\n");
				$vocab_table = $row['vocab_table'];
				$vocab_field = $row['vocab_field'];
				$query_result2 = mysqli_query($db,"SELECT $vocab_field FROM $vocab_table ORDER BY $vocab_field ASC");
				while ($row2 = mysqli_fetch_assoc($query_result2))
				{
					print("<option value=\"{$row2[$vocab_field]}\"");
					if ($row2[$vocab_field] == $field_value)
					{
						print(" selected");
					}
					print(">{$row2[$vocab_field]}</option>\n");
				}
				print("</select>");
				break;

			case 'textarea':
				print("<textarea  name=\"field_$field_name\" rows=\"6\" cols=\"64\">$field_value</textarea>\n");
				break;

			case 'checkbox':
				print("<input type=\"checkbox\" name=\"field_$field_name\"");
				if ($field_value)
				{
					print(" checked");
				}
				print(">");
				break;

			case 'auto-increment';
				print("AI [$field_value]");
				print("<input type=\"hidden\" name=\"field_$field_name\" value=\"$field_value\">");
				break;

			case 'static';
				print("$field_value");
				print("<input type=\"hidden\" name=\"field_$field_name\" value=\"$field_value\">");
				break;

			case 'file':
				print("$field_value<br />");
				print("<input type=\"file\" name=\"field_$field_name\"");
				if (!empty($row['allowed_filetypes']))
				{
					print(" accept=\"{$row['allowed_filetypes']}\"><br /><span class=\"small\">Allowed types:-&nbsp;&nbsp;{$row['allowed_filetypes']}</span><br />");
				}
				else
				{
					print("><br />");
				}
				print("<input type=\"checkbox\" name=\"overwrite_$field_name\">&nbsp;Allow overwrite");
				$tempstr = strrev($field_value);
				$fileext = strrev(strtok($tempstr,'.'));
				if (($fileext == 'gif') || ($fileext == 'jpg') || ($fileext == 'jpeg') || ($fileext == 'png'))
				{
					$file_path = "$BaseDir/{$row['relative_path']}/$field_value";
					if (is_file($file_path))
					{
						$file_url = "$BaseURL/{$row['relative_path']}/$field_value";
						print("<br /><img src=\"$file_url\" class=\"widget-image\" /><br />\n");
					}
				}
				break;

			case 'hidden':
				print("******");
				break;
		}
	}
}

//==============================================================================
/*
Function handle_file_widget_before_save
*/
//==============================================================================

function handle_file_widget_before_save(&$record,$field)
{
	global $BaseDir;
	$db = admin_db_connect();
	$table = $record->table;
	$base_table = get_base_table($table);
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND field_name='$field'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$filename = basename($_FILES["field_$field"]['name']);
		if (!empty($filename))
		{
			$target_file = "$BaseDir/{$row['relative_path']}/$filename";
			if ((is_file($target_file)) && (!isset($_POST["overwrite_$field"])))
			{
				return report_error("File <em>$filename</em> already exists and <em>overwrite</em> option not selected.");
			}
		}
	}
	$record->SetField($field,$filename);

	/*
	// MAKE SURE THAT THE FOLLOWING CODE IS NOW REDUNDANT
	// Copy the stored filename into the record
	$field_processed = false;
	$query = "SELECT * FROM $table WHERE";
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$pk_field = $row['field_name'];
		$pk_value = $record->FieldVal($pk_field);
		if ($field_processed)
		{
			$query .= " AND";
		}
		$field_processed = true;
		$query .= " $pk_field='".addslashes($pk_value)."'";
	}
	$query_result = mysqli_query($db,$query);
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$record->SetField($field,$row[$field]);
	}
	*/
}

//==============================================================================
/*
Function handle_file_widget_after_save
*/
//==============================================================================

function handle_file_widget_after_save($record,$field)
{
	global $BaseDir;
	$db = admin_db_connect();
	$table = $record->table;
	$base_table = get_base_table($table);

	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$table' AND field_name='$field'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$filename = basename($_FILES["field_$field"]['name']);
		if (!empty($filename))
		{
			$target_file = "$BaseDir/{$row['relative_path']}/$filename";
			if (is_file($target_file))
			{
				// Delete any existing file of the same name. The 'allow overwrite' option
				// must have been selected by the user in order to get to this point.
				unlink($target_file);
				if (is_file($target_file))
				{
					return report_error("Unable to delete existing file <em>$filename</em>.");
				}
			}
			// Copy the file to the destination
			$result = move_uploaded_file($_FILES["field_$field"]['tmp_name'],$target_file);
			if ($result === false)
			{
				return report_error("File <em>$filename</em> could not be uploaded.");
			}

			// Update the record field to contain the filename
			$field_processed = false;
			$query = "UPDATE $table SET $field='$filename' WHERE";
			$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1");
			while ($row = mysqli_fetch_assoc($query_result))
			{
				$pk_field = $row['field_name'];
				$pk_value = $record->FieldVal($pk_field);
				if ($field_processed)
				{
					$query .= " AND";
				}
				$field_processed = true;
				$query .= " $pk_field='".addslashes($pk_value)."'";
			}
			mysqli_query($db,$query);
		}
	}
}

//==============================================================================
/*
Function report_error
*/
//==============================================================================

function report_error($message)
{
	$_SESSION['error_message'] = $message;
	return false;
}

//==============================================================================
/*
Function save_record
*/
//==============================================================================

function save_record($record,$old_record_id,$new_record_id)
{
	global $CustomPagesPath, $RelativePath;
	$action = $record->action;
	$table = $record->table;
	global $CustomPagesPath, $RelativePath;
	$db = admin_db_connect();
	$base_table = get_base_table($table);
	$old_primary_keys = fully_decode_record_id($old_record_id);
	$new_primary_keys = fully_decode_record_id($new_record_id);
	foreach($new_primary_keys as $key => $value)
	{
		if (!isset($old_primary_keys[$key]))
		{
			$old_primary_keys[$key] = '';
		}
	}
	$record->SaveOldPKs($old_primary_keys);

	// Generate the strings for all the record fields in the format that they
	// would be used in a MySQL query.
	$old_mysql_fields = array();
	$new_mysql_fields = array();

	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
		if ($row2 = mysqli_fetch_assoc($query_result2))
		{
			$field_value = $record->FieldVal($field_name);
			if ($row2['widget_type'] == 'auto-increment')
			{
				// No action here as an auto-increment field needs to be omitted
			}
			elseif ((!is_numeric($field_value)) && (empty($field_value)))
			{
				// Field is empty. Set it to null if allowed, otherwise to an empty string
				if ($row['Null'] == 'YES')
				{
					$new_mysql_fields[$field_name] = 'NULL';
				}
				else
				{
					$new_mysql_fields[$field_name] = "''";
				}
			}
			elseif (($row2['widget_type'] == 'input-num') || ($row2['widget_type'] == 'checkbox'))
			{
				// Field is a number
				$new_mysql_fields[$field_name] = $field_value;
			}
			else
			{
				// Field is a string
				$new_mysql_fields[$field_name] = "'".addslashes($field_value)."'";
			}
			if ($row2['is_primary'])
			{
				if (($row2['widget_type'] == 'input-num') || ($row2['widget_type'] == 'checkbox'))
				{
					// Field is a number
					$old_mysql_fields[$field_name] = $old_primary_keys[$field_name];
				}
				else
				{
					// Field is a string
					$old_mysql_fields[$field_name] = "'".addslashes($old_primary_keys[$field_name])."'";
				}
			}
		}
	}

	// Determine whether the save operation involves a primary key change
	if ($new_record_id != $old_record_id)
	{
		// Check for duplicate record ID
		$query = "SELECT * FROM $table WHERE";
		$field_processed = false;
		foreach ($new_primary_keys as $field => $val)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field={$new_mysql_fields[$field]}";
		}
		$query_result = mysqli_query($db,$query);
		if (mysqli_num_rows($query_result) > 0)
		{
			return report_error("Unable to save due to duplicate record ID.\n");
		}
	}

	$classname = "tables_$table";
	if  (class_exists ($classname,false))
	{
		$table_obj = new $classname;

		// Run any validate methods
		foreach ($new_mysql_fields as $field => $value)
		{
			$result = check_field_status($table,$field,$record->FieldVal($field));
			if ($result === false)
			{
				return false;
			}

			$method = $field.'__validate';
			if (method_exists($table_obj,$method))
			{
				$result = call_user_func_array(array($table_obj,$method),array($record,$record->FieldVal($field)));
				if ($result === false)
				{
					return false;
				}
			}

			$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field'");
			if (($row = mysqli_fetch_assoc($query_result)) && ($row['widget_type'] == 'file'))
			{
				$result = handle_file_widget_before_save($record,$field);
				if ($result === false)
				{
					return false;
				}
				$new_mysql_fields[$field] = "'".$record->FieldVal($field)."'";
			}
		}

		// Run beforeSave method if available
		if (method_exists($table_obj,'beforeSave'))
		{
			$result = $table_obj->beforeSave($record);
			if ($result === false)
			{
				return false;
			}
		}
	}

	if (($action == 'edit') || ($action == 'update'))
	{
		// Save the record
		$query = "UPDATE $table SET ";
		foreach ($new_mysql_fields AS $field => $value)
		{
			$query .= "$field=$value,";
		}
		$query = rtrim($query,',');
		$query .= " WHERE";
		$field_processed = false;
		foreach ($old_primary_keys as $field => $value)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field={$old_mysql_fields[$field]}";
		}
		$main_query_result = mysqli_query($db,$query);
	}
	elseif (($action == 'new') || ($action == 'copy'))
	{
		// Insert the record
		$field_list = '';
		$value_list = '';
		foreach ($new_mysql_fields as $field => $value)
		{
			$field_list .= "$field,";
			$value_list .= "$value,";
		}
		$field_list = rtrim($field_list,',');
		$value_list = rtrim($value_list,',');
		$main_query_result = mysqli_query($db,"INSERT INTO $table ($field_list) VALUES ($value_list)");
	}

	// Update any auto-increment fields in the record object to reflect the
	// newly assigned value.
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 AND widget_type='auto-increment'");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['field_name'];
		$record->SetField($field_name,$new_primary_keys[$field_name]);
	}

	// Run the after save function for any file widgets
	$query_result = mysqli_query($db,"SHOW COLUMNS FROM $table");
	while ($row = mysqli_fetch_assoc($query_result))
	{
		$field_name = $row['Field'];
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
		if (($row2 = mysqli_fetch_assoc($query_result2)) && ($row2['widget_type'] == 'file'))
		{
			$result = handle_file_widget_after_save($record,$field_name);
			if ($result === false)
			{
				return false;
			}
		}
	}

	/*
	Save the new record ID as a session variable. This may be overridden
	by an action in the afterSave function, thus allowing an updated record ID
	to be used should a primary key field be updated by afterSave.
	*/
	$_SESSION['saved_record_id'] = $new_record_id;

	// Run afterSave method if available
	if  (class_exists ($classname,false))
	{
		if (method_exists($table_obj,'afterSave'))
		{
			$table_obj->afterSave($record);
		}
	}

	if (isset($main_query_result))
	{
		return $main_query_result;
	}
	else
	{
		return true;
	}
}

//==============================================================================
/*
Function handle_record

This function performs the main handling of a record screen during an 'edit',
'new' or 'update operation'.

It basically operates on the table/view itself, though it refers to the
associated base table for widget information.
*/
//==============================================================================

function handle_record($action,$params)
{
	global $BaseURL, $BaseDir, $DBAdminURL, $RelativePath, $Location, $presets;
	$db = admin_db_connect();

	// Interpret the URL parameters
	if (isset($_GET['-table']))
	{
		$table = $_GET['-table'];
	}
	else
	{
		print("<p>No table parameter specified.</p>\n");
		return;
	}
	$base_table = get_base_table($table);

	if (isset($_GET['-recordid']))
	{
		$record_id = $_GET['-recordid'];
	}
	elseif ($action == 'new')
	{
		$record_id = '';
	}
	else
	{
		print("<p>No record ID parameter specified.</p>\n");
		return;
	}

	$presets = array();
	if (isset($params['presets']))
	{
		$presets = fully_decode_record_id($params['presets']);
	}

	// Create query to select this record (used more than once)
	$select_this_record = "SELECT * FROM $table WHERE ";
	$field_added = false;
	$primary_key = decode_record_id($record_id);
	foreach ($primary_key as $field => $value)
	{
		if ($field_added)
		{
			$select_this_record .= " AND";
		}
		$field_added = true;
		$select_this_record .= " $field='$value'";
	}

	// Determine the access level for the table
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$table'");
	if ($row = mysqli_fetch_assoc($query_result))
	{
		$access_level = $row[$Location.'_access'];
	}
	else
	{
		$access_level = 'read-only';  // This should not occur
	}

	// Output any success/error message from a save operation
	if (isset($_GET['-saveresult']))
	{
		if ($_GET['-saveresult'] == 1)
		{
			print("<p class=\"highlight-success\">Record successfully saved</p>\n");
		}
		else
		{
			print("<p class=\"highlight-error\">{$_SESSION['error_message']}</p>\n");
			unset($_SESSION['error_message']);
		}
	}

	// Output top navigation
	print("<p>");
	if ($access_level == 'full')
	{
		print("<a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-action=new&-table=$table\">New&nbsp;Record</a>");
	}
	print("<a class=\"admin-link\" href=\"$BaseURL/$RelativePath/?-table=$table\">Show&nbsp;All</a>");
	if (isset($_GET['-returnurl']))
	{
		print("<a class=\"admin-link\" href=\"{$_GET['-returnurl']}\">Go&nbsp;Back</a>");
	}
	elseif (isset($_SESSION['get_vars']['-returnurl']))
	{
		print("<a class=\"admin-link\" href=\"{$_SESSION['get_vars']['-returnurl']}\">Go&nbsp;Back</a>");
	}
	else
	{
		$return_url = cur_url_par();
	}
	if (isset($params['additional_links']))
	{
		print($params['additional_links']);
	}
	print("</p>\n");

	$param_list = "-action=$action&-table=$table&-recordid=";
	$param_list .= urlencode($record_id);
	$param_list .= "&-basedir=";
	$param_list .= urlencode($BaseDir);
	$param_list .= "&-relpath=";
	$param_list .= urlencode($RelativePath);
	if (isset($_GET['-returnurl']))
	{
		$param_list .= "&-returnurl=";
		$param_list .= urlencode($_GET['-returnurl']);
	}
	elseif (isset($_SESSION['get_vars']['-returnurl']))
	{
		$param_list .= "&-returnurl=";
		$param_list .= urlencode($_SESSION['get_vars']['-returnurl']);
	}
	print("<form method=\"post\" action=\"$DBAdminURL/record_action.php?$param_list\" enctype=\"multipart/form-data\">\n");
	$last_display_group = '';
	$query_result = mysqli_query($db,$select_this_record);

	// Check that the record exists unless the action is set to 'new'.
	if (($row = mysqli_fetch_assoc($query_result)) || ($action == 'new'))
	{
		$query_result2 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' ORDER by display_order ASC");

		// Main loop for processing record fields
		$query_result2 = mysqli_query($db,"SHOW COLUMNS FROM $table");
		while ($row2 = mysqli_fetch_assoc($query_result2))
		{
			$field_name = $row2['Field'];
			$query_result3 = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND field_name='$field_name'");
			if ($row3 = mysqli_fetch_assoc($query_result3))
			{
				// Process new display group if required
				$display_group = $row3['display_group'];
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
					$last_display_group = $display_group;
					print("<table class=\"table-record\">\n");
				}

				$label = field_label($table,$field_name);
				$description = $row3['description'];

				// Create the URL to edit the field attributes in table dba_table_fields
				if ($table == 'dba_table_fields')
				{
					$edit_field_atts_url = '';
				}
				else
				{
					$temp_pk = array();
					$temp_pk['table_name'] = $base_table;
					$temp_pk['field_name'] = $field_name;
					$edit_field_atts_url = "$BaseURL/$RelativePath/?-action=edit&-table=dba_table_fields&-recordid=".encode_record_id($temp_pk);
					$edit_field_atts_url .= "&-returnurl=$return_url";
				}

				// Select the CSS class for use with the field label
				if (($row3['required']) && ($row3['widget_type'] != 'checkbox') && ($row3['widget_type'] != 'static'))
				{
					$class = 'required';
				}
				else
				{
					$class = 'not-required';
				}

				/*
					Determine the value to be placed into the field. This will be set to
					the first of the following which applies (list checked in order):-
					1. The value from the record following a successful record save.
					2. The associated $_POST variable following an unsuccessful save attempt.
					   (passed via the $_SESSION['post_vars'] array.)
					3. A blank/default value. Any presets passed in $_GET['-presets'] will be
						 applied.
				*/
				if (isset($_GET['-saveresult']))
				{
					$widget_type = $row3['widget_type'];
					if ($_GET['-saveresult'] == 1)
					{
						/*
							Condition indicates that record has been successfully saved.
							The query to select the record (see above) should therefore
							have executed successfully with a result.
						*/
						$value = $row[$field_name];
					}
					elseif ($widget_type == 'checkbox')
					{
						if (isset($_SESSION['post_vars']["field_$field_name"]))
						{
							$value = 1;
						}
						else
						{
							$value = 0;
						}
					}
					else
					{
						$value = stripslashes($_SESSION['post_vars']["field_$field_name"]);
					}
				}
				elseif (($action == 'edit') || ($action == 'update'))
				{
					$value = $row[$field_name];
				}
				elseif (($action == 'new') && (isset($presets[$field_name])))
				{
					$value = $presets[$field_name];
				}
				else
				{
					// Action = 'new'
					$value = false;
				}
			}

			switch ($action)
			{
				case 'edit':
				case 'update':
					if ($access_level != 'read-only')
					{
						print("<tr><td><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></td>\n");
						print("<td>");
						generate_widget($table,$field_name,$value);
						if (!empty($description))
						{
							print("<p class=\"field-description\">$description</p>");
						}
						print("</td></tr>\n");
						break;
					}
					// Drop down to next case if access level is read-only.

				case 'view':
					print("<tr><td>$label</td>\n");
					print("<td>{$row[$field_name]}</td></tr>\n");
					break;

				case 'new':
					if ($access_level == 'full')
					{
						print("<tr><td><a class=\"$class\" href=\"$edit_field_atts_url\">$label</a></td>\n");
						print("<td>");
						generate_widget($table,$field_name,$value);
						if (!empty($description))
						{
							print("<p class=\"field-description\">$description</p>");
						}
						print("</td></tr>\n");
					}
					else
					{
						print("<p>Record insertion not enabled in this context</p>\n");
					}
					break;
			}
		}
		print("</table>\n");
		if ($action != 'view')
		{
			print("<input type=\"Submit\" value =\"Save\">\n");
			print("<input type=\"hidden\" name=\"submitted\"/>\n");
		}
		print("</form>\n");
	}
	else
	{
		print("<p>Record not found.</p>\n");
	}
	if (isset($_SESSION['get_vars']))
	{
		unset($_SESSION['get_vars']);
	}
	if (isset($_SESSION['post_vars']))
	{
		unset($_SESSION['post_vars']);
	}
}

//==============================================================================
/*
Function delete_record
*/
//==============================================================================

function delete_record($record,$record_id)
{
	$db = admin_db_connect();
	$table = $record->table;
	$base_table = get_base_table($table);
	$classname = "tables_$table";
	if  (class_exists ($classname,false))
	{
		$table_obj = new $classname;

		// Run beforeDelete method if available
		if (method_exists($table_obj,'beforeDelete'))
		{
			$result = $table_obj->beforeDelete($record);
			if ($result === false)
			{
				return false;
			}
		}

		// Create deletion query
		$query = "DELETE FROM $table WHERE";
		$query_result = mysqli_query($db,"SELECT * FROM dba_table_fields WHERE table_name='$base_table' AND is_primary=1 ORDER by display_order ASC");
		while ($row = mysqli_fetch_assoc($query_result))
		{
			$field_name = $row['field_name'];
			if (($row['widget_type'] == 'input-num') || ($row['widget_type'] == 'checkbox'))
			{
				// Field is a number
				$mysql_fields[$field_name] = $record->FieldVal($field_name);
			}
			else
			{
				// Field is a string
				$mysql_fields[$field_name] = "'".addslashes($record->FieldVal($field_name))."'";
			}
		}
		$primary_keys = fully_decode_record_id($record_id);
		$field_processed = false;
		foreach ($primary_keys as $field => $value)
		{
			if ($field_processed)
			{
				$query .= " AND";
			}
			$field_processed = true;
			$query .= " $field={$mysql_fields[$field]}";
		}

		// Delete the record
		mysqli_query($db,$query);

		if  (class_exists ($classname,false))
		{
			// Run afterDelete method if available
			if (method_exists($table_obj,'afterDelete'))
			{
				$result = $table_obj->afterDelete($record);
			}
		}
	}
	return true;
}

//==============================================================================
/*
Function load_return_url
*/
//==============================================================================

function load_return_url()
{
	if (!headers_sent())
	{
		if (isset($_GET['-returnurl']))
		{
			header("Location: {$_GET['-returnurl']}");
			exit;
		}
		elseif (isset($_SESSION['get_vars']['-returnurl']))
		{
			header("Location: {$_SESSION['get_vars']['-returnurl']}");
			exit;
		}
	}
}

//==============================================================================
}
//==============================================================================
?>
