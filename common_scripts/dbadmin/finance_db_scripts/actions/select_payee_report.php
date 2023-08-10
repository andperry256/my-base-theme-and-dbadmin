<?php
//==============================================================================

$db = admin_db_connect();

print("<h1>Payee Report</h1>\n");
print("<p>Please select the required payee:-</p>\n");

$where_clause = '';
$where_values = array();
$query_result = mysqli_select_query($db,'payees','*',$where_clause,$where_values,'');
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result))
{
	print("<li><a href=\"index.php?-action=display_transaction_report&payee={$row['name']}\">{$row['name']}</a></li>\n");
}
print("</ul>\n");

//==============================================================================
?>
