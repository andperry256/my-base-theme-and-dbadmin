<?php
//==============================================================================
if (!function_exists('format_view_name'))
{
//==============================================================================
/*
Function format_view_name
*/
//==============================================================================

function format_view_name($view_name)
{
	$view_name = preg_replace("/[^A-Za-z0-9_]/i",'_',$view_name);
	return $view_name;
}

//==============================================================================
/*
Function set_temp_view_name
*/
//==============================================================================

function set_temp_view_name()
{
	if ((!isset($_SESSION['TEMP_VIEW'])) || (empty($_SESSION['TEMP_VIEW'])))
	{
		$temp_str1 = str_replace('.','_',$_SERVER['REMOTE_ADDR']);
		$temp_str2 = date('His');
		$_SESSION['TEMP_VIEW'] = "_view_temp_$temp_str1"."_$temp_str2";
	}
}

//==============================================================================
/*
Function create_view_structure
*/
//==============================================================================

function create_view_structure($view,$table,$conditions)
{
  global $CustomPagesPath, $RelativePath;
	$db = admin_db_connect();

	// Create the view and drop any old tables/views that may conflict with the
	// current setup.
	mysqli_query($db,"CREATE OR REPLACE VIEW $view AS SELECT * FROM $table WHERE $conditions");
	if (substr($view,0,6) == '_view_')
	{
		mysqli_query($db,"DROP TABLE IF EXISTS $view");
		$old_view = substr($view,6);
		mysqli_query($db,"DROP VIEW IF EXISTS $old_view");
	}

	// Add new class definition and symbolic link to directory if the directory
	// for the table already exists.
	if (is_dir("$CustomPagesPath/$RelativePath/tables/$table"))
	{
		$file = "$CustomPagesPath/$RelativePath/tables/$table/$view.php";
		if (!is_file($file))
		{
			$ofp = fopen($file,'w');
			fprintf($ofp,"<?php\n");
			fprintf($ofp,"include(\"\$CustomPagesPath/\$RelativePath/tables/$table/$table.php\");\n");
			fprintf($ofp,"class tables_$view extends tables_$table {}\n");
			fprintf($ofp,"?>\n");
			fclose($ofp);
		}
		$link = "$CustomPagesPath/$RelativePath/tables/$view";
		if (!is_link($link))
	  {
			symlink("$CustomPagesPath/$RelativePath/tables/$table","$link");
	  }
	}

	// Set the parent table for the view in the table info record for the view.
	$query_result = mysqli_query($db,"SELECT * FROM dba_table_info WHERE table_name='$view'");
	if (mysqli_num_rows($query_result) == 0)
	{
  	mysqli_query($db,"INSERT INTO dba_table_info (table_name,parent_table) VALUES ('$view','$table')");
	}
	else
	{
		mysqli_query($db,"UPDATE dba_table_info SET parent_table='$table' WHERE table_name='$view'");
	}
}

//==============================================================================
/*
Function delete_view_structure
*/
//==============================================================================

function delete_view_structure($view,$table)
{
	global $CustomPagesPath, $RelativePath;
	$db = admin_db_connect();

	mysqli_query($db,"DROP VIEW IF EXISTS $view");
	$file = "$CustomPagesPath/$RelativePath/tables/$table/$view.php";
	if (is_file($file))
  {
		unlink($file);
  }
	$link = "$CustomPagesPath/$RelativePath/tables/$view";
	if (is_link($link))
  {
		unlink($link);
  }
  mysqli_query($db,"DELETE FROM dba_table_info WHERE table_name='$view' AND parent_table='$table'");
}

//==============================================================================
/*
Function create_child_table_structure
*/
//==============================================================================

function create_child_table_structure($child,$parent)
{
  global $CustomPagesPath, $RelativePath;
	$db = admin_db_connect();

	mysqli_query($db,"CREATE TABLE IF NOT EXISTS $child LIKE $parent");
	$file = "$CustomPagesPath/$RelativePath/tables/$parent/$child.php";
	if (!is_file($file))
	{
		$ofp = fopen($file,'w');
		fprintf($ofp,"<?php\n");
		fprintf($ofp,"include(\"\$CustomPagesPath/\$RelativePath/tables/$parent/$parent.php\");\n");
		fprintf($ofp,"class tables_$child extends tables_$parent {}\n");
		fprintf($ofp,"?>\n");
		fclose($ofp);
	}
	$link = "$CustomPagesPath/$RelativePath/tables/$child";
	if (!is_link($link))
  {
		symlink("$CustomPagesPath/$RelativePath/tables/$parent","$link");
  }
  mysqli_query($db,"INSERT INTO dba_table_info (table_name,parent_table) VALUES ('$child','$parent')");
}

//==============================================================================
/*
Function delete_child_table_structure
*/
//==============================================================================

function delete_child_table_structure($child,$parent)
{
	global $CustomPagesPath, $RelativePa;
	$db = admin_db_connect();

	mysqli_query($db,"DROP TABLE $child");
	$file = "$CustomPagesPath/$RelativePath/tables/$parent/$child.php";
	if (is_file($file))
  {
		unlink($file);
  }
	$link = "$CustomPagesPath/$RelativePath/tables/$child";
	if (is_link($link))
  {
		unlink($link);
  }
  mysqli_query($db,"DELETE FROM dba_table_info WHERE table_name='$child' AND parent_table='$parent'");
}

//==============================================================================
}
//==============================================================================
?>
