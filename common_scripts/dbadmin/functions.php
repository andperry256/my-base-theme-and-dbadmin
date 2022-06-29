<?php
	if (!defined('RELATIONSHIP_VARIABLE_MATCH_1'))
	{
		$dummy = '({';  // To avoid false positives in PHP code checker
		DEFINE('RELATIONSHIP_VARIABLE_MATCH_1','/[ =<>*+\'\^\)\}]\$[A-Za-z0-9_]+/');
		DEFINE('RELATIONSHIP_VARIABLE_MATCH_2',str_replace('\\$','\\$\\$',RELATIONSHIP_VARIABLE_MATCH_1));
	}
	require("update_table_data.php");
	require("common_funct.php");
	require("view_funct.php");
	require("misc_funct.php");
	require("import_export_funct.php");
	require("table_funct.php");
	require("record_funct.php");
	require("database_sync.php");
	require("search_and_replace.php");
?>
