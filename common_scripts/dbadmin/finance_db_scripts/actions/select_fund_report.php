<?php
//==============================================================================

$db = admin_db_connect();

print("<h1>Fund Report</h1>\n");
print("<p>Please select the required fund:-</p>\n");
$fund_exclusions = select_excluded_funds('name');

$previous_superfund = '';
$where_clause = "(type<>'built-in' OR name='-none-') $fund_exclusions";
$query_result = mysqli_select_query($db,'funds','*',$where_clause,[],'');
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result))
{
    $fund = $row['name'];
    $superfund = strtok($fund,':');
    if (($superfund != $previous_superfund ) && (strpos($fund,':') !== false))
    {
        print("<li><a href=\"index.php?-action=display_transaction_report&fund=$superfund:%%\">$superfund [ALL]</a></li>\n");
    }
    print("<li><a href=\"index.php?-action=display_transaction_report&fund=$fund\">$fund</a></li>\n");
    $previous_superfund = $superfund;
}
print("</ul>\n");

//==============================================================================
?>
