<?php

class tables_payees
{
	function regex_match__validate($record, $value)
	{
		if ((!empty($value)) & (strtoupper($value) != $value))
		{
			return report_error("Regex match patter must be all uppercase.");
		}
		else
			return true;
	}

	function beforeSave($record)
	{

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
				// Apply name change to existing transactions
				mysqli_query($db,"UPDATE transactions SET payee='$name' WHERE payee='$old_name'");
			}
		}

	}
}
?>
