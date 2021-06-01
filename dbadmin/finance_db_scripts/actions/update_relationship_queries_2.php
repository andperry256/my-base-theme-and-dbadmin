<?php
//==============================================================================

global $DBAdminDir;
$db = admin_db_connect();
print("<h1>Update Relationship Queries</h1>\n");
$sql_scripts = file("$DBAdminDir/finance_db_scripts/relationships.sql");
mysqli_query($db,"DELETE FROM dba_relationships");
$count = 0;
foreach ($sql_scripts as $line)
{
	if (mysqli_query($db,$line))
	{
		$count++;
	}
}
print("<p>$count Entries added to relationships table</p>\n");

//==============================================================================
?>
