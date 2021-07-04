<?php

class tables_notes
{
	function beforeSave($record)
	{

	}

	function afterSave($record)
	{
		$db = admin_db_connect();
		$action = $record->action;
		$table = $record->table;
		$today_date = date('Y-m-d');
		mysqli_query($db,"UPDATE notes SET date='$today_date' WHERE date NOT LIKE '20%'");
	}
}
?>
