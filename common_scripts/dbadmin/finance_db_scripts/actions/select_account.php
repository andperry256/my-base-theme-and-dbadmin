<?php
//==============================================================================

$db1 = main_admin_db_connect();
$db2 = admin_db_connect();

print("<h1>Select Account</h1>\n");
print("<p>Please select the required account:-</p>\n");
$user_access_level = 9;
if (session_var_is_set('user'))
{
	$user = get_session_var('user');
	if ($row = mysqli_fetch_assoc(mysqli_query($db1,"SELECT * FROM admin_passwords WHERE username='$user'")))
	{
		$user_access_level = $row['access_level'];
	}
}

$query_result = mysqli_query($db2,"SELECT * FROM accounts WHERE access_level<=$user_access_level");
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result))
{
	$view = format_view_name("_view_account_{$row['label']}");
	print("<li><a href=\"index.php?-table=$view\">{$row['name']}</a></li>\n");
}
print("</ul>\n");

//==============================================================================
?>
