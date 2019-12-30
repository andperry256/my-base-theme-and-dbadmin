<?php

class tables_funds
{
	function beforeDelete($record)
	{
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
			return report_error("This is a built-in system fund - please delete using phpMyAdmin.");
	}

	function beforeSave($record)
	{
		$action = $record->action;
		$table = $record->table;
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
			return report_error("This is a built-in system fund - please edit using phpMyAdmin.");
}

	function afterSave($record)
	{
		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;

		$old_name = addslashes($record->OldPKVal('name'));
		$name = addslashes($record->FieldVal('name'));
		if (!empty($record->OldPKVal('name')))
		{
			if ($name != $old_name)
			{
				// Apply name change to other tables
				mysqli_query($db,"UPDATE transactions SET fund='$name' WHERE fund='$old_name'");
				mysqli_query($db,"UPDATE splits SET fund='$name' WHERE fund='$old_name'");
				mysqli_query($db,"UPDATE payees SET default_fund='$name' WHERE default_fund='$old_name'");
				mysqli_query($db,"UPDATE categories SET default_fund='$name' WHERE default_fund='$old_name'");
			}
		}
	}
}
?>
