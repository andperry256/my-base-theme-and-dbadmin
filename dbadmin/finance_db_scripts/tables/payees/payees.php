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


	}
}
?>
