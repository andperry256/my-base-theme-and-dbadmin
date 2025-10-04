<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Delete Unused Payees</h1>\n");
print("<p>The following payees are currently unused and will be deleted:-</p>\n");
print("<ul>\n");
count_payee_instances();
$where_clause = "instances=0 AND (default_fund IS NULL OR default_fund='') AND ";
$where_clause .= "(default_cat IS NULL OR default_cat='') AND name NOT LIKE '**%'";
$add_clause = ' ORDER BY name ASC';
$query_result = mysqli_select_query($db,'payees','*',$where_clause,[],$add_clause);
while ($row = mysqli_fetch_assoc($query_result)) {
    print("<li>{$row['name']}</li>\n");
}
print("</ul>\n");
print("<p><a href=\"index.php?-action=delete_unused_payees_2\"><button>Continue</button></a></p>\n");

//==============================================================================
