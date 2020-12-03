<?php
//==============================================================================

global $CustomPagesURL, $RelativePath;

$db = admin_db_connect();
$account = $_GET['account'];

print("<h1>Go to Date</h1>\n");
print("<p>Please select the required date. You will be redirected to the page containing the last entry for the given date");
print(" using defaut pagination.</p>\n");
if (isset($error_message))
{
	print("<p>ERROR - $error_message</p>\n");
}

$return_url = cur_url_par();
print("<form method=\"post\" action=\"$CustomPagesURL/$RelativePath/go_to_date_2.php?table=$table&account=$account&-returnurl=$return_url\">\n");
print("<table cellpadding=\"5\">\n");
print("<tr><td>Date:</td>\n");
print("<td>");
datepicker_widget('date_selection','');
print("</td></tr>\n");

print("<tr><td>&nbsp;</td><td colspan=\"2\"><input type=\"submit\" name=\"submitted\" value=\"Display Transactions\"></td></tr>\n");
print("</table>\n");
print("</form>\n");

//==============================================================================
?>
