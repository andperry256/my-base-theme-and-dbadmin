<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Delete Unused Payees</h1>\n");
$query_result = mysqli_query_normal($db,"SELECT * FROM payees WHERE instances=0 and locked=0 ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	print("<p>Payee <em>{$row['name']}</em> deleted.</p>\n");
}
$query_result = mysqli_query_normal($db,"DELETE FROM payees WHERE instances=0");
print("<p>Operation completed.</p>\n");

//==============================================================================
?>
