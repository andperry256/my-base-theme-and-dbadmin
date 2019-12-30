<?php
//==============================================================================

$db = admin_db_connect();

print("<h1>Select Account</h1>\n");
print("<p>Please select the required account:-</p>\n");

$query_result = mysqli_query($db,"SELECT * FROM accounts");
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result))
{
	$view = format_view_name("_view_account_{$row['label']}");
	print("<li><a href=\"index.php?-table=$view\">{$row['name']}</a></li>\n");
}
print("</ul>\n");

//==============================================================================
?>
