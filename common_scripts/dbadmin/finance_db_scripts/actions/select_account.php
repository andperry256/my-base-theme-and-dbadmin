<?php
//==============================================================================

$db1 = main_admin_db_connect();
$db2 = admin_db_connect();

print("<h1>Select Account</h1>\n");
print("<p>Please select the required account:-</p>\n");
$user_access_level = 9;
if (session_var_is_set(SV_USER)) {
    $user = get_session_var(SV_USER);
    $where_clause = 'username=?';
    $where_values = ['s',$user];
    if ($row = mysqli_fetch_assoc(mysqli_select_query($db,'admin_passwords','*',$where_clause,$where_values,''))) {
        $user_access_level = $row['access_level'];
    }
}

$where_clause = 'access_level<=?';
$where_values = ['i',$user_access_level];
$query_result = mysqli_select_query($db,'accounts','*',$where_clause,$where_values,'');
print("<ul>\n");
while ($row = mysqli_fetch_assoc($query_result)) {
    $view = format_view_name("_view_account_{$row['label']}");
    print("<li><a href=\"index.php?-table=$view\">{$row['name']}</a></li>\n");
}
print("</ul>\n");

//==============================================================================
?>
