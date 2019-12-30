<?php

class tables_categories
{
	function beforeDelete($record)
	{
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
			return report_error("This is a built-in system category - please delete using phpMyAdmin.");
	}

	function beforeSave($record)
	{
		$action = $record->action;
		$table = $record->table;
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
			return report_error("This is a built-in system category - please edit using phpMyAdmin.");
	}

	function afterSave($record)
	{
		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;
		if (!empty($record->OldPKVal('name')))
		{
			$old_name = addslashes($record->OldPKVal('name'));
			$name = addslashes($record->FieldVal('name'));
			if ($name != $old_name)
			{
				// Apply name change to other tables
				mysqli_query($db,"UPDATE transactions SET category='$name' WHERE category='$old_name'");
				mysqli_query($db,"UPDATE splits SET category='$name' WHERE category='$old_name'");
				mysqli_query($db,"UPDATE payees SET default_cat='$name' WHERE default_cat='$old_name'");
				mysqli_query($db,"UPDATE funds SET default_income_cat='$name' WHERE default_income_cat='$old_name'");
				mysqli_query($db,"UPDATE funds SET default_expense_cat='$name' WHERE default_expense_cat='$old_name'");
			}
		}
	}
}
?>
