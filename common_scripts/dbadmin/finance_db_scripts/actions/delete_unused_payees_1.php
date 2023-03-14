<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Delete Unused Payees</h1>\n");
print("<p>The following payees are currently unused and will be deleted:-</p>\n");
print("<ul>\n");
$query_result = mysqli_query_strict($db,"SELECT * FROM payees ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
		$payee = addslashes($row['name']);
		$query_result2 = mysqli_query_strict($db,"SELECT * FROM transactions WHERE payee='$payee'");
		$count = mysqli_num_rows($query_result2);
		mysqli_query_normal($db,"UPDATE payees SET instances=$count WHERE name='$payee'");
		if (($count == 0) && ($row['locked'] == 0))
		{
			print("<li>{$row['name']}</li>\n");
		}
}
print("</ul>\n");
print("<p><a href=\"index.php?-action=delete_unused_payees_2\"><button>Continue</button></a></p>\n");

//==============================================================================
?>
