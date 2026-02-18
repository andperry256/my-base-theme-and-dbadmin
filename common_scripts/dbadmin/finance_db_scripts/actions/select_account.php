<?php
//==============================================================================

$db1 = main_admin_db_connect();
$db2 = admin_db_connect();

print("<h1>Select Account</h1>\n");
print("<p>Please select the required account:-</p>\n");

$add_clause = "ORDER BY name ASC";
$query_result = mysqli_select_query($db,'accounts','*','',[],$add_clause);
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result)) {
    $view = format_view_name("_view_account_{$row['label']}");
    print("<li><a href=\"index.php?-table=$view\">{$row['name']}</a></li>\n");
}
print("</ul>\n");

//==============================================================================
