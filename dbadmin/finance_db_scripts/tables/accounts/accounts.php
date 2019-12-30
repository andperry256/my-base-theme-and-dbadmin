<?php

class tables_accounts
{
	function beforeSave($record)
	{

	}

	function afterSave($record)
	{
		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;
		$old_name = addslashes($record->OldPKVal('name'));
		$old_label = str_replace(' ','_',$old_name);
		$name = addslashes($record->FieldVal('name'));
		$label = str_replace(' ','_',$name);
		mysqli_query($db, "UPDATE accounts SET label='$label' WHERE name='$name'");
		if ($label != $old_label)
		{
			// Apply name change to existing transactions
			mysqli_query($db,"UPDATE transactions SET account='$label' WHERE account='$old_label'");
			mysqli_query($db,"UPDATE splits SET account='$label' WHERE account='$old_label'");
			mysqli_query($db,"UPDATE transactions SET target_account='$label' WHERE target_account='$old_label'");
			mysqli_query($db,"UPDATE transactions SET source_account='$label' WHERE source_account='$old_label'");
		}
	}
}
?>
