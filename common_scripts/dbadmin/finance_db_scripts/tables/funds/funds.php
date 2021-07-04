<?php

class tables_funds
{
	function beforeDelete($record)
	{
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
		{
			return report_error("This is a built-in system fund - please delete using phpMyAdmin.");
		}
	}

	function beforeSave($record)
	{
		$action = $record->action;
		$table = $record->table;
		$type = $record->FieldVal('type');
		if ($type == 'built-in')
		{
			return report_error("This is a built-in system fund - please edit using phpMyAdmin.");
		}
}

	function afterSave($record)
	{

	}
}
?>
