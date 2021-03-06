<?php
//==============================================================================

global $CustomPagesURL, $RelativePath;

$db = admin_db_connect();

print("<h1>Transaction Report</h1>\n");

print("<form method=\"post\" action=\"$CustomPagesURL/$RelativePath/load_multi_report.php\">\n");
print("<table cellpadding=\"10\">\n");

// Build select list for accounts
print("<tr><td>Account:</td><td>\n");
print("<select name=\"account\">\n");
print("<option value=\"\">--all--</option>\n");
$query_result = mysqli_query($db,"SELECT * FROM accounts ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	print("<option value=\"{$row['label']}\">{$row['name']}</option>\n");
}
print("</select>\n");
print("</td></tr>\n");

// Build select list for funds
$previous_superfund = '';
print("<tr><td>Fund:</td><td>\n");
print("<select name=\"fund\">\n");
print("<option value=\"\">--all--</option>\n");
$query_result = mysqli_query($db,"SELECT * FROM funds ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$fund = $row['name'];
	$superfund = strtok($fund,':');
	if (($superfund != $previous_superfund ) && (strpos($fund,':') !== false))
	{
		$superfund_par = urlencode($superfund);
		print("<option value=\"$superfund_par:%\">$superfund [ALL]</option>\n");
	}
	$fund_par = urlencode($fund);
	print("<option value=\"$fund_par\">$fund</option>\n");
	$previous_superfund = $superfund;
}
print("</select>\n");
print("</td></tr>\n");

// Build select list for categories
$previous_supercategory = '';
print("<tr><td>Category:</td><td>\n");
print("<select name=\"category\">\n");
print("<option value=\"\">--all--</option>\n");
$query_result = mysqli_query($db,"SELECT * FROM categories ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$category = $row['name'];
	$supercategory = strtok($category,':');
	if (($supercategory != $previous_supercategory ) && (strpos($category,':') !== false))
	{
		$supercategory_par = urlencode($supercategory);
		print("<option value=\"$supercategory_par:%\">$supercategory [ALL]</option>\n");
	}
	$category_par = urlencode($category);
	print("<option value=\"$category_par\">$category</option>\n");
	$previous_supercategory = $supercategory;
}
print("</select>\n");
print("</td></tr>\n");

// Build select list for payees
print("<tr><td>Payee:</td><td>\n");
print("<select name=\"payee\">\n");
print("<option value=\"\">-all--</option>\n");
$query_result = mysqli_query($db,"SELECT * FROM payees ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($query_result))
{
	$payee_par = urlencode($row['name']);
	print("<option value=\"$payee_par\">{$row['name']}</option>\n");
}
print("</select>\n");
print("</td></tr>\n");

// Build select list for currency
print("<tr><td>Currency:</td>\n");
print("<td colspan=2><select name=\"currency\">\n");
$query_result = mysqli_query($db,"SELECT * FROM currencies ORDER BY id ASC");
while($row = mysqli_fetch_assoc($query_result))
{
	$id = $row['id'];
	print("<option value=\"$id\"");
	if ($id == 'GBP')
	{
		print(" SELECTED");
	}
	print(">$id</option>\n");
}
print("</select></td>\n");

print("<tr><td></td><td><input type=\"submit\" name=\"submitted\" value=\"Continue\"></td></tr>\n");
print("</table>\n");
print("</form>\n")

//==============================================================================
?>
