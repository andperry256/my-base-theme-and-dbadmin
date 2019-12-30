<?php
//==============================================================================

global $CustomPagesURL, $RelativePath;

$db = admin_db_connect();
$account = $_GET['account'];

print("<h1>Go to Date</h1>\n");
print("<p>Please select the required date. You will be redirected to the page containing the last entry for the given date");
print(" using defaut pagination (i.e. 100 items per page).</p>\n");
if (isset($error_message))
{
	print("<p>ERROR - $error_message</p>\n");
}

print("<form method=\"post\" action=\"$CustomPagesURL/$RelativePath/go_to_date_2.php?table=$table&account=$account\">\n");
print("<table cellpadding=\"5\">\n");
print("<tr><td>Date:</td>\n");

// Day
print("<td><select name=\"dy\">\n");
print("<option value=\"\">Select ...</option>\n");
for ($day = 1; $day <= 31; $day++)
{
	print("<option ");
	if ((isset($_POST['dy'])) && ($_POST['dy'] == $day))
		print("SELECTED ");
	print("value=\"$day\">$day</option>\n");
}
print("</select></td>\n");

// Month
print("<td><select name=\"mth\">\n");
print("<option value=\"\">Select ...</option>\n");
for ($month = 1; $month <= 12; $month++)
{
	$month_name = monthName($month);
	print("<option ");
	if ((isset($_POST['mth'])) && ($_POST['mth'] == $month))
		print("SELECTED ");
	print("value=\"$month\">$month_name</option>\n");
}
print("</select></td>\n");

// Year
print("<td><select name=\"yr\">\n");
print("<option value=\"\">Select ...</option>\n");
$this_year = (int)date('Y');
for ($year = 2017; $year <= $this_year; $year++)
{
	print("<option ");
	if ((isset($_POST['yr'])) && ($_POST['yr'] == $year))
		print("SELECTED ");
	print("value=\"$year\">$year</option>\n");
}
print("</select></td></tr>\n");

print("<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"submit\" name=\"submitted\" value=\"Display Transactions\"></td></tr>\n");
print("</table>\n");
print("</form>\n");

//==============================================================================
?>
