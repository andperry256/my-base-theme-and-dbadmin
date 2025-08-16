<?php
//==============================================================================

$db = admin_db_connect();
print("<h1>Delete Unused Payees</h1>\n");
$where_clause = "instances=0 AND (default_fund IS NULL OR default_fund='') AND ";
$where_clause .= "(default_cat IS NULL OR default_cat='') AND name NOT LIKE '**%'";
$where_values = [];
$add_clause = 'ORDER BY name ASC';
$query_result = mysqli_select_query($db,'payees','*',$where_clause,[],$add_clause);
while ($row = mysqli_fetch_assoc($query_result)) {
    print("<p>Payee <em>{$row['name']}</em> deleted.</p>\n");
}
mysqli_delete_query($db,'payees',$where_clause,$where_values);
print("<p>Operation completed.</p>\n");

//==============================================================================
