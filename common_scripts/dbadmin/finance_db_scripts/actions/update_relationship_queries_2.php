<?php
//==============================================================================

global $DBAdminDir,$CustomPagesPath,$RelativePath;
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
if (is_file("$CustomPagesPath/$RelativePath/relationships.sql"))
{
	$sql_scripts = file("$CustomPagesPath/$RelativePath/relationships.sql");
	foreach ($sql_scripts as $line)
	{
		if ((substr($line,0,1) != '#') && (mysqli_query($db,$line)))
		{
			$count++;
		}
	}
}
print("<p>$count Entries added to relationships table</p>\n");

//==============================================================================
?>
